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
use local_smartmedia\location_transcode_pricing;
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
        $this->fixture = require($CFG->dirroot . '/local/smartmedia/tests/fixtures/aws_elastic_transcoder_fixture.php');

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
            'High Definition input - 1 HD, 1 SD, 1 audio conversion' =>
                ['1080', '3600', '0.0035', '0.0017', '0.0008', 0.21 + 0.102 + 0.048],
            'Standard Definition input - 2 SD, 1 audio conversion' =>
                ['540', '3600', '0.0035', '0.0017', '0.0008', 2 * 0.102 + 0.048],
            'Audio input - 3 audio conversion' =>
                ['0', '3600', '0.0035', '0.0017', '0.0008', 3 * 0.048],
            'High Definition input: no HD pricing - 1 SD, 1 audio conversion' =>
                ['1080', '3600', null, '0.0017', '0.0008', 0.102 + 0.048],
            'High Definition input: no SD pricing - 1 HD, 1 audio conversion' =>
                ['1080', '3600', '0.0035', null, '0.0008', 0.21 + 0.048],
            'High Definition input: no audio pricing - 1 HD, 1 SD conversion' =>
                ['1080', '3600', '0.0035', '0.0017', null, 0.21 + 0.102],
            'Standard Definition input: no HD pricing - 2 SD, 1 audio conversion' =>
                ['540', '3600', null, '0.0017', '0.0008', 2 * 0.102 + 0.048],
            'Standard Definition input: no SD pricing - 1 audio conversion' =>
                ['540', '3600', '0.0035', null, '0.0008', 0.048],
            'Standard Definition input: no audio pricing - 2 SD conversion' =>
                ['540', '3600', '0.0035', '0.0017', null, 2 * 0.102],
            'Audio input: no HD pricing - 3 audio conversion' =>
                ['0', '3600', null, '0.0017', '0.0008', 3 * 0.048],
            'Audio input: no SD pricing - 3 audio conversion' =>
                ['0', '3600', '0.0035', null, '0.0008', 3 * 0.048],
            'Audio input: no audio pricing - no conversion' =>
                ['0', '3600', '0.0035', '0.0017', null, 0],
            'High Definition input: no duration - zero cost' =>
                ['1080', '0', '0.0035', '0.0017', '0.0008', 0],
            'Standard Definition input: no duration - zero cost' =>
                ['540', '0', '0.0035', '0.0017', '0.0008', 0],
            'Audio calculation input: no duration - zero cost' =>
                ['0', '0', '0.0035', '0.0017', '0.0008', 0],
            'High Definition input: pricing is zero - zero cost' =>
                ['1080', '3600', '0', '0', '0', 0],
            'Standard Definition input: pricing is zero - zero cost' =>
                ['540', '3600', '0', '0', '0', 0],
            'Audio calculation input: pricing is zero - zero cost' =>
                ['0', '3600', '0', '0', '0', 0],
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

        // Setup the location pricing for dependency injection.
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

        $pricingcalculator = new pricing_calculator($locationpricing, $this->presets);
        $actual = $pricingcalculator->calculate_transcode_cost($height, $duration);

        $this->assertEquals($expected, $actual);
    }

}
