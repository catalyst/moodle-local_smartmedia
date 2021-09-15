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
 * This command line script will provision the Smartmedia environment in AWS.
 *
 * @package     local_smartmedia
 * @copyright   2019 Matt Porritt <mattp@catalyst-au.net>
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
        'set-config'        => false,
        'identifier'        => false,
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
    $help = "Command line Smartmedia provision.
This command line script will provision the Smart Media environment in AWS.

Options:
--keyid=STRING            AWS API Access Key ID.
--secret=STRING           AWS API Secret Access Key.
--region=STRING           The AWS region to create the environment in.
                          e.g. ap-southeast-2
--set-config              Will update the plugin configuration with the resources
                          created by this script.
--identifier              The stack resource identifier prefix

-h, --help                Print out this help

Example:
\$sudo -u www-data php local/smartmedia/cli/provision.php \
--keyid=QKIAIVYPO6FXJESSW4HQ \
--secret=CzI0r0FvPf/TqPwCoiPOdhztEkvkyULbWike1WqA \
--region=ap-southeast-2 \
--set-config \
--identifier=prod
";

    echo $help;
    die;
}

$provisioner = new \local_smartmedia\provision(
    $options['keyid'],
    $options['secret'],
    $options['region']
    );
$now = time();

$identifier = $options['identifier'] ? $options['identifier'] : $now;

// Resource stack name.
$stackname = 'smr-'. $identifier;

// Transcoder stack name.
$transcoderstackname = 'smt-'. $identifier;

// Create S3 resource bucket.
cli_heading(get_string('provision:creatings3', 'local_smartmedia'));

$bucketname = $stackname . '-' . 'resource';
$resourcebucketresposnse = $provisioner->create_bucket($bucketname);
if ($resourcebucketresposnse->code != 0 ) {
    $errormsg = $resourcebucketresposnse->code . ': ' . $resourcebucketresposnse->message;
    throw new \moodle_exception($errormsg);
    exit(1);
} else {
    echo get_string('provision:bucketcreated', 'local_smartmedia', array(
        'bucket' => 'resource',
        'location' => $resourcebucketresposnse->message)) . PHP_EOL . PHP_EOL;
}

// Upload lambda function archives to the S3 resource bucket.
cli_heading(get_string('provision:uploadlambdaarchives', 'local_smartmedia'));
$archivepath = $CFG->dirroot . '/local/smartmedia/aws/';
$archives = glob($archivepath . '/*.{zip}', GLOB_BRACE);

foreach ($archives as $archive) {
    $lambdaarchiveuploadresponse = $provisioner->upload_file($archive, $resourcebucketresposnse->bucketname);
    if ($lambdaarchiveuploadresponse->code != 0 ) {
        $errormsg = $lambdaarchiveuploadresponse->code . ': ' . $lambdaarchiveuploadresponse->message;
        throw new \moodle_exception($errormsg);
        exit(1);
    } else {
        echo get_string(
            'provision:lambdaarchiveuploaded',
            'local_smartmedia', $lambdaarchiveuploadresponse->message) . PHP_EOL . PHP_EOL;
    }
}

// Create the Lambda function and associated services for the
// custom elastic transcoder resource cloudformation provider.
cli_heading(get_string('provision:resourcestack', 'local_smartmedia'));
$cloudformationpath = $CFG->dirroot . '/local/smartmedia/aws/resource.template';

$params = array(
    'LambdaTranscodeResourceArchiveKey' => 'lambda_resource_transcoder.zip',
    'ResourceBucket' => $resourcebucketresposnse->bucketname,
    'templatepath' => $cloudformationpath
);

$createstackresponse = $provisioner->create_stack($stackname, $params);
if ($createstackresponse->code != 0 ) {
    $errormsg = $createstackresponse->code . ': ' . $createstackresponse->message;
    throw new \moodle_exception($errormsg);
    exit(1);
} else {
    echo get_string('provision:resourcestackcreated', 'local_smartmedia', $createstackresponse->message) . PHP_EOL . PHP_EOL;
}

// Get Lmbda ARN.
$lambdaresourcesrn = $createstackresponse->outputs['LambdaTranscodeResourceFunction'];

// Print Summary.
echo get_string('provision:lambdaresourcearn', 'local_smartmedia', $lambdaresourcesrn) . PHP_EOL;

// Create Lambda function, IAM roles and the rest of the stack.
cli_heading(get_string('provision:stack', 'local_smartmedia'));
$cloudformationpath = $CFG->dirroot . '/local/smartmedia/aws/stack.template';

$params = array(
    'LambdaTranscodeTriggerArchiveKey' => 'lambda_transcoder_trigger.zip',
    'LambdaAiArchiveKey' => 'lambda_ai_trigger.zip',
    'LambdaRekognitionCompleteArchiveKey' => 'lambda_rekognition_complete.zip',
    'LambdaTranscribeCompleteArchiveKey' => 'lambda_transcribe_complete.zip',
    'LambdaTranscodeResourceFunctionArn' => $lambdaresourcesrn,
    'ResourceBucket' => $resourcebucketresposnse->bucketname,
    'templatepath' => $cloudformationpath
);

$createstackresponse = $provisioner->create_stack($transcoderstackname, $params);
if ($createstackresponse->code != 0 ) {
    $errormsg = $createstackresponse->code . ': ' . $createstackresponse->message;
    throw new \moodle_exception($errormsg);
    exit(1);
} else {
    echo get_string('provision:stackcreated', 'local_smartmedia', $createstackresponse->message) . PHP_EOL . PHP_EOL;
}

// We need to update the created Lambda functions environment variables
// as trying to do it at stack build time causes a circular references in cloudformation.

$envvararray = array(
    array(
        'function' => $createstackresponse->outputs['TranscodeLambdaArn'],
        'values' => array(
            'PipelineId' => $createstackresponse->outputs['TranscodePipelineId'],
            'SmartmediaSqsQueue' => $createstackresponse->outputs['SmartmediaSqsQueue'])
    )
);

foreach ($envvararray as $envvars) {
    echo get_string('provision:lambdaenvupdate', 'local_smartmedia', $envvars['function']) . PHP_EOL;
    $updatelambdaresponse = $provisioner->update_lambda($envvars['function'], $envvars['values']);
    if ($updatelambdaresponse->code != 0 ) {
        $errormsg = $updatelambdaresponse->code . ': ' . $updatelambdaresponse->message;
        throw new \moodle_exception($errormsg);
        exit(1);
    } else {
        echo $updatelambdaresponse->message . PHP_EOL . PHP_EOL;
    }
};

// Print summary.
cli_heading(get_string('provision:stack', 'local_smartmedia'));
echo get_string(
    'provision:s3useraccesskey',
    'local_smartmedia',
    $createstackresponse->outputs['SmartMediaS3UserAccessKey']) . PHP_EOL;
echo get_string(
    'provision:s3usersecretkey',
    'local_smartmedia',
    $createstackresponse->outputs['SmartMediaS3UserSecretKey']) . PHP_EOL;
echo get_string('provision:inputbucket', 'local_smartmedia', $createstackresponse->outputs['InputBucket']) . PHP_EOL;
echo get_string('provision:outputbucket', 'local_smartmedia', $createstackresponse->outputs['OutputBucket']) . PHP_EOL;
echo get_string('provision:sqsqueue', 'local_smartmedia', $createstackresponse->outputs['SmartmediaSqsQueue']) . PHP_EOL;

exit(0); // 0 means success.
