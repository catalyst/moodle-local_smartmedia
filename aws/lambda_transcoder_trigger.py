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
from botocore.exceptions import ClientError

s3_client = boto3.client('s3')
et_client = boto3.client('elastictranscoder')
logger = logging.getLogger()


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

    #  Get Pipeline ID from environment variable
    pipeline_id = os.environ.get('PipelineId')
    logger.error('Executing Pipeline: {}'.format(pipeline_id))

    #  Now get and process the file from the input bucket.
    for record in event['Records']:
        bucket = record['s3']['bucket']['name']
        key = record['s3']['object']['key']

        #  Filter out permissions check file.
        #  This is initiated by Moodle to check bucket access is correct
        if key == 'permissions_check_file':
            continue

        logger.error('File uploaded: {}'.format(key))

