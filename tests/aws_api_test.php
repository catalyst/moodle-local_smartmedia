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
 * @author      Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright   2019 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
// Autoload the SDK for AWS service usage.
require_once($CFG->dirroot . '/local/aws/sdk/aws-autoloader.php');
require_once($CFG->libdir . '/moodlelib.php');

use local_smartmedia\aws_api;

/**
 * Unit test for \local_smartmedia\aws_api class.
 *
 * @package    local_smartmedia
 * @author      Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright   2019 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_smartmedia_aws_api_testcase extends advanced_testcase {

    public function setUp() {
        $this->resetAfterTest();
    }

    /**
     * Provider for test_get_credentials.
     *
     * @return array
     */
    public function credentials_provider() {
        return [
            'No accesskey:' => [
                '',
                '012345678910aBcDeFgHiJkLmNOpQrSTuVwXyZ'
            ],
            'No secret:' => [
                'ABCDEFGHIJKLMNO',
                ''
            ],
            'Accesskey and secret:' => [
                'ABCDEFGHIJKLMNO',
                '012345678910aBcDeFgHiJkLmNOpQrSTuVwXyZ'
            ]
        ];
    }

    /**
     * Test that we can set AWS credentials for use by the API.
     *
     * @param string $apikey the mock key for testing.
     * @param string $apisecret the mock secret for testing.
     *
     * @dataProvider credentials_provider
     * @throws \dml_exception
     * @throws \ReflectionException
     */
    public function test_set_credentials($apikey, $apisecret) {

        $api = new aws_api();
        $api->set_credentials($apikey, $apisecret);

        // We're testing the setting of a private instance variable, so we need to setup reflector magic.
        $reflector = new ReflectionClass('\local_smartmedia\aws_api');
        $property = $reflector->getProperty('credentials');
        $property->setAccessible(true); // Allow accessing of class property.
        $credentials = $property->getValue($api);

        $this->assertSame($credentials->getAccessKeyId(), $apikey);
        $this->assertSame($credentials->getSecretKey(), $apisecret);
    }

    /**
     * Test that we can get a pricing client for querying the AWS Price List Service API.
     *
     * @param string $apikey the mock key for testing.
     * @param string $apisecret the mock secret for testing.
     *
     * @dataProvider credentials_provider
     * @throws \dml_exception
     */
    public function test_create_pricing_client($apikey, $apisecret) {

        set_config('api_key', $apikey, 'local_smartmedia');
        set_config('api_secret', $apisecret, 'local_smartmedia');

        $api = new aws_api();
        $pricingclient = $api->create_pricing_client();
        $this->assertInstanceOf(\Aws\Pricing\PricingClient::class, $pricingclient);

        // Incorrect credentials should result in an AwsException when trying to use the client,
        // so check this is the case by trying to use an \Aws\Pricing\PricingClient method.
        $this->expectException(\Aws\Exception\AwsException::class);
        $pricingclient->describeServices();
    }
}