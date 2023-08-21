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

use Aws\ElasticTranscoder\ElasticTranscoderClient;
use Aws\Result;
use Aws\MockHandler;
use Aws\CommandInterface;
use Psr\Http\Message\RequestInterface;
use Aws\S3\Exception\S3Exception;
use local_smartmedia\aws_api;
use local_smartmedia\aws_elastic_transcoder;

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

    /**
     * @var string the AWS region to test against.
     */
    public $region;

    /**
     * @var string 'YYYY-MM-DD' date version of the AWS Elastic Transcoder Client API version to test against.
     */
    public $etsversion;

    /**
     * @var string the AWS API Key to test against.
     */
    public $apikey;

    /**
     * @var string the AWS API Secret to test against.
     */
    public $apisecret;

    /*
     * Set up method for this test suite.
     */
    public function setUp(): void {
        global $CFG;
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

        // Plugin settings.
        $this->region = 'ap-southeast-2';
        $this->etsversion = '2012-09-25';
        $this->apikey = 'ABCDEFGHIJKLMNO';
        $this->apisecret = '012345678910aBcDeFgHiJkLmNOpQrSTuVwXyZ';

        // Get fixture for tests.
        $this->fixture = require($CFG->dirroot . '/local/smartmedia/tests/fixtures/conversion_test_fixture.php');
    }

    /**
     * Create a mock of \Aws\ElasticTranscoderClient for injecting into \local_smartmedia\aws_elastic_transcoder.
     *
     * @param array $fixtures array of mock data to use for results of api calls.
     *
     * @return \Aws\ElasticTranscoder\ElasticTranscoderClient mock with \Aws\Result instances injected in handler.
     */
    public function create_mock_elastic_transcoder_client(array $fixtures = []) {
        // Inject our results fixture into the API dependency as a mock using a handler.
        $mockhandler = new MockHandler();
        foreach ($fixtures as $fixture) {
            $mockresult = new Result($fixture);
            $mockhandler->append($mockresult);
        }

        // Create the mock response Elastic Transcoder Client.
        $mock = new ElasticTranscoderClient([
            'region' => $this->region,
            'version' => $this->etsversion,
            'credentials' => ['key' => $this->apikey, 'secret' => $this->apisecret],
            'handler' => $mockhandler]);

        return $mock;
    }

    /**
     * Test get smart media method for no media.
     */
    public function test_get_smart_media_nomedia() {
        global $CFG;
        $this->resetAfterTest(true);

        $linkhref = 'http://moodle.local/pluginfile.php/1461/mod_label/intro/SampleVideo1mb.mp4';
        $moodleurl = new \moodle_url($linkhref);

        $api = new aws_api();
        $transcoder = new aws_elastic_transcoder($api->create_elastic_transcoder_client());
        $conversion = new \local_smartmedia\conversion($transcoder);
        $smartmedia = $conversion->get_smart_media($moodleurl);

        // Check for just the null context index.
        $this->assertEquals(1, count($smartmedia));
        $this->assertEmpty($smartmedia['context']);
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

        // Setup course and activity context for file.
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);
        $context = \context_module::instance($activity->cmid);

        // Mock the initial file record from which conversions were made.
        $initialfilerecord = array (
            'contextid' => $context->id,
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
            'filename' => $presetid . '_hls_playlist.m3u8');
        $fs->create_file_from_string($convertedmediarecord, 'the first test file');

        // Mock a metadata file received from s3.
        $converteddatarecord = array(
            'contextid' => 1,
            'component' => 'local_smartmedia',
            'filearea' => 'metadata',
            'itemid' => 0,
            'filepath' => '/' . $initialfile->get_contenthash() . '/metadata/',
            'filename' => 'Labels.json');
        $fs->create_file_from_string($converteddatarecord, 'label data');

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

        $api = new aws_api();
        $transcoder = new aws_elastic_transcoder($api->create_elastic_transcoder_client());
        $conversion = new \local_smartmedia\conversion($transcoder);

        $smartmedia = $conversion->get_smart_media($href);

        // Check that the smart media urls match the passed in mock data and `id` parameter matches initial moodle file id.
        $expectedmediaurl = "$CFG->wwwroot/pluginfile.php/1/local_smartmedia/media/" . $initialfile->get_id()
            . "/$contenthash/conversions/$presetid" . "_hls_playlist.m3u8";
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

        $api = new aws_api();
        $transcoder = new aws_elastic_transcoder($api->create_elastic_transcoder_client());
        $conversion = new \local_smartmedia\conversion($transcoder);

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

        $api = new aws_api();
        $transcoder = new aws_elastic_transcoder($api->create_elastic_transcoder_client());
        $conversion = new \local_smartmedia\conversion($transcoder);

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

        // Ensure files with URL-encoded characters are handled correctly.
        $filerecord4 = array(
            'contextid' => 1386,
            'component' => 'mod_folder',
            'filearea' => 'content',
            'itemid' => 45,
            'filepath' => '/a/b/c/',
            'filename' => 'myfile 4.txt');

        $file4 = $fs->create_file_from_string($filerecord4, 'the fourth test file');
        $filepathnamehash4 = $file4->get_pathnamehash();
        $href4 = moodle_url::make_pluginfile_url(
            $filerecord4['contextid'], $filerecord4['component'], $filerecord4['filearea'],
            $filerecord4['itemid'], $filerecord4['filepath'], $filerecord4['filename']);

        $filerecord5 = array(
            'contextid' => 1386,
            'component' => 'mod_folder',
            'filearea' => 'content',
            'itemid' => 45,
            'filepath' => '/a/b/c/',
            'filename' => 'myfile+!@#$%^&*(5.txt');

        $file5 = $fs->create_file_from_string($filerecord5, 'the fifth test file');
        $filepathnamehash5 = $file5->get_pathnamehash();
        $href5 = moodle_url::make_pluginfile_url(
            $filerecord5['contextid'], $filerecord5['component'], $filerecord5['filearea'],
            $filerecord5['itemid'], $filerecord5['filepath'], $filerecord5['filename']);

        // Instansiate new conversion class.
        $api = new aws_api();
        $transcoder = new aws_elastic_transcoder($api->create_elastic_transcoder_client());
        $conversion = new \local_smartmedia\conversion($transcoder);

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_smartmedia\conversion', 'get_file_from_url');
        $method->setAccessible(true); // Allow accessing of private method.

        $result1 = $method->invoke($conversion, $href1); // Get result of invoked method.
        $result2 = $method->invoke($conversion, $href2); // Get result of invoked method.
        $result3 = $method->invoke($conversion, $href3); // Get result of invoked method.
        $result4 = $method->invoke($conversion, $href4); // Get result of invoked method.
        $result5 = $method->invoke($conversion, $href5); // Get result of invoked method.

        $this->assertEquals($filepathnamehash1, $result1->get_pathnamehash());
        $this->assertEquals($filepathnamehash2, $result2->get_pathnamehash());
        $this->assertEquals($filepathnamehash3, $result3->get_pathnamehash());
        $this->assertEquals($filepathnamehash4, $result4->get_pathnamehash());
        $this->assertEquals($filepathnamehash5, $result5->get_pathnamehash());

        // Lets test a pluginfile-like URL that is incompatible.
        $urlstr = implode('/', [
            'tokenpluginfile.php',
            'mycooltoken',
            $file1->get_contextid(),
            $file1->get_component(),
            $file1->get_filearea(),
            $file1->get_itemid()
        ]);
        // Now append the filepath and name without extra strings.
        $urlstr .= $file1->get_filepath() . $file1->get_filename();
        $tokenurl = new moodle_url($urlstr);

        // This should return false.
        $this->assertFalse($method->invoke($conversion, $tokenurl));
    }

    /**
     * Test method that gets conversion status when there is no existing
     * conversion record in the database.
     */
    public function test_get_conversion_statuses_no_record() {
        $this->resetAfterTest(true);
        $api = new aws_api();
        $transcoder = new aws_elastic_transcoder($api->create_elastic_transcoder_client());
        $conversion = new \local_smartmedia\conversion($transcoder);

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

        $api = new aws_api();
        $transcoder = new aws_elastic_transcoder($api->create_elastic_transcoder_client());
        $conversion = new \local_smartmedia\conversion($transcoder);

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
        set_config('transcribe', 0, 'local_smartmedia');

        // Mock the elastic transcoder client so it returns low quality and high quality presets.
        $mockdata = array_merge(
            $this->fixture['readPreset']['quality_low'],
            $this->fixture['readPreset']['quality_high'],
            $this->fixture['readPreset']['download_files']
        );
        $mock = $this->create_mock_elastic_transcoder_client($mockdata);

        $transcoder = new aws_elastic_transcoder($mock);
        $conversion = new \local_smartmedia\conversion($transcoder);

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
        $this->assertEquals(7, $result);

    }

    /**
     * Test getting preset records.
     */
    public function test_get_preset_records() {
        $this->resetAfterTest(true);
        global $DB;

        set_config('quality_low', 1, 'local_smartmedia');
        set_config('quality_high', 1, 'local_smartmedia');
        set_config('audio_output', 1, 'local_smartmedia');
        set_config('download_files', 1, 'local_smartmedia');

        // Mock the elastic transcoder client so it returns fixture preset data for low quality, high quality, audio and download.
        $fixturedata = array_values(array_merge(
            $this->fixture['readPreset']['quality_low'],
            $this->fixture['readPreset']['quality_high'],
            $this->fixture['readPreset']['audio_output'],
            $this->fixture['readPreset']['download_files']));
        // We invoke the method three times, so we need to mock this data three times.
        $mockdata = array_merge($fixturedata, $fixturedata, $fixturedata);
        $mock = $this->create_mock_elastic_transcoder_client($mockdata);

        // Create file metadata records.
        $metadatarecord1 = new \stdClass();
        $metadatarecord1->contenthash = 'fakecontenthash1';
        $metadatarecord1->pathnamehash = 'fakepathnamehash1';
        $metadatarecord1->duration = 3.123;
        $metadatarecord1->bitrate = 1000;
        $metadatarecord1->size = 390;
        $metadatarecord1->videostreams = 1;
        $metadatarecord1->audiostreams = 1;
        $metadatarecord1->width = 1920;
        $metadatarecord1->height = 1080;
        $metadatarecord1->metadata = '{}';

        $DB->insert_record('local_smartmedia_data', $metadatarecord1);

        $metadatarecord2 = new \stdClass();
        $metadatarecord2->contenthash = 'fakecontenthash2';
        $metadatarecord2->pathnamehash = 'fakepathnamehash2';
        $metadatarecord2->duration = 3.123;
        $metadatarecord2->bitrate = 1000;
        $metadatarecord2->size = 390;
        $metadatarecord2->videostreams = 1;
        $metadatarecord2->audiostreams = 0;
        $metadatarecord2->width = 1920;
        $metadatarecord2->height = 1080;
        $metadatarecord2->metadata = '{}';

        $DB->insert_record('local_smartmedia_data', $metadatarecord2);

        $metadatarecord3 = new \stdClass();
        $metadatarecord3->contenthash = 'fakecontenthash3';
        $metadatarecord3->pathnamehash = 'fakepathnamehash3';
        $metadatarecord3->duration = 3.123;
        $metadatarecord3->bitrate = 1000;
        $metadatarecord3->size = 390;
        $metadatarecord3->videostreams = 0;
        $metadatarecord3->audiostreams = 1;
        $metadatarecord3->width = 0;
        $metadatarecord3->height = 0;
        $metadatarecord3->metadata = '{}';

        $DB->insert_record('local_smartmedia_data', $metadatarecord3);

        $transcoder = new aws_elastic_transcoder($mock);
        $conversion = new \local_smartmedia\conversion($transcoder);

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_smartmedia\conversion', 'get_preset_records');
        $method->setAccessible(true); // Allow accessing of private method.

        $results = $method->invoke($conversion, 123, 'fakecontenthash1');
        $presetids = array();
        foreach ($results as $result) {
            $presetids[] = $result->preset;
        }

        $this->assertCount(8, $presetids);
        $this->assertContains('1351620000001-200015', $presetids);
        $this->assertContains('1351620000001-500030', $presetids);
        $this->assertContains('1351620000001-200045', $presetids);
        $this->assertContains('1351620000001-500050', $presetids);
        $this->assertContains('1351620000001-300020', $presetids);
        $this->assertContains('1351620000001-100070', $presetids);

        $results = $method->invoke($conversion, 123, 'fakecontenthash2');
        $presetids = array();
        foreach ($results as $result) {
            $presetids[] = $result->preset;
        }

        $this->assertCount(5, $presetids);
        $this->assertContains('1351620000001-200015', $presetids);
        $this->assertContains('1351620000001-500030', $presetids);
        $this->assertContains('1351620000001-200045', $presetids);
        $this->assertContains('1351620000001-500050', $presetids);
        $this->assertContains('1351620000001-100070', $presetids);

        $results = $method->invoke($conversion, 123, 'fakecontenthash3');
        $presetids = array();
        foreach ($results as $result) {
            $presetids[] = $result->preset;
        }

        $this->assertCount(3, $presetids);
        $this->assertContains('1351620000001-300020', $presetids);

    }

    /**
     * Test that initial conversion records are successfully created.
     */
    public function test_get_conversion_records() {
        $this->resetAfterTest(true);

        // Turn off all quality options.
        set_config('quality_low', 0, 'local_smartmedia');
        set_config('quality_high', 0, 'local_smartmedia');
        // Disable enrichment that forces quality.
        set_config('transcribe', 0, 'local_smartmedia');
        set_config('detectlabels', 0, 'local_smartmedia');
        set_config('detectmoderation', 0, 'local_smartmedia');
        set_config('detectfaces', 0, 'local_smartmedia');
        set_config('detectpeople', 0, 'local_smartmedia');

        $api = new aws_api();
        $transcoder = new aws_elastic_transcoder($api->create_elastic_transcoder_client());
        $conversion = new \local_smartmedia\conversion($transcoder);

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

        set_config('quality_low', 1, 'local_smartmedia');
        set_config('quality_high', 0, 'local_smartmedia');
        // Disable enrichment that forces quality.
        set_config('transcribe', 0, 'local_smartmedia');
        set_config('detectlabels', 0, 'local_smartmedia');
        set_config('detectmoderation', 0, 'local_smartmedia');
        set_config('detectfaces', 0, 'local_smartmedia');
        set_config('detectpeople', 0, 'local_smartmedia');

        // Mock the elastic transcoder client so it returns fixture preset data for low quality.
        $mockdata = array_values($this->fixture['readPreset']['quality_low']);
        $mock = $this->create_mock_elastic_transcoder_client($mockdata);

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

        $transcoder = new aws_elastic_transcoder($mock);
        $conversion = new \local_smartmedia\conversion($transcoder);
        $method = new ReflectionMethod('\local_smartmedia\conversion', 'get_conversion_settings');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($conversion, $conversionrecord);

        $this->assertEquals('10101010', $result['processes']);
        // Check that all low quality fixture presets are present in the metadata.
        foreach ($this->fixture['readPreset']['quality_low'] as $preset) {
            if (method_exists($this, 'assertStringContainsString')) {
                $this->assertStringContainsString($preset['Preset']['Id'], $result['presets']);
            } else {
                $this->assertContains($preset['Preset']['Id'], $result['presets']);
            }
        }
        // Check that no medium quality fixture presets are present in the metadata.
        foreach ($this->fixture['readPreset']['quality_medium'] as $preset) {
            // Ignore the audio track.
            if (in_array($preset['Preset']['Id'], aws_elastic_transcoder::HLS_AUDIO)
                    || in_array($preset['Preset']['Id'], aws_elastic_transcoder::MPD_AUDIO)) {
                continue;
            }

            if (method_exists($this, 'assertStringNotContainsString')) {
                $this->assertStringNotContainsString($preset['Preset']['Id'], $result['presets']);
            } else {
                $this->assertNotContains($preset['Preset']['Id'], $result['presets']);
            }
        }
    }

    /**
     * Test sending file to aws for conversion.
     */
    public function test_send_file_for_processing() {
        $this->resetAfterTest(true);
        global $CFG;

        $api = new aws_api();
        $transcoder = new aws_elastic_transcoder($api->create_elastic_transcoder_client());
        $conversion = new \local_smartmedia\conversion($transcoder);

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
        $api = new aws_api();
        $transcoder = new aws_elastic_transcoder($api->create_elastic_transcoder_client());
        $conversion = new \local_smartmedia\conversion($transcoder);

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

        $api = new aws_api();
        $transcoder = new aws_elastic_transcoder($api->create_elastic_transcoder_client());
        $conversion = new \local_smartmedia\conversion($transcoder);

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
        $conversionrecord->detect_sentiment_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->detect_entities_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->detect_phrases_status = $conversion::CONVERSION_NOT_FOUND;
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

        $api = new aws_api();
        $transcoder = new aws_elastic_transcoder($api->create_elastic_transcoder_client());
        $conversion = new \local_smartmedia\conversion($transcoder);

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
        $conversionrecord->transcribe_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->detect_sentiment_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->detect_entities_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->detect_phrases_status = $conversion::CONVERSION_NOT_FOUND;
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

        $api = new aws_api();
        $transcoder = new aws_elastic_transcoder($api->create_elastic_transcoder_client());
        $conversion = new \local_smartmedia\conversion($transcoder);

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
        $conversionrecord->transcribe_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->detect_sentiment_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->detect_entities_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->detect_phrases_status = $conversion::CONVERSION_NOT_FOUND;
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

        // Set up the AWS mock.
        $mock = new MockHandler();
        $mock->append(new Result($this->fixture['listobjects']));
        foreach ($this->fixture['listobjects']['Contents'] as $object) {
            // The fixture contains mock body data for non-binary files only.
            if (array_key_exists('Body', $object)) {
                $mock->append(new Result($object));
            } else {
                $mock->append(new Result(array()));
            }
        }

        $api = new aws_api();
        $transcoder = new aws_elastic_transcoder($api->create_elastic_transcoder_client());
        $conversion = new \local_smartmedia\conversion($transcoder);

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
        $conversionrecord->transcribe_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->detect_sentiment_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->detect_entities_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->detect_phrases_status = $conversion::CONVERSION_NOT_FOUND;
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
     * Test processing conversions for a record with no current queue messages.
     */
    public function test_process_conversion_no_messages() {
        $this->resetAfterTest(true);

        // Set up the AWS mock.
        $mock = new MockHandler();
        $mock->append(new Result($this->fixture['listobjects']));
        foreach ($this->fixture['listobjects']['Contents'] as $object) {
            // The fixture contains mock body data for non-binary files only.
            if (array_key_exists('Body', $object)) {
                $mock->append(new Result($object));
            } else {
                $mock->append(new Result(array()));
            }
        }

        $api = new aws_api();
        $transcoder = new aws_elastic_transcoder($api->create_elastic_transcoder_client());
        $conversion = new \local_smartmedia\conversion($transcoder);

        $conversionrecord = new \stdClass();
        $conversionrecord->id = 508000;
        $conversionrecord->pathnamehash = '4a1bba15ebb79e7813e642790a551bfaaf6c6066';
        $conversionrecord->contenthash = 'SampleVideo1mb';
        $conversionrecord->status = $conversion::CONVERSION_ACCEPTED;
        $conversionrecord->transcoder_status = $conversion::CONVERSION_ACCEPTED;
        $conversionrecord->rekog_label_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->rekog_moderation_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->rekog_face_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->rekog_person_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->transcribe_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->detect_sentiment_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->detect_entities_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->detect_phrases_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->timecreated = time();
        $conversionrecord->timemodified = time();

        $messages = array();

        $method = new ReflectionMethod('\local_smartmedia\conversion', 'process_conversion');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($conversion, $conversionrecord, $messages, $mock);

        $this->assertEquals($conversion::CONVERSION_ACCEPTED, $result->transcoder_status);
        $this->assertEquals($conversion::CONVERSION_ACCEPTED, $result->status);
    }

    /**
     * Test ability to correctly replace urls in playlist files with pluginfile urls.
     */
    public function test_replace_playlist_urls_with_pluginfile_urls() {
        global $CFG;

        $this->resetAfterTest(true);

        // Set up for test.
        $contenthash = 'SampleVideo1mb'; // Content hash used in all fixtures.
        $playlists = [];
        foreach ($this->fixture['listobjects']['Contents'] as $object) {
            // The fixture contains body data for playlists.
            if (array_key_exists('Body', $object)) {
                $playlists[] = $object['Body'];
            }
        }

        $api = new aws_api();
        $transcoder = new aws_elastic_transcoder($api->create_elastic_transcoder_client());
        $conversion = new \local_smartmedia\conversion($transcoder);

        foreach ($playlists as $playlistcontent) {
            // Use reflector magic on private method to get the file with replaced urls as array for comparison.
            $method = new ReflectionMethod('\local_smartmedia\conversion', 'replace_playlist_urls_with_pluginfile_urls');
            $method->setAccessible(true); // Allow accessing of private method.
            $result = $method->invoke($conversion, $playlistcontent, $contenthash);

            $needle = "pluginfile.php/1/local_smartmedia/media/0/$contenthash/conversions/";
            $this->assertContains($needle, $result);
        }
    }

    /**
     * Test processing conversions for a record with a successful elastic transcode process.
     */
    public function test_process_conversion_process() {
        $this->resetAfterTest(true);

        // Set up the AWS mock.
        $mock = new MockHandler();
        $mock->append(new Result(array()));

        $api = new aws_api();
        $transcoder = new aws_elastic_transcoder($api->create_elastic_transcoder_client());
        $conversion = new \local_smartmedia\conversion($transcoder);

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
        $conversionrecord->transcribe_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->detect_sentiment_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->detect_entities_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->detect_phrases_status = $conversion::CONVERSION_NOT_FOUND;
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

        $mockhandler = new MockHandler();

        $api = new aws_api();
        $transcoder = new aws_elastic_transcoder($api->create_elastic_transcoder_client());
        $conversion = new \local_smartmedia\conversion($transcoder);
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
        $conversionrecord->transcribe_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->detect_sentiment_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->detect_entities_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->detect_phrases_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->timecreated = time();
        $conversionrecord->timemodified = time();

        $mockresult = new Result(array());
        $mockhandler->append($mockresult);
        $mockresult = new Result(array());
        $mockhandler->append($mockresult);
        $mockresult = new Result(array());
        $mockhandler->append($mockresult);
        $result = $method->invoke($conversion, $conversionrecord, $mockhandler);
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
        $conversionrecord->transcribe_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->detect_sentiment_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->detect_entities_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->detect_phrases_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->timecreated = time();
        $conversionrecord->timemodified = time();

        $mockresult = new Result(array());
        $mockhandler->append($mockresult);
        $mockresult = new Result(array());
        $mockresult = new Result(array());
        $mockhandler->append($mockresult);
        $mockhandler->append($mockresult);
        $result = $method->invoke($conversion, $conversionrecord, $mockhandler);
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
        $conversionrecord->transcribe_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->detect_sentiment_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->detect_entities_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->detect_phrases_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->timecreated = time();
        $conversionrecord->timemodified = time();

        $mockresult = new Result(array());
        $mockhandler->append($mockresult);
        $mockresult = new Result(array());
        $mockresult = new Result(array());
        $mockhandler->append($mockresult);
        $mockhandler->append($mockresult);
        $result = $method->invoke($conversion, $conversionrecord, $mockhandler);
        $this->assertEquals($conversion::CONVERSION_ACCEPTED, $result->status);

        // Try again with only transcode configured to run.
        $conversionrecord = new \stdClass();
        $conversionrecord->id = 508000;
        $conversionrecord->pathnamehash = '4a1bba15ebb79e7813e642790a551bfaaf6c6066';
        $conversionrecord->contenthash = 'SampleVideo1mb';
        $conversionrecord->status = $conversion::CONVERSION_ACCEPTED;
        $conversionrecord->transcoder_status = $conversion::CONVERSION_ACCEPTED;
        $conversionrecord->rekog_label_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->rekog_moderation_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->rekog_face_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->rekog_person_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->transcribe_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->detect_sentiment_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->detect_entities_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->detect_phrases_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->timecreated = time();
        $conversionrecord->timemodified = time();

        $mockresult = new Result(array());
        $mockhandler->append($mockresult);
        $mockresult = new Result(array());
        $mockhandler->append($mockresult);
        $mockresult = new Result(array());
        $mockhandler->append($mockresult);
        $result = $method->invoke($conversion, $conversionrecord, $mockhandler);
        $this->assertEquals($conversion::CONVERSION_ACCEPTED, $result->status);

    }

    /**
     * Test getting fileids for new conversion records.
     */
    public function test_get_fileids() {
        $this->resetAfterTest(true);
        global $DB;

        // Create some test files.
        $fs = get_file_storage();
        set_config('convertfrom', 604800, 'local_smartmedia');
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

        $DB->insert_record('local_smartmedia_data', $metadatarecord1);

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

        $DB->insert_record('local_smartmedia_data', $metadatarecord2);

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

        $DB->insert_record('local_smartmedia_data', $metadatarecord3);

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

        $DB->insert_record('local_smartmedia_conv', $conversionrecord);

        $api = new aws_api();
        $transcoder = new aws_elastic_transcoder($api->create_elastic_transcoder_client());
        $conversion = new \local_smartmedia\conversion($transcoder);
        $method = new ReflectionMethod('\local_smartmedia\conversion', 'get_fileids');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($conversion);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey($file1->get_id(), $result);
        $this->assertArrayHasKey($file2->get_id(), $result);
        $this->assertArrayNotHasKey($file3->get_id(), $result);

    }

    /**
     * Test getting fileids for new conversion records with date restriction.
     */
    public function test_get_fileids_date() {
        $this->resetAfterTest(true);
        global $DB;

        set_config('convertfrom', 86400, 'local_smartmedia');

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
            'filename' => 'video2.mp4',
            'timecreated' => time() - 604800
        );

        // For this test it doesn't actually matter these are not real multimedia files.
        $file1 = $fs->create_file_from_string($filerecord1, 'I am the first video.');
        $file2 = $fs->create_file_from_string($filerecord2, 'I am the second video.');

        // Cheat and delete the folder records that are too recent.
        $DB->delete_records('files', ['filename' => '.', 'component' => 'mod_label']);

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

        $DB->insert_record('local_smartmedia_data', $metadatarecord1);

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

        $DB->insert_record('local_smartmedia_data', $metadatarecord2);

        $api = new aws_api();
        $transcoder = new aws_elastic_transcoder($api->create_elastic_transcoder_client());
        $conversion = new \local_smartmedia\conversion($transcoder);
        $method = new ReflectionMethod('\local_smartmedia\conversion', 'get_fileids');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($conversion);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey($file1->get_id(), $result);
        $this->assertArrayNotHasKey($file2->get_id(), $result);

    }
    /**
     * Test getting fileids for new conversion records.
     */
    public function test_check_smartmedia_file() {
        $this->resetAfterTest(true);
        global $DB;

        $api = new aws_api();
        $transcoder = new aws_elastic_transcoder($api->create_elastic_transcoder_client());
        $conversion = new \local_smartmedia\conversion($transcoder);

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
        $conversionrecord->transcribe_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->detect_sentiment_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->detect_entities_status = $conversion::CONVERSION_NOT_FOUND;
        $conversionrecord->detect_phrases_status = $conversion::CONVERSION_NOT_FOUND;
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

    /**
     * Test getting getting only relevant media files.
     */
    public function test_get_media_files() {
        $this->resetAfterTest(true);

        // Create some test files.
        $fs = get_file_storage();

        $smartfilerecord = array(
                'contextid' => 1,
                'component' => 'local_smartmedia',
                'filearea' => 'media',
                'itemid' => 0,
                'filepath' => '/aaaaaaaaaaaaaaaaaa/conversions/',
                'filename' => 'contenthash_mpegdash_playlist.mpd');

        // For this test it doesn't actually matter these are not real multimedia files.
        $fs->create_file_from_string($smartfilerecord, 'I am the mpeg-dash playlist.');

        $smartfilerecord['filename'] = 'contenthash_hls_playlist.m3u8';
        $fs->create_file_from_string($smartfilerecord, 'I am the HLS playlist.');

        $smartfilerecord['filename'] = 'contenthash_preset-id.mp4';
        $fs->create_file_from_string($smartfilerecord, 'I am the mp4 download video.');

        $smartfilerecord['filename'] = 'contenthash_preset-id.mp3';
        $fs->create_file_from_string($smartfilerecord, 'I am the audio only mp3.');

        $smartfilerecord['filename'] = 'contenthash_preset-id.ts';
        $fs->create_file_from_string($smartfilerecord, 'I am a segment file.');

        // Set up the method to test.
        $api = new aws_api();
        $transcoder = new aws_elastic_transcoder($api->create_elastic_transcoder_client());
        $conversion = new \local_smartmedia\conversion($transcoder);
        $method = new ReflectionMethod('\local_smartmedia\conversion', 'get_media_files');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($conversion, 'aaaaaaaaaaaaaaaaaa');

        // We should only get 2 results here as non playlist filter files shouls have been filtered.
        $this->assertCount(2, $result);

    }

    /**
     * Test getting getting only relevant media files.
     */
    public function test_filter_playlists() {
        $this->resetAfterTest(true);

        // Create some test files.
        $fs = get_file_storage();
        $files = array();

        $smartfilerecord = array(
                'contextid' => 1,
                'component' => 'local_smartmedia',
                'filearea' => 'media',
                'itemid' => 0,
                'filepath' => '/aaaaaaaaaaaaaaaaaa/conversions/',
                'filename' => 'contenthash_mpegdash_playlist.mpd');

        // For this test it doesn't actually matter these are not real multimedia files.
        $files[] = $fs->create_file_from_string($smartfilerecord, 'I am the mpeg-dash playlist.');

        $smartfilerecord['filename'] = 'contenthash_hls_playlist.m3u8';
        $files[] = $fs->create_file_from_string($smartfilerecord, 'I am the HLS playlist.');

        $smartfilerecord['filename'] = 'contenthash_preset-id.mp4';
        $files[] = $fs->create_file_from_string($smartfilerecord, 'I am the mp4 download video.');

        $smartfilerecord['filename'] = 'contenthash_preset-id.mp3';
        $files[] = $fs->create_file_from_string($smartfilerecord, 'I am the audio only mp3.');

        $smartfilerecord['filename'] = 'contenthash_preset-id.ts';
        $files[] = $fs->create_file_from_string($smartfilerecord, 'I am a segment file.');

        // Set up the method to test.
        $api = new aws_api();
        $transcoder = new aws_elastic_transcoder($api->create_elastic_transcoder_client());
        $conversion = new \local_smartmedia\conversion($transcoder);
        $method = new ReflectionMethod('\local_smartmedia\conversion', 'filter_playlists');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($conversion, $files);

        $this->assertCount(4, $result);

        foreach ($result as $file) {
            $this->assertNotEquals('contenthash_preset-id.ts', $file->get_filename());
        }

    }


    /**
     * Test playlist generation.
     */
    public function test_generate_playlists() {
        $this->resetAfterTest(true);

        // Create some test files.
        $fs = get_file_storage();
        $files = array();

        $smartfilerecord = array(
            'contextid' => 1,
            'component' => 'local_smartmedia',
            'filearea' => 'media',
            'itemid' => 0,
            'filepath' => '/aaaaaaaaaaaaaaaaaa/conversions/',
            'filename' => 'contenthash_mpegdash_playlist.mpd');

        // For this test it doesn't actually matter these are not real multimedia files.
        $files[] = $fs->create_file_from_string($smartfilerecord, $this->fixture['mpd_playlist_fixture']);

        $smartfilerecord['filename'] = 'contenthash_hls_playlist.m3u8';
        $files[] = $fs->create_file_from_string($smartfilerecord, $this->fixture['hls_playlist_fixture']);

        $smartfilerecord['filename'] = 'contenthash_preset-id.mp4';
        $files[] = $fs->create_file_from_string($smartfilerecord, 'I am the mp4 download video.');

        $smartfilerecord['filename'] = 'contenthash_preset-id.mp3';
        $files[] = $fs->create_file_from_string($smartfilerecord, 'I am the audio only mp3.');

        $smartfilerecord['filename'] = 'contenthash_preset-id.ts';
        $files[] = $fs->create_file_from_string($smartfilerecord, 'I am a segment file.');

        // Source file id.
        $fileid = 1391;

        // Set up the method to test.
        $api = new aws_api();
        $transcoder = new aws_elastic_transcoder($api->create_elastic_transcoder_client());
        $conversion = new \local_smartmedia\conversion($transcoder);
        $method = new ReflectionMethod('\local_smartmedia\conversion', 'generate_playlists');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($conversion, $files, $fileid);

        foreach ($result as $file) {
            if ($file->get_filename() == 'contenthash_mpegdash_playlist.mpd') {
                $this->assertEquals(1391, $file->get_itemid());
            } else if ($file->get_filename() == 'contenthash_hls_playlist.m3u8') {
                $this->assertEquals(1391, $file->get_itemid());
            } else {
                $this->assertEquals(0, $file->get_itemid());
            }
        }
    }

    /**
     * Test MPD playlist URL replacement.
     */
    public function test_replace_urls_mpd() {
        $this->resetAfterTest(true);

        $filecontent = $this->fixture['mpd_playlist_fixture'];
        $fileid = 1391;

        // Set up the method to test.
        $api = new aws_api();
        $transcoder = new aws_elastic_transcoder($api->create_elastic_transcoder_client());
        $conversion = new \local_smartmedia\conversion($transcoder);
        $method = new ReflectionMethod('\local_smartmedia\conversion', 'replace_urls');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($conversion, $filecontent, $fileid);

        $this->assertNotContains('media/0/13ed14cef7', $result);
        $this->assertContains('media/1391/13ed14cef7', $result);
        $this->assertNotContains('conversions/1351620000001-500030.fmp4', $result);
        $this->assertContains('conversions/13ed14cef757cd7797345cb76b30c3d83caf2513_1351620000001-500030.fmp4', $result);
        $this->assertNotContains('conversions/1351620000001-500050.fmp4', $result);
        $this->assertContains('conversions/13ed14cef757cd7797345cb76b30c3d83caf2513_1351620000001-500050.fmp4', $result);
    }

    /**
     * Test HLS playlist URL replacement.
     */
    public function test_replace_urls_hls() {
        $this->resetAfterTest(true);

        $filecontent = $this->fixture['hls_playlist_fixture'];
        $fileid = 1391;

        // Set up the method to test.
        $api = new aws_api();
        $transcoder = new aws_elastic_transcoder($api->create_elastic_transcoder_client());
        $conversion = new \local_smartmedia\conversion($transcoder);
        $method = new ReflectionMethod('\local_smartmedia\conversion', 'replace_urls');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($conversion, $filecontent, $fileid);

        $this->assertNotContains('media/0/13ed14cef7', $result);
        $this->assertContains('media/1391/13ed14cef7', $result);
        $this->assertNotContains('conversions/1351620000001-200015_v4.m3u8', $result);
        $this->assertContains('conversions/13ed14cef757cd7797345cb76b30c3d83caf2513_1351620000001-200015_v4.m3u8', $result);
        $this->assertNotContains('conversions/1351620000001-200045_v4.m3u8', $result);
        $this->assertContains('conversions/13ed14cef757cd7797345cb76b30c3d83caf2513_1351620000001-200045_v4.m3u8', $result);
    }

    /**
     * Test cleaning up source files in AWS.
     */
    public function test_cleanup_aws_files() {
        $this->resetAfterTest(true);

        $mockhandler = new MockHandler();

        // Result from deleting object from input bucket.
        $mockresult = new Result(array());
        $mockhandler->append($mockresult);

        // List objects result.
        $listobjects = $this->fixture['list_object_fixture'];
        $mockresult = new Result($listobjects);
        $mockhandler->append($mockresult);

        $mockresult = new Result(array());
        $mockhandler->append($mockresult);

        $filehash = '8f3d12e28ecb231852436d5c905d2a3e6ee8e119';

        // Set up the method to test.
        $api = new aws_api();
        $transcoder = new aws_elastic_transcoder($api->create_elastic_transcoder_client());
        $conversion = new \local_smartmedia\conversion($transcoder);
        $method = new ReflectionMethod('\local_smartmedia\conversion', 'cleanup_aws_files');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($conversion, $filehash, $mockhandler);

        $expected = array (
            array (
                'Key' => '8f3d12e28ecb231852436d5c905d2a3e6ee8e119/conversions/'
                . '8f3d12e28ecb231852436d5c905d2a3e6ee8e119_1351620000001-200015.ts',
            ),
            array (
                'Key' => '8f3d12e28ecb231852436d5c905d2a3e6ee8e119/conversions/'
                . '8f3d12e28ecb231852436d5c905d2a3e6ee8e119_1351620000001-200015_iframe.m3u8',
            ),
            array (
                'Key' => '8f3d12e28ecb231852436d5c905d2a3e6ee8e119/conversions/'
                . '8f3d12e28ecb231852436d5c905d2a3e6ee8e119_1351620000001-200015_v4.m3u8',
            ),
            array (
                'Key' => '8f3d12e28ecb231852436d5c905d2a3e6ee8e119/conversions/'
                . '8f3d12e28ecb231852436d5c905d2a3e6ee8e119_1351620000001-200045.ts',
            ),
            array (
                'Key' => '8f3d12e28ecb231852436d5c905d2a3e6ee8e119/conversions/'
                . '8f3d12e28ecb231852436d5c905d2a3e6ee8e119_1351620000001-200045_iframe.m3u8',
            ),
            array (
                'Key' => '8f3d12e28ecb231852436d5c905d2a3e6ee8e119/conversions/'
                . '8f3d12e28ecb231852436d5c905d2a3e6ee8e119_1351620000001-200045_v4.m3u8',
            ),
            array (
                'Key' => '8f3d12e28ecb231852436d5c905d2a3e6ee8e119/conversions/'
                . '8f3d12e28ecb231852436d5c905d2a3e6ee8e119_1351620000001-500030.fmp4',
            ),
            array (
                'Key' => '8f3d12e28ecb231852436d5c905d2a3e6ee8e119/conversions/'
                . '8f3d12e28ecb231852436d5c905d2a3e6ee8e119_1351620000001-500050.fmp4',
            ),
            array (
                'Key' => '8f3d12e28ecb231852436d5c905d2a3e6ee8e119/conversions/'
                . '8f3d12e28ecb231852436d5c905d2a3e6ee8e119_hls_playlist.m3u8',
            ),
            array (
                'Key' => '8f3d12e28ecb231852436d5c905d2a3e6ee8e119/conversions/'
                . '8f3d12e28ecb231852436d5c905d2a3e6ee8e119_mpegdash_playlist.mpd',
            ),
        );

        $this->assertEquals($expected, $result);
    }

    /**
     * Test string starts with helper function.
     */
    public function test_string_starts_with() {
        $this->resetAfterTest(true);

        $goodneedle = '8f3d12e28ecb231852436d5c905d2a3e6ee8e119';
        $badneedle = '8f3d12e28ecb232252436d5c905d2a3e6ee8e119';
        $haystack = '8f3d12e28ecb231852436d5c905d2a3e6ee8e119/conversions/';

        // Set up the method to test.
        $api = new aws_api();
        $transcoder = new aws_elastic_transcoder($api->create_elastic_transcoder_client());
        $conversion = new \local_smartmedia\conversion($transcoder);
        $method = new ReflectionMethod('\local_smartmedia\conversion', 'string_starts_with');
        $method->setAccessible(true); // Allow accessing of private method.
        $trueresult = $method->invoke($conversion, $haystack, $goodneedle);
        $falseresult = $method->invoke($conversion, $haystack, $badneedle);

        $this->assertTrue($trueresult);
        $this->assertFalse($falseresult);

    }

    /**
     * Test will convert.
     */
    public function test_will_convert() {
        global $DB, $CFG;
        $this->resetAfterTest(true);

        // Setup for testing.
        $fs = new file_storage();

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

        $href = moodle_url::make_pluginfile_url(
            $initialfilerecord['contextid'], $initialfilerecord['component'], $initialfilerecord['filearea'],
            $initialfilerecord['itemid'], $initialfilerecord['filepath'], $initialfilerecord['filename']);

        $api = new aws_api();
        $transcoder = new aws_elastic_transcoder($api->create_elastic_transcoder_client());
        $conversion = new \local_smartmedia\conversion($transcoder);

        // Set convert time to future to test convert correctly.
        set_config('convertfrom', -10, 'local_smartmedia');
        $willconvert = $conversion->will_convert($href);

        // No settings and no conversion record.
        $this->assertEquals($conversion::CONVERSION_NOT_FOUND, $willconvert);

        // No conversion record but settings say it is eligble for conversion.
        set_config('proactiveconversion', 1, 'local_smartmedia');
        set_config('convertfrom', 3628800, 'local_smartmedia');
        $willconvert = $conversion->will_convert($href);
        $this->assertEquals($conversion::CONVERSION_ACCEPTED, $willconvert);

        // Existing conversion record, so in progress.
        $DB->insert_record('local_smartmedia_conv', $conversionrecord);
        $willconvert = $conversion->will_convert($href);
        $this->assertEquals($conversion::CONVERSION_IN_PROGRESS, $willconvert);

    }

}
