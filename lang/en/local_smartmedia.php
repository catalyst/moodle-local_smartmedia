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

$string['ffprobe:invalidpath'] = 'Invalid FFProbe path';
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
$string['provision:stackcreated'] = 'Cloudformation stack created. Stack ID is: {$a}';
$string['provision:uploadlambdaarchives'] = 'Uploading Lambda function archives to resource S3 bucket';
$string['report:duration'] = 'Duration (sec)';
$string['report:format'] = 'Format';
$string['report:heading'] = 'Smart Media Report ({$a})';
$string['report:nocostdata'] = ' - ';
$string['report:resolution'] = 'Resolution';
$string['report:size'] = 'Size (Mb)';
$string['report:summary:filesummary'] = 'All file record summary';
$string['report:summary:title'] = 'Database file record totals';
$string['report:summary:filesummary:otherfiles'] = 'Other files';
$string['report:summary:filesummary:videofiles'] = 'Video files';
$string['report:summary:filesummary:audiofiles'] = 'Audio files';
$string['report:summary:filesummary:total'] = 'Total';
$string['report:summary:cost:description'] = 'Transcode cost for all files';
$string['report:summary:cost:total'] = 'Total:';
$string['report:summary:nodata'] = 'No data found';
$string['report:summary:transcodetotal'] = 'Transcode cost';
$string['report:summary:warning:noaudiocost'] = 'No audio transcode data: could not obtain audio transcode pricing data for {$a} region.';
$string['report:summary:warning:nohdcost'] = 'No high definition transcode data: Could not obtain high definition transcode pricing data for {$a} region.';
$string['report:summary:warning:nosdcost'] = 'No standard definition transcode data: Could not obtain standard definition transcode pricing data for {$a} region.';
$string['report:transcodecost'] = 'Transcode cost';
$string['report:type'] = 'File type';
$string['report:typeaudio'] = 'Audio';
$string['report:typevideo'] = 'Video';
$string['settings:aws:header'] = 'AWS settings';
$string['settings:aws:header_desc'] = 'The settings for the AWS components used to convert media files and extract information.';
$string['settings:aws:key'] = 'Key';
$string['settings:aws:key_help'] = 'Amazon API key credential.';
$string['settings:aws:secret'] = 'Secret';
$string['settings:aws:secret_help'] = 'Amazon API secret credential.';
$string['settings:aws:input_bucket'] = 'Input bucket';
$string['settings:aws:input_bucket_help'] = 'Amazon S3 bucket to upload assignment submissions.';
$string['settings:aws:output_bucket'] = 'Output bucket';
$string['settings:aws:output_bucket_help'] = 'Amazon S3 bucket to fetch converted assignment submissions.';
$string['settings:aws:region'] = 'Region';
$string['settings:aws:region_help'] = 'Amazon API gateway region.';
$string['settings:ffprobe:header'] = 'FFProbe settings';
$string['settings:ffprobe:header_desc'] = 'The settings for the FFProbe binary.';
$string['settings:processing:detectentities'] = 'Entity detection';
$string['settings:processing:detectentities_desc'] = 'Detect entities in video files, such as companies and locations.';
$string['settings:processing:detectfaces'] = 'Face detection';
$string['settings:processing:detectfaces_desc'] = 'Detect faces in video files, including facial attributes and expressions.';
$string['settings:processing:detectlabels'] = 'Label detection';
$string['settings:processing:detectlabels_desc'] = 'Detect object labels in video files, such as chair, crowd, and human.';
$string['settings:processing:detectpeople'] = 'People detection';
$string['settings:processing:detectpeople_desc'] = 'Detect the occurance of individuals in a video.';
$string['settings:processing:detectphrases'] = 'Key phrase detection';
$string['settings:processing:detectphrases_desc'] = 'Detect key phrases in audio and video files.';
$string['settings:processing:detectmoderation'] = 'Moderation detection';
$string['settings:processing:detectmoderation_desc'] = 'Perform moderation content on videos for adult or sensitive content.';
$string['settings:processing:detectsentiment'] = 'Sentiment detection';
$string['settings:processing:detectsentiment_desc'] = 'Perform sentiment detection on video and audio files.';
$string['settings:processing:header'] = 'Processing settings';
$string['settings:processing:header_desc'] = 'These settings control how media files are processed.';
$string['settings:processing:transcodepresets'] = 'Trascoding presets';
$string['settings:processing:transcodepresets_desc'] = 'Comma delimited list of AWS Elastic Transcoder presets used to convert media files.';
$string['settings:processing:transcribe'] = 'Trascribe file';
$string['settings:processing:transcribe_desc'] = 'Attempt an automated transcription on audio and video files.';
$string['settings:ffprobe:pathtoffprobe'] = 'FFProbe binary path';
$string['settings:ffprobe:pathtoffprobe_desc'] = 'The path to the FFProbe binary on the server running Moodle.';
$string['task:extractmetadata'] = 'Smartmedia: extract multimedia file metadata.';
$string['task:reportprocess'] = 'Smartmedia: extract report data.';
