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
     * @var array Fixtures used in this test.
     */
    public $fixture;

    /*
     * Set up method for this test suite.
     */
    public function setUp() {
        global $CFG;
        set_config('api_region', 'ap-southeast-2', 'local_smartmedia');
        set_config('api_key', 'somefakekey', 'local_smartmedia');
        set_config('api_secret', 'somefakesecret', 'local_smartmedia');
        set_config('s3_input_bucket', 'inputbucket', 'local_smartmedia');
        set_config('s3_output_bucket', 'outputbucket', 'local_smartmedia');
        set_config('detectlabels', 1, 'local_smartmedia');
        set_config('detectmoderation', 1, 'local_smartmedia');
        set_config('detectfaces', 1, 'local_smartmedia');
        set_config('detectpeople', 1, 'local_smartmedia');
        set_config('detectsentiment', 1, 'local_smartmedia');
        set_config('detectphrases', 1, 'local_smartmedia');
        set_config('detectentities', 1, 'local_smartmedia');
        set_config('transcribe', 1, 'local_smartmedia');

        // Get fixture for tests.
        $this->fixture = require($CFG->dirroot . '/local/smartmedia/tests/fixtures/conversion_test_fixture.php');
    }

    /**
     * Test getting smart media.
     */
    public function test_get_smart_media() {
        global $DB, $CFG;

        $this->resetAfterTest(true);

        // Setup for testing.
        $fs = new file_storage();

        // Converted media has the preset id in filename to make each filename unique across conversions.
        $presetid = '1351620000001-100180';

        // Mock the initial file record from which conversions were made.
        $initialfilerecord = array (
            'contextid' => 31,
            'component' => 'mod_forum',
            'filearea' => 'attachment',
            'itemid' => 2,
            'filepath' => '/',
            'filename' => 'myfile1.mp4');
        $initialfile = $fs->create_file_from_string($initialfilerecord, 'the first test file');
        $contenthash = $initialfile->get_contenthash();

        // Mock a transcode file received from s3.
        $convertedmediarecord = array(
            'contextid' => 1,
            'component' => 'local_smartmedia',
            'filearea' => 'media',
            'itemid' => 0,
            'filepath' => '/' . $initialfile->get_contenthash() . '/conversions/',
            'filename' => $presetid . '-myfile1.mp4');
        $convertedmediafile = $fs->create_file_from_string($convertedmediarecord, 'the first test file');

        // Mock a metadata file received from s3.
        $converteddatarecord = array(
            'contextid' => 1,
            'component' => 'local_smartmedia',
            'filearea' => 'metadata',
            'itemid' => 0,
            'filepath' => '/' . $initialfile->get_contenthash() . '/metadata/',
            'filename' => 'Labels.json');
        $converteddatafile = $fs->create_file_from_string($converteddatarecord, 'label data');

        // Add a successful conversion status for this file.
        $conversionrecord = new \stdClass();
        $conversionrecord->pathnamehash = $contenthash;
        $conversionrecord->contenthash = $contenthash;
        $conversionrecord->status = 201;
        $conversionrecord->transcribe_status = 201;
        $conversionrecord->rekog_label_status = 201;
        $conversionrecord->rekog_moderation_status = 201;
        $conversionrecord->rekog_face_status = 201;
        $conversionrecord->rekog_person_status = 201;
        $conversionrecord->detect_sentiment_status = 201;
        $conversionrecord->detect_phrases_status = 201;
        $conversionrecord->detect_entities_status = 201;
        $conversionrecord->timecreated = time();
        $conversionrecord->timemodified = time();

        $DB->insert_record('local_smartmedia_conv', $conversionrecord);

        $href = moodle_url::make_pluginfile_url(
            $initialfilerecord['contextid'], $initialfilerecord['component'], $initialfilerecord['filearea'],
            $initialfilerecord['itemid'], $initialfilerecord['filepath'], $initialfilerecord['filename']);

        $conversion = new \local_smartmedia\conversion();

        $smartmedia = $conversion->get_smart_media($href);

        // Check that the smart media urls match the passed in mock data and `id` parameter matches initial moodle file id.
        $expectedmediaurl = "$CFG->wwwroot/pluginfile.php/1/local_smartmedia/media/" . $initialfile->get_id()
            . "/$contenthash/conversions/$presetid-myfile1.mp4";
        $mediaurl = reset($smartmedia['media']);

        $expecteddataurl = "$CFG->wwwroot/pluginfile.php/1/local_smartmedia/metadata/" . $initialfile->get_id()
            . "/$contenthash/metadata/Labels.json";
        $dataurl = reset($smartmedia['data']);

        $this->assertEquals($expectedmediaurl, $mediaurl->out());
        $this->assertEquals($expecteddataurl, $dataurl->out());
    }

    /**
     * Test filtering stored files by a specific filepath.
     */
    public function test_filter_files_by_filepath() {

        $this->resetAfterTest(true);

        // Setup for testing.
        $fs = new file_storage();

        $contenthash = '7ddf32e17a6ac5ce04a8ecbf782ca509';

        // Mock a transcode file.
        $convertedmediarecord = array(
            'contextid' => 1,
            'component' => 'local_smartmedia',
            'filearea' => 'media',
            'itemid' => 0,
            'filepath' => '/' . $contenthash . '/conversions/',
            'filename' => '1351620000001-100180-myfile1.mp4');
        $convertedmediafile = $fs->create_file_from_string($convertedmediarecord, 'the first test file');

        // Mock a metadata file.
        $converteddatarecord = array(
            'contextid' => 1,
            'component' => 'local_smartmedia',
            'filearea' => 'metadata',
            'itemid' => 0,
            'filepath' => '/' . $contenthash . '/metadata/',
            'filename' => 'Labels.json');
        $converteddatafile = $fs->create_file_from_string($converteddatarecord, 'Label data');

        $files = [$convertedmediafile, $converteddatafile];

        $conversion = new \local_smartmedia\conversion();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_smartmedia\conversion', 'filter_files_by_filepath');
        $method->setAccessible(true); // Allow accessing of private method.

        $result1 = $method->invoke($conversion, $files, '/' . $contenthash . '/conversions/');
        $result2 = $method->invoke($conversion, $files, '/' . $contenthash . '/metadata/');

        $this->assertContains($convertedmediafile, $result1);
        $this->assertContains($converteddatafile, $result2);
    }

    /**
     * Test mapping passed in \stored_file objects to \moodle_url objects.
     */
    public function test_map_files_to_urls() {
        global $CFG;

        $this->resetAfterTest(true);

        // Setup for testing.
        $fs = new file_storage();

        $contenthash = '7ddf32e17a6ac5ce04a8ecbf782ca509';

        // Mock the parent moodle file id for conversions, as we don't actually have one in this test.
        $parentfileid = 154600;

        // Mock a transcode file.
        $convertedmediarecord = array(
            'contextid' => 1,
            'component' => 'local_smartmedia',
            'filearea' => 'media',
            'itemid' => 0,
            'filepath' => '/' . $contenthash . '/conversions/',
            'filename' => '1351620000001-100180-myfile1.mp4');
        $convertedmediafile = $fs->create_file_from_string($convertedmediarecord, 'the first test file');

        // Mock a metadata file.
        $converteddatarecord = array(
            'contextid' => 1,
            'component' => 'local_smartmedia',
            'filearea' => 'metadata',
            'itemid' => 0,
            'filepath' => '/' . $contenthash . '/metadata/',
            'filename' => 'Labels.json');
        $converteddatafile = $fs->create_file_from_string($converteddatarecord, 'Label data');

        $files = [$convertedmediafile, $converteddatafile];

        $conversion = new \local_smartmedia\conversion();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_smartmedia\conversion', 'map_files_to_urls');
        $method->setAccessible(true); // Allow accessing of private method.

        $actual = $method->invoke($conversion, $files, $parentfileid);

        // Check that the returned urls match the passed in mock data.
        $expectedmediaurl = "$CFG->wwwroot/pluginfile.php/1/local_smartmedia/media/$parentfileid/$contenthash/conversions"
            . "/1351620000001-100180-myfile1.mp4";

        $expecteddataurl = "$CFG->wwwroot/pluginfile.php/1/local_smartmedia/metadata/$parentfileid/$contenthash/metadata"
            . "/Labels.json";

        $this->assertEquals($expectedmediaurl, $actual[0]->out());
        $this->assertEquals($expecteddataurl, $actual[1]->out());
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
    public function test_get_conversion_statuses_no_record() {
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
        $method = new ReflectionMethod('\local_smartmedia\conversion', 'get_conversion_statuses');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($conversion, $file);

        $this->assertEquals(404, $result->status);
    }

    /**
     * Test method that gets conversion status when there is an existing
     * conversion record in the database.
     */
    public function test_get_conversion_statuses() {
        $this->resetAfterTest(true);
        global $DB;

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

        $conversionrecord = new \stdClass();
        $conversionrecord->pathnamehash = $file->get_pathnamehash();
        $conversionrecord->contenthash = $file->get_contenthash();
        $conversionrecord->status = 202;
        $conversionrecord->transcribe_status = 202;
        $conversionrecord->rekog_label_status = 404;
        $conversionrecord->rekog_moderation_status = 202;
        $conversionrecord->rekog_face_status = 404;
        $conversionrecord->rekog_person_status = 202;
        $conversionrecord->detect_sentiment_status = 404;
        $conversionrecord->detect_phrases_status = 202;
        $conversionrecord->detect_entities_status = 404;
        $conversionrecord->timecreated = time();
        $conversionrecord->timemodified = time();

        $DB->insert_record('local_smartmedia_conv', $conversionrecord);

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_smartmedia\conversion', 'get_conversion_statuses');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($conversion, $file);

        $this->assertEquals($conversionrecord->status, $result->status);
        $this->assertEquals($conversionrecord->transcribe_status, $result->transcribe_status);
        $this->assertEquals($conversionrecord->rekog_label_status, $result->rekog_label_status);
        $this->assertEquals($conversionrecord->rekog_moderation_status, $result->rekog_moderation_status);
        $this->assertEquals($conversionrecord->rekog_face_status, $result->rekog_face_status);
        $this->assertEquals($conversionrecord->rekog_person_status, $result->rekog_person_status);
        $this->assertEquals($conversionrecord->detect_sentiment_status, $result->detect_sentiment_status);
        $this->assertEquals($conversionrecord->detect_phrases_status, $result->detect_phrases_status);
        $this->assertEquals($conversionrecord->detect_entities_status, $result->detect_entities_status);
    }

    /**
     * Test that initial conversion records are successfully created.
     */
    public function test_create_conversion() {
        $this->resetAfterTest(true);
        global $DB;

        set_config('quality_low', 1, 'local_smartmedia');
        set_config('quality_high', 1, 'local_smartmedia');

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

        $result = $DB->get_record('local_smartmedia_conv', array('pathnamehash' => $file1->get_pathnamehash()), '*', MUST_EXIST);
        $this->assertEquals($conversion::CONVERSION_ACCEPTED, $result->rekog_label_status);

        $result = $DB->count_records('local_smartmedia_presets');
        $this->assertEquals(4, $result);

    }

    /**
     * Test getting preset records.
     */
    public function test_get_preset_records() {
        $this->resetAfterTest(true);

        set_config('quality_low', 1, 'local_smartmedia');
        set_config('quality_high', 1, 'local_smartmedia');

        $conversion = new \local_smartmedia\conversion();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_smartmedia\conversion', 'get_preset_records');
        $method->setAccessible(true); // Allow accessing of private method.
        $results = $method->invoke($conversion, 123);

        $presetids = array();
        foreach ($results as $result) {
            $presetids[] = $result->preset;
        }

        $this->assertCount(4, $presetids);
        $this->assertContains('1351620000001-200015', $presetids);
        $this->assertContains('1351620000001-500030', $presetids);
        $this->assertContains('1351620000001-200045', $presetids);
        $this->assertContains('1351620000001-500050', $presetids);
        $this->assertNotContains('1351620000001-200035', $presetids);
        $this->assertNotContains('1351620000001-500040', $presetids);
    }

    /**
     * Test that initial conversion records are successfully created.
     */
    public function test_get_conversion_records() {
        $this->resetAfterTest(true);
        global $DB;
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
    public function test_get_conversion_settings() {
        $this->resetAfterTest(true);
        global $DB;

        $conversionrecord = new \stdClass();
        $conversionrecord->id = 508000;
        $conversionrecord->pathnamehash = '4a1bba15ebb79e7813e642790a551bfaaf6c6066';
        $conversionrecord->contenthash = '8d6985bd0d2abb09a444eb7066efc43678465fc0';
        $conversionrecord->status = 202;
        $conversionrecord->transcribe_status = 202;
        $conversionrecord->rekog_label_status = 404;
        $conversionrecord->rekog_moderation_status = 202;
        $conversionrecord->rekog_face_status = 404;
        $conversionrecord->rekog_person_status = 202;
        $conversionrecord->detect_sentiment_status = 404;
        $conversionrecord->detect_phrases_status = 202;
        $conversionrecord->detect_entities_status = 404;

        $preset1 = new \stdClass();
        $preset1->convid = 508000;
        $preset1->preset = 'preset1';

        $preset2 = new \stdClass();
        $preset2->convid = 508000;
        $preset2->preset = 'preset2';

        $DB->insert_record('local_smartmedia_presets', $preset1);
        $DB->insert_record('local_smartmedia_presets', $preset2);

        $conversion = new \local_smartmedia\conversion();
        $method = new ReflectionMethod('\local_smartmedia\conversion', 'get_conversion_settings');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($conversion, $conversionrecord);

        $this->assertEquals('10101010', $result['processes']);
        $this->assertStringContainsString('preset1', $result['presets']);
        $this->assertStringContainsString('preset2', $result['presets']);

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
        $conversionrecord->transcribe_status = 202;
        $conversionrecord->rekog_label_status = 404;
        $conversionrecord->rekog_moderation_status = 202;
        $conversionrecord->rekog_face_status = 404;
        $conversionrecord->rekog_person_status = 202;
        $conversionrecord->detect_sentiment_status = 404;
        $conversionrecord->detect_phrases_status = 202;
        $conversionrecord->detect_entities_status = 404;
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

        $conversion = new \local_smartmedia\conversion();

        $conversionrecord = new \stdClass();
        $conversionrecord->id = 508000;
        $conversionrecord->pathnamehash = '4a1bba15ebb79e7813e642790a551bfaaf6c6066';
        $conversionrecord->contenthash = '8d6985bd0d2abb09a444eb7066efc43678465fc0';
        $conversionrecord->status = $conversion::CONVERSION_ACCEPTED;
        $conversionrecord->transcoder_status = $conversion::CONVERSION_ACCEPTED;
        $conversionrecord->transcribe_status = $conversion::CONVERSION_ACCEPTED;
        $conversionrecord->rekog_label_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->rekog_moderation_status = $conversion::CONVERSION_ACCEPTED;
        $conversionrecord->rekog_face_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->rekog_person_status = $conversion::CONVERSION_ACCEPTED;
        $conversionrecord->timecreated = time();
        $conversionrecord->timemodified = time();

        $messagerecord1 = new \stdClass();
        $messagerecord1->objectkey = '8d6985bd0d2abb09a444eb7066efc43678465fc0';
        $messagerecord1->process = 'StartContentModeration';
        $messagerecord1->status = 'SUCCEEDED';
        $messagerecord1->messagehash = md5('a');
        $messagerecord1->message = '{}';
        $messagerecord1->senttime = '1566091817';
        $messagerecord1->timecreated = '1566197550';

        $messagerecord2 = new \stdClass();
        $messagerecord2->objectkey = '8d6985bd0d2abb09a444eb7066efc43678465fc0';
        $messagerecord2->process = 'elastic_transcoder';
        $messagerecord2->status = 'COMPLETED';
        $messagerecord2->messagehash = md5('b');
        $messagerecord2->message = '{}';
        $messagerecord2->senttime = '1566091817';
        $messagerecord2->timecreated = '1566197550';

        $messagerecord3 = new \stdClass();
        $messagerecord3->objectkey = '8d6985bd0d2abb09a444eb7066efc43678465fc0';
        $messagerecord3->process = 'elastic_transcoder';
        $messagerecord3->messagehash = md5('c');
        $messagerecord3->status = 'PROGRESSING';
        $messagerecord3->message = '{}';
        $messagerecord3->senttime = '1566091817';
        $messagerecord3->timecreated = '1566197550';

        $msg1 = $DB->insert_record('local_smartmedia_queue_msgs', $messagerecord1);
        $msg2 = $DB->insert_record('local_smartmedia_queue_msgs', $messagerecord2);
        $msg3 = $DB->insert_record('local_smartmedia_queue_msgs', $messagerecord3);

        $method = new ReflectionMethod('\local_smartmedia\conversion', 'get_queue_messages');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($conversion, $conversionrecord);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey($msg1, $result);
        $this->assertArrayHasKey($msg2, $result);
        $this->assertArrayNotHasKey($msg3, $result);

        // Test again with a record that transcoder has finished.
        $conversionrecord->transcoder_status = $conversion::CONVERSION_FINISHED;
        $result = $method->invoke($conversion, $conversionrecord);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey($msg1, $result);
        $this->assertArrayNotHasKey($msg2, $result);
        $this->assertArrayNotHasKey($msg3, $result);
    }

    /**
     * Test processing conversions for a record with a failed transcode.
     */
    public function test_process_conversion_transcode_failed() {
        $this->resetAfterTest(true);
        global $DB;

        $conversion = new \local_smartmedia\conversion();

        $conversionrecord = new \stdClass();
        $conversionrecord->id = 508000;
        $conversionrecord->pathnamehash = '4a1bba15ebb79e7813e642790a551bfaaf6c6066';
        $conversionrecord->contenthash = '8d6985bd0d2abb09a444eb7066efc43678465fc0';
        $conversionrecord->status = $conversion::CONVERSION_ACCEPTED;
        $conversionrecord->transcoder_status = $conversion::CONVERSION_ACCEPTED;
        $conversionrecord->rekog_label_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->rekog_moderation_status = $conversion::CONVERSION_ACCEPTED;
        $conversionrecord->rekog_face_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->rekog_person_status = $conversion::CONVERSION_ACCEPTED;
        $conversionrecord->timecreated = time();
        $conversionrecord->timemodified = time();

        $messagerecord1 = new \stdClass();
        $messagerecord1->objectkey = '8d6985bd0d2abb09a444eb7066efc43678465fc0';
        $messagerecord1->process = 'elastic_transcoder';
        $messagerecord1->status = 'ERROR';
        $messagerecord1->message = '{}';
        $messagerecord1->senttime = '1566091817';
        $messagerecord1->timecreated = '1566197550';

        $messages = array($messagerecord1);

        $method = new ReflectionMethod('\local_smartmedia\conversion', 'process_conversion');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($conversion, $conversionrecord, $messages);

        $this->assertEquals($conversion::CONVERSION_ERROR, $result->status);
        $this->assertEquals($conversion::CONVERSION_ERROR, $result->transcoder_status);
        $this->assertEquals($conversion::CONVERSION_ERROR, $result->rekog_label_status);
        $this->assertEquals($conversion::CONVERSION_ERROR, $result->rekog_moderation_status);
        $this->assertEquals($conversion::CONVERSION_ERROR, $result->rekog_face_status);
        $this->assertEquals($conversion::CONVERSION_ERROR, $result->rekog_person_status);

    }

    /**
     * Test processing conversions for a record with a failed individual process.
     */
    public function test_process_conversion_process_fail() {
        $this->resetAfterTest(true);
        global $DB;

        $conversion = new \local_smartmedia\conversion();

        $conversionrecord = new \stdClass();
        $conversionrecord->id = 508000;
        $conversionrecord->pathnamehash = '4a1bba15ebb79e7813e642790a551bfaaf6c6066';
        $conversionrecord->contenthash = '8d6985bd0d2abb09a444eb7066efc43678465fc0';
        $conversionrecord->status = $conversion::CONVERSION_ACCEPTED;
        $conversionrecord->transcoder_status = $conversion::CONVERSION_ACCEPTED;
        $conversionrecord->rekog_label_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->rekog_moderation_status = $conversion::CONVERSION_ACCEPTED;
        $conversionrecord->rekog_face_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->rekog_person_status = $conversion::CONVERSION_ACCEPTED;
        $conversionrecord->timecreated = time();
        $conversionrecord->timemodified = time();

        $messagerecord1 = new \stdClass();
        $messagerecord1->objectkey = '8d6985bd0d2abb09a444eb7066efc43678465fc0';
        $messagerecord1->process = 'StartContentModeration';
        $messagerecord1->status = 'ERROR';
        $messagerecord1->message = '{}';
        $messagerecord1->senttime = '1566091817';
        $messagerecord1->timecreated = '1566197550';

        $messages = array($messagerecord1);

        $method = new ReflectionMethod('\local_smartmedia\conversion', 'process_conversion');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($conversion, $conversionrecord, $messages);

        $this->assertEquals($conversion::CONVERSION_ERROR, $result->rekog_moderation_status);
        $this->assertEquals($conversion::CONVERSION_ACCEPTED, $result->status);
    }

    /**
     * Test processing conversions for a record with a sucessful elastic transcode process.
     */
    public function test_process_conversion_transcode() {
        $this->resetAfterTest(true);
        global $DB;

        // Set up the AWS mock.
        $mock = new MockHandler();
        $mock->append(new Result($this->fixture['listobjects']));
        $mock->append(new Result(array()));
        $mock->append(new Result(array()));
        $mock->append(new Result(array()));

        $conversion = new \local_smartmedia\conversion();

        $conversionrecord = new \stdClass();
        $conversionrecord->id = 508000;
        $conversionrecord->pathnamehash = '4a1bba15ebb79e7813e642790a551bfaaf6c6066';
        $conversionrecord->contenthash = 'SampleVideo1mb';
        $conversionrecord->status = $conversion::CONVERSION_ACCEPTED;
        $conversionrecord->transcoder_status = $conversion::CONVERSION_ACCEPTED;
        $conversionrecord->rekog_label_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->rekog_moderation_status = $conversion::CONVERSION_ACCEPTED;
        $conversionrecord->rekog_face_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->rekog_person_status = $conversion::CONVERSION_ACCEPTED;
        $conversionrecord->timecreated = time();
        $conversionrecord->timemodified = time();

        $messagerecord1 = new \stdClass();
        $messagerecord1->objectkey = 'SampleVideo1mb';
        $messagerecord1->process = 'elastic_transcoder';
        $messagerecord1->status = 'COMPLETED';
        $messagerecord1->message = '{}';
        $messagerecord1->senttime = '1566091817';
        $messagerecord1->timecreated = '1566197550';

        $messages = array($messagerecord1);

        $method = new ReflectionMethod('\local_smartmedia\conversion', 'process_conversion');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($conversion, $conversionrecord, $messages, $mock);

        $this->assertEquals($conversion::CONVERSION_FINISHED, $result->transcoder_status);
        $this->assertEquals($conversion::CONVERSION_ACCEPTED, $result->status);
    }

    /**
     * Test processing conversions for a record with a sucessful elastic transcode process.
     */
    public function test_process_conversion_process() {
        $this->resetAfterTest(true);
        global $DB;

        // Set up the AWS mock.
        $mock = new MockHandler();
        $mock->append(new Result(array()));

        $conversion = new \local_smartmedia\conversion();

        $conversionrecord = new \stdClass();
        $conversionrecord->id = 508000;
        $conversionrecord->pathnamehash = '4a1bba15ebb79e7813e642790a551bfaaf6c6066';
        $conversionrecord->contenthash = 'SampleVideo1mb';
        $conversionrecord->status = $conversion::CONVERSION_ACCEPTED;
        $conversionrecord->transcoder_status = $conversion::CONVERSION_ACCEPTED;
        $conversionrecord->rekog_label_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->rekog_moderation_status = $conversion::CONVERSION_ACCEPTED;
        $conversionrecord->rekog_face_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->rekog_person_status = $conversion::CONVERSION_ACCEPTED;
        $conversionrecord->timecreated = time();
        $conversionrecord->timemodified = time();

        $messagerecord1 = new \stdClass();
        $messagerecord1->objectkey = 'SampleVideo1mb';
        $messagerecord1->process = 'StartContentModeration';
        $messagerecord1->status = 'SUCCEEDED';
        $messagerecord1->message = '{}';
        $messagerecord1->senttime = '1566091817';
        $messagerecord1->timecreated = '1566197550';

        $messages = array($messagerecord1);

        $method = new ReflectionMethod('\local_smartmedia\conversion', 'process_conversion');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($conversion, $conversionrecord, $messages, $mock);

        $this->assertEquals($conversion::CONVERSION_FINISHED, $result->rekog_moderation_status);
        $this->assertEquals($conversion::CONVERSION_ACCEPTED, $result->status);
    }

    /**
     * Test processing conversions for a record with a sucessful elastic transcode process.
     */
    public function test_update_completion_status() {
        $this->resetAfterTest(true);
        global $DB;

        $conversion = new \local_smartmedia\conversion();
        $method = new ReflectionMethod('\local_smartmedia\conversion', 'update_completion_status');
        $method->setAccessible(true); // Allow accessing of private method.

        $conversionrecord = new \stdClass();
        $conversionrecord->id = 508000;
        $conversionrecord->pathnamehash = '4a1bba15ebb79e7813e642790a551bfaaf6c6066';
        $conversionrecord->contenthash = 'SampleVideo1mb';
        $conversionrecord->status = $conversion::CONVERSION_ACCEPTED;
        $conversionrecord->transcoder_status = $conversion::CONVERSION_FINISHED;
        $conversionrecord->rekog_label_status = $conversion::CONVERSION_FINISHED;
        $conversionrecord->rekog_moderation_status = $conversion::CONVERSION_FINISHED;
        $conversionrecord->rekog_face_status = $conversion::CONVERSION_FINISHED;
        $conversionrecord->rekog_person_status = $conversion::CONVERSION_FINISHED;
        $conversionrecord->timecreated = time();
        $conversionrecord->timemodified = time();

        $result = $method->invoke($conversion, $conversionrecord);
        $this->assertEquals($conversion::CONVERSION_FINISHED, $result->status);

        // Try again with some conversions configured to not run.
        $conversionrecord = new \stdClass();
        $conversionrecord->id = 508000;
        $conversionrecord->pathnamehash = '4a1bba15ebb79e7813e642790a551bfaaf6c6066';
        $conversionrecord->contenthash = 'SampleVideo1mb';
        $conversionrecord->status = $conversion::CONVERSION_ACCEPTED;
        $conversionrecord->transcoder_status = $conversion::CONVERSION_FINISHED;
        $conversionrecord->rekog_label_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->rekog_moderation_status = $conversion::CONVERSION_FINISHED;
        $conversionrecord->rekog_face_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->rekog_person_status = $conversion::CONVERSION_FINISHED;
        $conversionrecord->timecreated = time();
        $conversionrecord->timemodified = time();

        $result = $method->invoke($conversion, $conversionrecord);
        $this->assertEquals($conversion::CONVERSION_FINISHED, $result->status);

        // Try again with some conversions configured to not run.
        $conversionrecord = new \stdClass();
        $conversionrecord->id = 508000;
        $conversionrecord->pathnamehash = '4a1bba15ebb79e7813e642790a551bfaaf6c6066';
        $conversionrecord->contenthash = 'SampleVideo1mb';
        $conversionrecord->status = $conversion::CONVERSION_ACCEPTED;
        $conversionrecord->transcoder_status = $conversion::CONVERSION_ACCEPTED;
        $conversionrecord->rekog_label_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->rekog_moderation_status = $conversion::CONVERSION_FINISHED;
        $conversionrecord->rekog_face_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->rekog_person_status = $conversion::CONVERSION_FINISHED;
        $conversionrecord->timecreated = time();
        $conversionrecord->timemodified = time();

        $result = $method->invoke($conversion, $conversionrecord);
        $this->assertEquals($conversion::CONVERSION_ACCEPTED, $result->status);

    }

    /**
     * Test getting pathnamehashes for new conversion records.
     */
    public function test_get_pathnamehashes() {
        $this->resetAfterTest(true);
        global $DB;

        // Create some test files.
        $fs = get_file_storage();

        $filerecord1 = array(
            'contextid' => 1461,
            'component' => 'mod_label',
            'filearea' => 'intro',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'video1.mp4');

        $filerecord2 = array(
            'contextid' => 1461,
            'component' => 'mod_label',
            'filearea' => 'intro',
            'itemid' => 1,
            'filepath' => '/',
            'filename' => 'video2.mp4');

        $filerecord3 = array(
            'contextid' => 1461,
            'component' => 'mod_label',
            'filearea' => 'intro',
            'itemid' => 2,
            'filepath' => '/',
            'filename' => 'video3.mp4');

        // For this test it doesn't actually matter these are not real multimedia files.
        $file1 = $fs->create_file_from_string($filerecord1, 'I am the first video.');
        $file2 = $fs->create_file_from_string($filerecord2, 'I am the second video.');
        $file3 = $fs->create_file_from_string($filerecord3, 'I am the third video.');

        // Create file metadata records.
        $metadatarecord1 = new \stdClass();
        $metadatarecord1->contenthash = $file1->get_contenthash();
        $metadatarecord1->pathnamehash = $file1->get_pathnamehash();
        $metadatarecord1->duration = 3.123;
        $metadatarecord1->bitrate = 1000;
        $metadatarecord1->size = 390;
        $metadatarecord1->videostreams = 1;
        $metadatarecord1->audiostreams = 1;
        $metadatarecord1->width = 1920;
        $metadatarecord1->height = 1080;
        $metadatarecord1->metadata = '{}';

        $id1 = $DB->insert_record('local_smartmedia_data', $metadatarecord1);

        $metadatarecord2 = new \stdClass();
        $metadatarecord2->contenthash = $file2->get_contenthash();
        $metadatarecord2->pathnamehash = $file2->get_pathnamehash();
        $metadatarecord2->duration = 3.123;
        $metadatarecord2->bitrate = 1000;
        $metadatarecord2->size = 390;
        $metadatarecord2->videostreams = 1;
        $metadatarecord2->audiostreams = 1;
        $metadatarecord2->width = 1920;
        $metadatarecord2->height = 1080;
        $metadatarecord2->metadata = '{}';

        $id2 = $DB->insert_record('local_smartmedia_data', $metadatarecord2);

        $metadatarecord3 = new \stdClass();
        $metadatarecord3->contenthash = $file3->get_contenthash();
        $metadatarecord3->pathnamehash = $file3->get_pathnamehash();
        $metadatarecord3->duration = 3.123;
        $metadatarecord3->bitrate = 1000;
        $metadatarecord3->size = 390;
        $metadatarecord3->videostreams = 1;
        $metadatarecord3->audiostreams = 1;
        $metadatarecord3->width = 1920;
        $metadatarecord3->height = 1080;
        $metadatarecord3->metadata = '{}';

        $id3 = $DB->insert_record('local_smartmedia_data', $metadatarecord3);

        $conversionrecord = new \stdClass();
        $conversionrecord->contenthash = $file3->get_contenthash();;
        $conversionrecord->pathnamehash = $file3->get_pathnamehash();
        $conversionrecord->status = 202;
        $conversionrecord->transcribe_status = 202;
        $conversionrecord->rekog_label_status = 404;
        $conversionrecord->rekog_moderation_status = 202;
        $conversionrecord->rekog_face_status = 404;
        $conversionrecord->rekog_person_status = 202;
        $conversionrecord->detect_sentiment_status = 404;
        $conversionrecord->detect_phrases_status = 202;
        $conversionrecord->detect_entities_status = 404;
        $conversionrecord->timecreated = time();
        $conversionrecord->timemodified = time();

        $recordid = $DB->insert_record('local_smartmedia_conv', $conversionrecord);

        $conversion = new \local_smartmedia\conversion();
        $method = new ReflectionMethod('\local_smartmedia\conversion', 'get_pathnamehashes');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($conversion);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey($id1, $result);
        $this->assertArrayHasKey($id2, $result);
        $this->assertArrayNotHasKey($id3, $result);

    }

    /**
     * Test getting pathnamehashes for new conversion records.
     */
    public function test_check_smartmedia_file() {
        $this->resetAfterTest(true);
        global $DB;

        $conversion = new \local_smartmedia\conversion();

        // Create some test files.
        $fs = get_file_storage();

        $sourcefilerecord = array(
                'contextid' => 1461,
                'component' => 'mod_label',
                'filearea' => 'intro',
                'itemid' => 0,
                'filepath' => '/',
                'filename' => 'video1.mp4');

        // For this test it doesn't actually matter these are not real multimedia files.
        $sourcefile = $fs->create_file_from_string($sourcefilerecord, 'I am the first video.');

        $smartfilerecord = array(
                'contextid' => 1,
                'component' => 'local_smartmedia',
                'filearea' => 'media',
                'itemid' => 0,
                'filepath' => '/aaaaaaaaaaaaaaaaaa/conversions/',
                'filename' => $sourcefile->get_contenthash() . 'mp4');

        // For this test it doesn't actually matter these are not real multimedia files.
        $smartfile = $fs->create_file_from_string($smartfilerecord, 'I am the smart video.');

        $result = $conversion->check_smartmedia_file($sourcefile, $smartfile);
        $this->assertFalse($result); // Should be false as there is no conversion record.

        $conversionrecord = new \stdClass();
        $conversionrecord->contenthash = $sourcefile->get_contenthash();;
        $conversionrecord->pathnamehash = $sourcefile->get_pathnamehash();
        $conversionrecord->status = $conversion::CONVERSION_FINISHED;
        $conversionrecord->transcoder_status = $conversion::CONVERSION_FINISHED;
        $conversionrecord->rekog_label_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->rekog_moderation_status = $conversion::CONVERSION_FINISHED;
        $conversionrecord->rekog_face_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->rekog_person_status = $conversion::CONVERSION_FINISHED;
        $conversionrecord->timecreated = time();
        $conversionrecord->timemodified = time();

        $DB->insert_record('local_smartmedia_conv', $conversionrecord);

        $result = $conversion->check_smartmedia_file($sourcefile, $smartfile);
        $this->assertFalse($result); // Should be false as there is a contenthash path mismatch.

        $smartfilerecord['filepath'] = '/' . $sourcefile->get_contenthash() . '/conversions/';
        $smartfile = $fs->create_file_from_string($smartfilerecord, 'I am the smart video.');
        $result = $conversion->check_smartmedia_file($sourcefile, $smartfile);
        $this->assertTrue($result);

    }

}
