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
import urllib3
from botocore.exceptions import ClientError
from datetime import datetime

logger = logging.getLogger()

# Get clients and resources.
s3_client = boto3.client('s3')
s3_resource = boto3.resource('s3')
sqs_client = boto3.client('sqs')
transcribe_client = boto3.client('transcribe')
comprehend_client = boto3.client('comprehend')

def get_enabled_services(s3_client, bucket, input_key):
    # Get input object metadata as we will need for SQS message sending.
    input_object_headdata_object = s3_client.head_object(
        Bucket=bucket,
        Key=input_key
        )

    metadata = input_object_headdata_object['Metadata']
    serviceraw = list(metadata['processes'])

    # Dict of metadata position => service
    positions = [
        'sentiment',
        'phrases',
        'entities',
        ]

    # Map services to bool from position in processes string.
    services = dict()
    for x in range(5,8):
        services[positions[x - 5]] = True if serviceraw[x] == '1' else False

    return services

def sqs_send_message(input_key, message_state, process):
    # Get environvent variables
    input_bucket = os.environ.get('InputBucket')  # Ouput S3 bucket
    sqs_url = os.environ.get('SmartmediaSqsQueue')  # SQS queue URL.

    # Get input object metadata as we will need for SQS message sending.
    input_object_headdata_object = s3_client.head_object(
        Bucket=input_bucket,
        Key=input_key
        )

    now = datetime.now()  # Current date and time.

    message_object = {
        'siteid' : input_object_headdata_object['Metadata']['siteid'],
        'objectkey' : input_key,
        'process': process,
        'status': message_state,
        'message': '{}: {}'.format(input_key, process), # Use the process name and objectkey to deduplicate. If we get here, we have no meaningful info for the message.
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

    # logging.error(json.dumps(event))

    job_name = event['detail']['TranscriptionJobName']

    transcription_response = transcribe_client.get_transcription_job(
        TranscriptionJobName=job_name
        )

    # logging.error(transcription_response)

    input_url = transcription_response['TranscriptionJob']['Media']['MediaFileUri']
    transcription_url = transcription_response['TranscriptionJob']['Transcript']['TranscriptFileUri']

    output_vars = input_url.split('/')
    input_key = output_vars[4]
    output_key = '{}/metadata/transcription.json'.format(input_key)
    output_bucket = os.environ.get('OutputBucket')
    input_bucket = os.environ.get('InputBucket')

    # Given an Internet-accessible URL, download the data and upload it to S3,
    # without needing to persist the image to disk locally.
    http = urllib3.PoolManager()
    response = http.request('GET', transcription_url)
    transcription_object = json.loads(response.data.decode('utf-8'))
    transcription_text = transcription_object['results']['transcripts'][0]['transcript']

    # Do the actual upload to s3
    s3_resource.Bucket(output_bucket).put_object(Key=output_key, Body=response.data.decode('utf-8'))

    # Send SQS message for completed transcription.
    sqs_send_message(input_key, 'SUCCEEDED', 'TranscribeComplete')  # Send message to SQS queue.

    perform_analysis = len(transcription_text) > 0
    services = get_enabled_services(s3_client, input_bucket, input_key)

    # Send the transcription for sentiment analysis.
    # Detect sentiment
    if perform_analysis:
        if services['sentiment']:
            sentiment_response = comprehend_client.detect_sentiment(
                Text=transcription_text,
                LanguageCode='en'
            )

            s3_object = s3_resource.Object(output_bucket, '{}/metadata/sentiment.json'.format(input_key))
            s3_object.put(
                Body=(bytes(json.dumps(sentiment_response).encode('UTF-8')))
            )
             # Send SQS message for completed sentiment.
            sqs_send_message(input_key, 'SUCCEEDED', 'SentimentComplete')  # Send message to SQS queue.

        # Detect key phrases
        if services['phrases'] :
            keyphrases_response = comprehend_client.detect_key_phrases(
                Text=transcription_text,
                LanguageCode='en'
            )

            s3_object = s3_resource.Object(output_bucket, '{}/metadata/phrases.json'.format(input_key))
            s3_object.put(
                Body=(bytes(json.dumps(keyphrases_response).encode('UTF-8')))
            )
            # Send SQS message for completed phrases.
            sqs_send_message(input_key, 'SUCCEEDED', 'PhrasesComplete')  # Send message to SQS queue.

        # Detect entities
        if services['entities']:
            entities_response = comprehend_client.detect_entities(
                Text=transcription_text,
                LanguageCode='en'
            )

            s3_object = s3_resource.Object(output_bucket, '{}/metadata/entities.json'.format(input_key))
            s3_object.put(
                Body=(bytes(json.dumps(entities_response).encode('UTF-8')))
            )
            # Send SQS message for completed phrases.
            sqs_send_message(input_key, 'SUCCEEDED', 'EntitiesComplete')  # Send message to SQS queue.
