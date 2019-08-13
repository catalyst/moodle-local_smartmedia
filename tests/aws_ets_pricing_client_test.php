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
 * Unit test for \local_smartmedia\output\report_filter class.
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
use Aws\Pricing\PricingClient;
use Aws\Result;
use local_smartmedia\aws_ets_pricing_client;
use local_smartmedia\aws_ets_product;
use local_smartmedia\location_transcode_pricing;

/**
 * Unit test for \local_smartmedia\output\report_filter class.
 *
 * @package    local_smartmedia
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_smartmedia_aws_ets_pricing_client_testcase extends advanced_testcase {

    /**
     * @var array of json objects representing the expected API response from \Aws\Pricing\PricingClient::getProducts
     * for 'ServiceCode' = AmazonETS.
     */
    public $fixture;

    /**
     * @var string the AWS region to test against.
     */
    public $region;

    /**
     * @var string 'YYYY-MM-DD' date version of the AWS Pricing Client API version to test against.
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

        $this->region = 'us-east-1';
        $this->version = '2017-10-15';
        $this->apikey = 'ABCDEFGHIJKLMNO';
        $this->apisecret = '012345678910aBcDeFgHiJkLmNOpQrSTuVwXyZ';

        // Get our fixture representing a response from the AWS Price List API.
        $this->fixture = require($CFG->dirroot . '/local/smartmedia/tests/fixtures/aws_pricing_client_fixture.php');
    }

    /**
     * Create a mock of \local_smartmedia\aws_ets_pricing_client for injecting into \local_smartmedia\aws_ets_pricing_client.
     *
     * @param array $mockdata the mock data to use for result.
     *
     * @return array the api stub and expected result from calling get_pricing_client method on stub.
     */
    public function create_mock_pricing_client($mockdata) {
        // Inject our results fixture into the API dependency as a mock using a handler.
        $mockhandler = new MockHandler();
        $mockresult = new Result($mockdata);
        $mockhandler->append($mockresult);

        // Create the mock response Pricing Client.
        $mock = new PricingClient([
            'region' => $this->region,
            'version' => $this->version,
            'credentials' => ['key' => $this->apikey, 'secret' => $this->apisecret],
            'handler' => $mockhandler]);

        return [$mock, $mockresult];
    }

    /**
     * Test that we can get all products for the AWS Elastic Transcode Service.
     */
    public function test_get_products() {

        // Mock the pricing client so it returns fixture data.
        list($mock, $mockresult) = $this->create_mock_pricing_client($this->fixture['getProducts']);

        // Instantiate the class, injecting our mock.
        $pricingclient = new aws_ets_pricing_client($mock);
        $actual = $pricingclient->get_products();

        // Get the expected results from the fixture to compare.
        $expected = [];
        foreach ($mockresult->get('PriceList') as $product) {
            $expected[] = new aws_ets_product($product);
        }

        $this->assertEquals($expected, $actual);

    }

    /**
     * Test that we can get a description of the AmazonETS service.
     */
    public function test_describe_service() {

        // Mock the pricing client so it returns fixture data.
        list($mock, $mockresult) = $this->create_mock_pricing_client($this->fixture['describeServices']);

        // Instantiate the class, injecting our mock.
        $pricingservice = new aws_ets_pricing_client($mock);
        $actual = $pricingservice->describe_service();

        // Get the expected result from fixture.
        $services = $mockresult->get('Services');
        $service = reset($services);
        // Expect the result to be an object for ease of handling.
        $expected = (object) $service;

        $this->assertEquals($expected, $actual);
    }

    /**
     * Possible attribute names for test_get_attribute_values.
     */
    public function get_attributes_provider() {
        return [
            ['productFamily'],
            ['transcodingResult'],
            ['serviceCode'],
            ['termType'],
            ['usageType'],
            ['location'],
            ['videoResolution']
        ];
    }

    /**
     * Test that we can get all attribute values for pricing of AmazonETS.
     *
     * @dataProvider get_attributes_provider
     *
     * @param string $attribute the attribute to test getting values for.
     */
    public function test_get_attribute_values($attribute) {

        // Get the fixture for creating out mock.
        $fixture = $this->fixture['getAttributeValues'][$attribute];
        $apiresponse = json_decode($fixture, true);

        // Mock the pricing client so it returns fixture data.
        list($mock, $mockresult) = $this->create_mock_pricing_client($apiresponse);

        // Instantiate the class, injecting our stub.
        $pricingservice = new aws_ets_pricing_client($mock);
        $actual = $pricingservice->get_attribute_values($attribute);

        // Expect that we'll get all values in a single array.
        $expected = [];
        $values = $mockresult->get('AttributeValues');
        foreach ($values as $value) {
            $expected[] = $value['Value'];
        }
        $this->assertEquals($expected, $actual);
    }

    /**
     * Possible location names for test_get_location_pricing.
     */
    public function get_location_provider() {
        return [
            ['Any'],
            ['Asia Pacific (Mumbai)'],
            ['Asia Pacific (Singapore)'],
            ['Asia Pacific (Sydney)'],
            ['Asia Pacific (Tokyo)'],
            ['EU (Ireland)'],
            ['US East (N. Virginia)'],
            ['US West (N. California)'],
            ['US West (Oregon)']
        ];
    }

    /**
     * Test that we can get pricing for a specific location.
     *
     * @param string $location the location to test getting pricing for.
     *
     * @dataProvider get_location_provider
     */
    public function test_get_location_pricing($location) {

        // Mock the pricing client so it returns fixture data.
        list($mock, $mockresult) = $this->create_mock_pricing_client($this->fixture['getProducts']);

        // Instantiate the class, injecting our stub.
        $pricingservice = new aws_ets_pricing_client($mock);
        $actual = $pricingservice->get_location_pricing($location);

        $this->assertInstanceOf(location_transcode_pricing::class, $actual);

        // Should have a price for each product type, which may be zero.
        $this->assertNotNull($actual->get_sd_pricing());
        $this->assertNotNull($actual->get_hd_pricing());
        $this->assertNotNull($actual->get_audio_pricing());
    }

}