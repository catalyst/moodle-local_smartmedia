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

use local_smartmedia\aws_ets_preset;
use local_smartmedia\location_transcode_pricing;
use local_smartmedia\output\report_summary;
use local_smartmedia\pricing_calculator;

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

    /**
     * @var array of json objects representing the expected API response from \Aws\ElasticTranscoder
     * for various method calls.
     */
    public $fixture;

    /**
     * @var string the AWS region to test against.
     */
    public $region;

    public function setUp() {
        global $CFG;

        $this->resetAfterTest();

        $this->region = 'ap-southeast-2';
        // Get our fixture representing a response from the AWS Elastic Transcoder API.
        $this->fixture = require($CFG->dirroot . '/local/smartmedia/tests/fixtures/aws_elastic_transcoder_fixture.php');
    }

    /**
     * Provider for test_calculate_total_cost.
     *
     * @return array of test data.
     */
    public function calculate_total_cost_provider() {
        return [
            'HD, SD and audio content:' => [60, 0.0035, 60, 0.0017, 60, 0.0008],
            'No HD content:' => [0, 0.0035, 60, 0.0017, 60, 0.0008],
            'No SD content:' => [60, 0.0035, 0, 0.0017, 60, 0.0008],
            'No audio content:' => [60, 0.0035, 60, 0.0017, 0, 0.0008],
            'No HD or SD content:' => [0, 0.0035, 0, 0.0017, 60, 0.0008],
            'No SD or audio content:' => [60, 0.0035, 0, 0.0017, 0, 0.0008],
            'No HD or audio content:' => [0, 0.0035, 60, 0.0017, 0, 0.0008],
            'No content:' => [0, 0.0035, 0, 0.0017, 0, 0.0008],
        ];
    }

    /**
     * Test that total cost is correctly calculated.
     *
     * @param int|float $hdduration length of high definition content in test DB.
     * @param float $hdpricing cost per minute for high definition transcoding.
     * @param int|float $sdduration length of standard definition content in test DB.
     * @param float $sdpricing cost per minute for standard definition transcoding.
     * @param int|float $audioduration length of audio content in test DB.
     * @param float $audiopricing cost per minute for audio transcoding.
     *
     * @throws \ReflectionException
     * @throws \dml_exception
     * @dataProvider calculate_total_cost_provider
     */
    public function test_calculate_total_cost ($hdduration, $hdpricing, $sdduration, $sdpricing, $audioduration, $audiopricing) {
        global $DB;

        // Create presets (pricing calculator dependency) from fixture data.
        $presets = [];
        foreach ($this->fixture['readPreset'] as $apiresult) {
            $preset = $apiresult['Preset'];
            $presets[] = new aws_ets_preset($preset);
        }

        // Create location pricing (pricing calculator dependency).
        $locationpricing = new location_transcode_pricing($this->region);
        $locationpricing->set_hd_pricing($hdpricing);
        $locationpricing->set_sd_pricing($sdpricing);
        $locationpricing->set_audio_pricing($audiopricing);

        // Create the pricing calculator dependency.
        $pricingcalculator = new pricing_calculator($locationpricing, $presets);

        // Add records for various media types to database for testing.
        if (!empty($hdduration)) {
            // Create a high definition metadata record.
            $metadatarecord = new \stdClass();
            $metadatarecord->contenthash = '353e7803284d4735030e079a8047bc4e6e3fdf47';
            $metadatarecord->duration = $hdduration;
            $metadatarecord->bitrate = 150000;
            $metadatarecord->size = 1000000;
            $metadatarecord->videostreams = 1;
            $metadatarecord->audiostreams = 1;
            $metadatarecord->width = 1920;
            $metadatarecord->height = 1080;
            $metadatarecord->metadata = '{}';
            $DB->insert_record('local_smartmedia_data', $metadatarecord);
        }

        if (!empty($sdduration)) {
            // Create a standard definition file metadata record.
            $metadatarecord = new \stdClass();
            $metadatarecord->contenthash = '3f51b74477d9c6c23fd363fec4de4be021785663';
            $metadatarecord->duration = $sdduration;
            $metadatarecord->bitrate = 780000;
            $metadatarecord->size = 750000;
            $metadatarecord->videostreams = 1;
            $metadatarecord->audiostreams = 1;
            $metadatarecord->width = 960;
            $metadatarecord->height = 540;
            $metadatarecord->metadata = '{}';
            $DB->insert_record('local_smartmedia_data', $metadatarecord);
        }

        if (!empty($audioduration)) {
            // Create an audio metadata record.
            $metadatarecord = new \stdClass();
            $metadatarecord->contenthash = '01ebfc70983b8a0ee2b4fa090d1f17ef90eca708';
            $metadatarecord->duration = $audioduration;
            $metadatarecord->bitrate = 128001;
            $metadatarecord->size = 725240;
            $metadatarecord->videostreams = 0;
            $metadatarecord->audiostreams = 1;
            $metadatarecord->width = 0;
            $metadatarecord->height = 0;
            $metadatarecord->metadata = '{}';
            $DB->insert_record('local_smartmedia_data', $metadatarecord);
        }

        $reportsummary = new report_summary($pricingcalculator, $this->region);

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_smartmedia\output\report_summary', 'calculate_total_cost');
        $method->setAccessible(true); // Allow accessing of private method.
        $actual = $method->invoke($reportsummary); // Get result of invoked method.

        $expected = $pricingcalculator->calculate_transcode_cost(LOCAL_SMARTMEDIA_MINIMUM_HD_HEIGHT, $hdduration);
        $expected += $pricingcalculator->calculate_transcode_cost(LOCAL_SMARTMEDIA_MINIMUM_SD_HEIGHT, $sdduration);
        $expected += $pricingcalculator->calculate_transcode_cost(LOCAL_SMARTMEDIA_AUDIO_HEIGHT, $audioduration);

        $this->assertEquals($expected, $actual);
    }
}