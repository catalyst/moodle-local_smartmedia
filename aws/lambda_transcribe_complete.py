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
from botocore.vendored import requests
from botocore.exceptions import ClientError

logger = logging.getLogger()

# Get clients and resources.
s3_resource = boto3.resource('s3')
transcribe_client = boto3.client('transcribe')


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

    job_name = event['detail']['TranscriptionJobName']

    transcription_response = transcribe_client.get_transcription_job(
        TranscriptionJobName=job_name
        )

    logging.error(transcription_response)

    input_url = transcription_response['TranscriptionJob']['Media']['MediaFileUri']
    transcription_url = transcription_response['TranscriptionJob']['Transcript']['TranscriptFileUri']

    output_vars = input_url.split('/')
    output_bucket = output_vars[3]
    output_key = '{}/metadata/transcription.json'.format(output_vars[4])

    # Given an Internet-accessible URL, download the data and upload it to S3,
    # without needing to persist the image to disk locally.
    json_request = requests.get(transcription_url, stream=True)
    file_object = json_request.raw
    request_data = file_object.read()

    # Do the actual upload to s3
    s3_resource.Bucket(output_bucket).put_object(Key=output_key, Body=request_data)
