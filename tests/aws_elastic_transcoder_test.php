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
 * Unit test for \local_smartmedia\aws_elastic_transcoder class.
 *
 * @package    local_smartmedia
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
// Autoload the SDK for AWS service usage.
require_once($CFG->dirroot . '/local/aws/sdk/aws-autoloader.php');

use Aws\MockHandler;
use Aws\ElasticTranscoder\ElasticTranscoderClient;
use Aws\Result;
use local_smartmedia\aws_elastic_transcoder;
use local_smartmedia\aws_ets_preset;

/**
 * Unit test for \local_smartmedia\aws_elastic_transcoder class.
 *
 * @package    local_smartmedia
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      local_smartmedia
 */
class local_smartmedia_aws_elastic_transcoder_testcase extends advanced_testcase {

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
        // Presets used in aws_elastic_transcoder_fixture.
        $this->presets = '1351620000001-000001, 1351620000001-000020, 1351620000001-300010';

        // Get our fixture representing a response from the AWS Elastic Transcoder API.
        $this->fixture = require($CFG->dirroot . '/local/smartmedia/tests/fixtures/aws_elastic_transcoder_fixture.php');
    }

    /**
     * Create a mock of \Aws\ElasticTranscoderClient for injecting into \local_smartmedia\aws_elastic_transcoder.
     *
     * @param array $fixtures array of mock data to use for results of api calls.
     *
     * @return array the api stub and expected result from calling get_pricing_client method on stub.
     */
    public function create_mock_elastic_transcoder_client(array $fixtures) {
        // Inject our results fixture into the API dependency as a mock using a handler.
        $mockhandler = new MockHandler();
        $mockresults = [];
        foreach ($fixtures as $fixture) {
            $mockresult = new Result($fixture);
            $mockresults[] = $mockresult;
            $mockhandler->append($mockresult);
        }

        // Create the mock response Pricing Client.
        $mock = new ElasticTranscoderClient([
            'region' => $this->region,
            'version' => $this->version,
            'credentials' => ['key' => $this->apikey, 'secret' => $this->apisecret],
            'handler' => $mockhandler]);

        return [$mock, $mockresults];
    }

    /**
     * Test that we can get presets as aws_ets_preset instances.
     */
    public function test_get_presets() {

        // Mock the elastic transcoder client so it returns fixture data presets.
        list($mock, $mockresults) = $this->create_mock_elastic_transcoder_client($this->fixture['readPreset']);

        // Instantiate the class, injecting our mock.
        $pricingclient = new aws_elastic_transcoder($mock);
        $actual = $pricingclient->get_presets($this->presets);

        // Get the expected results from the fixture to compare.
        $expected = [];
        foreach ($mockresults as $mockresult) {
            $preset = $mockresult->get('Preset');
            $expected[] = new aws_ets_preset($preset);
        }

        $this->assertEquals($expected, $actual);
    }

}