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
 * @copyright   2018 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_smartmedia;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/aws/sdk/aws-autoloader.php');

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Aws\CloudFormation\CloudFormationClient;
use Aws\CloudFormation\Exception\CloudFormationException;
use Aws\Lambda\LambdaClient;
use Aws\Lambda\Exception\LambdaException;

/**
 * Class for provisioning AWS resources.
 *
 * @package     local_smartmedia
 * @copyright   2018 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provision {

    /**
     * AWS API Access Key ID.
     *
     * @var string
     */
    protected $keyid;

    /**
     * AWS API Secret Access Key.
     *
     * @var string
     */
    protected $secret;

    /**
     * The AWS region to create the environment in.
     *
     * @var string
     */
    protected $region;

    /**
     *
     * @var \Aws\S3\S3Client S3 client.
     */
    private $s3client;

    /**
     *
     * @var \Aws\CloudFormation\CloudformationClient Cloudformation client.
     */
    private $cloudformationclient;

    /**
     *
     * @var \Aws\Lambda\LambdaClient Lambda client.
     */
    private $lambdaclient;


    /**
     * The constructor for the class
     *
     * @param string $keyid AWS API Access Key ID.
     * @param string $secret AWS API Secret Access Key.
     * @param string $region The AWS region to create the environment in.
     */
    public function __construct($keyid, $secret, $region) {
        global $CFG;

        $this->keyid = $keyid;
        $this->secret = $secret;
        $this->region = $region;

    }

    /**
     * Check if the bucket already exists in AWS.
     *
     * @param string $bucketname The name of the bucket to check.
     * @return boolean $bucketexists The result of the check.
     */
    private function check_bucket_exists($bucketname) {
        $bucketexists = true;

        try {
            $this->s3client->headBucket(array('Bucket' => $bucketname));
        } catch (S3Exception $e) {
            // Check the error code. If code = 403, this means the bucket
            // exists but we can't access it.  Need to know either way.
            $errorcode = $e->getAwsErrorCode();
            if ($errorcode != 403) {
                $bucketexists = false;
            }

        }
        return $bucketexists;
    }


    /**
     * Create AWS S3 API client.
     *
     * @param \GuzzleHttp\Handler $handler Optional handler.
     * @return \Aws\S3\S3Client
     */
    public function create_s3_client($handler=null) {
        $connectionoptions = array(
                'version' => 'latest',
                'region' => $this->region,
                'credentials' => [
                        'key' => $this->keyid,
                        'secret' => $this->secret
                ]);

        // Allow handler overriding for testing.
        if ($handler != null) {
            $connectionoptions['handler'] = $handler;
        }

        // Only create client if it hasn't already been done.
        if ($this->s3client == null) {
            $this->s3client = new S3Client($connectionoptions);
        }

        return $this->s3client;
    }


    /**
     * Create an S3 Bucket in AWS.
     *
     * @param string $bucketname The name to use for the S3 bucket.
     * @return \stdClass $result The result of the bucket creation.
     */
    private function create_s3_bucket($bucketname) {
        $result = new \stdClass();
        $result->status = true;
        $result->code = 0;
        $result->message = '';
        try {
            $s3result = $this->s3client->createBucket(array(
                    'ACL' => 'private',
                    'Bucket' => $bucketname, // Required.
                    'CreateBucketConfiguration' => array(
                            'LocationConstraint' => $this->region,
                    ),
            ));
            $result->message = $s3result['Location'];
        } catch (S3Exception $e) {
            $result->status = false;
            $result->code = $e->getAwsErrorCode();
            $result->message = $e->getAwsErrorMessage();
        }

        return $result;
    }

    /**
     * Creates a S3 bucket in AWS.
     *
     * @param string $bucketname The name of the bucket to create.
     * @return \stdClass $result The result from the bucket creation.
     */
    public function create_bucket($bucketname) {
        $result = new \stdClass();
        $result->status = true;
        $result->code = 0;
        $result->message = '';
        $result->bucketname = $bucketname;

        // Setup the S3 client.
        $this->create_s3_client();

        // Check bucket exists.
        // If not create it.
        $bucketexists = $this->check_bucket_exists($bucketname);
        if (!$bucketexists) {
            $result = $this->create_s3_bucket($bucketname);
            $result->bucketname = $bucketname;
        } else {
            $result->status = false;
            $result->code = 1;
            $result->message = get_string('provision:bucketexists', 'local_smartmedia');
        }

        return $result;

    }


    /**
     * Put file into S3 bucket.
     *
     * @param string $filepath The path to the local file to Put.
     * @param string $bucketname Te name of the bucket to use.
     * @return \stdClass $result The result of the Put operation.
     */
    private function bucket_put_object($filepath, $bucketname) {
        $result = new \stdClass();
        $result->status = true;
        $result->code = 0;
        $result->message = '';

        $client = $this->s3client;
        $fileinfo = pathinfo($filepath);

        $uploadparams = array(
            'Bucket' => $bucketname, // Required.
            'Key' => $fileinfo['basename'], // Required.
            'SourceFile' => $filepath, // Required.
            'Metadata' => array(
                'description' => 'This is the Libreoffice archive.',
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
     * @param string $bucketname Te name of the bucket to use.
     * @return \stdClass $result The result of the Put operation.
     */
    public function upload_file($filepath, $bucketname) {
        $result = new \stdClass();
        $result->status = true;
        $result->code = 0;
        $result->message = '';

        // Setup the S3 client.
        $this->create_s3_client();

        // Check input bucket exists.
        $bucketexists = $this->check_bucket_exists($bucketname);
        if ($bucketexists) {
            // If we have bucket, upload file.
            $result = $this->bucket_put_object($filepath, $bucketname);
        } else {
            $result->status = false;
            $result->code = 1;
            $result->message = get_string('test:bucketnotexists', 'local_smartmedia', 'input');
        }

        return $result;

    }

    /**
     * Create and AWS Cloudformation API client.
     *
     * @param \GuzzleHttp\Handler $handler Optional handler.
     * @return \Aws\CloudFormation\CloudFormationClient The create Cloudformation client.
     */
    public function create_cloudformation_client($handler=null) {
        $connectionoptions = array(
            'version' => 'latest',
            'region' => $this->region,
            'credentials' => [
                'key' => $this->keyid,
                'secret' => $this->secret
            ]);

        // Allow handler overriding for testing.
        if ($handler != null) {
            $connectionoptions['handler'] = $handler;
        }

        // Only create client if it hasn't already been done.
        if ($this->cloudformationclient == null) {
            $this->cloudformationclient = new CloudFormationClient($connectionoptions);
        }

        return $this->cloudformationclient;
    }

    /**
     * Use cloudformation to create the "stack" in AWS.
     * The stack creation creates the input and output S3 buckets,
     * the required roles and user permisions, and the Lmabda function
     * to convert documents.
     *
     * @param string $stackname The name to give the created stack.
     * @param array $params The params to create the stack with.
     * @return \stdClass $result The result of stack creation.
     */
    public function create_stack($stackname, $params) {
        $result = new \stdClass();
        $result->status = true;
        $result->code = 0;
        $result->message = '';

        // Setup the Cloudformation client.
        $this->create_cloudformation_client();

        // Create stack.
        $template = file_get_contents($params['templatepath']);
        $parameters = array();
        foreach ($params as $key => $value) {
            if ($key == 'templatepath') {
                continue;
            } else {
                $parameters[] = array(
                    'ParameterKey' => $key,
                    'ParameterValue' => $value
                );
            }
        }

        $stackparams = array(
            'Capabilities' => array('CAPABILITY_NAMED_IAM'),
            'OnFailure' => 'DELETE',
            'Parameters' => $parameters,
            'StackName' => $stackname, // Required.
            'TemplateBody' => $template,
            'TimeoutInMinutes' => 6
        );

        $client = $this->cloudformationclient;

        try {
            $createstack = $client->createStack($stackparams);
            $result->message = $createstack['StackId'];

        } catch (CloudFormationException $e) {
            $result->status = false;
            $result->code = $e->getAwsErrorCode();
            $result->message = $e->getAwsErrorMessage();
        }

        if ($result->status == true) {
            $desctibeparams = array(
                'StackName' => $result->message,
            );

            // Stack creation can take several minutes.
            // Periodically check for stack updates.
            $timeout = time() + (60 * 5); // Five minute timeout.
            $exitcodes = array(
                'CREATE_FAILED',
                'CREATE_COMPLETE',
                'DELETE_COMPLETE'
            );
            $stackcreated = false;

            // Check stack creation until exit code received,
            // or we timeout.
            while (time() < $timeout) {
                $stackdetails = $client->describeStacks($desctibeparams);
                $stackdetail = $stackdetails['Stacks'][0];
                $stackstatus = $stackdetail['StackStatus'];

                echo "Stack status: " . $stackstatus . PHP_EOL;

                // Exit under cetain conditions.
                if (in_array($stackstatus, $exitcodes)) {
                    $stackcreated = true;
                    break;
                }

                sleep(30);  // Sleep for a bit before rechecking.
            }

            if ($stackcreated) {
                $outputs = array();
                foreach ($stackdetail['Outputs'] as $output) {
                    $outputs[$output['OutputKey']] = $output['OutputValue'];
                }
                $result->outputs = $outputs;

            } else {
                $result->status = false;
                $result->code = $stackstatus;
                $result->message = 'Stack creation failed';
            }

        }

        return $result;
    }


    /**
     * Create and AWS Lambda API client.
     *
     * @param \GuzzleHttp\Handler $handler Optional handler.
     * @return \Aws\Lambda\LambdaClient The created Lambda client.
     */
    public function create_lambda_client($handler=null) {
        $connectionoptions = array(
            'version' => 'latest',
            'region' => $this->region,
            'credentials' => [
                'key' => $this->keyid,
                'secret' => $this->secret
            ]);

        // Allow handler overriding for testing.
        if ($handler != null) {
            $connectionoptions['handler'] = $handler;
        }

        // Only create client if it hasn't already been done.
        if ($this->lambdaclient == null) {
            $this->lambdaclient = new LambdaClient($connectionoptions);
        }

        return $this->lambdaclient;
    }

    /**
     * Update the environment variables in a Lambda function.
     *
     * @param string $function The ARN of the lambda function.
     * @param array $lambdaenvvars
     */
    public function update_lambda($function, $lambdaenvvars) {
        $result = new \stdClass();
        $result->status = true;
        $result->code = 0;
        $result->message = '';

        // Setup the Lambda client.
        $client = $this->create_lambda_client();

        try {
            $client->updateFunctionConfiguration([
                'Environment' => [
                    'Variables' => $lambdaenvvars,
                ],
                'FunctionName' => $function, // REQUIRED.
            ]);
            $result->message = 'Environment variables updated';
        } catch (LambdaException $e) {
            $result->status = false;
            $result->code = $e->getAwsErrorCode();
            $result->message = $e->getAwsErrorMessage();
        }

        return $result;
    }
}
