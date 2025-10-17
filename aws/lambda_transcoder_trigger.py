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
from collections import defaultdict

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
    Submits jobs to Media Convert
    """

    logger.info('Creating media convert job...')

    role = get_lambda_execution_role_arn()
    logger.info(role)

    response = mc_client.create_job(
        Queue=queue_id,
        Settings=settings,
        Role=role
    )

def get_job_settings(input_object_key, input_bucket, output_bucket, metadata):
    """
    Get the job settings from the S3 Object metadata
    """

    raw_presets_data = metadata['presets']
    decoded_presets = json.loads(raw_presets_data)
    

    # Group presets together by container
    # So say all .mpd are in one mpd playlist (with varying qualities)
    grouped_presets = defaultdict(list)
    for key, value in decoded_presets.items():
        grouped_presets[value].append(key)

    output_groups = []
    for container, presets in grouped_presets.items():
        output_group_settings = {}

        logger.info(container)
        logger.info(presets)

        outputs = [{
            "Preset": p,
            "NameModifier": p
        } for p in presets]

        # HLS
        if container == "M3U8":
            output_group_settings = {
                "Type": "HLS_GROUP_SETTINGS",
                "HlsGroupSettings": {
                    "Destination": f"s3://{output_bucket}/{input_object_key}/conversions/{input_object_key}_hls_playlist",
                    "SegmentControl": "SINGLE_FILE",
                    # These two are required by the SDK but we don't care about them.
                    # So just use the defaults (segmentlength=10,minsegmentlength=0)
                    "SegmentLength": 10,
                    "MinSegmentLength": 0,
                }
            }

            # This cannot be added in presets, so it must be added here instead.
            outputs = [{**o, **{ "OutputSettings": { "HlsSettings": { "IFrameOnlyManifest": "INCLUDE" }}}} for o in outputs]
        
        # MPEG Dash
        elif container == "MPD":
            output_group_settings = {
                "Type": "DASH_ISO_GROUP_SETTINGS",
                "DashIsoGroupSettings": {
                    "Destination": f"s3://{output_bucket}/{input_object_key}/conversions/{input_object_key}_mpegdash_playlist",
                    "SegmentControl": "SINGLE_FILE",
                    # This is required by the SDK but we don't care about them.
                    # So just use the defaults (segmentlength=10,fragmentlength=1)
                    "SegmentLength": 10,
                    "FragmentLength": 1
                }
            }
        
        # MP4 / MP3 (aka RAW)
        elif container == "MP4" or container == "RAW":
            output_group_settings = {
                "Type": "FILE_GROUP_SETTINGS",
                "FileGroupSettings": {
                    "Destination": f"s3://{output_bucket}/{input_object_key}/conversions/"
                }
            }
        
        else:
            logger.info(f"Unhandled container {container} - skipping")
            continue

        output_groups.append({
            "OutputGroupSettings": output_group_settings,
            "Outputs": outputs
        })

    settings = {
        "Inputs": [
            {
                "AudioSelectors": {
                    "Audio Selector 1": {
                        "DefaultSelection": "DEFAULT"
                    }
                },
                "FileInput": f"s3://{input_bucket}/{input_object_key}"
            }
        ],
        "OutputGroups": output_groups
    }

    return settings

def get_lambda_execution_role_arn():
    return os.environ.get('MediaConvertRoleArn')

def get_output_bucket_name():
    return os.environ.get('OutputBucketArn').split(':')[-1]

def get_media_convert_queue_name():
    return os.environ.get('MediaConvertQueueArn').split('/')[1]

def lambda_handler(event, context):
    """
    lambda_handler is the entry point that is invoked when the lambda function is called,
    more information can be found in the docs:
    https://docs.aws.amazon.com/lambda/latest/dg/python-programming-model-handler-types.html

    Trigger the file conversion when the source file is uploaded to the input s3 bucket.
    """

    #  Set logging
    logging_level = logging.INFO # DEBUG os.environ.get('LoggingLevel', logging.INFO)
    logger.setLevel(int(logging_level))

    queue_id = get_media_convert_queue_name()
    output_bucket = get_output_bucket_name()

    #  Now get and process the file from the input bucket.
    for record in event['Records']:
        input_bucket = record['s3']['bucket']['name']
        key = record['s3']['object']['key']

        #  Filter out permissions check file.
        #  This is initiated by Moodle to check bucket access is correct
        if key == 'permissions_check_file':
            continue
        
        logger.info('File uploaded: {}'.format(key))

        # Get input object metadata as we will need for SQS message sending.
        input_object_headdata_object = s3_client.head_object(
            Bucket=input_bucket,
            Key=key
            )

        metadata = input_object_headdata_object['Metadata']

        # Send message to SQS queue.
        logger.info('Sending metadata to SQS')
        sqs_send_message(key, input_bucket, record, metadata)

        logger.info('Submitting transcode job to queue {}'.format(queue_id))
        settings = get_job_settings(key, input_bucket, output_bucket, metadata)
        submit_transcode_jobs(queue_id, settings)
