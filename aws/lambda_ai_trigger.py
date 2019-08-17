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

s3_client = boto3.client('s3')
sqs_client = boto3.client('sqs')
rekognition_client = boto3.client('rekognition')
transcribe_client = boto3.client('transcribe')
logger = logging.getLogger()


def start_rekognition(input_key, job_id):

    # Get environvent variables
    output_bucket = os.environ.get('OutputBucket')  # Ouput S3 bucket
    rekognition_Complete_Role_arn = os.environ.get('RekognitionCompleteRoleArn')
    sns_rekognition_complete_arn = os.environ.get('SnsTopicRekognitionCompleteArn')

    rekognition_input = '{}/conversions/{}.mp4'.format(input_key, input_key)

    video_dict = {
            'S3Object': {
                'Bucket': output_bucket,
                'Name': rekognition_input
            }
        }
    notification_dict = {
            'SNSTopicArn': sns_rekognition_complete_arn,
            'RoleArn': rekognition_Complete_Role_arn
        }

    # Start Rekognition Label extraction.
    logger.info('Starting Rekognition label detection')
    label_response = rekognition_client.start_label_detection(
        Video=video_dict,
        ClientRequestToken=job_id,
        MinConfidence=80,  # 50 is default.
        NotificationChannel=notification_dict,
        JobTag=job_id
        )

    logging.error(label_response)

    # Start Rekognition content moderation operatations.
    logger.info('Starting Rekognition moderation detection')
    moderation_response = rekognition_client.start_content_moderation(
        Video=video_dict,
        MinConfidence=80,  # 50 is default.
        ClientRequestToken=job_id,
        NotificationChannel=notification_dict,
        JobTag=job_id
        )

    logging.error(moderation_response)

    # Start Rekognition face detection.
    logger.info('Starting Rekognition face detection')
    face_response = rekognition_client.start_face_detection(
        Video=video_dict,
        ClientRequestToken=job_id,
        NotificationChannel=notification_dict,
        FaceAttributes='DEFAULT',  # Other option is ALL.
        JobTag=job_id
    )

    logger.error(face_response)

    # Start Rekognition Person tracking.
    logger.info('Starting Rekognition person tracking')
    person_tracking_response = rekognition_client.start_person_tracking(
        Video=video_dict,
        ClientRequestToken=job_id,
        NotificationChannel=notification_dict,
        JobTag=job_id
    )

    logger.error(person_tracking_response)

    # Start transcription job.
    logger.info('Starting transcription.')
    media_uri = 'https://s3-{}.amazonaws.com/{}/{}/conversions/{}.mp3'.format(
        os.environ.get('AWS_REGION'),
        output_bucket,
        input_key,
        input_key
        )
    transcription_response = transcribe_client.start_transcription_job(
        TranscriptionJobName=job_id,
        LanguageCode='en-AU',
        MediaSampleRateHertz=44100,
        MediaFormat='mp3',
        Media={
            'MediaFileUri': media_uri
        },
        Settings={}
        )

    logger.error(transcription_response)


def sqs_send_message(input_key):
    # Get environvent variables
    input_bucket = os.environ.get('InputBucket')  # Ouput S3 bucket
    sqs_url = os.environ.get('SmartmediaSqsQueue')  # SQS queue URL.

    # Get input object metadata as we will need for SQS message sending.
    input_object_headdata_object = s3_client.head_object(
        Bucket=input_bucket,
        Key=input_key
        )

    # Create JSON message to send to SQS queue.
    # Need a universal format that suits all conversion processes

    # Send message to SQS queue, we do this from Lambda not directly from sns,
    # as we want to add some extra information to the message.
    sqs_client.send_message(
        QueueUrl=sqs_url,
        MessageBody='Test message that will eventually be json',
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

    Function is triggered from SNS conversion topic.
    """

    #  Set logging
    logging_level = os.environ.get('LoggingLevel', logging.ERROR)
    logger.setLevel(int(logging_level))

    for record in event['Records']:
        sns_message_json = record['Sns']['Message']
        sns_message_object = json.loads(sns_message_json)
        input_key = sns_message_object['input']['key']
        output_key_prefix = sns_message_object['outputKeyPrefix']
        job_id = sns_message_object['jobId']
        message_state = sns_message_object['state']  # Get message state

        sqs_send_message(input_key)  # Send message to SQS queue

        # Only process Rekognition tasks if job status is complete
        if message_state == 'COMPLETED':
            start_rekognition(input_key, job_id)
