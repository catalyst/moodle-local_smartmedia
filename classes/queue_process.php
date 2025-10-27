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

namespace local_smartmedia;

use Aws\MediaConvert\MediaConvertClient;
use core\exception\coding_exception;
use stdClass;

/**
 * Class for AWS SQS processing operations.
 *
 * @package     local_smartmedia
 * @copyright   2019 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class queue_process {
    /**
     *
     * @var object Plugin confiuration.
     */
    private $config;

    /**
     *
     * @var \Aws\Sqs\SqsClient SQS client.
     */
    private $client;

    /**
     * @var \Aws\MediaConvert\MediaConvertClient
     */
    private $mediaconvertclient;

    /**
     * Max messages to get from AWS SQS queue per run..
     *
     * @var integer
     */
    private const MAX_MESSAGES = 100;



    /**
     * Class constructor
     */
    public function __construct() {
        $this->config = get_config('local_smartmedia');
    }

    /**
     * Create AWS SQS API client.
     *
     * @param \GuzzleHttp\Handler $handler Optional handler.
     * @return \Aws\Sqs\SqsClient
     */
    public function create_sqs_client($handler = null) {
        $connectionoptions = [
            'version' => 'latest',
            'region' => $this->config->api_region,
        ];

        $usesdkcreds = get_config('local_smartmedia', 'usesdkcreds');
        if (!$usesdkcreds) {
            $connectionoptions['credentials'] = [
                'key' => $this->config->api_key,
                'secret' => $this->config->api_secret,
            ];
        }

        // We should use the test handler if provided.
        if (isset($handler)) {
            $connectionoptions['handler'] = $handler;
        }

        // Only create client if it hasn't already been done.
        if (!isset($this->client)) {
            $this->client = client_factory::get_client('\Aws\Sqs\SqsClient', $connectionoptions);
        }

        return $this->client;
    }

    /**
     * Create AWS MediaConvert client.
     *
     * @param \GuzzleHttp\Handler $handler Optional handler.
     * @return \Aws\MediaConvert\MediaConvertClient
     */
    public function create_media_convert_client($handler = null) {
        $connectionoptions = [
            'version' => 'latest',
            'region' => $this->config->api_region,
        ];

        $usesdkcreds = get_config('local_smartmedia', 'usesdkcreds');
        if (!$usesdkcreds) {
            $connectionoptions['credentials'] = [
                'key' => $this->config->api_key,
                'secret' => $this->config->api_secret,
            ];
        }

        // We should use the test handler if provided.
        if (isset($handler)) {
            $connectionoptions['handler'] = $handler;
        }

        // Only create client if it hasn't already been done.
        if (!isset($this->mediaconvertclient)) {
            $this->mediaconvertclient = new MediaConvertClient($connectionoptions);
        }

        return $this->mediaconvertclient;
    }

    /**
     * Reads up until self::MAX_MESSAGES messages from queue, returns raw data.
     * @return array
     */
    private function read_up_until_max_messages(): array {
        $messageparams = [
            'AttributeNames' => ['All'],
            'MaxNumberOfMessages' => 10, // 10 is AWS maximum per call.
            'MessageAttributeNames' => ['All'],
            'QueueUrl' => $this->config->sqs_queue_url,
            'VisibilityTimeout' => 60,
            'WaitTimeSeconds' => 10, // To quick and we miss messages, to long and it's slow.
        ];

        $messages = [];
        while (count($messages) < self::MAX_MESSAGES) {
            $result = $this->client->receiveMessage($messageparams);
            $newmessages = $result->get('Messages') ?? []; // Number of received messages varies unpredictably.
            $messages = array_merge($messages, $newmessages);

            // Queue empty, exit early.
            if (empty($newmessages)) {
                break;
            }
        }
        return $messages;
    }

    /**
     * Handle queue message received from SQS
     * @param array $message raw message from SQS queue
     */
    private function handle_message(array $message) {
        // First extract the record that goes into the DB local_smartmedia_queue_msgs.
        $record = $this->extract_record_from_message($message);

        // Only store messages we care about and handle.
        if (!empty($record)) {
            // Then ensure it is stored.
            $this->store_message_record_if_not_already_stored($record);
        }

        // Now successfully stored (or unhandled), delete the SQS message.
        $this->delete_sqs_queue_message($message['ReceiptHandle']);
    }

    /**
     * Delete message from SQS queue
     * @param string $receipthandle SQS receipt handle (identifier for SQS message)
     */
    private function delete_sqs_queue_message(string $receipthandle) {
        $deleteparams = [
            'QueueUrl' => $this->config->sqs_queue_url,
            'ReceiptHandle' => $receipthandle,
        ];
        $this->client->deleteMessage($deleteparams);
    }

    /**
     * Store message record into local_smartmedia_queue_msgs if not already there
     * Deduplication is required as SQS messages can appear multiple times
     * @param object $record
     */
    private function store_message_record_if_not_already_stored(object $record) {
        global $DB;

        if (empty($record->messagehash)) {
            throw new coding_exception("Message must have message hash");
        }

        // Check if this record is already stored (SQS can sometimes emit duplicates).
        $transaction = $DB->start_delegated_transaction();
        $existing = $DB->get_record('local_smartmedia_queue_msgs', ['messagehash' => $record->messagehash]);
        if (empty($existing)) {
            $DB->insert_record('local_smartmedia_queue_msgs', $record);
        }
        $transaction->allow_commit();
    }

    /**
     * Extract the record from the raw sqs message
     * @param array $message raw SQS message
     * @return object|null Object record, otherwise null if unhandled.
     */
    private function extract_record_from_message(array $message): ?object {
        $messagebody = json_decode($message['Body']);

        if (!empty($messagebody->source) && $messagebody->source === 'aws.mediaconvert') {
            return $this->extract_record_from_mediaconvert_message($message);
        }

        // For now, we just ignore these and return null.
        // TODO handle others e.g. rekognition.
        return null;
    }

    /**
     * Extract a local_smartmedia_queue_msgs record from raw SQS mediaconvert message
     * @param array $message raw SQS message
     * @return object record to insert into the database
     */
    private function extract_record_from_mediaconvert_message(array $message): object {
        $messagejson = $message['Body'];
        $messagebody = json_decode($messagejson);

        // Lookup job to get the input (i.e. what file this is for).
        $mcclient = $this->create_media_convert_client();
        $jobdetails = $mcclient->getJob(['Id' => $messagebody->resources[0]]);

        // Explode the s3 objects ARN to get the object key.
        $parts = explode("/", $jobdetails["Job"]["Settings"]["Inputs"][0]["FileInput"]);
        $inputobjectkey = array_pop($parts);

        $record = new stdClass();
        $record->objectkey = $inputobjectkey;
        $record->process = "mediaconvert";
        $record->status = $messagebody->detail->status;
        $record->messagehash = md5($messagejson);
        $record->message = $messagejson;
        $record->senttime = strtotime($messagebody->time);
        $record->timecreated = time();
        return $record;
    }

    /**
     * Process outstanding queue messages.
     *
     * @return int Count of messages processed.
     */
    public function process_queue(): int {
        $this->create_sqs_client();
        $this->create_media_convert_client();

        $messages = $this->read_up_until_max_messages();
        foreach ($messages as $message) {
            $this->handle_message($message);
        }
        return count($messages);
    }
}
