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
 * Class for provisioning AWS resources.
 *
 * @package     local_smartmedia
 * @copyright   2019 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_smartmedia;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/aws/sdk/aws-autoloader.php');

use Aws\S3\Exception\S3Exception;

/**
 * Class for provisioning AWS resources.
 *
 * @package     local_smartmedia
 * @copyright   2019 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tester {

    /**
     * The AWS S3 input bucket name.
     *
     * @var string
     */
    protected $inputbucket;

    /**
     * The AWS S3 output bucket name.
     *
     * @var string
     */
    protected $outputbucket;

    /**
     *
     * @var \Aws\S3\S3Client S3 client.
     */
    private $s3client;

    /**
     * The constructor for the class
     *
     * @param string $keyid AWS API Access Key ID.
     * @param string $secret AWS API Secret Access Key.
     * @param string $region The AWS region to create the environment in.
     * @param string $inputbucket The AWS S3 input bucket name.
     * @param string $outputbucket The AWS S3 output bucket name.
     */
    public function __construct($keyid, $secret, $region, $inputbucket, $outputbucket) {
        $config = new \stdClass();
        $config->api_region = $region;
        $config->api_key = $keyid;
        $config->api_secret = $secret;
        $config->s3_input_bucket = $inputbucket;
        $config->s3_output_bucket = $outputbucket;

        $this->awss3  = new \local_smartmedia\aws_s3($config);
        $this->s3client = $this->awss3->create_client();

        $this->inputbucket = $inputbucket;
        $this->outputbucket = $outputbucket;

    }

    /**
     * Put file into S3 bucket
     *
     * @param string $filepath The filepath to the local file.
     * @param string $bucketname The name of the S3 bucket to put the object.
     * @return \stdClass $result The result from the operation.
     */
    private function bucket_put_object($filepath, $bucketname) {
        global $CFG;

        $result = new \stdClass();
        $result->status = true;
        $result->code = 0;
        $result->message = 'File error';

        $client = $this->s3client;
        $fileinfo = pathinfo($filepath);

        $uploadparams = array(
            'Bucket' => $bucketname,
            'Key' => $fileinfo['filename'], // Required.
            'SourceFile' => $filepath, // Required.
            'Metadata' => array(
                'siteid' => $CFG->siteidentifier,
                'processes' => '11111111',
                'presets' => '1351620000001-100070,1351620000001-100240,1351620000001-300020'
            )
        );

        try {
            $putobject = $client->putObject($uploadparams);
            $result->message = $putobject['ObjectURL'];

        } catch (S3Exception $e) {
            $result->status = false;
            $result->code = $e->getAwsErrorCode();
            $result->message = $e->getAwsErrorMessage();
        }

        return $result;
    }

    /**
     * Uploads a file to the S3 input bucket.
     *
     * @param string $filepath The path to the file to upload.
     *
     */
    public function upload_file($filepath) {
        $result = new \stdClass();
        $result->status = true;
        $result->code = 0;
        $result->message = '';

        $bucketname = $this->inputbucket;

        // Check input bucket exists.
        $bucketexists = $this->awss3->is_bucket_accessible($bucketname);
        if ($bucketexists) {
            // If we have bucket, upload file.
            $result = $this->bucket_put_object($filepath, $bucketname);
        } else {
            $result->status = false;
            $result->code = 1;
            $result->message = get_string('test:bucketnotexists', 'local_smartmedia', $bucketname);
        }

        return $result;

    }

}
