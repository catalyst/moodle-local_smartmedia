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
 * Unit test for local_smartmedia task classes.
 *
 * @package    local_smartmedia
 * @copyright  2019 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      local_smartmedia
 */
class local_smartmedia_tasks_testcase extends advanced_testcase {

    public function setUp() {
        $this->resetAfterTest();

        // Allow setting of FFProbe via Env Var or define
        // to cater for mulitiple test setups.
        $pathtoffprobe = getenv('TEST_LOCAL_SMARTMEDIA_FFPROBE');

        if (!$pathtoffprobe && defined('TEST_LOCAL_SMARTMEDIA_FFPROBE')) {
            $pathtoffprobe = TEST_LOCAL_SMARTMEDIA_FFPROBE;
        }

        set_config('pathtoffprobe', (string)$pathtoffprobe, 'local_smartmedia');
    }

    /**
     * Test getting start file id.
     */
    public function test_get_start_id() {
        $task = new \local_smartmedia\task\extract_metadata();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_smartmedia\task\extract_metadata', 'get_start_id');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke($task); // Get result of invoked method.

        // Initial result should be zero as there are no records yet.
        $this->assertEquals(0, $proxy);

        set_config('startfileid', 1, 'local_smartmedia');
        $proxy = $method->invoke($task); // Get result of invoked method.

        $this->assertEquals(1, $proxy);
    }

    /**
     * Test getting supported mime types.
     */
    public function test_get_supported_mime_types() {
        $task = new \local_smartmedia\task\extract_metadata();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_smartmedia\task\extract_metadata', 'get_supported_mime_types');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke($task, false); // Get result of invoked method.

        $this->assertTrue(is_array($proxy));

        $proxy = $method->invoke($task, true); // Get result of invoked method.
        $this->assertTrue(is_string($proxy));
    }

    /**
     * Test get files to process method.
     */
    public function test_get_files_to_process() {
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

        // Create an existing file metadata record.
        $metadatarecord = new \stdClass();
        $metadatarecord->contenthash = $file1->get_contenthash();
        $metadatarecord->pathnamehash = $file1->get_pathnamehash();
        $metadatarecord->duration = 3.123;
        $metadatarecord->bitrate = 1000;
        $metadatarecord->size = 390;
        $metadatarecord->videostreams = 1;
        $metadatarecord->audiostreams = 1;
        $metadatarecord->width = 1920;
        $metadatarecord->height = 1080;
        $metadatarecord->metadata = '{}';

        $DB->insert_record('local_smartmedia_data', $metadatarecord);

        $task = new \local_smartmedia\task\extract_metadata();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_smartmedia\task\extract_metadata', 'get_files_to_process');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke($task); // Get result of invoked method.

        $this->assertArrayNotHasKey($file1->get_id(), $proxy);
        $this->assertArrayHasKey($file2->get_id(), $proxy);
        $this->assertArrayHasKey($file3->get_id(), $proxy);

    }

    /**
     * Test get files to process method.
     */
    public function test_process_files() {
        global $CFG, $DB;

        // Skip if no valid FFProbe executable.
        if (get_config('local_smartmedia', 'pathtoffprobe') == '') {
            $this->markTestSkipped('Test skipped as no valid FFProbe executable set');
        }

        // Setup for testing.
        $fs = new file_storage();
        $filerecord = array(
            'contextid' => 1461,
            'component' => 'mod_label',
            'filearea' => 'intro',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'SampleVideo1mb.mp4');
        $pathname = $CFG->dirroot . '/local/smartmedia/tests/fixtures/SampleVideo1mb.mp4';

        $file = $fs->create_file_from_pathname($filerecord, $pathname);
        $fileobject = new \stdClass();
        $fileobject->pathnamehash = $file->get_pathnamehash();
        $fileobject->contenthash = $file->get_contenthash();
        $filehashes = array(
            $file->get_pathnamehash() => $fileobject
        );

        $task = new \local_smartmedia\task\extract_metadata();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_smartmedia\task\extract_metadata', 'process_files');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke($task, $filehashes); // Get result of invoked method.

        $this->assertEquals(1, $proxy['successcount']);
        $this->assertEquals(0, $proxy['failcount']);

        $metadatarecord = $DB->get_record('local_smartmedia_data', array('contenthash' => $file->get_contenthash()));
        $this->assertEquals(1280, $metadatarecord->width);
    }

    /**
     * Test getting metadata entries to remove.
     */
    public function test_get_files_to_remove() {
        global $DB;
        $contenthash = 'aaaaaaaaaaaaaaaa3255bfef95601890afd80709';

        // Create an existing file metadata record.
        $metadatarecord = new \stdClass();
        $metadatarecord->contenthash = $contenthash;
        $metadatarecord->duration = 3.123;
        $metadatarecord->bitrate = 1000;
        $metadatarecord->size = 390;
        $metadatarecord->videostreams = 1;
        $metadatarecord->audiostreams = 1;
        $metadatarecord->width = 1920;
        $metadatarecord->height = 1080;
        $metadatarecord->metadata = '{}';

        $DB->insert_record('local_smartmedia_data', $metadatarecord);

        $task = new \local_smartmedia\task\extract_metadata();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_smartmedia\task\extract_metadata', 'get_files_to_remove');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke($task); // Get result of invoked method.

        $this->assertEquals($metadatarecord->contenthash, $proxy[$contenthash]->contenthash);
    }

    /**
     * Test removing metadata entries.
     */
    public function test_remove_metadata_records() {
        global $DB;
        $contenthash = 'aaaaaaaaaaaaaaaa3255bfef95601890afd80709';

        // Create an existing file metadata record.
        $metadatarecord = new \stdClass();
        $metadatarecord->contenthash = $contenthash;
        $metadatarecord->duration = 3.123;
        $metadatarecord->bitrate = 1000;
        $metadatarecord->size = 390;
        $metadatarecord->videostreams = 1;
        $metadatarecord->audiostreams = 1;
        $metadatarecord->width = 1920;
        $metadatarecord->height = 1080;
        $metadatarecord->metadata = '{}';

        $DB->insert_record('local_smartmedia_data', $metadatarecord);

        $task = new \local_smartmedia\task\extract_metadata();

        $metaobj = new \stdClass();
        $metaobj->contenthash = 'aaaaaaaaaaaaaaaa3255bfef95601890afd80709';
        $toremove = array(
            'aaaaaaaaaaaaaaaa3255bfef95601890afd80709' => $metaobj
        );

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_smartmedia\task\extract_metadata', 'remove_metadata_records');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke($task, $toremove); // Get result of invoked method.

        $result = $DB->record_exists('local_smartmedia_data', array('contenthash' => 'aaaaaaaaaaaaaaaa3255bfef95601890afd80709'));

        $this->assertFalse($result);
    }

}
