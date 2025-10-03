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
from botocore.exceptions import ClientError
from datetime import datetime

s3_client = boto3.client('s3')
sqs_client = boto3.client('sqs')
mc_client = boto3.client('mediaconvert')
logger = logging.getLogger()


def sqs_send_message(key, bucket, record, metadata):
    # Get environment variables
    sqs_url = os.environ.get('SmartmediaSqsQueue')  # SQS queue URL.

    # Create JSON message to send to SQS queue.

    now = datetime.now()  # Current date and time.

    message_object = {
        'siteid' : metadata['siteid'],
        'objectkey' : key,
        'process': 'S3',
        'status': record['eventName'],
        'message': record,
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
                'StringValue': metadata['siteid'],
                'DataType': 'String'
            },
            'inputkey': {
                'StringValue': key,
                'DataType': 'String'
            },
        }
    )


def submit_transcode_jobs(queue_id, settings):
    """
    Submits jobs to Elastic Transcoder.
    """

    logger.info('Creating media convert job...')

    response = mc_client.create_job(
        queue=queue_id,
        role='TODO',
        settings=settings
    )
    logger.info(response)


def get_job_settings(key, bucket, metadata):
    """
    Get the job settings from the S3 Object metadata
    """
    raw_settings_data = metadata['settings']
    decoded_settings = json.loads(raw_settings_data)
    logger.info(decoded_settings)
    return decoded_settings

def lambda_handler(event, context):
    """
    lambda_handler is the entry point that is invoked when the lambda function is called,
    more information can be found in the docs:
    https://docs.aws.amazon.com/lambda/latest/dg/python-programming-model-handler-types.html

    Trigger the file conversion when the source file is uploaded to the input s3 bucket.
    """

    #  Set logging
    logging_level = os.environ.get('LoggingLevel', logging.INFO)
    logger.setLevel(int(logging_level))

    logger.info(event)

    #  Get MediaConvert queue ID from environment variable
    queue_id = 'smt-1759470910-queue' # TODO os.environ.get('QueueId')
    logger.info('Inserting S3 input objects into queue: {}'.format(queue_id))

    #  Now get and process the file from the input bucket.
    for record in event['Records']:
        bucket = record['s3']['bucket']['name']
        key = record['s3']['object']['key']

        #  Filter out permissions check file.
        #  This is initiated by Moodle to check bucket access is correct
        if key == 'permissions_check_file':
            continue
        
        logger.info('File uploaded: {}'.format(key))

        # Get input object metadata as we will need for SQS message sending.
        input_object_headdata_object = s3_client.head_object(
            Bucket=bucket,
            Key=key
            )

        metadata = input_object_headdata_object['Metadata']

        # Send message to SQS queue.
        logger.info('Sending metadata to SQS')
        sqs_send_message(key, bucket, record, metadata)

        logger.info('Submitting transcode job to queue {}'.format(queue_id))
        settings = get_jobs_settings(key, bucket, metadata)
        submit_transcode_jobs(queue_id, settings)

