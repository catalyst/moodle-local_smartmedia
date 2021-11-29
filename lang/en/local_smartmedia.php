<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     local_smartmedia
 * @category    string
 * @copyright   2019 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Smart Media';

$string['cachedef_serve'] = 'Low latency file serve cache';
$string['ffprobe:invalidpath'] = 'Invalid FFProbe path';
$string['privacy:metadata:local_smartmedia:externalpurpose'] = 'This information is sent to AWS API in order the file to be converted to an alternative format. The file is temporarily kept in an AWS S3 bucket and gets deleted after the conversion is done.';
$string['privacy:metadata:local_smartmedia:filecontent'] = 'The content of the file.';
$string['privacy:metadata:local_smartmedia:params'] = 'The query parameters passed to AWS API.';
$string['provision:creatings3'] = 'Creating resource S3 Bucket';
$string['provision:bucketcreated'] = 'Created {$a->bucket} bucket, at location {$a->location}';
$string['provision:bucketexists'] = 'Bucket exists';
$string['provision:inputbucket'] = 'Input bucket: {$a}';
$string['provision:lambdaenvupdate'] = 'Updating Lambda transcode funciton enivronment variables.';
$string['provision:lambdaresourcearn'] = 'Lambda Resource ARN: {$a}';
$string['provision:lambdaarchiveuploaded'] = 'Lambda archive uploaded sucessfully to: {$a}';
$string['provision:outputbucket'] = 'Output bucket: {$a}';
$string['provision:setconfig'] = 'Setting plugin configuration in Moodle, from returned settings.';
$string['provision:resourcestack'] = 'Provisioning the Lambda function to provide a custom cloudformation resource provider';
$string['provision:resourcestackcreated'] = 'Cloudformation custom resource stack created. Stack ID is: {$a}';
$string['provision:stack'] = 'Provisioning the smart media stack resources';
$string['provision:s3useraccesskey'] = 'Smart media S3 user access key: {$a}';
$string['provision:s3usersecretkey'] = 'Smart media S3 user secret key: {$a}';
$string['provision:sqsqueue'] = 'SQS queue URL: {$a}';
$string['provision:stackcreated'] = 'Cloudformation stack created. Stack ID is: {$a}';
$string['provision:uploadlambdaarchives'] = 'Uploading Lambda function archives to resource S3 bucket';
$string['dashboard:heading'] = 'Smart Media Dashboard';
$string['report:created'] = 'Created';
$string['report:completed'] = 'Completed';
$string['report:duration'] = 'Duration: ';
$string['report:files'] = 'Instances: ';
$string['report:filedetails'] = 'File details';
$string['report:filename'] = 'Filename';
$string['report:format'] = 'Format: ';
$string['report:heading'] = 'Smart Media Overview Report';
$string['report:heading_desc'] = 'This table shows an overview of Smartmedia conversions.';
$string['report:nocostdata'] = ' - ';
$string['report:sourceresolution'] = 'Source resolution: ';
$string['report:targetresolutions'] = 'Target resolutions: ';
$string['report:size'] = 'Size: ';
$string['report:status'] = 'Status';
$string['report:summary:filesummary'] = 'All file record summary';
$string['report:summary:processsummary'] = 'Multimedia file process summary';
$string['report:processsummary:title'] = 'Process status totals';
$string['report:summary:processsummary:uniquemultimediaobjects'] = 'Unique file objects';
$string['report:summary:processsummary:metadataprocessedfiles'] = 'Metadata processed files';
$string['report:summary:processsummary:transcodedfiles'] = 'Transcoded files';
$string['report:summary:title'] = 'Database file record totals';
$string['report:summary:totals'] = 'Totals';
$string['report:summary:filesummary:otherfiles'] = 'Other files';
$string['report:summary:filesummary:videofiles'] = 'Video files';
$string['report:summary:filesummary:audiofiles'] = 'Audio files';
$string['report:summary:filesummary:total'] = 'Total';
$string['report:summary:cost:remaindescription'] = 'Cost to convert remaining files based on current settings.';
$string['report:summary:cost:donedescription'] = 'Accumulated cost for converted files.';
$string['report:summary:cost:disclaimer'] = 'Note: Cost displayed are calculated based on conversion settings, and are for the puposes of planning. Please refer to AWS billing for actual amounts charged.';
$string['report:summary:cost:total'] = 'Total:';
$string['report:summary:nodata'] = 'No data found';
$string['report:summary:transcodetotal'] = 'Transcode cost';
$string['report:summary:warning:invalidpresets'] = 'There are no valid AWS Elastic Transcoder preset ids in your SmartMedia settings, pricing cannot be calculated.';
$string['report:summary:warning:noaudiocost'] = 'No audio transcode data: could not obtain audio transcode pricing data for {$a} region.';
$string['report:summary:warning:nohdcost'] = 'No high definition transcode data: Could not obtain high definition transcode pricing data for {$a} region.';
$string['report:summary:warning:nosdcost'] = 'No standard definition transcode data: Could not obtain standard definition transcode pricing data for {$a} region.';
$string['report:transcodecost'] = 'Transcode cost: ';
$string['report:type'] = 'File type: ';
$string['report:typeaudio'] = 'Audio';
$string['report:typevideo'] = 'Video';
$string['settings:aws:description'] = 'These settings define the required AWS settings to allow Moodle to conntect to AWS for conersion.';
$string['settings:aws:header'] = 'AWS settings';
$string['settings:aws:header_desc'] = 'The settings for the AWS components used to convert media files and extract information.';
$string['settings:aws:key'] = 'Key';
$string['settings:aws:key_help'] = 'Amazon API key credential.';
$string['settings:aws:secret'] = 'Secret';
$string['settings:aws:secret_help'] = 'Amazon API secret credential.';
$string['settings:aws:input_bucket'] = 'Input bucket';
$string['settings:aws:input_bucket_help'] = 'Amazon S3 bucket to upload media files to gor conversion.';
$string['settings:aws:output_bucket'] = 'Output bucket';
$string['settings:aws:output_bucket_help'] = 'Amazon S3 bucket to fetch converted media files.';
$string['settings:aws:region'] = 'Region';
$string['settings:aws:region_help'] = 'Amazon API gateway region.';
$string['settings:aws:sqs_queue_url'] = 'SQS queue URL';
$string['settings:aws:sqs_queue_url_help'] = 'URL of the AWS SQS queue to receive status messages from.';
$string['settings:aws:usesdkcreds'] = 'Use the default credential provider chain to find AWS credentials';
$string['settings:aws:usesdkcreds_desc'] = 'If Moodle is hosted inside AWS, the default credential chain can be used for access to Smartmedia resources. If so, the AWS key and Secret key are not required to be provided.';
$string['settings:connectionsuccess'] = 'Could establish connection to the external object storage.';
$string['settings:connectionfailure'] = 'Could not establish connection to the external object storage.';
$string['settings:description'] = 'Description';
$string['settings:lowlatency'] = 'Use cached file serving';
$string['settings:lowlatency_help'] = 'This allows smartmedia to use low latency moodle cached file serving for smartmedia. Improves performance for Moodle based file serving. Disable if using external file serving such a CDN.';
$string['settings:writefailure'] = 'Could not write object to the external object storage. ';
$string['settings:readfailure'] = 'Could not read object from the external object storage. ';
$string['settings:deletesuccess'] = 'Could delete object from the external object storage - It is not recommended for the user to have delete permissions. ';
$string['settings:deleteerror'] = 'Could not delete object from the external object storage. ';
$string['settings:permissioncheckpassed'] = 'Permissions check passed.';
$string['settings:ffprobe:description'] = 'These settings relate to FFProbe. FFprobe is used for initial Moodle server side analysis of files.';
$string['settings:ffprobe:header'] = 'FFProbe settings';
$string['settings:ffprobe:header_desc'] = 'The settings for the FFProbe binary.';
$string['settings:enrichment:description'] = 'These settings control what services are used to enrich the information related to media files';
$string['settings:enrichment:header'] = 'Enrichment settings';
$string['settings:enrichment:header_desc'] = 'These settings determine the enrichment processing.';
$string['settings:enrichment:detectentities'] = 'Entity detection';
$string['settings:enrichment:detectentities_help'] = 'Detect entities in video files, such as companies and locations.';
$string['settings:enrichment:detectfaces'] = 'Face detection';
$string['settings:enrichment:detectfaces_help'] = 'Detect faces in video files, including facial attributes and expressions.';
$string['settings:enrichment:detectlabels'] = 'Label detection';
$string['settings:enrichment:detectlabels_help'] = 'Detect object labels in video files, such as chair, crowd, and human.';
$string['settings:enrichment:detectpeople'] = 'People detection';
$string['settings:enrichment:detectpeople_help'] = 'Detect the occurance of individuals in a video.';
$string['settings:enrichment:detectphrases'] = 'Key phrase detection';
$string['settings:enrichment:detectphrases_help'] = 'Detect key phrases in audio and video files.';
$string['settings:enrichment:detectmoderation'] = 'Moderation detection';
$string['settings:enrichment:detectmoderation_help'] = 'Perform moderation content on videos for adult or sensitive content.';
$string['settings:enrichment:detectsentiment'] = 'Sentiment detection';
$string['settings:enrichment:detectsentiment_help'] = 'Perform sentiment detection on video and audio files.';
$string['settings:processing:description'] = 'These settings control how and when media files are processed.';
$string['settings:processing:header'] = 'Processing settings';
$string['settings:processing:header_desc'] = 'These settings control how media files are processed.';
$string['settings:processing:convertfrom'] = 'Convert since';
$string['settings:processing:convertfrom_help'] = 'Only files added to Moodle since this time period will be converted. Applies to both background processing and view conversions.';
$string['settings:processing:maxruntime'] = 'Maximum task runtime';
$string['settings:processing:maxruntime_help'] = 'This setting controls the maximum runtime of the metadata extraction task. After this duration, the task will exit cleanly.';
$string['settings:processing:proactiveconversion'] = 'Background processing';
$string['settings:processing:proactiveconversion_help'] = 'When enabled media files will be processed via scheduled task.';
$string['settings:processing:viewconversion'] = 'View processing';
$string['settings:processing:viewconversion_help'] = 'When enabled media files will be processed when they are first viewed.';
$string['settings:output:description'] = 'These settings control what outputs are produced when a file is converted.';
$string['settings:output:header'] = 'Output settings';
$string['settings:output:header_desc'] = 'These settings control what outputs are produced when a file is converted.';
$string['settings:output:quality_low'] = ' Low quailty output - 600 Kb/s';
$string['settings:output:quality_low_help'] = 'Source file will be transcode to a low quality low bandwidth output.';
$string['settings:output:quality_medium'] = 'Medium quality output - 1.2 Mb/s';
$string['settings:output:quality_medium_help'] = 'Source file will be transcode to a medium quality medium bandwidth output.';
$string['settings:output:quality_high'] = 'High quality output - 2.4 Mb/s';
$string['settings:output:quality_high_help'] = 'Source file will be transcode to a high quality high bandwidth output.';
$string['settings:output:quality_extrahigh'] = 'Extra high quality output - 4.8 Mb/s';
$string['settings:output:quality_extrahigh_help'] = 'Source file will be transcode to an extra high quality extra high bandwidth output.';
$string['settings:output:audio_output'] = 'Audio output';
$string['settings:output:audio_output_help'] = 'Provide an audio only output for video files.';
$string['settings:output:download_files'] = 'Download files';
$string['settings:output:download_files_help'] = 'Provide option for users to download files.';
$string['settings:output:usecustompresets'] = 'Use custom presets';
$string['settings:output:usecustompresets_help'] = 'Check to enable support for custom ETS presets.';
$string['settings:output:custompresets'] = 'Define custom presets';
$string['settings:output:custompresets_help'] = 'Enter any custom preset ID\'s to use, seperated by a comma.';
$string['settings:enrichment:transcribe'] = 'Trascribe file';
$string['settings:enrichment:transcribe_help'] = 'Attempt an automated transcription on audio and video files.';
$string['settings:ffprobe:pathtoffprobe'] = 'FFProbe binary path';
$string['settings:ffprobe:pathtoffprobe_desc'] = 'The path to the FFProbe binary on the server running Moodle.';
$string['settings:useproxy'] = 'Use proxy';
$string['settings:useproxy_help'] = 'Smartmedia can use configured Moodle proxy to reach AWS API';
$string['task:extractmetadata'] = 'Smartmedia: extract multimedia file metadata.';
$string['task:processconversions'] = 'Smartmedia: process pending conversions.';
$string['test:bucketnotexists'] = 'The {$a} bucket does not exist.';
$string['test:fileuploaded'] = 'Test file uploaded';
$string['test:uploadfile'] = 'Uploading test file';
$string['task:reportprocess'] = 'Smartmedia: extract report data.';
