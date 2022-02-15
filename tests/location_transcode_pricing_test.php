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
 * @group      local_smartmedia
 */

use local_smartmedia\pricing\location_transcode_pricing;

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
     * Provider for test methods calculating costs for different location product types.
     *
     * @return array
     */
    public function calculate_cost_provider() {
        return [
            'No pricing set: null' => [null, 7200, null],
            'Valid pricing string: correct float answer' => ['0.035', '7200', 252],
            'Valid pricing float/int: correct float answer' => [0.035, 7200, 252]
        ];
    }

    /**
     * Test ability to calculate high definition cost.
     *
     * @param float|null $hdpricing the pricing per minute to use for test, null if no pricing set.
     * @param int|float $duration the duration in seconds to use for test.
     * @param float|null $expected the expected result.
     *
     * @dataProvider calculate_cost_provider
     */
    public function test_calculate_high_definition_cost($hdpricing, $duration, $expected) {

        $locationpricing = new location_transcode_pricing('ap-southeast-2');
        if (!is_null($hdpricing)) {
            $locationpricing->set_hd_pricing($hdpricing);
        }
        $actual = $locationpricing->calculate_high_definition_cost($duration);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test ability to calculate standard definition cost.
     *
     * @param float|null $sdpricing the pricing per minute to use for test, null if no pricing set.
     * @param int|float $duration the duration in seconds to use for test.
     * @param float|null $expected the expected result.
     *
     * @dataProvider calculate_cost_provider
     */
    public function test_calculate_standard_definition_cost($sdpricing, $duration, $expected) {

        $locationpricing = new location_transcode_pricing('ap-southeast-2');
        if (!is_null($sdpricing)) {
            $locationpricing->set_sd_pricing($sdpricing);
        }
        $actual = $locationpricing->calculate_standard_definition_cost($duration);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test ability to calculate standard definition cost.
     *
     * @param float|null $audiopricing the pricing per minute to use for test, null if no pricing set.
     * @param int|float $duration the duration in seconds to use for test.
     * @param float|null $expected the expected result.
     *
     * @dataProvider calculate_cost_provider
     */
    public function test_calculate_audio_cost($audiopricing, $duration, $expected) {

        $locationpricing = new location_transcode_pricing('ap-southeast-2');
        if (!is_null($audiopricing)) {
            $locationpricing->set_audio_pricing($audiopricing);
        }
        $actual = $locationpricing->calculate_audio_cost($duration);
        $this->assertEquals($expected, $actual);
    }
}
