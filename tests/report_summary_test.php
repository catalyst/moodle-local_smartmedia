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
 * Unit test for \local_smartmedia\output\report_summary class.
 *
 * @package    local_smartmedia
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
// Autoload the SDK for AWS service usage.
require_once($CFG->dirroot . '/local/aws/sdk/aws-autoloader.php');

/**
 * Unit test for \local_smartmedia\output\report_summary class.
 *
 * @package    local_smartmedia
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      local_smartmedia
 */
class local_smartmedia_report_summary_testcase extends advanced_testcase {

    public function setUp() {
        $this->resetAfterTest();
    }

    /**
     * Test getting file totals used in the files sumamry chart
     */
    public function test_get_file_summary_totals () {
        global $DB;

        // Setup the dta required for the test.
        $record1 = new \stdClass();
        $record1->name = 'totalfiles';
        $record1->value = 100;

        $record2 = new \stdClass();
        $record2->name = 'videofiles';
        $record2->value = 50;

        $record3 = new \stdClass();
        $record3->name = 'audiofiles';
        $record3->value = 30;

        $dataobjects = array($record1, $record2, $record3);

        // Get a class instance without invoking the constructor,
        // this allows us to skip a lot of setup.
        $builder = $this->getMockBuilder('\local_smartmedia\output\report_summary');
        $builder->disableOriginalConstructor();
        $stub = $builder->getMock();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_smartmedia\output\report_summary', 'get_file_summary_totals');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke($stub); // Get result of invoked method.

        // Should be an empty array as there are no records in DB.
        $this->assertEmpty($proxy);

        // Add the records.
        $DB->insert_records('local_smartmedia_reports', $dataobjects);
        $proxy = $method->invoke($stub);

        $this->assertEquals(($record1->value - ($record2->value + $record3->value)), $proxy[0]);
        $this->assertEquals($record2->value, $proxy[1]);
        $this->assertEquals($record3->value, $proxy[2]);

    }
}