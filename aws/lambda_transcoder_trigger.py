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
et_client = boto3.client('elastictranscoder')
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


def submit_transcode_jobs(s3key, pipeline_id, presets):
    """
    Submits jobs to Elastic Transcoder.
    """

    logger.info('Triggering transcode job...')

    outputs = []
    playlists = {} # Start as a dictionary, so we can add outputs by playlist key.

    # Create a playlist for MPEG-DASH adaptive streaming if required.
    if 'fmp4' in presets.values() :
        fmp4playlist = {}
        fmp4playlist['Name'] = '{0}_mpegdash_playlist'.format(s3key)
        fmp4playlist['Format'] = 'MPEG-DASH'
        fmp4playlist['OutputKeys'] = []
        playlists['fmp4playlist'] = fmp4playlist

    # Create a playlist for HLS adaptive streaming if required.
    if 'ts' in presets.values() :
        tsplaylist = {}
        tsplaylist['Name'] = '{0}_hls_playlist'.format(s3key)
        tsplaylist['Format'] = 'HLSv4'
        tsplaylist['OutputKeys'] = []
        playlists['tsplaylist'] = tsplaylist

    for preset_id, container in presets.items() :
        output = {}
        filename = '{0}_{1}'.format(s3key, preset_id)
        # HLS outputs will add .ts file extension automatically, so don't append container type.
        if container == 'ts' :
            output['Key'] = filename
        else :
            output['Key'] = '{0}.{1}'.format(filename, container)
        output['PresetId'] = preset_id
        output['ThumbnailPattern'] = ''

        # Add output to appropriate playlist if the preset outputs fragmented media.
        if container == 'fmp4' or container == 'ts' :
            output['SegmentDuration'] = '6' # Hard code segments to 3 seconds duration.
            if container == 'fmp4' :
                playlists['fmp4playlist']['OutputKeys'].append(output['Key'])
            if container == 'ts' :
                playlists['tsplaylist']['OutputKeys'].append(filename)

        outputs.append(output)

    response = et_client.create_job(
        PipelineId=pipeline_id,
         OutputKeyPrefix=s3key + '/conversions/',
         Input={
            'Key': s3key,
        },
        Outputs=outputs,
        Playlists=list(playlists.values()) # Convert dictionary to list.
    )

    logger.info(response)

def get_presets(key, bucket, metadata):
    """
    Get applicable elastic transcoder presets from S3 metadata
    """
    raw_preset_data = metadata['presets']
    decoded_presets = json.loads(raw_preset_data)
    logger.info(decoded_presets)
    return decoded_presets

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

    logger.info(event)

    #  Get Pipeline ID from environment variable
    pipeline_id = os.environ.get('PipelineId')
    logger.info('Executing Pipeline: {}'.format(pipeline_id))

    #  Now get and process the file from the input bucket.
    for record in event['Records']:
        bucket = record['s3']['bucket']['name']
        key = record['s3']['object']['key']

        #  Filter out permissions check file.
        #  This is initiated by Moodle to check bucket access is correct
        if key == 'permissions_check_file':
            continue

        # Get input object metadata as we will need for SQS message sending.
        input_object_headdata_object = s3_client.head_object(
            Bucket=bucket,
            Key=key
            )

        metadata = input_object_headdata_object['Metadata']

        logger.info('File uploaded: {}'.format(key))

        # Send message to SQS queue.
        sqs_send_message(key, bucket, record, metadata)

        presets = get_presets(key, bucket, metadata)

        submit_transcode_jobs(key, pipeline_id, presets)
