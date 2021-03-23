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
 * Class for converting files between different file formats using AWS.
 *
 * @package     local_smartmedia
 * @copyright   2019 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_smartmedia;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/aws/sdk/aws-autoloader.php');

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

/**
 * Class for converting files between different formats using unoconv.
 *
 * @package     local_smartmedia
 * @copyright   2018 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class aws_s3 {

    /**
     *
     * @var object Plugin confiuration.
     */
    private $config;

    /**
     *
     * @var \Aws\S3\S3Client S3 client.
     */
    private $client;


    /**
     * Class constructor
     *
     * @param \stdClass|null $config Optional configuarion object to use.
     */
    public function __construct($config=null) {

        if ($config) {
            $this->config = $config;
        } else {
            $this->config = get_config('local_smartmedia');
        }

    }

    /**
     * Create AWS S3 API client.
     *
     * @param \GuzzleHttp\Handler $handler Optional handler.
     * @return \Aws\S3\S3Client
     */
    public function create_client($handler=null) {
        $connectionoptions = array(
            'version' => 'latest',
            'region' => $this->config->api_region
        );

        $usesdkcreds = get_config('local_smartmedia', 'usesdkcreds');
        if (!$usesdkcreds) {
            $connectionoptions['credentials'] = [
                'key' => $this->config->api_key,
                'secret' => $this->config->api_secret
            ];
        }

        // If use proxy is configured, add to args.
        if ($this->config->useproxy) {
            $connectionoptions['http'] = ['proxy' => \local_aws\local\aws_helper::get_proxy_string()];
        }

        // Allow handler overriding for testing.
        if ($handler != null) {
            $connectionoptions['handler'] = $handler;
        }

        // Only create client if it hasn't already been done.
        if ($this->client == null) {
            $this->client = new S3Client($connectionoptions);
        }

        return $this->client;
    }

    /**
     * When an exception occurs get and return
     * the exception details.
     *
     * @param \Aws\Exception $exception The thrown exception.
     * @return string $details The details of the exception.
     */
    private function get_exception_details($exception) {
        $message = $exception->getMessage();

        if (get_class($exception) !== 'S3Exception') {
            return "Not a S3 exception : $message";
        }

        $errorcode = $exception->getAwsErrorCode();

        $details = ' ';

        if ($message) {
            $details .= "ERROR MSG: " . $message . "\n";
        }

        if ($errorcode) {
            $details .= "ERROR CODE: " . $errorcode . "\n";
        }

        return $details;
    }

    /**
     * Check if the plugin has the required configuration set.
     *
     * @return boolean $isset Is all configuration options set.
     */
    private function is_config_set() : bool {
        $isset = true;

        if (empty($this->config->api_key) ||
            empty($this->config->api_secret) ||
            empty($this->config->s3_input_bucket) ||
            empty($this->config->s3_output_bucket) ||
            empty($this->config->api_region)) {
                $isset = false;
        }
            return $isset;
    }

    /**
     * Tests connection to S3 and bucket.
     * There is no check connection in the AWS API.
     * We use list buckets instead and check the bucket is in the list.
     *
     * @param string $bucket Name of buket to check.
     * @return boolean true on success, false on failure.
     */
    public function is_bucket_accessible($bucket) {
        $connection = new \stdClass();
        $connection->success = true;
        $connection->message = '';

        try {
            $result = $this->client->headBucket(array(
                'Bucket' => $bucket));

            $connection->message = get_string('settings:connectionsuccess', 'local_smartmedia');
        } catch (S3Exception $e) {
            $connection->success = false;
            $details = $this->get_exception_details($e);
            $connection->message = get_string('settings:connectionfailure', 'local_smartmedia') . $details;
        }
        return $connection;
    }

    /**
     * Tests connection to S3 and bucket.
     * There is no check connection in the AWS API.
     * We use list buckets instead and check the bucket is in the list.
     *
     * @param string $bucket The bucket to check.
     * @return boolean true on success, false on failure.
     */
    private function have_bucket_permissions($bucket) {
        $permissions = new \stdClass();
        $permissions->success = true;
        $permissions->messages = array();

        try {
            $result = $this->client->putObject(array(
                'Bucket' => $bucket,
                'Key' => 'permissions_check_file',
                'Body' => 'test content'));
        } catch (S3Exception $e) {
            $details = $this->get_exception_details($e);
            $permissions->messages[] = get_string('settings:writefailure', 'local_smartmedia') . $details;
            $permissions->success = false;
        }

        try {
            $result = $this->client->getObject(array(
                'Bucket' => $bucket,
                'Key' => 'permissions_check_file'));
        } catch (S3Exception $e) {
            $errorcode = $e->getAwsErrorCode();
            // Write could have failed.
            if ($errorcode !== 'NoSuchKey') {
                $details = $this->get_exception_details($e);
                $permissions->messages[] = get_string('settings:readfailure', 'local_smartmedia') . $details;
                $permissions->success = false;
            }
        }

        try {
            $result = $this->client->deleteObject(array(
                'Bucket' => $bucket,
                'Key' => 'permissions_check_file'));
            $permissions->messages[] = get_string('settings:deletesuccess', 'local_smartmedia');
        } catch (S3Exception $e) {
            $errorcode = $e->getAwsErrorCode();
            // Something else went wrong.
            if ($errorcode !== 'AccessDenied') {
                $details = $this->get_exception_details($e);
                $permissions->messages[] = get_string('settings:deleteerror', 'local_smartmedia') . $details;
            }
        }

        if ($permissions->success) {
            $permissions->messages[] = get_string('settings:permissioncheckpassed', 'local_smartmedia');
        }
        return $permissions;
    }

    /**
     * Whether the plugin is configured and requirements are met.
     *
     * @return  bool
     */
    public function are_requirements_met() {

        // Check that we can access the input S3 Bucket.
        $connection = $this->is_bucket_accessible($this->config->s3_input_bucket);
        if (!$connection->success) {
            debugging('local_smartmedia: cannot connect to input bucket');
            return false;
        }

        // Check that we can access the output S3 Bucket.
        $connection = $this->is_bucket_accessible($this->config->s3_output_bucket);
        if (!$connection->success) {
            debugging('local_smartmedia: cannot connect to output bucket');
            return false;
        }

        // Check input bucket permissions.
        $bucket = $this->config->s3_input_bucket;
        $permissions = $this->have_bucket_permissions($bucket);
        if (!$permissions->success) {
            debugging('local_smartmedia: permissions failure on input bucket');
            return false;
        }

        // Check output bucket permissions.
        $bucket = $this->config->s3_output_bucket;
        $permissions = $this->have_bucket_permissions($bucket);
        if (!$permissions->success) {
            debugging('local_smartmedia: permissions failure on output bucket');
            return false;
        }

        return true;
    }



}
