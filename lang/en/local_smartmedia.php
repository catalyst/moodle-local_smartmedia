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

$string['provision:creatings3'] = 'Creating resource S3 Bucket';
$string['provision:bucketcreated'] = 'Created {$a->bucket} bucket, at location {$a->location}';
$string['provision:inputbucket'] = 'Input bucket: {$a}';
$string['provision:lambdaarchiveuploaded'] = 'Lambda function archive uploaded sucessfully to: {$a}';
$string['provision:lambdalayeruploaded'] = 'Lambda layer uploaded sucessfully to: {$a}';
$string['provision:librearchiveuploaded'] = 'Libreoffice archive uploaded sucessfully to: {$a}';
$string['provision:outputbucket'] = 'Output bucket: {$a}';
$string['provision:setconfig'] = 'Setting plugin configuration in Moodle, from returned settings.';
$string['provision:stack'] = 'Provisioning the Lambda function and stack resources';
$string['provision:s3useraccesskey'] = 'S3 user access key: {$a}';
$string['provision:s3usersecretkey'] = 'S3 user secret key: {$a}';
$string['provision:stackcreated'] = 'Cloudformation stack created. Stack ID is: {$a}';
$string['provision:uploadlibrearchive'] = 'Uploading LibreOffice archive to resource S3 bucket';
$string['provision:uploadlambdaarchive'] = 'Uploading Lambda archive to resource S3 bucket';
$string['provision:uploadlambdalayer'] = 'Uploading Lambda layer to resource S3 bucket';