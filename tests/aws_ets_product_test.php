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
 * Unit test for \local_smartmedia\aws_ets_product class.
 *
 * @package    local_smartmedia
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use local_smartmedia\pricing\aws_ets_product;

/**
 * Unit test for \local_smartmedia\aws_ets_product class.
 *
 * @package    local_smartmedia
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      local_smartmedia
 */
class local_smartmedia_aws_ets_product_testcase extends advanced_testcase {

    /**
     * @var array of json objects representing the expected API response from \Aws\Pricing\PricingClient::getProducts
     * for 'ServiceCode' = AmazonETS.
     */
    public $fixture;

    public function setUp() {
        global $CFG;

        $this->resetAfterTest();

        // Get our fixture representing a response from the AWS Price List API.
        $this->fixture = require($CFG->dirroot . '/local/smartmedia/tests/fixtures/aws_pricing_client_fixture.php');
    }

    /**
     * Get the expected transcode cost from a product fixture.
     *
     * @param array $fixture json encoded string of the product from fixture.
     *
     * @return float $expected value for product transcode cost.
     */
    public function get_expected_transcodecost_from_fixture($fixture) {
        $fixturearray = json_decode($fixture, true);
        $terms = $fixturearray['terms']['OnDemand'];
        $termspricing = reset($terms);
        $pricingdimension = reset($termspricing['priceDimensions']);
        $expected = $pricingdimension['pricePerUnit']['USD'];

        return $expected;
    }

    /**
     * Test that transcode cost is set correctly when aws_ets_product is constructed.
     */
    public function test_set_transcodecost() {

        foreach ($this->fixture['getProducts']['PriceList'] as $rawproduct) {
            $product = new aws_ets_product($rawproduct);
            $actual = $product->get_cost();
            $expected = $this->get_expected_transcodecost_from_fixture($rawproduct);

            $this->assertEquals($expected, $actual);
        }
    }
}
