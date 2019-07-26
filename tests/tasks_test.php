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
 * Unit tesst for local_smartmedia convserion class.
 *
 * @package    local
 * @subpackage smartmedia
 * @copyright  2019 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


class local_smartmedia_tasks_testcase extends advanced_testcase {

    /**
     *
     */
    function test_execute() {
        $this->resetAfterTest(true);
        global $DB;

        $record = new \stdClass();
        $record->id = 1;
        $record->fileid = 1;
        $record->duration = 3.123;
        $record->bitrate = 1000;
        $record->videostreams = 1;
        $record->audiostreams = 1;
        $record->width = 1920;
        $record->height = 1080;
        $record->metadata = '{}';

        $task = new \local_smartmedia\task\extract_metadata();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_smartmedia\task\extract_metadata', 'get_start_id');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke($task); // Get result of invoked method.

        // Initial result should be zero as there are no records yet.
        $this->assertEquals(0, $proxy);

        $id = $DB->insert_record('local_smartmedia_data', $record);
        $proxy = $method->invoke($task); // Get result of invoked method.

        $this->assertEquals($id, $proxy);
    }

}
