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
 * This command line script will test a provisioned Smartmedia environment in AWS.
 *
 * @package     local_smartmedia
 * @copyright   2019 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
define('CACHE_DISABLE_ALL', true);

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/clilib.php');

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
    array(
        'keyid'             => false,
        'secret'            => false,
        'help'              => false,
        'region'            => false,
        'input-bucket'       => '',
        'output-bucket'      => '',
        'file'              => ''
    ),
    array(
        'h' => 'help'
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help'] || !$options['keyid'] || !$options['secret'] || !$options['region']
    || !$options['input-bucket'] || !$options['output-bucket']) {
    $help = "Command line Smartmedia test script.
This command line script will test the Smartmedia environment in AWS.
It will upload a test file to the input S3 bucket and watch the output.

Options:
--keyid=STRING            AWS API Access Key ID.
--secret=STRING           AWS API Secret Access Key.
--region=STRING           The AWS region to create the environment in.
                          e.g. ap-southeast-2
--input-bucket=STRING     The input AWS S3 bucket to use.
                          Must exist.
--output-bucket=STRING    The output AWS S3 bucket to use.
                          Must exist.
--file=STRING             The file (with path) to process.

-h, --help                Print out this help

Example:
\$sudo -u www-data php local/smartmedia/cli/test.php \
--keyid=QKIAIVYPO6FXJESSW4HQ \
--secret=CzI0r0FvPf/TqPwCoiPOdhztEkvkyULbWike1WqA \
--region=ap-southeast-2 \
--input-bucket=smartmedia_input \
--output-bucket=smartmedia_output \
--file='\\tmp\\test.mp4'
";

    echo $help;
    die;
}

$tester = new \local_smartmedia\tester(
    $options['keyid'],
    $options['secret'],
    $options['region'],
    $options['input-bucket'],
    $options['output-bucket']);

// Upload file to input S3 bucket.
cli_heading(get_string('test:uploadfile', 'local_smartmedia'));

$uploadresposnse = $tester->upload_file($options['file']);
if ($uploadresposnse->code != 0 ) {
    $errormsg = $uploadresposnse->code . ': ' . $uploadresposnse->message;
    throw new \moodle_exception($errormsg);
    exit(1);
} else {
    echo get_string('test:fileuploaded', 'local_smartmedia') . PHP_EOL . PHP_EOL;
}

exit(0); // 0 means success.
