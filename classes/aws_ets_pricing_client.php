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
 * Service client for getting AWS pricing information for the Elastic Transcode Services (ETS).
 *
 * @package     local_smartmedia
 * @author      Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright   2019 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_smartmedia;

use Aws\Pricing\PricingClient;

defined('MOODLE_INTERNAL') || die;

global $CFG;
// Autoload the SDK for AWS service usage.
require_once($CFG->dirroot . '/local/aws/sdk/aws-autoloader.php');

/**
 * A client for getting pricing information for AWS Elastic Transcode Services.
 *
 * @package     local_smartmedia
 * @author      Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright   2019 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class aws_ets_pricing_client {

    /**
     * The default filter field for getting AmazonETS pricing information.
     */
    const DEFAULT_FIELD = 'servicecode';

    /**
     * The default filter type used by AWS Pricing List API filters.
     */
    const DEFAULT_TYPE = 'TERM_MATCH';

    /**
     * The string represention an audio transcode service.
     */
    const MEDIATYPE_AUDIO = 'Audio';

    /**
     * The string represention a high definition transcode service (width >= 720).
     */
    const MEDIATYPE_HIGH_DEFINITION = 'High Definition';

    /**
     * The string represention a standard definition transcode service (width < 720).
     */
    const MEDIATYPE_STANDARD_DEFINITION = 'Standard Definition';

    /**
     * The ServiceCode for Amazon Elastic Transcode Services.
     */
    const SERVICE_CODE = 'AmazonETS';

    /**
     * @var \Aws\Pricing\PricingClient
     */
    private $pricingclient;


    /**
     * aws_ets_pricing_client constructor.
     *
     * @param \Aws\Pricing\PricingClient $pricingclient the client for making Pricing List API Calls.
     */
    public function __construct(PricingClient $pricingclient) {
        $this->pricingclient = $pricingclient;
    }

    /**
     * Default filters to get all Elastic Transcode Service products.
     *
     * @return array the default filter values for getting AWS Pricing List information.
     */
    private function get_default_product_filters() {
        return [
            [
                'Field' => self::DEFAULT_FIELD,
                'Type' => self::DEFAULT_TYPE,
                'Value' => self::SERVICE_CODE,
            ],
        ];
    }

    /**
     * Get all available Amazon Elastic Transcode Service products.
     *
     * @return array $products of \local_smartmedia\aws_ets_product.
     */
    public function get_products() {
        $params = [];
        // Ensure we are only looking for Amazon ETS services.
        $params['ServiceCode'] = self::SERVICE_CODE;
        $params['Filters'] = $this->get_default_product_filters();

        $result = $this->pricingclient->getProducts($params);
        $products = [];
        foreach ($result->get('PriceList') as $product) {
             $products[] = new aws_ets_product($product);
        }
        return $products;
    }

    /**
     * Get a description of this service and it's attributes.
     *
     * @return \stdClass $description object describing this service.
     */
    public function describe_service() {

        // Ensure we are only looking for Amazon ETS services.
        $params = ['ServiceCode' => self::SERVICE_CODE];

        $result = $this->pricingclient->describeServices($params);
        $services = $result->get('Services');
        $service = reset($services);
        $description = (object) $service;
        return $description;
    }

    /**
     * Get a list of attribute values.
     *
     * @param string $attributename the attribute to get value(s) for.
     *
     * @return array $attributevalues array of values.
     */
    public function get_attribute_values($attributename) {
        $attributevalues = [];

        // Set up the required parameters for the Pricing Client query.
        $params = [];
        $params['AttributeName'] = $attributename;
        // Ensure we are only looking for Amazon ETS services.
        $params['ServiceCode'] = self::SERVICE_CODE;

        $result = $this->pricingclient->getAttributeValues($params);
        $values = $result->get('AttributeValues');

        foreach ($values as $value) {
            $attributevalues[] = $value['Value'];
        }

        return $attributevalues;
    }

    /**
     * Get the pricing for a specific transcode location.
     *
     * @param string $location the name of an AmazonETS location to get pricing for.
     *
     * @return \local_smartmedia\location_transcode_pricing $locationpricing object containing pricing.
     */
    public function get_location_pricing($location) {
        $locationpricing = new location_transcode_pricing();

        $products = $this->get_products();
        foreach ($products as $product) {
            // We don't want pricing for failing transcode services.
            if ($product->get_transcodingresult() != 'Error' && $product->get_location() == $location) {
                $productfamily = $product->get_productfamily();
                switch ($productfamily) {
                    case self::MEDIATYPE_STANDARD_DEFINITION :
                        $locationpricing->set_sd_pricing($product->get_transcodecost());
                        break;
                    case self::MEDIATYPE_HIGH_DEFINITION :
                        $locationpricing->set_hd_pricing($product->get_transcodecost());
                        break;
                    default :
                        $locationpricing->set_audio_pricing($product->get_transcodecost());
                        break;
                }
            }
        }
        return $locationpricing;
    }
}
