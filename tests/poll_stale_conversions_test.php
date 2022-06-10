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
 * Unit test for local_smartmedia conversion class.
 *
 * @package    local_smartmedia
 * @copyright  2019 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/aws/sdk/aws-autoloader.php');

use local_smartmedia\conversion;
use local_smartmedia\aws_api;
use local_smartmedia\aws_elastic_transcoder;
use local_smartmedia\task\poll_stale_conversions;
use Aws\CommandInterface;
use Aws\Result;
use Aws\MockHandler;
use Aws\S3\Exception\S3Exception;
use Psr\Http\Message\RequestInterface;

/**
 * Unit test for local_smartmedia conversion class.
 *
 * @package     local_smartmedia
 * @author      Peter Burnett <peterburnett@catalyst-au.net>
 * @copyright   2022 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      local_smartmedia
 */
class local_smartmedia_poll_stale_conversions_testcase extends advanced_testcase {

    public function test_get_stale_conversions() {
        global $DB;
        $this->resetAfterTest(true);

        $baserecord = [
            'pathnamehash'              => sha1('path'),
            'contenthash'               => sha1('content'),
            'status'                    => conversion::CONVERSION_IN_PROGRESS,
            'transcoder_status'         => conversion::CONVERSION_ACCEPTED,
            'transcribe_status'         => conversion::CONVERSION_NOT_FOUND,
            'rekog_label_status'        => conversion::CONVERSION_NOT_FOUND,
            'rekog_moderation_status'   => conversion::CONVERSION_NOT_FOUND,
            'rekog_face_status'         => conversion::CONVERSION_NOT_FOUND,
            'rekog_person_status'       => conversion::CONVERSION_NOT_FOUND,
            'detect_sentiment_status'   => conversion::CONVERSION_NOT_FOUND,
            'detect_phrases_status'     => conversion::CONVERSION_NOT_FOUND,
            'detect_entities_status'    => conversion::CONVERSION_NOT_FOUND,
            'timecreated'               => time(),
            'timemodified'              => time(),
            'timecompleted'             => null
        ];

        // Setup a valid poll candidate. Old and in progress.
        $selected = $baserecord;
        $selected['timecreated'] = time() - 8 * DAYSECS;
        $selected['pathnamehash'] = 'selected';
        $selected['contenthash'] = 'selected';
        $DB->insert_record('local_smartmedia_conv', $selected);

        // Now a record that is too early. Nonvalid
        $nonselected1 = $baserecord;
        $nonselected1['timecreated'] = time() - 6 * DAYSECS;
        $selected['pathnamehash'] = 'nonselected1';
        $nonselected1['contenthash'] = 'nonselected1';
        $DB->insert_record('local_smartmedia_conv', $nonselected1);

        // A record that is already finished. Nonvalid
        $nonselected2 = $baserecord;
        $nonselected2['timecreated'] = time() - 8 * DAYSECS;
        $nonselected2['pathnamehash'] = 'nonselected2';
        $nonselected2['contenthash'] = 'nonselected2';
        $nonselected2['status'] = conversion::CONVERSION_FINISHED;
        $nonselected2['transcoder_status'] = conversion::CONVERSION_FINISHED;
        $DB->insert_record('local_smartmedia_conv', $nonselected2);

        // Now get records, and confirm only the valid record is selected.
        $task = new poll_stale_conversions();
        $method = new \ReflectionMethod($task, 'get_stale_conversions');
        $method->setAccessible(true);
        $results = $method->invoke($task);

        $this->assertCount(1, $results);
        $record = reset($results);
        $this->assertEquals('selected', $record->contenthash);
    }



    public function test_poll_conversion_status() {
        global $CFG, $DB;
        $this->resetAfterTest(true);

        set_config('api_region', 'ap-southeast-2', 'local_smartmedia');
        set_config('api_key', 'somefakekey', 'local_smartmedia');
        set_config('api_secret', 'somefakesecret', 'local_smartmedia');
        set_config('s3_input_bucket', 'inputbucket', 'local_smartmedia');
        set_config('s3_output_bucket', 'outputbucket', 'local_smartmedia');
        set_config('audio_output', 0, 'local_smartmedia');
        set_config('download_files', 0, 'local_smartmedia');
        set_config('detectlabels', 1, 'local_smartmedia');
        set_config('detectmoderation', 1, 'local_smartmedia');
        set_config('detectfaces', 1, 'local_smartmedia');
        set_config('detectpeople', 1, 'local_smartmedia');
        set_config('detectsentiment', 1, 'local_smartmedia');
        set_config('detectphrases', 1, 'local_smartmedia');
        set_config('detectentities', 1, 'local_smartmedia');
        set_config('transcribe', 1, 'local_smartmedia');

        // Load in fixtures for AWS responses.
        $fixture = require($CFG->dirroot . '/local/smartmedia/tests/fixtures/poll_conversions_test_fixture.php');

        $mockhandler = new MockHandler();
        $mockgetobject = new Result($fixture['getObject']);
        $mockgetlist = new Result($fixture['listObjects']);
        $mockgetempty = new Result($fixture['listObjectsEmpty']);
        $mockdelete = new Result($fixture['deleteObject']);

        // Now get records, and confirm only the valid record is selected.
        $task = new poll_stale_conversions();
        $method = new \ReflectionMethod($task, 'poll_conversion_status');
        $method->setAccessible(true);
        $api = new aws_api();
        $transcoder = new aws_elastic_transcoder($api->create_elastic_transcoder_client());
        $conversion = new \local_smartmedia\conversion($transcoder);

        $baserecord = [
            'pathnamehash'              => sha1('path'),
            'contenthash'               => sha1('content'),
            'status'                    => conversion::CONVERSION_IN_PROGRESS,
            'transcoder_status'         => conversion::CONVERSION_ACCEPTED,
            'transcribe_status'         => conversion::CONVERSION_NOT_FOUND,
            'rekog_label_status'        => conversion::CONVERSION_NOT_FOUND,
            'rekog_moderation_status'   => conversion::CONVERSION_NOT_FOUND,
            'rekog_face_status'         => conversion::CONVERSION_NOT_FOUND,
            'rekog_person_status'       => conversion::CONVERSION_NOT_FOUND,
            'detect_sentiment_status'   => conversion::CONVERSION_NOT_FOUND,
            'detect_phrases_status'     => conversion::CONVERSION_NOT_FOUND,
            'detect_entities_status'    => conversion::CONVERSION_NOT_FOUND,
            'timecreated'               => time(),
            'timemodified'              => time(),
            'timecompleted'             => null
        ];

        // First we want to test a record with no files found for transcoder or status.
        $nofiles = $baserecord;
        $nofiles['pathnamehash'] = 'nofiles';
        $nofiles['contenthash'] = 'nofiles';
        $nofiles['transcribe_status'] = conversion::CONVERSION_IN_PROGRESS;
        $DB->insert_record('local_smartmedia_conv', $nofiles);
        // Append an empty list for all files, and a s3 exception for data file getting, and an exception for deleting.
        $mockhandler->append($mockgetempty);
        $mockhandler->append(function (CommandInterface $cmd, RequestInterface $req) {
            return new S3Exception('Mock exception', $cmd, array('code' => 'FAIL'));
        });
        $mockhandler->append(function (CommandInterface $cmd, RequestInterface $req) {
            return new S3Exception('Mock exception', $cmd, array('code' => 'FAIL'));
        });
        $mockhandler->append($mockgetempty);

        // Pull the record from DB in format required.
        $record = $DB->get_record('local_smartmedia_conv', ['contenthash' => 'nofiles']);
        $this->expectOutputString("Finished polling stale conversion nofiles\n");
        $method->invoke($task, $record, $conversion, $mockhandler);
        $this->assertDebuggingCalledCount(2);

        // Check all is finished as errored, except ones not started.
        $updatedrecord = $record = $DB->get_record('local_smartmedia_conv', ['contenthash' => 'nofiles']);
        $this->assertEquals(conversion::CONVERSION_FINISHED, $updatedrecord->status);
        $this->assertEquals(conversion::CONVERSION_ERROR, $updatedrecord->transcoder_status);
        $this->assertEquals(conversion::CONVERSION_ERROR, $updatedrecord->transcribe_status);
        $this->assertEquals(conversion::CONVERSION_NOT_FOUND, $updatedrecord->detect_sentiment_status);
    }

}
