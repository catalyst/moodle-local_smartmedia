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
 * Unit test for \local_smartmedia\location_transcode_pricing class.
 *
 * @package    local_smartmedia
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use local_smartmedia\location_transcode_pricing;

/**
 * Unit test for \local_smartmedia\location_transcode_pricing class.
 *
 * @package    local_smartmedia
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_smartmedia_location_transcode_pricing_testcase extends advanced_testcase {

    public function setUp() {
        $this->resetAfterTest();
    }

    /**
     * Data provider for test_calculate_transcode_cost.
     *
     * @return array
     */
    public function calculate_transcode_cost_provider() {
        return [
            'High Definition' => ['1080'],
            'Standard Definition' => ['540'],
            'Audio' => ['0'],
            'Not defined' => [null]
        ];
    }

    /**
     * Test transcode cost calculation.
     *
     * @param int $height the height of the resolution to test.
     *
     * @dataProvider calculate_transcode_cost_provider
     */
    public function test_calculate_transcode_cost($height) {

        $mockpricing = [
            'High Definition' => rand(0, 1),
            'Standard Definition' => rand(0, 1),
            'Audio' => rand(0, 1)
        ];

        $locationpricing = new location_transcode_pricing();
        $locationpricing->set_hd_pricing($mockpricing['High Definition']);
        $locationpricing->set_sd_pricing($mockpricing['Standard Definition']);
        $locationpricing->set_audio_pricing($mockpricing['Audio']);

        // Run the test multiple times with random duration.
        for ($i = 0; $i < 10; $i++) {
            // Test up to two hours in duration.
            $duration = rand(0, 7200);

            if ($height >= 720) {
                $expected = $duration / 60 * $mockpricing['High Definition'];
            } else if ($height > 0) {
                $expected = $duration / 60 * $mockpricing['Standard Definition'];
            } else {
                $expected = $duration / 60 * $mockpricing['Audio'];
            }
            $actual = $locationpricing->calculate_transcode_cost($height, $duration);

            $this->assertEquals($expected, $actual);
        }
    }
}
