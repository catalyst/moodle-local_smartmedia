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
import json
import os
import logging
import urllib3
from botocore.exceptions import ClientError

et_client = boto3.client('elastictranscoder')
logger = logging.getLogger()


def create(event):
    '''
    Create the Elastictranscoder pipline.
    '''
    logger.info('Creating Pipeline for request: {}'.format(event['RequestId']))
    try:
        request = et_client.create_pipeline(
            Name=event['ResourceProperties']['Name'],
            InputBucket=event['ResourceProperties']['InputBucket'],
            OutputBucket=event['ResourceProperties']['OutputBucket'],
            Role=event['ResourceProperties']['Role'],
            Notifications={
                'Progressing': event['ResourceProperties']['Notifications']['Progressing'],
                'Completed': event['ResourceProperties']['Notifications']['Completed'],
                'Warning': event['ResourceProperties']['Notifications']['Warning'],
                'Error': event['ResourceProperties']['Notifications']['Error'],
            }
        )

        logger.info('Created Pipeline with ID: {}'.format(request['Pipeline']['Id']))

        status = 'SUCCESS'
        response = {
            'action': 'create',
            'physicalresourceid': request['Pipeline']['Id'],
            'pipelineid' : request['Pipeline']['Id']
        }
    except Exception as e:
        logger.error(e)

        status = 'FAILED'
        response = {
            'action': 'create',
            'physicalresourceid': status,
            'pipelineid' : status
        }

    return [status, response]


def delete(event):
    '''
    Delete the Elastictranscoder pipline.
    '''
    logger.info('Deleting Pipeline for request: {}'.format(event['RequestId']))

    try:
        response = et_client.delete_pipeline(
            Id=event['PhysicalResourceId']
        )

        logger.info('Deleted Pipeline with ID: {}'.format(event['PhysicalResourceId']))

        status = 'SUCCESS'
    except Exception as e:
        logger.error(e)
        status = 'FAILED'

    response = {
        'action': 'delete',
        'physicalresourceid': event['PhysicalResourceId'],
        'pipelineid' : event['PhysicalResourceId']
        }

    return [status, response]


def update(event):
    pass


def send_response(event, status, actiondata):
    '''
    Send action response to cloudformation.
    '''
    logger.info('Sending {} response, for {} action to cloudformation'.format(status, actiondata['action']))

    request = {
       'Status' : status,
       'Reason' : status,
       'PhysicalResourceId' : actiondata['physicalresourceid'],
       'StackId' : event['StackId'],
       'RequestId' : event['RequestId'],
       'LogicalResourceId' : event['LogicalResourceId'],
       'Data' : {
          'PipelineId' : actiondata['pipelineid']
       }
    }

    jsonrequest = json.dumps(request)
    headers = {
        'content-type': 'application/json',
        'content-length': str(len(jsonrequest))
    }

    try:
        http = urllib3.PoolManager()
        response = http.request('PUT',
                                event['ResponseURL'],
                                body=jsonrequest,
                                headers=headers)

        logger.info("CloudFormation returned status code: {}".format(response.reason))
    except Exception as e:
        logger.error("Failed sending request, error received: {}".format(e))
        raise


def lambda_handler(event, context):
    """
    lambda_handler is the entry point that is invoked when the lambda function is called,
    more information can be found in the docs:
    https://docs.aws.amazon.com/lambda/latest/dg/python-programming-model-handler-types.html

    Handle to cloudformation stack options for elastic transcoder custom resource.
    """

    # Set logging.
    logging_level = os.environ.get('LoggingLevel', logging.ERROR)
    logger.setLevel(int(logging_level))

    # Execute custom resource handlers.
    action = event['RequestType']
    logger.info("Received a {} Request".format(action))

    if action == 'Create':
        status, actiondata = create(event)
    elif action == 'Update':
        status, actiondata = update(event)
    elif action == 'Delete':
        status, actiondata = delete(event)

    # Send response back to CloudFormation
    send_response(event, status, actiondata)

    return
