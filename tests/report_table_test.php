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
 * Unit test for \local_smartmedia\output\report_table class.
 *
 * @package    local_smartmedia
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/tablelib.php');
use local_smartmedia\output\report_table;

class local_smartmedia_report_table_testcase extends advanced_testcase {

    /**
     * Conversion rate for Standard Definition video.
     */
    const STANDARD_DEFINITION_PER_MINUTE = 0.017;

    /**
     * Conversion rate for High Definition video.
     */
    const HIGH_DEFINITION_PER_MINUTE = 0.034;

    public function setUp() {
        $this->resetAfterTest();
    }

    /**
     * Test that transcode cost is correctly calculated.
     */
    public function test_col_cost() {

        $url = new moodle_url("/local/smartmedia/report.php");
        $params = new stdClass();
        $params->download = '';

        $reporttable = new report_table('local_smartmedia', $url, $params);

        $row = new stdClass();
        // Get a random time between zero and 360 seconds.
        $row->duration = rand(0, 36000) / 100;

        // Audio transcode cost is correctly calculated.
        $row->width = 0;
        $expected = '$' . round($row->duration / 60 * self::STANDARD_DEFINITION_PER_MINUTE, 4);
        $this->assertEquals($expected, $reporttable->col_cost($row));

        // Standard Definition width video cost is correctly calculated.
        $row->width = 540;
        $expected = '$' . round($row->duration / 60 * self::STANDARD_DEFINITION_PER_MINUTE, 4);
        $this->assertEquals($expected, $reporttable->col_cost($row));

        // Exact HD resolution width video cost is correctly calculated.
        $row->width = 720;
        $expected = '$' . round($row->duration / 60 * self::HIGH_DEFINITION_PER_MINUTE ,4);
        $this->assertEquals($expected, $reporttable->col_cost($row));

        // High Definition resolution width video cost is correctly calculated.
        $row->width = 1080;
        $expected = '$' . round($row->duration / 60 * self::HIGH_DEFINITION_PER_MINUTE, 4);
        $this->assertEquals($expected, $reporttable->col_cost($row));
    }
}