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
        $record->duration = 599;
        $record->videostreams = 1;
        $record->audiostreams = 1;
        $record->height = 1080;
        $record->id = 1;

        // Setup pricing mock for test.
        $transcodelocationpricing = new \local_smartmedia\pricing\location_transcode_pricing('ap-southeast-2');
        $transcodelocationpricing->set_hd_pricing(0.034);
        $transcodelocationpricing->set_sd_pricing(0.017);
        $transcodelocationpricing->set_audio_pricing(0.00522);

        $mocktranscodepricing = $this->createMock(\local_smartmedia\pricing\aws_ets_pricing_client::class);
        $mocktranscodepricing->method('get_location_pricing')->willReturn($transcodelocationpricing);

        $rekoglocationpricing = new \local_smartmedia\pricing\location_rekog_pricing('ap-southeast-2');
        $rekoglocationpricing->set_face_detection_pricing(0.017);
        $rekoglocationpricing->set_label_detection_pricing(0.017);
        $rekoglocationpricing->set_content_moderation_pricing(0.017);
        $rekoglocationpricing->set_person_tracking_pricing(0.017);

        $mockrekogpricing = $this->createMock(\local_smartmedia\pricing\aws_rekog_pricing_client::class);
        $mockrekogpricing->method('get_location_pricing')->willReturn($rekoglocationpricing);

        $transcribelocationpricing = new \local_smartmedia\pricing\location_transcribe_pricing('ap-southeast-2');
        $transcribelocationpricing->set_transcribe_pricing(0.00125);

        $mocktranscribepricing = $this->createMock(\local_smartmedia\pricing\aws_transcribe_pricing_client::class);
        $mocktranscribepricing->method('get_location_pricing')->willReturn($transcribelocationpricing);

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
        $result = $method->invoke(
            $task,
            $mocktranscodepricing,
            $mockrekogpricing,
            $mocktranscribepricing,
            $mocktranscoder,
            $record
        ); // Get result of invoked method.

        $this->assertEquals('1.752', round($result, 3));
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
        $metadatarecord->duration = 599;
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

        // Setup pricing mocks for test.
        $transcodelocationpricing = new \local_smartmedia\pricing\location_transcode_pricing('ap-southeast-2');
        $transcodelocationpricing->set_hd_pricing(0.034);
        $transcodelocationpricing->set_sd_pricing(0.017);
        $transcodelocationpricing->set_audio_pricing(0.00522);

        $mocktranscodepricing = $this->createMock(\local_smartmedia\pricing\aws_ets_pricing_client::class);
        $mocktranscodepricing->method('get_location_pricing')->willReturn($transcodelocationpricing);

        $rekoglocationpricing = new \local_smartmedia\pricing\location_rekog_pricing('ap-southeast-2');
        $rekoglocationpricing->set_face_detection_pricing(0.017);
        $rekoglocationpricing->set_label_detection_pricing(0.017);
        $rekoglocationpricing->set_content_moderation_pricing(0.017);
        $rekoglocationpricing->set_person_tracking_pricing(0.017);

        $mockrekogpricing = $this->createMock(\local_smartmedia\pricing\aws_rekog_pricing_client::class);
        $mockrekogpricing->method('get_location_pricing')->willReturn($rekoglocationpricing);

        $transcribelocationpricing = new \local_smartmedia\pricing\location_transcribe_pricing('ap-southeast-2');
        $transcribelocationpricing->set_transcribe_pricing(0.00125);

        $mocktranscribepricing = $this->createMock(\local_smartmedia\pricing\aws_transcribe_pricing_client::class);
        $mocktranscribepricing->method('get_location_pricing')->willReturn($transcribelocationpricing);

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
        $method->invoke(
            $task,
            $mocktranscodepricing,
            $mockrekogpricing,
            $mocktranscribepricing,
            $mocktranscoder
        ); // Get result of invoked method.

        $records = $DB->get_records('local_smartmedia_report_over');
        $record = reset($records);

        $this->assertCount(1, $records);
        $this->assertEquals($contenthash, $record->contenthash);
        $this->assertEquals('Video', $record->type);
        $this->assertEquals('avi', $record->format);
        $this->assertEquals('1920 X 1080', $record->resolution);
        $this->assertEquals('599.000', $record->duration);
        $this->assertEquals('3900000', $record->filesize);
        $this->assertEquals('2.092', $record->cost);
        $this->assertEquals('Finished', $record->status);
        $this->assertEquals(3, $record->files);

    }

    /**
     * Test poplulating overview report.
     */
    public function test_get_total_converted_cost() {
        $this->resetAfterTest();
        global $DB;

        // Create report overview records.
        $reportrecord = new \stdClass();
        $reportrecord->contenthash = '8f3d12e28ecb231852436d5c905d2a3e6ee8e119';
        $reportrecord->type = 'Video';
        $reportrecord->format = 'avi';
        $reportrecord->resolution = '1280 x 720';
        $reportrecord->duration = 599;
        $reportrecord->filesize = 3900000;
        $reportrecord->cost = 1;
        $reportrecord->status = 'Finished';
        $reportrecord->files = 1;
        $reportrecord->timecreated = 1575095609;
        $reportrecord->timecompleted = 1575097299;

        $DB->insert_record('local_smartmedia_report_over', $reportrecord);

        $reportrecord = new \stdClass();
        $reportrecord->contenthash = '85be44230f22d78ec9187fbe3eb04ed4ae6d0807';
        $reportrecord->type = 'Video';
        $reportrecord->format = 'avi';
        $reportrecord->resolution = '1280 x 720';
        $reportrecord->duration = 599;
        $reportrecord->filesize = 3900000;
        $reportrecord->cost = 2;
        $reportrecord->status = 'Finished';
        $reportrecord->files = 1;
        $reportrecord->timecreated = 1575095609;
        $reportrecord->timecompleted = 1575097299;

        $DB->insert_record('local_smartmedia_report_over', $reportrecord);

        // We're testing a private method, so we need to setup reflector magic.
        $task = new \local_smartmedia\task\report_process();
        $method = new ReflectionMethod('\local_smartmedia\task\report_process', 'get_total_converted_cost');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($task); // Get result of invoked method.

        $this->assertEquals(3, $result);
    }

    /**
     * Test that total cost is correctly calculated.
     *
     */
    public function test_calculate_total_conversion_cost () {
        $this->resetAfterTest();

        global $DB, $CFG;
        set_config('api_region', 'ap-southeast-2', 'local_smartmedia');

        $fs = get_file_storage();
        set_config('convertfrom', 604800, 'local_smartmedia');
        $hdfilerec = array(
            'contextid' => 1461,
            'component' => 'mod_label',
            'filearea' => 'intro',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'video1.mp4',
            'timecreated' => 1575095000);

        $sdfilerec = array(
            'contextid' => 1461,
            'component' => 'mod_label',
            'filearea' => 'intro',
            'itemid' => 1,
            'filepath' => '/',
            'filename' => 'video2.mp4',
            'timecreated' => 1575095000);

        $audiofilerec = array(
            'contextid' => 1461,
            'component' => 'mod_label',
            'filearea' => 'intro',
            'itemid' => 2,
            'filepath' => '/',
            'filename' => 'video3.mp4',
            'timecreated' => 1575095000);

        // For this test it doesn't actually matter these are not real multimedia files.
        $hdfile = $fs->create_file_from_string($hdfilerec, 'I am the first video.');
        $sdfile = $fs->create_file_from_string($sdfilerec, 'I am the second video.');
        $audiofile = $fs->create_file_from_string($audiofilerec, 'I am the third video.');

        // Cheat and delete the folder records that are too recent.
        $DB->delete_records('files', ['filename' => '.', 'component' => 'mod_label']);

        // Create a high definition metadata record.
        $metadatarecord = new \stdClass();
        $metadatarecord->contenthash = $hdfile->get_contenthash();
        $metadatarecord->duration = 600;
        $metadatarecord->bitrate = 150000;
        $metadatarecord->size = 1000000;
        $metadatarecord->videostreams = 1;
        $metadatarecord->audiostreams = 1;
        $metadatarecord->width = 1920;
        $metadatarecord->height = 1080;
        $metadatarecord->metadata = '{}';
        $metadatarecord->timecreated = 1575095000;

        $DB->insert_record('local_smartmedia_data', $metadatarecord);

        // Create a standard definition file metadata record.
        $metadatarecord = new \stdClass();
        $metadatarecord->contenthash = $sdfile->get_contenthash();
        $metadatarecord->duration = 600;
        $metadatarecord->bitrate = 780000;
        $metadatarecord->size = 750000;
        $metadatarecord->videostreams = 1;
        $metadatarecord->audiostreams = 1;
        $metadatarecord->width = 960;
        $metadatarecord->height = 540;
        $metadatarecord->metadata = '{}';
        $metadatarecord->timecreated = 1575095000;

        $DB->insert_record('local_smartmedia_data', $metadatarecord);

        // Create an audio metadata record.
        $metadatarecord = new \stdClass();
        $metadatarecord->contenthash = $audiofile->get_contenthash();
        $metadatarecord->duration = 600;
        $metadatarecord->bitrate = 128001;
        $metadatarecord->size = 725240;
        $metadatarecord->videostreams = 0;
        $metadatarecord->audiostreams = 1;
        $metadatarecord->width = 0;
        $metadatarecord->height = 0;
        $metadatarecord->metadata = '{}';
        $metadatarecord->timecreated = 1575095000;

        $DB->insert_record('local_smartmedia_data', $metadatarecord);

        // Setup pricing mocks for test.
        $transcodelocationpricing = new \local_smartmedia\pricing\location_transcode_pricing('ap-southeast-2');
        $transcodelocationpricing->set_hd_pricing(0.034);
        $transcodelocationpricing->set_sd_pricing(0.017);
        $transcodelocationpricing->set_audio_pricing(0.00522);

        $mocktranscodepricing = $this->createMock(\local_smartmedia\pricing\aws_ets_pricing_client::class);
        $mocktranscodepricing->method('get_location_pricing')->willReturn($transcodelocationpricing);

        $rekoglocationpricing = new \local_smartmedia\pricing\location_rekog_pricing('ap-southeast-2');
        $rekoglocationpricing->set_face_detection_pricing(0.017);
        $rekoglocationpricing->set_label_detection_pricing(0.017);
        $rekoglocationpricing->set_content_moderation_pricing(0.017);
        $rekoglocationpricing->set_person_tracking_pricing(0.017);

        $mockrekogpricing = $this->createMock(\local_smartmedia\pricing\aws_rekog_pricing_client::class);
        $mockrekogpricing->method('get_location_pricing')->willReturn($rekoglocationpricing);

        $transcribelocationpricing = new \local_smartmedia\pricing\location_transcribe_pricing('ap-southeast-2');
        $transcribelocationpricing->set_transcribe_pricing(0.00125);

        $mocktranscribepricing = $this->createMock(\local_smartmedia\pricing\aws_transcribe_pricing_client::class);
        $mocktranscribepricing->method('get_location_pricing')->willReturn($transcribelocationpricing);

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
        $method = new ReflectionMethod('\local_smartmedia\task\report_process', 'calculate_total_conversion_cost');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke(
            $task,
            $mocktranscodepricing,
            $mockrekogpricing,
            $mocktranscribepricing,
            $mocktranscoder
        ); // Get result of invoked method.

        // Proactive conversion not enabled so we should get 0.
        $this->assertEquals(0, $result);

        set_config('proactiveconversion', '1', 'local_smartmedia');
        set_config('convertfrom', 3628800, 'local_smartmedia');
        $result = $method->invoke(
            $task,
            $mocktranscodepricing,
            $mockrekogpricing,
            $mocktranscribepricing,
            $mocktranscoder
        ); // Get result of invoked method.

        // Zero should be returned, as files are to old.
        $this->assertEquals(0, $result);

        set_config('convertfrom', 3144960000, 'local_smartmedia');
        $result = $method->invoke(
            $task,
            $mocktranscodepricing,
            $mockrekogpricing,
            $mocktranscribepricing,
            $mocktranscoder
        ); // Get result of invoked method.

        // Should now get a result.
        $this->assertEquals(3.0466, $result);

        // Add a conversion record.
        $conversionrecord = new \stdClass();
        $conversionrecord->contenthash = $hdfile->get_contenthash();
        $conversionrecord->pathnamehash = $hdfile->get_pathnamehash();
        $conversionrecord->status = 200;
        $conversionrecord->transcoder_status = 200;
        $conversionrecord->rekog_label_status = 200;
        $conversionrecord->rekog_moderation_status = 200;
        $conversionrecord->rekog_face_status = 200;
        $conversionrecord->rekog_person_status = 200;
        $conversionrecord->transcribe_status = 200;
        $conversionrecord->timecreated = time();
        $conversionrecord->timemodified = time();
        $id = $DB->insert_record('local_smartmedia_conv', $conversionrecord);

        $result = $method->invoke(
            $task,
            $mocktranscodepricing,
            $mockrekogpricing,
            $mocktranscribepricing,
            $mocktranscoder
        ); // Get result of invoked method.

        // Should now get a lesser result.
        $this->assertEquals(1.2944, $result);

        set_config('detectfaces', 1, 'local_smartmedia');
        set_config('detectmoderation', 1, 'local_smartmedia');
        set_config('detectlabels', 1, 'local_smartmedia');
        set_config('detectpeople', 1, 'local_smartmedia');
        set_config('transcribe', 1, 'local_smartmedia');

        $result = $method->invoke(
            $task,
            $mocktranscodepricing,
            $mockrekogpricing,
            $mocktranscribepricing,
            $mocktranscoder
        ); // Get result of invoked method.

        $this->assertEquals(3.4744, $result);
    }
}
