[![Build Status](https://travis-ci.org/catalyst/moodle-local_smartmedia.svg?branch=master)](https://travis-ci.org/catalyst/moodle-local_smartmedia)

# Smart Media #

Smart media aims to enhance Moodle's processing and delivery of multimedia while simplifying the process of managing multimedia for teachers and students.

Smart media leverages cloud services provided through Amazon Web Services (AWS) in order to conduct video transcoding into required formats and provide additional analytics functionality for multimedia, initially support has not be provided for other cloud service providers.

## Plugin Installation ##

1. Install dependency plugin local/aws. See [local/aws](##local/aws)
2. Install dependency binary FFmpeg on your Moodle server. See [FFmpeg](##FFmpeg)
3. Clone the plugin git repo into your Moodle codebase root `git clone git@github.com:catalyst/moodle-local_smartmedia.git local/smartmedia`
4. Update plugin settings. See [Settings](##Settings)

### local/aws

The local/aws plugin is a dependency for local/smartmedia, and provides the AWS PHP SDK that is required in order for Moodle to utilise AWS cloud services.

You can download this from the Moodle plugin library at <https://moodle.org/plugins/local_aws>

Most simply, you can install this by cloning the git repo into your Moodle codebase:
```bash
git clone https://github.com/catalyst/moodle-local_aws local/aws
```

More detailed information is available at <https://github.com/catalyst/moodle-local_aws> in the README

## FFmpeg

FFmpeg is an open source multimedia framework and provides the multimedia stream analyser `ffprobe`, facilitating the collection of metadata for multimedia. 

This metadata is utilised to present reports on what media is actually in your Moodle instance.

FFmpeg is a requirement for running the local/smartmedia scheduled tasks.

To install FFmpeg, if your using a Debian based Linux distribution:
```bash
sudo apt-get update
sudo apt install ffmpeg
```
For Windows based or any other servers follow the instructions and link at <https://ffmpeg.org/download.html>

You can get more info about FFmpeg at <https://ffmpeg.org/>

## Plugin Settings

Once the local/smartmedia plugin is installed, double check the installation location of `ffprobe` and ensure that you set this in Moodle settings under `Site Administration > Plugins > Local plugins > Smart Media` for the *FFProbe binary path* field.
```bash
whereis ffprobe # default /usr/bin/ffprobe
```

## AWS Stack Setup
The following steps will setup the Amazon Web Services (AWS) infrastructure. The AWS infrastructure is required to do the actual processing of multimedia files. While setting up the AWS infrastructure is largely automated by scripts included in this plugin, a working knowledge of AWS is highly recommended.

For more information on how the submitted files are processed in AWS please refer to the topic: [Conversion Architecture](#conversion-architecture)

This step should be completed once the plugin has been installed into your Moodle instance and the other Moodle setup tasks have been completed.

**Note:** Full support on setting up an AWS account and API access keys for AWS stack infrastructure provisioning is beyond the scope of this guide.

To setup the AWS conversion stack infrastructure:

1. Create an AWS account, see: `https://aws.amazon.com/premiumsupport/knowledge-center/create-and-activate-aws-account/` for information on how to do this.
2. Create an AWS API user with administrator access and generate a API Key ID and a API Secret Key, see: `https://docs.aws.amazon.com/IAM/latest/UserGuide/id_users_create.html` for information on how to do this.
3. Change to your Moodle instance application directory. e.g. `cd /var/www/moodle`
4. Run the provisioning script below, replacing `<keyid>` and `<secretkey>` With the AWS API Key ID and AWS API Secret Key that you obtained in step 2. <br/> Replace `<region>` with the AWS region you wish to set up your AWS stack, e.g. `ap-southeast-2`. <br/> The command to execute is:

```console
sudo -u www-data php local/smartmedia/cli/provision.php \
--keyid=<keyid> \
--secret=<secretkey> \
--region=<region> \
--set-config
```
**Note:** the user may be different to www-data on your system.

The `--set-config` option will automatically set the plugin settings in Moodle based on the results returned by the provisioning script.

The script will return output similar to, the following:

```console
    
== Creating resource S3 Bucket ==
Created resource bucket, at location http://smr1565939869-resource.s3.amazonaws.com/

== Uploading Lambda function archives to resource S3 bucket ==
Lambda archive uploaded sucessfully to: https://smr1565939869-resource.s3.ap-southeast-2.amazonaws.com/lambda_ai_trigger.zip

Lambda archive uploaded sucessfully to: https://smr1565939869-resource.s3.ap-southeast-2.amazonaws.com/lambda_rekognition_complete.zip

Lambda archive uploaded sucessfully to: https://smr1565939869-resource.s3.ap-southeast-2.amazonaws.com/lambda_resource_transcoder.zip

Lambda archive uploaded sucessfully to: https://smr1565939869-resource.s3.ap-southeast-2.amazonaws.com/lambda_transcoder_trigger.zip

Lambda archive uploaded sucessfully to: https://smr1565939869-resource.s3.ap-southeast-2.amazonaws.com/lambda_transcribe_complete.zip

== Provisioning the Lambda function to provide a custom cloudformation resource provider ==
Stack status: CREATE_IN_PROGRESS
Stack status: CREATE_COMPLETE
Cloudformation custom resource stack created. Stack ID is: arn:aws:cloudformation:ap-southeast-2:693620471840:stack/smr1565939869/f5f297c0-bff5-11e9-86c0-0290a6e588aa

Lambda Resource ARN: arn:aws:lambda:ap-southeast-2:693620471840:function:smr1565939869_transcoder_resource
== Provisioning the smart media stack resources ==
Stack status: CREATE_IN_PROGRESS
Stack status: CREATE_COMPLETE
Cloudformation stack created. Stack ID is: arn:aws:cloudformation:ap-southeast-2:693620471840:stack/smt1565939869/1a1e1570-bff6-11e9-b220-02a73bda2f36

Updating Lambda transcode funciton enivronment variables.
Environment variables updated

Updating Lambda transcode funciton enivronment variables.
Environment variables updated

== Provisioning the smart media stack resources ==
Smart media S3 user access key: AKIA2C8YAPAQEWV347IT
Smart media S3 user secret key: uBdLPiBwHj+ANmYP+bpzNx5zCEqjpktjFON/NsAG
Input bucket: smt1565939869-input
Output bucket: smt1565939869-output

```

## Additional Information
The following sections provide an overview of some additional topics for this plugin and it's associated AWS architecture.

### Conversion Architecture
The below image shows the high level architecture the plugin provisioning process sets up in AWS.

![Conversion Architecture](/pix/SmartMediaAWSArch.png?raw=true)

## License ##

2019 Catalyst IT Australia

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <http://www.gnu.org/licenses/>.


This plugin was developed by Catalyst IT Australia:

https://www.catalyst-au.net/

<img alt="Catalyst IT" src="https://raw.githubusercontent.com/catalyst/moodle-local_smartmedia/master/pix/catalyst-logo.svg?sanitize=true" width="400">