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
            'High Definition - pricing set: correct calculation' => ['1080', '3600', '0.0035', '0.0017', '0.0008', 0.21],
            'Standard Definition - pricing set: correct calculation' => ['540', '3600', '0.0035', '0.0017', '0.0008', 0.102],
            'Audio - pricing set: correct calculation' => ['0', '3600', '0.0035', '0.0017', '0.0008', 0.048],
            'High Definition - pricing not set: null' => ['1080', '3600', null, '0.0017', '0.0008', null],
            'Standard Definition - pricing not set: null' => ['540', '3600', '0.0035', null, '0.0008', null],
            'Audio calculation - pricing not set: null' => ['0', '3600', '0.0035', '0.0017', null, null],
            'High Definition - no duration: zero cost' => ['1080', '0', '0.0035', '0.0017', '0.0008', 0],
            'Standard Definition - no duration: zero cost' => ['540', '0', '0.0035', '0.0017', '0.0008', 0],
            'Audio calculation - no duration: zero cost' => ['0', '0', '0.0035', '0.0017', '0.0008', 0],
            'High Definition - pricing is zero: zero cost' => ['1080', '3600', '0', '0', '0', 0],
            'Standard Definition - pricing is zero: zero cost' => ['540', '3600', '0', '0', '0', 0],
            'Audio calculation - pricing is zero: zero cost' => ['0', '3600', '0', '0', '0', 0],
        ];
    }

    /**
     * Test transcode cost calculation.
     *
     * @param int $height the height of the resolution to test.
     * @param int|float $duration duration in seconds.
     * @param float|null $hdpricing cost per minute for hd transcoding, null if pricing wasn't set.
     * @param float|null $sdpricing cost per minute for sd transcoding, null if pricing wasn't set.
     * @param float|null $audiopricing cost per minute for audio transcoding, null if pricing wasn't set.
     * @param float|null $expected the expected return value.
     *
     * @dataProvider calculate_transcode_cost_provider
     */
    public function test_calculate_transcode_cost($height, $duration, $hdpricing, $sdpricing, $audiopricing, $expected) {

        $locationpricing = new location_transcode_pricing('ap-southeast-2');
        if (!is_null($hdpricing)) {
            $locationpricing->set_hd_pricing($hdpricing);
        }
        if (!is_null($sdpricing)) {
            $locationpricing->set_sd_pricing($sdpricing);
        }
        if (!is_null($audiopricing)) {
            $locationpricing->set_audio_pricing($audiopricing);
        }

        $actual = $locationpricing->calculate_transcode_cost($height, $duration);

        if (is_null($expected)) {
            $this->assertNull($actual);
        } else {
            $this->assertEquals($expected, $actual);
        }
    }

    /**
     * Provider for test methods calculating costs for different location product types.
     *
     * @return array
     */
    public function calculate_cost_provider() {
        return [
            'No pricing set: null' => [null, 7200, null],
            'Valid pricing string: correct float answer' => ['0.035', '7200', 4.2],
            'Valid pricing float/int: correct float answer' => [0.035, 7200, 4.2]
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
