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
 * Unit tests for local_smartmedia utility class.
 *
 * @package    local_smartmedia
 * @copyright  2019 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for local_smartmedia utility class.
 *
 * @package    local_smartmedia
 * @copyright  2019 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      local_smartmedia
 */
class local_smartmedia_utility_testcase extends advanced_testcase {

    /**
     * Test for updating arg array..
     */
    public function test_update_args() {
        $inputargs = array(
            '8',
            '8f3d12e28ecb231852436d5c905d2a3e6ee8e119',
            'conversions',
            'pluginfile.php',
            '1',
            'local_smartmedia',
            'media',
            '8',
            '8f3d12e28ecb231852436d5c905d2a3e6ee8e119',
            'conversions',
            '8f3d12e28ecb231852436d5c905d2a3e6ee8e119_1351620000001-200045_v4.m3u8'
        );
        $utility = new \local_smartmedia\utility();

        $updatedargs = $utility->update_args($inputargs);

        $this->assertCount(4, $updatedargs);
        $this->assertNotContains('pluginfile.php', $updatedargs);
    }
}
