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

logger = logging.getLogger()

# Get clients and resources.
s3_resource = boto3.resource('s3')
rekognition_client = boto3.client('rekognition')

# Some exceptions are expected and when we get them we just want to retry.
RETRY_EXCEPTIONS = ('ProvisionedThroughputExceededException',
                    'ThrottlingException')
MAX_RETRIES = 8


def get_label_detection_results(job_id):
    """
    Get the results returned by a Rekognition start_label_detection call.
    """

    next_token = ''
    video_metadata = {}
    labels = list()  # List to hold returned labels.
    retries = 1

    while True:
        # Get results from Rekognition.
        try:
            results = rekognition_client.get_label_detection(
                JobId=job_id,
                MaxResults=1000,  # Get 1000 results at a time, max is 1000.
                NextToken=next_token,
                SortBy='TIMESTAMP'  # Other option is 'NAME'.
            )

            labels += results['Labels']  # Append the labels to the list.
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


def get_moderation_detection_results(job_id):
    """
    Get the results returned by a Rekognition start_moderation_detection call.
    """

    next_token = ''
    video_metadata = {}
    labels = list()  # List to hold returned labels.
    retries = 1

    getter_method = getattr(rekognition_client, 'get_content_moderation')

    while True:
        # Get results from Rekognition.
        try:
            results = rekognition_client.getter_method(
                JobId=job_id,
                MaxResults=1000,  # Get 1000 results at a time, max is 1000.
                NextToken=next_token,
                SortBy='TIMESTAMP'  # Other option is 'NAME'.
            )

            labels += results['Labels']  # Append the labels to the list.
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

    logging.error(json.dumps(event))

    for record in event['Records']:
        sns_message_json = record['Sns']['Message']
        sns_message_object = json.loads(sns_message_json)
        job_id = sns_message_object['JobId']
        rekognition_type = sns_message_object['API']

        labels = list()  # List to hold returned labels.

        if rekognition_type == 'StartLabelDetection':
            logger.info('Getting label detection results')
            label_results = get_label_detection_results(job_id)  # Get detected label data.
            # Take the collected metadata and labels and write them to a json file in the output bucket.
            output_bucket = sns_message_object['Video']['S3Bucket']
            object_name = sns_message_object['Video']['S3ObjectName']
            object_key = object_name.split('/', 1)[0]

            # Put detected lanels in output S3 bucket as json file.
            s3_object = s3_resource.Object(output_bucket, '{}/metadata/labels.json'.format(object_key))
            s3_object.put(
                Body=(bytes(json.dumps(label_results).encode('UTF-8')))
            )
        elif rekognition_type == 'StartContentModeration':
            logger.info('Getting label moderation results')
            moderation_results = get_moderation_detection_results(job_id)  # Get detected moderation data.
            # Take the collected metadata and labels and write them to a json file in the output bucket.
            output_bucket = sns_message_object['Video']['S3Bucket']
            object_name = sns_message_object['Video']['S3ObjectName']
            object_key = object_name.split('/', 1)[0]

            # Put detected lanels in output S3 bucket as json file.
            s3_object = s3_resource.Object(output_bucket, '{}/metadata/moderation.json'.format(object_key))
            s3_object.put(
                Body=(bytes(json.dumps(moderation_results).encode('UTF-8')))
            )
        elif rekognition_type == 'StartFaceDetection':
            logger.info('Getting face detection results')
            face_detection_results = get_face_detection_results(job_id)  # Get detected face data.
            # Take the collected metadata and labels and write them to a json file in the output bucket.
            output_bucket = sns_message_object['Video']['S3Bucket']
            object_name = sns_message_object['Video']['S3ObjectName']
            object_key = object_name.split('/', 1)[0]

            # Put detected lanels in output S3 bucket as json file.
            s3_object = s3_resource.Object(output_bucket, '{}/metadata/faces.json'.format(object_key))
            s3_object.put(
                Body=(bytes(json.dumps(face_detection_results).encode('UTF-8')))
            )
        elif rekognition_type == 'StartPersonTracking':
            logger.info('Getting person tracking results')
            person_tracking_results = get_person_detection_results(job_id)  # Get detected person data.
            # Take the collected metadata and labels and write them to a json file in the output bucket.
            output_bucket = sns_message_object['Video']['S3Bucket']
            object_name = sns_message_object['Video']['S3ObjectName']
            object_key = object_name.split('/', 1)[0]

            # Put detected lanels in output S3 bucket as json file.
            s3_object = s3_resource.Object(output_bucket, '{}/metadata/person.json'.format(object_key))
            s3_object.put(
                Body=(bytes(json.dumps(person_tracking_results).encode('UTF-8')))
            )
        else:
            logger.error('Other event')
            sns_message_json = record['Sns']['Message']
            sns_message_object = json.loads(sns_message_json)
            job_id = sns_message_object['JobId']
            rekognition_type = sns_message_object['API']
            logger.error(rekognition_type)
