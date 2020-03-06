[![Build Status](https://travis-ci.org/catalyst/moodle-local_smartmedia.svg?branch=master)](https://travis-ci.org/catalyst/moodle-local_smartmedia)

# Smart Media #

Smart media aims to enhance Moodle's processing and delivery of multimedia while simplifying the process of managing multimedia for teachers and students.

The smart media plugins in Moodle aim to solve the following two user stories:

> As a teacher I have a video that works on my local device and I want to make that video available to my students in a suitable format, by adding it to any rich text area in Moodle. Without the need for me to do any other operations on the video apart from uploading to Moodle.

> As a student I want to be able to view any video on my chosen device; added to a rich text area in Moodle by a teacher. Regardless of the environment I’m accessing the video or my bandwidth limitations.

Smart media leverages cloud services provided through Amazon Web Services (AWS) in order to conduct video transcoding into required formats and provide additional analytics functionality for multimedia.

The following sections outline how to install the required smart media plugins in Moodle. For advanced setup and plugin usage once the plugins have been installed please see the [project wiki](https://github.com/catalyst/moodle-local_smartmedia/wiki).

## Supported Moodle Versions
This plugin currently supports Moodle:

* 3.9

**Note:** The high version of Moodle required is due to internal Moodle API's that this plugin depends on where only introduced in Moodle 3.9. If you want to use smart media in earlier Moodle versions please contact [Catalyst IT](https://www.catalyst-au.net/) for commercial support.

## Plugin Installation ##
There are several dependencies required and steps to complete in order to setup smart media in your Moodle instance.

**Note:** These instructions assume knowledge of Git, Moodle plugin functionality and that you have access to the infrastructure that runs your Moodle instance.

1. Install dependency plugin local_aws. See [local_aws](#local_aws)
2. Clone the *local_smartmedia* plugin git repo into your Moodle codebase root `git clone git@github.com:catalyst/moodle-local_smartmedia.git local/smartmedia`
3. Install dependency plugin filter_smartmedia. See [filter_smartmedia](#filter_smartmedia)
4. Install dependency binary FFmpeg on your Moodle server. See [FFmpeg](#ffmpeg)
5. Setup the AWS Stack. See [AWS Stack Setup](#aws-stack-setup)
6. Update plugin settings. See [Plugin Settings](#plugin-settings)

### local_aws

The local_aws plugin is a dependency for local_smartmedia, it provides the AWS PHP SDK that is required in order for Moodle to utilise AWS cloud services.

You can download this from the Moodle plugin library at <https://moodle.org/plugins/local_aws>

You can install this by cloning the git repo into your Moodle codebase:
```bash
git clone https://github.com/catalyst/moodle-local_aws local/aws
```

More detailed information is available at <https://github.com/catalyst/moodle-local_aws> in the README

### filter_smartmedia

The filter_smartmedia plugin is a dependency for local_smartmedia, it provides rendering of converted smart media assets in the Moodle UI.

You can install this by cloning the git repo into your Moodle codebase:
```bash
git clone https://github.com/catalyst/moodle-filter_smartmedia filter/smartmedia
```

More detailed information is available at <https://github.com/catalyst/moodle-filter_smartmedia> in the README

### FFmpeg

FFmpeg is an open source multimedia framework and provides the multimedia stream analyser `ffprobe`, facilitating the collection of metadata for multimedia.

This metadata is utilised to identify which files in your Moodle instance to convert and to present reports on what media is actually in your Moodle instance.

FFmpeg is a requirement for running the local/smartmedia scheduled tasks.

To install FFmpeg, if your using a Debian based Linux distribution:
```bash
sudo apt-get update
sudo apt install ffmpeg
```
For Windows based or any other servers follow the instructions and link at <https://ffmpeg.org/download.html>

You can get more info about FFmpeg at <https://ffmpeg.org/>

### AWS Stack Setup
The following steps will setup the Amazon Web Services (AWS) infrastructure. The AWS infrastructure is required to do the actual processing of multimedia files. While setting up the AWS infrastructure is largely automated by scripts included in this plugin, a working knowledge of AWS is highly recommended.

For more information on how the submitted files are processed in AWS please refer to the topic: [Conversion Architecture](#conversion-architecture)

**Note:** Full support on setting up an AWS account and API access keys for AWS stack infrastructure provisioning is beyond the scope of this guide.

**Note:** This plugin currently does not support multiple Moodle's sharing Smartmedia Infrastructure. The AWS stack setup must be performed on an environment by environment basis, with a unique stack per environment.

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
```
**Note:** the user may be different to www-data on your system.

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

Record the ouput from the resources section of the script information.

### Plugin Settings

Once the dependency plugins are installed, the local/smartmedia plugin is installed, ffmpeg is installed and the AWS stack has been setup; it is now time to configure Moodle.

To do this:

1. Log into the Moodle UI as a site administrator
2. Navigate to `Site Administration > Plugins > Local plugins > Smart Media`.
3. Set all required fields. All of these with the exception of the FFProbe path will be gained from the output of the AWS stack provision script.
4. All other settings can be left as their defaults
5. Click `save changes`.
6. Navigate to `Site Administration > Plugins > Filters > Manage Filters`.
7. Enable the `Smart media` filter
8. Move the `Smart media` filter to be higher in priority (before) the `Multimedia` fitler in the list of filters.

## Testing Smartmedia Conversion
The following sections outline testing of the Smartmedia plugin from the CLI. Testing from the CLI is a good debugging technique, that verifies that the AWS architecture is setup correctly. It does not depend on Moodle or the configured plugin settings.

### Conversion test script
Once the AWS architecture has been setup using the provisioning script, it can be tested from the command line.

The following test command runs a basic conversion in AWS and returns the result status. To run the script:

1. Change to your Moodle instance application directory. e.g. `cd /var/www/moodle`
2. Run the following command, replacing `<keyid>` and `<secretkey>` With the AWS API Key ID and AWS API Secret Key that you obtained in the AWS Stack Setup. <br/> Replace `<region>` with the AWS region from the  AWS stack set, e.g. `ap-southeast-2`. <br/> Replace `<inputbucket>` and `<outputbucket>` with the buckets from the setup. <br/> Finally enter the path to the file wish to process.:

```console
sudo -u www-data php local/smartmedia/cli/test.php \
--keyid=<keyid> \
--secret=<secretkey> \
--region=<region> \
--input-bucket=<inputbucket> \
--output-bucket=<outputbucket> \
--file='/var/www/moodle/local/smartmedia/tests/fixtures/SampleVideo1mb.mp4'
```

**Note:** the user may be different to www-data on your system.

## Additional Information
The following sections provide an overview of some additional topics for this plugin and it's associated AWS architecture.

For advanced setup and plugin usage once the plugins have been installed please see the [project wiki](https://github.com/catalyst/moodle-local_smartmedia/wiki).

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
