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
        $proxy = $method->invoke($task, $name, $value); // Get result of invoked method.
        $proxy = $method->invoke($task, $name, $value); // Get result of invoked method.

        $record = $DB->get_record('local_smartmedia_reports', array('name' => $name));

        $this->assertEquals($name, $record->name);
        $this->assertEquals($value, $record->value);
    }
}
