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
 * Unit test for local_smartmedia task classes.
 *
 * @package    local_smartmedia
 * @copyright  2019 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Unit test for local_smartmedia extract metadata classes.
 *
 * @package    local_smartmedia
 * @copyright  2019 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      local_smartmedia
 */
class local_smartmedia_report_process_testcase extends advanced_testcase {

    /**
     * Test getting start file id.
     */
    public function test_update_report_data() {
        global $DB;

        $this->resetAfterTest();

        $name = 'totalfiles';
        $value = 64;

        $task = new \local_smartmedia\task\report_process();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_smartmedia\task\report_process', 'update_report_data');
        $method->setAccessible(true); // Allow accessing of private method.
        $method->invoke($task, $name, $value); // Get result of invoked method.
        $method->invoke($task, $name, $value); // Get result of invoked method.

        $record = $DB->get_record('local_smartmedia_reports', array('name' => $name));

        $this->assertEquals($name, $record->name);
        $this->assertEquals($value, $record->value);
    }

    /**
     * Test getting file type.
     */
    public function test_get_file_type() {
        $this->resetAfterTest();

        // Create an existing file metadata record.
        $record = new \stdClass();
        $record->contenthash = '8f3d12e28ecb231852436d5c905d2a3e6ee8e119';
        $record->videostreams = 1;
        $record->audiostreams = 1;

        // We're testing a private method, so we need to setup reflector magic.
        $task = new \local_smartmedia\task\report_process();
        $method = new ReflectionMethod('\local_smartmedia\task\report_process', 'get_file_type');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($task, $record); // Get result of invoked method.

        $this->assertEquals('Video', $result);
    }

    /**
     * Test getting file transcoding cost.
     */
    public function test_get_file_cost() {
        $this->resetAfterTest();
        global $CFG;

        // Create an existing file metadata record.
        $record = new \stdClass();
        $record->contenthash = '8f3d12e28ecb231852436d5c905d2a3e6ee8e119';
        $record->duration = 3.123456;
        $record->videostreams = 1;
        $record->audiostreams = 1;
        $record->height = 1080;

        // Setup pricing mock for test.
        $locationpricing = new \local_smartmedia\location_transcode_pricing('ap-southeast-2');
        $locationpricing->set_hd_pricing(3);
        $locationpricing->set_sd_pricing(2);
        $locationpricing->set_audio_pricing(1);

        $mockpricing = $this->createMock(\local_smartmedia\aws_ets_pricing_client::class);
        $mockpricing->method('get_location_pricing')->willReturn($locationpricing);

        // Get our fixture representing a response from the AWS Elastic Transcoder API.
        $this->fixture = require($CFG->dirroot . '/local/smartmedia/tests/fixtures/pricing_calculator_fixture.php');

        // Build presets dependency from fixture.
        $presets = [];
        foreach ($this->fixture['readPreset'] as $preset) {
            $presets[] = new \local_smartmedia\aws_ets_preset($preset['Preset']);
        }

        $mocktranscoder = $this->createMock(\local_smartmedia\aws_elastic_transcoder::class);
        $mocktranscoder->method('get_presets')->willReturn($presets);

        // We're testing a private method, so we need to setup reflector magic.
        $task = new \local_smartmedia\task\report_process();
        $method = new ReflectionMethod('\local_smartmedia\task\report_process', 'get_file_cost');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($task, $mockpricing, $mocktranscoder, $record); // Get result of invoked method.

        $this->assertEquals('0.937', round($result, 3));
    }

    /**
     * Test getting file type.
     */
    public function test_get_file_status() {
        $this->resetAfterTest();

        // Create an existing file metadata record.
        $code = 200;

        // We're testing a private method, so we need to setup reflector magic.
        $task = new \local_smartmedia\task\report_process();
        $method = new ReflectionMethod('\local_smartmedia\task\report_process', 'get_file_status');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($task, $code); // Get result of invoked method.

        $this->assertEquals('Finished', $result);
    }

    /**
     * Test getting file count.
     */
    public function test_get_file_count() {
        $this->resetAfterTest();

        // Setup the files for testing.
        $fs = new file_storage();
        $filecontent = 'some content to put into the file';

        $filerecord1 = array(
            'contextid' => 31,
            'component' => 'mod_forum',
            'filearea' => 'attachment',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'myfile1.txt');

        $file1 = $fs->create_file_from_string($filerecord1, $filecontent);

        $filerecord2 = array(
            'contextid' => 1386,
            'component' => 'mod_folder',
            'filearea' => 'content',
            'itemid' => 2,
            'filepath' => '/',
            'filename' => 'myfile2.txt');

        $fs->create_file_from_string($filerecord2, $filecontent);

        $filerecord3 = array(
            'contextid' => 1386,
            'component' => 'mod_folder',
            'filearea' => 'content',
            'itemid' => 45,
            'filepath' => '/a/b/c/',
            'filename' => 'myfile3.txt');

        $fs->create_file_from_string($filerecord3, $filecontent);

        $contenthash = $file1->get_contenthash();;

        // We're testing a private method, so we need to setup reflector magic.
        $task = new \local_smartmedia\task\report_process();
        $method = new ReflectionMethod('\local_smartmedia\task\report_process', 'get_file_count');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($task, $contenthash); // Get result of invoked method.

        $this->assertEquals(3, $result);
    }

    /**
     * Test poplulating overview report.
     */
    public function test_process_overview_report() {
        $this->resetAfterTest();
        global $DB, $CFG;
        set_config('api_region', 'ap-southeast-2', 'local_smartmedia');

        // Setup the files for testing.
        $fs = new file_storage();
        $filecontent = 'some content to put into the file';

        $filerecord1 = array(
            'contextid' => 31,
            'component' => 'mod_forum',
            'filearea' => 'attachment',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'myfile1.txt');

        $file1 = $fs->create_file_from_string($filerecord1, $filecontent);

        $filerecord2 = array(
            'contextid' => 1386,
            'component' => 'mod_folder',
            'filearea' => 'content',
            'itemid' => 2,
            'filepath' => '/',
            'filename' => 'myfile2.txt');

        $fs->create_file_from_string($filerecord2, $filecontent);

        $filerecord3 = array(
            'contextid' => 1386,
            'component' => 'mod_folder',
            'filearea' => 'content',
            'itemid' => 45,
            'filepath' => '/a/b/c/',
            'filename' => 'myfile3.txt');

        $fs->create_file_from_string($filerecord3, $filecontent);

        $contenthash = $file1->get_contenthash();
        $pathnamehash = $file1->get_pathnamehash();

        // Create an existing file metadata record.
        $metadatarecord = new \stdClass();
        $metadatarecord->contenthash = $contenthash;
        $metadatarecord->pathnamehash = $pathnamehash;
        $metadatarecord->duration = 3.123456;
        $metadatarecord->bitrate = 1000;
        $metadatarecord->size = 3900000;
        $metadatarecord->videostreams = 1;
        $metadatarecord->audiostreams = 1;
        $metadatarecord->width = 1920;
        $metadatarecord->height = 1080;
        $metadatarecord->metadata = '{"formatname": "avi"}';

        $DB->insert_record('local_smartmedia_data', $metadatarecord);

        // Create existing conversion record.
        $conversionrecord = new \stdClass();
        $conversionrecord->contenthash = $contenthash;
        $conversionrecord->pathnamehash = $pathnamehash;
        $conversionrecord->status = 200;
        $conversionrecord->transcoder_status = 200;
        $conversionrecord->rekog_label_status = 404;
        $conversionrecord->rekog_moderation_status = 200;
        $conversionrecord->rekog_face_status = 404;
        $conversionrecord->rekog_person_status = 200;
        $conversionrecord->timecreated = time();
        $conversionrecord->timemodified = time();

        $DB->insert_record('local_smartmedia_conv', $conversionrecord);

        // Setup pricing mock for test.
        $locationpricing = new \local_smartmedia\location_transcode_pricing('ap-southeast-2');
        $locationpricing->set_hd_pricing(3);
        $locationpricing->set_sd_pricing(2);
        $locationpricing->set_audio_pricing(1);

        $mockpricing = $this->createMock(\local_smartmedia\aws_ets_pricing_client::class);
        $mockpricing->method('get_location_pricing')->willReturn($locationpricing);

        // Get our fixture representing a response from the AWS Elastic Transcoder API.
        $this->fixture = require($CFG->dirroot . '/local/smartmedia/tests/fixtures/pricing_calculator_fixture.php');

        // Build presets dependency from fixture.
        $presets = [];
        foreach ($this->fixture['readPreset'] as $preset) {
            $presets[] = new \local_smartmedia\aws_ets_preset($preset['Preset']);
        }

        $mocktranscoder = $this->createMock(\local_smartmedia\aws_elastic_transcoder::class);
        $mocktranscoder->method('get_presets')->willReturn($presets);

        // We're testing a private method, so we need to setup reflector magic.
        $task = new \local_smartmedia\task\report_process();
        $method = new ReflectionMethod('\local_smartmedia\task\report_process', 'process_overview_report');
        $method->setAccessible(true); // Allow accessing of private method.
        $method->invoke($task, $mockpricing, $mocktranscoder); // Get result of invoked method.

        $records = $DB->get_records('local_smartmedia_report_over');
        $record = reset($records);

        $this->assertCount(1, $records);
        $this->assertEquals($contenthash, $record->contenthash);
        $this->assertEquals('Video', $record->type);
        $this->assertEquals('avi', $record->format);
        $this->assertEquals('1920 X 1080', $record->resolution);
        $this->assertEquals('3.123', $record->duration);
        $this->assertEquals('3.900', $record->filesize);
        $this->assertEquals('0.937', $record->cost);
        $this->assertEquals('Finished', $record->status);
        $this->assertEquals(3, $record->files);

    }
}
