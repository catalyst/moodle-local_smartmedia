'''
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.

@copyright   2019 Matt Porritt <mattp@catalyst-au.net>
@license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

'''

import boto3
import botocore
import os
import logging
import io
import json
import time
from botocore.exceptions import ClientError
from datetime import datetime

logger = logging.getLogger()

# Get clients and resources.
s3_client = boto3.client('s3')
sqs_client = boto3.client('sqs')
s3_resource = boto3.resource('s3')
rekognition_client = boto3.client('rekognition')

# Some exceptions are expected and when we get them we just want to retry.
RETRY_EXCEPTIONS = ('ProvisionedThroughputExceededException',
                    'ThrottlingException')
MAX_RETRIES = 8


def get_detection_results(job_id, method, sort, result_key):
    """
    Get the results returned by a Rekognition start detection calls.
    """

    next_token = ''
    video_metadata = {}
    labels = list()  # List to hold returned labels.
    retries = 1

    getter_method = getattr(rekognition_client, method)

    while True:
        method_args = {
            'JobId': job_id,
            'MaxResults': 1000,  # Get 1000 results at a time, max is 1000.
            'NextToken': next_token,
            }
        if sort != '':
            method_args['SortBy'] = sort

        # Get results from Rekognition.
        try:
            results = getter_method(**method_args)

            labels += results[result_key]  # Append the labels to the list.
            video_metadata = results['VideoMetadata']

            # Check if we have more results to get.
            if 'NextToken' in results:
                next_token = results['NextToken']
            else:
                next_token = ''

        except ClientError as err:
            if err.response['Error']['Code'] not in RETRY_EXCEPTIONS:
                raise
            logging.error('Rate limiting hit!, retries={}'.format(retries))
            if retires < MAX_RETRIES:
                sleep(2 ** retries)
                retries += 1  # TODO max limit.
                continue  # Try again
            else:
                break

        if next_token == '':  # Only continue to try and get more results if there is a valid next token.
            break

    result_data = {
        'metadata': video_metadata,
        'labels': labels
        }

    return result_data


def sqs_send_message(input_key, message_status, sns_message_object, rekognition_type):
    # Get environvent variables
    input_bucket = os.environ.get('InputBucket')  # Ouput S3 bucket
    sqs_url = os.environ.get('SmartmediaSqsQueue')  # SQS queue URL.

    # Get input object metadata as we will need for SQS message sending.
    input_object_headdata_object = s3_client.head_object(
        Bucket=input_bucket,
        Key=input_key
        )

    # Create JSON message to send to SQS queue.

    now = datetime.now()  # Current date and time.

    message_object = {
        'siteid' : input_object_headdata_object['Metadata']['siteid'],
        'objectkey' : input_key,
        'process': rekognition_type,
        'status': message_status,
        'message': sns_message_object,
        'timestamp': int(datetime.timestamp(now))
        }
    message_json = json.dumps(message_object)

    # Send message to SQS queue, we do this from Lambda not directly from sns,
    # as we want to add some extra information to the message.
    sqs_client.send_message(
        QueueUrl=sqs_url,
        MessageBody=message_json,
        MessageAttributes={
            'siteid': {
                'StringValue': input_object_headdata_object['Metadata']['siteid'],
                'DataType': 'String'
            },
            'inputkey': {
                'StringValue': input_key,
                'DataType': 'String'
            },
        }
    )


def lambda_handler(event, context):
    """
    lambda_handler is the entry point that is invoked when the lambda function is called,
    more information can be found in the docs:
    https://docs.aws.amazon.com/lambda/latest/dg/python-programming-model-handler-types.html

    Trigger the file conversion when the source file is uploaded to the input s3 bucket.
    """

    #  Set logging
    logging_level = os.environ.get('LoggingLevel', logging.ERROR)
    logger.setLevel(int(logging_level))

    for record in event['Records']:
        sns_message_json = record['Sns']['Message']
        sns_message_object = json.loads(sns_message_json)
        job_id = sns_message_object['JobId']
        rekognition_type = sns_message_object['API']
        message_status = sns_message_object['Status']  # Get message status

        labels = list()  # List to hold returned labels.
        output_bucket = sns_message_object['Video']['S3Bucket']
        object_name = sns_message_object['Video']['S3ObjectName']
        object_key = object_name.split('/', 1)[0]
        result_key = ''

        # Only process Rekognition tasks if job status is successful.
        if message_status == 'SUCCEEDED':

            if rekognition_type == 'StartLabelDetection':
                logger.info('Getting label detection results')
                method = 'get_label_detection'
                sort = 'TIMESTAMP'
                result_key = 'Labels'
                label_results = get_detection_results(job_id, method, sort, result_key)  # Get detected label data.

            elif rekognition_type == 'StartContentModeration':
                logger.info('Getting label moderation results')
                method = 'get_content_moderation'
                sort = 'TIMESTAMP'
                result_key = 'ModerationLabels'
                label_results = get_detection_results(job_id, method, sort, result_key)  # Get detected moderation data.

            elif rekognition_type == 'StartFaceDetection':
                logger.info('Getting face detection results')
                method = 'get_face_detection'
                sort = ''
                result_key = 'Faces'
                label_results = get_detection_results(job_id, method, sort, result_key)  # Get detected face data.

            elif rekognition_type == 'StartPersonTracking':
                logger.info('Getting person tracking results')
                method = 'get_person_tracking'
                sort = 'INDEX'
                result_key = 'Persons'
                label_results = get_detection_results(job_id, method, sort, result_key)  # Get detected person data.

            if result_key != '':
                # Put detected labels in output S3 bucket as json file.
                s3_object = s3_resource.Object(output_bucket, '{}/metadata/{}.json'.format(object_key, result_key))
                s3_object.put(
                    Body=(bytes(json.dumps(label_results).encode('UTF-8')))
                )

        sqs_send_message(object_key, message_status, sns_message_object, rekognition_type)  # Send message to SQS queue.
