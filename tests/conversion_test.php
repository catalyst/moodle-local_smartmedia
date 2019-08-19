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

use Aws\Result;
use Aws\MockHandler;
use Aws\CommandInterface;
use Psr\Http\Message\RequestInterface;
use Aws\S3\Exception\S3Exception;

/**
 * Unit test for local_smartmedia conversion class.
 *
 * @package    local_smartmedia
 * @copyright  2019 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      local_smartmedia
 */
class local_smartmedia_conversion_testcase extends advanced_testcase {

    /**
     * Test getting smart media.
     */
    public function test_get_smart_media() {
        $this->resetAfterTest(true);

        // Setup for testing.
        $fs = new file_storage();
        $filerecord = array(
            'contextid' => 31,
            'component' => 'mod_forum',
            'filearea' => 'attachment',
            'itemid' => 2,
            'filepath' => '/',
            'filename' => 'myfile1.txt');

        $file = $fs->create_file_from_string($filerecord, 'the first test file');
        $filepathnamehash = $file->get_pathnamehash();
        $href = moodle_url::make_pluginfile_url(
            $filerecord['contextid'], $filerecord['component'], $filerecord['filearea'],
            $filerecord['itemid'], $filerecord['filepath'], $filerecord['filename']);

        $conversion = new \local_smartmedia\conversion();

        // TODO: fix this test and add an assertion.
    }

    /**
     * Test argument extraction from various plugin types.
     */
    public function test_get_file_from_url() {
        $this->resetAfterTest(true);

        // Setup the files for testing.
        $fs = new file_storage();
        $filerecord1 = array(
            'contextid' => 31,
            'component' => 'mod_forum',
            'filearea' => 'attachment',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'myfile1.txt');

        $file1 = $fs->create_file_from_string($filerecord1, 'the first test file');
        $filepathnamehash1 = $file1->get_pathnamehash();
        $href1 = moodle_url::make_pluginfile_url(
            $filerecord1['contextid'], $filerecord1['component'], $filerecord1['filearea'],
            null, $filerecord1['filepath'], $filerecord1['filename']);

        $filerecord2 = array(
            'contextid' => 1386,
            'component' => 'mod_folder',
            'filearea' => 'content',
            'itemid' => 2,
            'filepath' => '/',
            'filename' => 'myfile2.txt');

        $file2 = $fs->create_file_from_string($filerecord2, 'the second test file');
        $filepathnamehash2 = $file2->get_pathnamehash();
        $href2 = moodle_url::make_pluginfile_url(
            $filerecord2['contextid'], $filerecord2['component'], $filerecord2['filearea'],
            $filerecord2['itemid'], $filerecord2['filepath'], $filerecord2['filename']);

        $filerecord3 = array(
            'contextid' => 1386,
            'component' => 'mod_folder',
            'filearea' => 'content',
            'itemid' => 45,
            'filepath' => '/a/b/c/',
            'filename' => 'myfile3.txt');

        $file3 = $fs->create_file_from_string($filerecord3, 'the third test file');
        $filepathnamehash3 = $file3->get_pathnamehash();
        $href3 = moodle_url::make_pluginfile_url(
            $filerecord3['contextid'], $filerecord3['component'], $filerecord3['filearea'],
            $filerecord3['itemid'], $filerecord3['filepath'], $filerecord3['filename']);

        // Instansiate new conversion class.
        $conversion = new \local_smartmedia\conversion();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_smartmedia\conversion', 'get_file_from_url');
        $method->setAccessible(true); // Allow accessing of private method.

        $result1 = $method->invoke($conversion, $href1); // Get result of invoked method.
        $result2 = $method->invoke($conversion, $href2); // Get result of invoked method.
        $result3 = $method->invoke($conversion, $href3); // Get result of invoked method.

        $this->assertEquals($filepathnamehash1, $result1->get_pathnamehash());
        $this->assertEquals($filepathnamehash2, $result2->get_pathnamehash());
        $this->assertEquals($filepathnamehash3, $result3->get_pathnamehash());

    }

    /**
     * Test method that gets conversion status when there is no existing
     * conversion record in the database.
     */
    public function test_get_conversion_status_no_record() {
        $this->resetAfterTest(true);
        $conversion = new \local_smartmedia\conversion();

        // Setup for testing.
        $fs = new file_storage();
        $filerecord = array(
            'contextid' => 31,
            'component' => 'mod_forum',
            'filearea' => 'attachment',
            'itemid' => 2,
            'filepath' => '/',
            'filename' => 'myfile1.txt');

        $file = $fs->create_file_from_string($filerecord, 'the first test file');

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_smartmedia\conversion', 'get_conversion_status');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($conversion, $file);

        $this->assertEquals(404, $result);

    }

    /**
     * Test that initial conversion records are successfully created.
     */
    public function test_create_conversion() {
        $this->resetAfterTest(true);
        global $DB;

        $presets = "preset1 \n preset2 \n preset3";
        set_config('transcodepresets', $presets, 'local_smartmedia');

        $conversion = new \local_smartmedia\conversion();

        // Setup for testing.
        $fs = new file_storage();
        $filerecord = array(
            'contextid' => 31,
            'component' => 'mod_forum',
            'filearea' => 'attachment',
            'itemid' => 2,
            'filepath' => '/',
            'filename' => 'myfile1.txt');

        $file1 = $fs->create_file_from_string($filerecord, 'the first test file');

        $filerecord['itemid'] = 3;
        $file2 = $fs->create_file_from_string($filerecord, 'the first test file');

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_smartmedia\conversion', 'create_conversion');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($conversion, $file1);
        $result = $method->invoke($conversion, $file1);  // Invoke twice to check error handling.
        $result = $method->invoke($conversion, $file2);  // Invoke again to check error handling.

        $result = $DB->record_exists('local_smartmedia_conv', array('pathnamehash' => $file1->get_pathnamehash()));
        $this->assertTrue($result);

        $result = $DB->count_records('local_smartmedia_presets');
        $this->assertEquals(3, $result);

    }

    /**
     * Test getting preset array from settings.
     */
    public function test_get_preset_ids() {
        $this->resetAfterTest(true);

        $presets = "preset1 \n preset2 \n preset3";
        set_config('transcodepresets', $presets, 'local_smartmedia');

        $conversion = new \local_smartmedia\conversion();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_smartmedia\conversion', 'get_preset_ids');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($conversion);

        $this->assertCount(3, $result);
        $this->assertEquals('preset2', $result[1]);
    }

    /**
     * Test getting preset records.
     */
    public function test_get_preset_records() {
        $this->resetAfterTest(true);

        $presets = "preset1 \n preset2 \n preset3";
        set_config('transcodepresets', $presets, 'local_smartmedia');

        $conversion = new \local_smartmedia\conversion();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_smartmedia\conversion', 'get_preset_records');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($conversion, 123);

        $this->assertCount(3, $result);
        $this->assertEquals('preset2', $result[1]->preset);
    }

    /**
     * Test that initial conversion records are successfully created.
     */
    public function test_get_conversion_records() {
        $this->resetAfterTest(true);
        global $DB;

        $presets = "preset1 \n preset2 \n preset3";
        set_config('transcodepresets', $presets, 'local_smartmedia');

        $conversion = new \local_smartmedia\conversion();

        // Setup for testing.
        $fs = new file_storage();
        $filerecord = array(
            'contextid' => 31,
            'component' => 'mod_forum',
            'filearea' => 'attachment',
            'itemid' => 2,
            'filepath' => '/',
            'filename' => 'myfile1.txt');

        $file = $fs->create_file_from_string($filerecord, 'the first test file');

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_smartmedia\conversion', 'create_conversion');
        $method->setAccessible(true); // Allow accessing of private method.
        $method->invoke($conversion, $file);

        $method = new ReflectionMethod('\local_smartmedia\conversion', 'get_conversion_records');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($conversion, $conversion::CONVERSION_ACCEPTED);

        $this->assertCount(1, $result);

        $record = reset($result);
        $this->assertEquals($file->get_contenthash(), $record->contenthash);
    }


    /**
     * Test that initial conversion records are successfully created.
     */
    public function test_get_convserion_settings() {
        $this->resetAfterTest(true);
        global $DB;

        $conversionrecord = new \stdClass();
        $conversionrecord->id = 508000;
        $conversionrecord->pathnamehash = '4a1bba15ebb79e7813e642790a551bfaaf6c6066';
        $conversionrecord->contenthash = '8d6985bd0d2abb09a444eb7066efc43678465fc0';
        $conversionrecord->status = 202;
        $conversionrecord->transcribe = 1;
        $conversionrecord->rekog_label = 0;
        $conversionrecord->rekog_moderation = 1;
        $conversionrecord->rekog_face = 0;
        $conversionrecord->rekog_person = 1;
        $conversionrecord->detect_sentiment = 0;
        $conversionrecord->detect_phrases = 1;
        $conversionrecord->detect_entities = 0;

        $preset1 = new \stdClass();
        $preset1->convid = 508000;
        $preset1->preset = 'preset1';

        $preset2 = new \stdClass();
        $preset2->convid = 508000;
        $preset2->preset = 'preset2';

        $DB->insert_record('local_smartmedia_presets', $preset1);
        $DB->insert_record('local_smartmedia_presets', $preset2);

        $conversion = new \local_smartmedia\conversion();
        $method = new ReflectionMethod('\local_smartmedia\conversion', 'get_convserion_settings');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($conversion, $conversionrecord);

        $this->assertEquals('10101010', $result['processes']);
        $this->assertEquals('preset2,preset1', $result['presets']);

    }

    /**
     * Test sending file to aws for conversion.
     */
    public function test_send_file_for_processing() {
        $this->resetAfterTest(true);
        global $CFG;

        $conversion = new \local_smartmedia\conversion();

        // Setup for testing.
        $fs = new file_storage();
        $filerecord = array(
            'contextid' => 31,
            'component' => 'mod_forum',
            'filearea' => 'attachment',
            'itemid' => 2,
            'filepath' => '/',
            'filename' => 'SampleVideo1mb.mp4');
        $fileurl = $CFG->dirroot . '/local/smartmedia/tests/fixtures/SampleVideo1mb.mp4';
        $file = $fs->create_file_from_pathname($filerecord, $fileurl);

        $settings = array(
            'processes' => '10101010',
            'presets' => 'preset1,preset2'
        );

        // Set up the AWS mock.
        $mockhandler = new MockHandler();
        $mockhandler->append(new Result(array('ObjectURL' => 's3://herpderp')));
        $mockhandler->append(function (CommandInterface $cmd, RequestInterface $req) {
            return new S3Exception('Mock exception', $cmd, array('code' => 'FAIL'));
        });

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_smartmedia\conversion', 'send_file_for_processing');
        $method->setAccessible(true); // Allow accessing of private method.
        $resultgood = $method->invoke($conversion, $file, $settings, $mockhandler);
        $resultbad = $method->invoke($conversion, $file, $settings, $mockhandler);

        $this->assertEquals($conversion::CONVERSION_IN_PROGRESS, $resultgood);
        $this->assertEquals($conversion::CONVERSION_ERROR, $resultbad);
    }

    /**
     * Test that initial conversion records are successfully created.
     */
    public function test_update_conversion_records() {
        $this->resetAfterTest(true);
        global $DB;

        $conversionrecord = new \stdClass();
        $conversionrecord->pathnamehash = '4a1bba15ebb79e7813e642790a551bfaaf6c6066';
        $conversionrecord->contenthash = '8d6985bd0d2abb09a444eb7066efc43678465fc0';
        $conversionrecord->status = 202;
        $conversionrecord->transcribe = 1;
        $conversionrecord->rekog_label = 0;
        $conversionrecord->rekog_moderation = 1;
        $conversionrecord->rekog_face = 0;
        $conversionrecord->rekog_person = 1;
        $conversionrecord->detect_sentiment = 0;
        $conversionrecord->detect_phrases = 1;
        $conversionrecord->detect_entities = 0;
        $conversionrecord->timecreated = time();
        $conversionrecord->timemodified = time();

        $recordid = $DB->insert_record('local_smartmedia_conv', $conversionrecord);
        $conversion = new \local_smartmedia\conversion();

        $updates = array();
        $updates[$recordid] = $conversion::CONVERSION_IN_PROGRESS;

        $method = new ReflectionMethod('\local_smartmedia\conversion', 'update_conversion_records');
        $method->setAccessible(true); // Allow accessing of private method.
        $method->invoke($conversion, $updates);

        $result = $DB->get_field('local_smartmedia_conv', 'status', array('id' => $recordid));

        $this->assertEquals($conversion::CONVERSION_IN_PROGRESS, $result);

    }

    /**
     * Test getting queue messages.
     */
    public function test_get_queue_messages() {
        $this->resetAfterTest(true);
        global $DB;

        $conversionrecord = new \stdClass();
        $conversionrecord->id = 508000;
        $conversionrecord->pathnamehash = '4a1bba15ebb79e7813e642790a551bfaaf6c6066';
        $conversionrecord->contenthash = '8d6985bd0d2abb09a444eb7066efc43678465fc0';
        $conversionrecord->status = 202;
        $conversionrecord->transcribe = 1;
        $conversionrecord->rekog_label = 0;
        $conversionrecord->rekog_moderation = 1;
        $conversionrecord->rekog_face = 0;
        $conversionrecord->rekog_person = 1;
        $conversionrecord->detect_sentiment = 0;
        $conversionrecord->detect_phrases = 1;
        $conversionrecord->detect_entities = 0;
        $conversionrecord->timecreated = time();
        $conversionrecord->timemodified = time();

        $messagerecord1 = new \stdClass();
        $messagerecord1->objectkey = '8d6985bd0d2abb09a444eb7066efc43678465fc0';
        $messagerecord1->process = 'StartContentModeration';
        $messagerecord1->status = 'SUCCEEDED';
        $messagerecord1->message = '{}';
        $messagerecord1->senttime = '1566091817';
        $messagerecord1->timecreated = '1566197550';

        $messagerecord2 = new \stdClass();
        $messagerecord2->objectkey = '8d6985bd0d2abb09a444eb7066efc43678465fc0';
        $messagerecord2->process = 'elastic_transcoder';
        $messagerecord2->status = 'COMPLETED';
        $messagerecord2->message = '{}';
        $messagerecord2->senttime = '1566091817';
        $messagerecord2->timecreated = '1566197550';

        $messagerecord3 = new \stdClass();
        $messagerecord3->objectkey = '8d6985bd0d2abb09a444eb7066efc43678465fc0';
        $messagerecord3->process = 'elastic_transcoder';
        $messagerecord3->status = 'PROGRESSING';
        $messagerecord3->message = '{}';
        $messagerecord3->senttime = '1566091817';
        $messagerecord3->timecreated = '1566197550';

        $DB->insert_record('local_smartmedia_queue_msgs', $messagerecord1);
        $DB->insert_record('local_smartmedia_queue_msgs', $messagerecord2);
        $DB->insert_record('local_smartmedia_queue_msgs', $messagerecord3);

        $conversion = new \local_smartmedia\conversion();
        $method = new ReflectionMethod('\local_smartmedia\conversion', 'get_queue_messages');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($conversion, $conversionrecord);

        error_log(print_r($result, true));
    }

}
