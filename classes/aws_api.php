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
 * API using the AWS PHP SDK to make service calls.
 *
 * @package     local_smartmedia
 * @author      Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright   2019 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_smartmedia;

use Aws\Credentials\Credentials;
use Aws\Pricing\PricingClient;

defined('MOODLE_INTERNAL') || die;

global $CFG;
// Autoload the SDK for AWS service usage.
require_once($CFG->dirroot . '/local/aws/sdk/aws-autoloader.php');

/**
 * API using the AWS PHP SDK to make service calls.
 *
 * @package     local_smartmedia
 * @author      Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright   2019 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class aws_api {

    /**
     * Region specifically for use with AWS Pricing List API.
     * (The AWS Pricing List API is only available to this region.)
     */
    const PRICING_CLIENT_REGION = 'us-east-1';

    /**
     * @var string AWS region to use for API calls.
     */
    private $region;

    /**
     * @var \Aws\Credentials\Credentials for accessing AWS services.
     */
    private $credentials;

    /**
     * aws_api constructor.
     *
     * @throws \dml_exception
     */
    public function __construct() {
        $this->region = get_config('local_smartmedia', 'api_region');
        $this->set_credentials(
            get_config('local_smartmedia', 'api_key'),
            get_config('local_smartmedia', 'api_secret'));
    }

    /**
     * Set credentials based on admin settings for signing AWS requests.
     *
     * @param string $apikey the Access key for AWS Security Credentials.
     * @param string $apisecret the Access key secret for AWS Security Credentials.
     */
    public function set_credentials(string $apikey, string $apisecret) {
        try {
            $credentials = new Credentials($apikey, $apisecret);
            $this->credentials = $credentials;
        } catch (\dml_exception $ex) {
            debugging('No api_key and/or api_secret setting found for local_smartmedia plugin: '
                . $ex->getMessage(), DEBUG_NORMAL);
        }
    }

    /**
     * Get the AWS Pricing Client for querying AWS Price List Service API.
     *
     * @param string $version the AWS Pricing Client version to use for API calls.
     *
     * @return \Aws\Pricing\PricingClient
     */
    public function get_pricing_client($version = '2017-10-15') : PricingClient {

        // Set up the minimum arguments required for client.
        $args = [
            'credentials' => $this->credentials,
            'region' => self::PRICING_CLIENT_REGION,
            'version' => $version,
        ];

        $client = new PricingClient($args);

        return $client;
    }
}