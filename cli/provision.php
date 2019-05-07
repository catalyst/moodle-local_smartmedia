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
 * This command line script will provision the Smart media environment in AWS.
 *
 * @package     local_smartmedia
 * @copyright   2018 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
define('CACHE_DISABLE_ALL', true);

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/clilib.php');

// Get cli options.
list($options, $unrecognized) = cli_get_params(
    array(
        'keyid'             => false,
        'secret'            => false,
        'help'              => false,
        'region'            => false,
        'bucket-prefix'     => '',
        'set-config'        => false,
    ),
    array(
        'h' => 'help'
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help'] || !$options['keyid'] || !$options['secret'] || !$options['region']) {
    $help = "Command line Librelmbda provision.
This command line script will provision the Smart Media environment in AWS.

Options:
--keyid=STRING            AWS API Access Key ID.
--secret=STRING           AWS API Secret Access Key.
--region=STRING           The AWS region to create the environment in.
                          e.g. ap-southeast-2
--bucket-prefix=STRING    The prefix to use for the created AWS S3 buckets.
                          Bucket names need to be globally unique.
                          If this isn't provided the a random prefix will be generated.
--set-config              Will update the plugin configuration with the resources
                          created by this script.

-h, --help                Print out this help

Example:
\$sudo -u www-data php local/smartmedia/cli/provision.php \
--keyid=QKIAIVYPO6FXJESSW4HQ \
--secret=CzI0r0FvPf/TqPwCoiPOdhztEkvkyULbWike1WqA \
--region=ap-southeast-2 \
--set-config
";

    echo $help;
    die;
}

$provisioner = new \local_smartmedia\provision(
    $options['keyid'],
    $options['secret'],
    $options['region'],
    $options['bucket-prefix']
    );

// Create S3 resource bucket.
cli_heading(get_string('provision:creatings3', 'local_smartmedia'));

$resourcebucketresposnse = $provisioner->create_bucket('resource');
if ($resourcebucketresposnse->code != 0 ) {
    $errormsg = $resourcebucketresposnse->code . ': ' . $resourcebucketresposnse->message;
    throw new \moodle_exception($errormsg);
    exit(1);
} else {
    echo get_string('provision:bucketcreated', 'local_smartmedia', array(
        'bucket' => 'resource',
        'location' => $resourcebucketresposnse->message)) . PHP_EOL . PHP_EOL;
}

// Upload custom transcoder resource provider lambda to resource bucket.
cli_heading(get_string('provision:uploadlambdatranscodearchive', 'local_smartmedia'));

$lambdapath = $CFG->dirroot . '/local/smartmedia/aws/lambda_resource_transcoder.zip';
$lambdaresourceuploadresponse = $provisioner->upload_file($lambdapath, $resourcebucketresposnse->bucketname);
if ($lambdaresourceuploadresponse->code != 0 ) {
    $errormsg = $lambdaresourceuploadresponse->code . ': ' . $lambdaresourceuploadresponse->message;
    throw new \moodle_exception($errormsg);
    exit(1);
} else {
    echo get_string(
        'provision:lambdaresourcearchiveuploaded',
        'local_smartmedia', $lambdaresourceuploadresponse->message) . PHP_EOL . PHP_EOL;
}

// Upload custom transcoder trigger provider lambda to resource bucket.
cli_heading(get_string('provision:uploadlambdatranscoder', 'local_smartmedia'));

$lambdapath = $CFG->dirroot . '/local/smartmedia/aws/lambda_transcoder_trigger.zip';
$lambdatranscodeuploadresponse = $provisioner->upload_file($lambdapath, $resourcebucketresposnse->bucketname);
if ($lambdatranscodeuploadresponse->code != 0 ) {
    $errormsg = $lambdatranscodeuploadresponse->code . ': ' . $lambdatranscodeuploadresponse->message;
    throw new \moodle_exception($errormsg);
    exit(1);
} else {
    echo get_string(
        'provision:lambdatranscoderarchiveuploaded',
        'local_smartmedia', $lambdatranscodeuploadresponse->message) . PHP_EOL . PHP_EOL;
}

die;

// Create Lambda function, IAM roles and the rest of the stack.
cli_heading(get_string('provision:stack', 'local_smartmedia'));
$cloudformationpath = $CFG->dirroot . '/local/smartmedia/aws/stack.template';

$params = array(
    'bucketprefix' => $provisioner->get_bucket_prefix(),
    'lambdaarchive' => 'lambdaconvert.zip',
    'lambdalayer' => 'lo.zip',
    'resourcebucket' => $resourcebucketresposnse->bucketname,
    'templatepath' => $cloudformationpath
);

$createstackresponse = $provisioner->create_stack($params);
if ($createstackresponse->code != 0 ) {
    $errormsg = $createstackresponse->code . ': ' . $createstackresponse->message;
    throw new \moodle_exception($errormsg);
    exit(1);
} else {
    echo get_string('provision:stackcreated', 'local_smartmedia', $createstackresponse->message) . PHP_EOL . PHP_EOL;
}

// Print summary.
cli_heading(get_string('provision:stack', 'local_smartmedia'));
echo get_string('provision:s3useraccesskey', 'local_smartmedia', $createstackresponse->S3UserAccessKey) . PHP_EOL;
echo get_string('provision:s3usersecretkey', 'local_smartmedia', $createstackresponse->S3UserSecretKey) . PHP_EOL;
echo get_string('provision:inputbucket', 'local_smartmedia', $createstackresponse->InputBucket) . PHP_EOL;
echo get_string('provision:outputbucket', 'local_smartmedia', $createstackresponse->OutputBucket) . PHP_EOL;

// Set config.
if ($options['set-config']) {
    cli_heading(get_string('provision:setconfig', 'local_smartmedia'));
    set_config('api_key', $createstackresponse->S3UserAccessKey, 'local_smartmedia');
    set_config('api_secret', $createstackresponse->S3UserSecretKey, 'local_smartmedia');
    set_config('s3_input_bucket', $createstackresponse->InputBucket, 'local_smartmedia');
    set_config('s3_output_bucket', $createstackresponse->OutputBucket, 'local_smartmedia');
    set_config('api_region', $options['region'], 'local_smartmedia');
    purge_all_caches();  // Purge caches to ensure UI updates with new settings.
}

exit(0); // 0 means success.
