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

use local_smartmedia\task\report_process;
use local_smartmedia\pricing\location_transcode_pricing;
use local_smartmedia\pricing\aws_ets_pricing_client;
use local_smartmedia\pricing\location_rekog_pricing;
use local_smartmedia\pricing\aws_rekog_pricing_client;
use local_smartmedia\pricing\location_transcribe_pricing;
use local_smartmedia\pricing\aws_transcribe_pricing_client;
use local_smartmedia\aws_ets_preset;
use local_smartmedia\aws_elastic_transcoder;

/**
 * Unit test for local_smartmedia extract metadata classes.
 *
 * @package    local_smartmedia
 * @copyright  2019 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      local_smartmedia
 */
final class report_process_test extends advanced_testcase {
    /**
     * @var mixed $fixture
     */
    protected $fixture;

    /**
     * Test getting start file id.
     */
    public function test_update_report_data(): void {
        global $DB;

        $this->resetAfterTest();

        $name = 'totalfiles';
        $value = 64;

        $task = new report_process();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_smartmedia\task\report_process', 'update_report_data');
        $method->setAccessible(true); // Allow accessing of private method.
        $method->invoke($task, $name, $value); // Get result of invoked method.
        $method->invoke($task, $name, $value); // Get result of invoked method.

        $record = $DB->get_record('local_smartmedia_reports', ['name' => $name]);

        $this->assertEquals($name, $record->name);
        $this->assertEquals($value, $record->value);
    }

    /**
     * Test getting file type.
     */
    public function test_get_file_type(): void {
        $this->resetAfterTest();

        // Create an existing file metadata record.
        $record = new stdClass();
        $record->contenthash = '8f3d12e28ecb231852436d5c905d2a3e6ee8e119';
        $record->videostreams = 1;
        $record->audiostreams = 1;

        // We're testing a private method, so we need to setup reflector magic.
        $task = new report_process();
        $method = new ReflectionMethod('\local_smartmedia\task\report_process', 'get_file_type');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($task, $record); // Get result of invoked method.

        $this->assertEquals('Video', $result);
    }

    /**
     * Test getting file type.
     */
    public function test_get_file_status(): void {
        $this->resetAfterTest();

        // Create an existing file metadata record.
        $code = 200;

        // We're testing a private method, so we need to setup reflector magic.
        $task = new report_process();
        $method = new ReflectionMethod('\local_smartmedia\task\report_process', 'get_file_status');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($task, $code); // Get result of invoked method.

        $this->assertEquals('Finished', $result);
    }

    /**
     * Test getting file count.
     */
    public function test_get_file_count(): void {
        $this->resetAfterTest();

        // Setup the files for testing.
        $fs = new file_storage();
        $filecontent = 'some content to put into the file';

        $filerecord1 = [
            'contextid' => 31,
            'component' => 'mod_forum',
            'filearea' => 'attachment',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'myfile1.txt'];

        $file1 = $fs->create_file_from_string($filerecord1, $filecontent);

        $filerecord2 = [
            'contextid' => 1386,
            'component' => 'mod_folder',
            'filearea' => 'content',
            'itemid' => 2,
            'filepath' => '/',
            'filename' => 'myfile2.txt'];

        $fs->create_file_from_string($filerecord2, $filecontent);

        $filerecord3 = [
            'contextid' => 1386,
            'component' => 'mod_folder',
            'filearea' => 'content',
            'itemid' => 45,
            'filepath' => '/a/b/c/',
            'filename' => 'myfile3.txt'];

        $fs->create_file_from_string($filerecord3, $filecontent);

        $contenthash = $file1->get_contenthash();
        ;

        // We're testing a private method, so we need to setup reflector magic.
        $task = new report_process();
        $method = new ReflectionMethod('\local_smartmedia\task\report_process', 'get_file_count');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($task, $contenthash); // Get result of invoked method.

        $this->assertEquals(3, $result);
    }

    /**
     * Test poplulating overview report.
     */
    public function test_get_total_converted_cost(): void {
        $this->resetAfterTest();
        global $DB;

        // Create report overview records.
        $reportrecord = new stdClass();
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

        $reportrecord = new stdClass();
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
        $task = new report_process();
        $method = new ReflectionMethod('\local_smartmedia\task\report_process', 'get_total_converted_cost');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($task); // Get result of invoked method.

        $this->assertEquals(3, $result);
    }
}
