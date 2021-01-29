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

use local_smartmedia\aws_ets_preset;
use local_smartmedia\pricing\location_transcode_pricing;
use local_smartmedia\pricing\location_rekog_pricing;
use local_smartmedia\pricing_calculator;

/**
 * Unit test for \local_smartmedia\location_transcode_pricing class.
 *
 * @package    local_smartmedia
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      local_smartmedia
 */
class local_smartmedia_pricing_calculator_testcase extends advanced_testcase {

    /**
     * @var array of json objects representing the expected API response from \Aws\ElasticTranscoder
     * for various method calls.
     */
    public $fixture;

    /**
     * @var string the AWS Elastic Transcoder presets to test against.
     */
    public $presets;

    /**
     * @var string the AWS region to test against.
     */
    public $region;

    /**
     * @var string 'YYYY-MM-DD' date version of the AWS Elastic Transcoder Client API version to test against.
     */
    public $version;

    /**
     * @var string the AWS API Key to test against.
     */
    public $apikey;

    /**
     * @var string the AWS API Secret to test against.
     */
    public $apisecret;

    public function setUp() {
        global $CFG;

        $this->resetAfterTest();

        // Plugin settings.
        $this->region = 'ap-southeast-2';
        $this->version = '2012-09-25';
        $this->apikey = 'ABCDEFGHIJKLMNO';
        $this->apisecret = '012345678910aBcDeFgHiJkLmNOpQrSTuVwXyZ';

        // Get our fixture representing a response from the AWS Elastic Transcoder API.
        $this->fixture = require($CFG->dirroot . '/local/smartmedia/tests/fixtures/pricing_calculator_fixture.php');

        // Build presets dependency from fixture.
        $presets = [];
        foreach ($this->fixture['readPreset'] as $preset) {
            $presets[] = new aws_ets_preset($preset['Preset']);
        }
        $this->presets = $presets;
    }

    /**
     * Data provider for test_calculate_transcode_cost.
     * @return array
     */
    public function calculate_transcode_cost_provider() {
        return [
            'High Definition input - 3 HD, 4 SD, 1 audio conversion' =>
                ['1080', '3600', 1, 1, '0.0035', '0.0017', '0.0008', (3 * 0.0035 + 4 * 0.0017 + 1 * 0.0008) * 3600 / 60],
            'Standard Definition input - 7 SD, 1 audio conversion' =>
                ['540', '3600', 1, 1, '0.0035', '0.0017', '0.0008', (0 * 0.0035 + 7 * 0.0017 + 1 * 0.0008) * 3600 / 60],
            'Audio input - 1 audio conversion' =>
                ['0', '3600', 0, 1, '0.0035', '0.0017', '0.0008', (0 * 0.0035 + 0 * 0.0017 + 1 * 0.0008) * 3600 / 60],
            'HD Video input only - 3 HD, 4 SD conversion' =>
                ['1080', '3600', 1, 0, '0.0035', '0.0017', '0.0008', (3 * 0.0035 + 4 * 0.0017 + 0 * 0.0008) * 3600 / 60],
            'SD Video input only - 7 SD conversion' =>
                ['540', '3600', 1, 0, '0.0035', '0.0017', '0.0008', (0 * 0.0035 + 7 * 0.0017 + 0 * 0.0008) * 3600 / 60],
            'High Definition input: no HD pricing - 4 SD, 1 audio conversion' =>
                ['1080', '3600', 1, 1, null, '0.0017', '0.0008', (0 * 0.0035 + 4 * 0.0017 + 1 * 0.0008) * 3600 / 60],
            'High Definition input: no SD pricing - 3 HD, 1 audio conversion' =>
                ['1080', '3600', 1, 1, '0.0035', null, '0.0008', (3 * 0.0035 + 0 * 0.0017 + 1 * 0.0008) * 3600 / 60],
            'High Definition input: no audio pricing - 3 HD, 4 SD conversion' =>
                ['1080', '3600', 1, 1, '0.0035', '0.0017', null, (3 * 0.0035 + 4 * 0.0017 + 0 * 0.0008) * 3600 / 60],
            'Standard Definition input: no HD pricing - 7 SD, 1 audio conversion' =>
                ['540', '3600', 1, 1, null, '0.0017', '0.0008', (0 * 0.0035 + 7 * 0.0017 + 1 * 0.0008) * 3600 / 60],
            'Standard Definition input: no SD pricing - 1 audio conversion' =>
                ['540', '3600', 1, 1, '0.0035', null, '0.0008', (0 * 0.0035 + 0 * 0.0017 + 1 * 0.0008) * 3600 / 60],
            'Standard Definition input: no audio pricing - 7 SD conversion' =>
                ['540', '3600', 1, 1, '0.0035', '0.0017', null, (0 * 0.0035 + 7 * 0.0017 + 0 * 0.0008) * 3600 / 60],
            'Audio input: no HD pricing - 1 audio conversion' =>
                ['0', '3600', 0, 1, null, '0.0017', '0.0008', (0 * 0.0035 + 0 * 0.0017 + 1 * 0.0008) * 3600 / 60],
            'Audio input: no SD pricing - 1 audio conversion' =>
                ['0', '3600', 0, 1, '0.0035', null, '0.0008', (0 * 0.0035 + 0 * 0.0017 + 1 * 0.0008) * 3600 / 60],
            'Audio input: no audio pricing - no conversion' =>
                ['0', '3600', 0, 1, '0.0035', '0.0017', null, 0],
            'HD Video only: no HD pricing - 4 SD conversion' =>
                ['1080', '3600', 1, 0, null, '0.0017', '0.0008', (0 * 0.0035 + 4 * 0.0017 + 0 * 0.0008) * 3600 / 60],
            'HD Video only input: no SD pricing - 3 HD conversion' =>
                ['1080', '3600', 1, 0, '0.0035', null, '0.0008', (3 * 0.0035 + 0 * 0.0017 + 0 * 0.0008) * 3600 / 60],
            'HD Video only input: no audio pricing - 3 HD, 4 SD conversion' =>
                ['1080', '3600', 1, 0, '0.0035', '0.0017', null, (3 * 0.0035 + 4 * 0.0017 + 0 * 0.0008) * 3600 / 60],
            'SD Video only: no HD pricing - 7 SD conversion' =>
                ['1080', '3600', 1, 0, null, '0.0017', '0.0008', (0 * 0.0035 + 4 * 0.0017 + 0 * 0.0008) * 3600 / 60],
            'SD Video only input: no SD pricing - no conversion' =>
                ['0', '3600', 1, 0, '0.0035', null, '0.0008', 0],
            'SD Video only input: no audio pricing - 7 SD conversion' =>
                ['0', '3600', 1, 0, '0.0035', '0.0017', null, 0],
            'High Definition input: no duration - zero cost' =>
                ['1080', '0', 1, 1, '0.0035', '0.0017', '0.0008', 0],
            'Standard Definition input: no duration - zero cost' =>
                ['540', '0', 1, 1, '0.0035', '0.0017', '0.0008', 0],
            'Audio calculation input: no duration - zero cost' =>
                ['0', '0', 0, 1, '0.0035', '0.0017', '0.0008', 0],
            'High Definition input: pricing is zero - zero cost' =>
                ['1080', '3600', 1, 1, '0', '0', '0', 0],
            'Standard Definition input: pricing is zero - zero cost' =>
                ['540', '3600', 1, 1, '0', '0', '0', 0],
            'Audio calculation input: pricing is zero - zero cost' =>
                ['0', '3600', 0, 1, '0', '0', '0', 0],
        ];
    }

    /**
     * Test transcode cost calculation.
     *
     * @param int $height the height of the resolution to test.
     * @param int|float $duration duration in seconds.
     * @param int $videostreams count of video streams file has.
     * @param int $audiostreams count of audio streams file has.
     * @param float|null $hdpricing cost per minute for hd transcoding, null if pricing wasn't set.
     * @param float|null $sdpricing cost per minute for sd transcoding, null if pricing wasn't set.
     * @param float|null $audiopricing cost per minute for audio transcoding, null if pricing wasn't set.
     * @param float|null $expected the expected return value.
     *
     * @dataProvider calculate_transcode_cost_provider
     */
    public function test_calculate_transcode_cost($height, $duration, $videostreams, $audiostreams, $hdpricing,
                                                  $sdpricing, $audiopricing, $expected) {

        // Setup the location pricing for dependency injection.
        $transcodelocationpricing = new location_transcode_pricing('ap-southeast-2');
        if (!is_null($hdpricing)) {
            $transcodelocationpricing->set_hd_pricing($hdpricing);
        }
        if (!is_null($sdpricing)) {
            $transcodelocationpricing->set_sd_pricing($sdpricing);
        }
        if (!is_null($audiopricing)) {
            $transcodelocationpricing->set_audio_pricing($audiopricing);
        }
        $rekoglocationpricing = new location_rekog_pricing('ap-southeast-2');

        $pricingcalculator = new pricing_calculator($transcodelocationpricing, $rekoglocationpricing, $this->presets);
        $actual = $pricingcalculator->calculate_transcode_cost($height, $duration, $videostreams, $audiostreams);

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test that calculate transcode costs will null if preset ids aren't in admin settings.
     */
    public function test_calculate_transcode_cost_no_presets() {

        // Setup the location pricing for dependency injection.
        $locationpricing = new location_transcode_pricing('ap-southeast-2');
        $rekogpricing = new location_rekog_pricing('ap-southeast-2');

        // Instantiate the class with no presets.
        $pricingcalculator = new pricing_calculator($locationpricing, $rekogpricing);
        $actual = $pricingcalculator->calculate_transcode_cost(rand(0, 1080), rand(0, 3600));

        $this->assertNull($actual);
    }

}
