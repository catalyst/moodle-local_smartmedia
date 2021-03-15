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
rekognition_client = boto3.client('rekognition')
transcribe_client = boto3.client('transcribe')
logger = logging.getLogger()


def start_rekognition(input_key, job_id):

    # Get environvent variables
    output_bucket = os.environ.get('OutputBucket')  # Ouput S3 bucket
    input_bucket = os.environ.get('InputBucket')  # Input S3 bucket
    rekognition_Complete_Role_arn = os.environ.get('RekognitionCompleteRoleArn')
    sns_rekognition_complete_arn = os.environ.get('SnsTopicRekognitionCompleteArn')

    rekognition_input = '{}/conversions/{}.mp4'.format(input_key, input_key)
    transcribe_input = '{}/conversions/{}.mp3'.format(input_key, input_key)

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

    services = get_enabled_services(s3_client, input_bucket, input_key)

    if not(True in services.values()):
        return

    # Now that we know we are running a service here, use the first encountered file that is .mp4.
    # There is guaranteed to be atleast one, as we force the download preset.
    # Do the same for mp3 using the audio preset.
    objects = s3_client.list_objects_v2(
        Bucket=output_bucket,
        Prefix='{}/conversions/'.format(input_key)
    )
    videofilename = None
    audiofilename = None
    for file_object in objects.get('Contents', []):
        # We are looking for the first file that has an .mp4 extension
        filename = file_object.get('Key')
        name, ext = os.path.splitext(filename)
        if ext == '.mp4':
            videofilename = filename
        if ext == '.mp3':
            audiofilename = filename

    if videofilename is not None:
        s3_client.copy_object(
            Bucket=output_bucket,
            Key=rekognition_input,
            CopySource={
                'Bucket': output_bucket,
                'Key': videofilename
            }
        )
    if audiofilename is not None:
        s3_client.copy_object(
            Bucket=output_bucket,
            Key=transcribe_input,
            CopySource={
                'Bucket': output_bucket,
                'Key': audiofilename
            }
        )

    # Start Rekognition Label extraction.
    if services['rekog_label'] and videofilename is not None:
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
    if services['rekog_moderation'] and videofilename is not None:
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
    if services['rekog_face'] and videofilename is not None:
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
    if services['rekog_label'] and videofilename is not None:
        logger.info('Starting Rekognition person tracking')
        person_tracking_response = rekognition_client.start_person_tracking(
            Video=video_dict,
            ClientRequestToken=job_id,
            NotificationChannel=notification_dict,
            JobTag=job_id
        )

        logger.error(person_tracking_response)

    # Start transcription job.
    if services['transcribe'] and audiofilename is not None:
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


def sqs_send_message(input_key, message_state, sns_message_object):
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
        'process': 'elastic_transcoder',
        'status': message_state,
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

def get_enabled_services(s3_client, bucket, input_key):
    # Get input object metadata as we will need for SQS message sending.
    input_object_headdata_object = s3_client.head_object(
        Bucket=bucket,
        Key=input_key
        )

    metadata = input_object_headdata_object['Metadata']
    serviceraw = list(metadata['processes'])

    # Dict of rekognition position => service
    positions = [
        'transcribe',
        'rekog_label',
        'rekog_moderation',
        'rekog_face',
        'rekog_person'
        ]

    # Map services to bool from position in processes string.
    services = dict()
    for x in range(5):
        services[positions[x]] = True if serviceraw[x] == '1' else False

    return services

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

        sqs_send_message(input_key, message_state, sns_message_object)  # Send message to SQS queue.

        # Only process Rekognition tasks if job status is complete
        if message_state == 'COMPLETED':
            start_rekognition(input_key, job_id)
