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
 * Unit tesst for local_smartmedia ffprobe class.
 *
 * @package    local_smartmedia
 * @copyright  2019 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tesst for local_smartmedia ffprobe class.
 *
 * @package    local_smartmedia
 * @copyright  2019 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      local_smartmedia
 */
class local_smartmedia_ffprobe_testcase extends advanced_testcase {

    /**
     * @var array Fixtures used in this test.
     */
    public $fixture;

    public function setUp() {
        $this->resetAfterTest();
        global $CFG;

        // Allow setting of FFProbe via Env Var or define
        // to cater for mulitiple test setups.
        $pathtoffprobe = getenv('TEST_LOCAL_SMARTMEDIA_FFPROBE');

        if (!$pathtoffprobe && defined('TEST_LOCAL_SMARTMEDIA_FFPROBE')) {
            $pathtoffprobe = TEST_LOCAL_SMARTMEDIA_FFPROBE;
        }

        set_config('pathtoffprobe', (string)$pathtoffprobe, 'local_smartmedia');

        // Get fixture for tests.
        $this->fixture = require($CFG->dirroot . '/local/smartmedia/tests/fixtures/ffprobe_test_fixture.php');
    }

    /**
     * Test get media metadata method.
     */
    public function test_get_media_metadata() {
        global $CFG;

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

        $ffprobe = new \local_smartmedia\ffprobe();
        $metadata = $ffprobe->get_media_metadata($file);

        $this->assertEquals('success', $metadata['status']);
    }

    /**
     * Test decoding FFProbe results.
     */
    public function test_decode_ffprobe_json() {
        // Disable the class contructor for this test.
        $builder = $this->getMockBuilder('\local_smartmedia\ffprobe');
        $builder->disableOriginalConstructor();
        $stub = $builder->getMock();

        $resultobject = json_decode($this->fixture['fulloutput']);

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_smartmedia\ffprobe', 'decode_ffprobe_json');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke($stub, $resultobject); // Get result of invoked method.

        $this->assertEquals('success', $proxy['status']);
        $this->assertEquals(2, $proxy['data']['totalstreams']);
        $this->assertEquals(1, $proxy['data']['totalvideostreams']);
        $this->assertEquals(1, $proxy['data']['totalaudiostreams']);
        $this->assertEquals('h264', $proxy['data']['videostreams'][0]['codecname']);
        $this->assertEquals('aac', $proxy['data']['audiostreams'][0]['codecname']);
    }

    /**
     * Test decoding FFProbe results for a partial response.
     * Some files do not return all the data we are after, we need to handle this case.
     */
    public function test_decode_ffprobe_json_partial() {
        // Disable the class contructor for this test.
        $builder = $this->getMockBuilder('\local_smartmedia\ffprobe');
        $builder->disableOriginalConstructor();
        $stub = $builder->getMock();

        $resultobject = json_decode($this->fixture['partialoutput']);

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_smartmedia\ffprobe', 'decode_ffprobe_json');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke($stub, $resultobject); // Get result of invoked method.

        $this->assertEquals('success', $proxy['status']);
        $this->assertEquals(1, $proxy['data']['totalstreams']);
        $this->assertEquals(0, $proxy['data']['totalvideostreams']);
        $this->assertEquals(1, $proxy['data']['totalaudiostreams']);
        $this->assertEquals('opus', $proxy['data']['audiostreams'][0]['codecname']);
        $this->assertEquals(0, $proxy['data']['audiostreams'][0]['bitrate']);
        $this->assertEquals(0, $proxy['data']['duration']);
        $this->assertEquals(0, $proxy['data']['bitrate']);
    }

}
