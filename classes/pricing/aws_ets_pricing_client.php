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

namespace local_smartmedia\pricing;

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
class aws_ets_pricing_client extends aws_base_pricing_client {
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
     * The string representing a successful transcoding result from a service.
     */
    const TRANSCODINGRESULT_SUCCESS = 'Success';

    public function __construct(PricingClient $pricingclient) {
        parent::__construct($pricingclient);
        $this->servicecode = 'AmazonETS';
    }

    /**
     * Get the pricing for a specific transcode location.
     *
     * @param string $region the region code of an AmazonETS location to get pricing for.
     *
     * @return location_transcode_pricing $locationpricing object containing pricing.
     */
    public function get_location_pricing($region) {
        $locationpricing = new location_transcode_pricing($region);

        // Filter products by location.
        $locationfilter = ['Field' => 'location', 'Type' => self::DEFAULT_TYPE, 'Value' => self::REGION_LOCATIONS[$region]];
        // Filter only working transcode services.
        $transcodingresultfilter = [
            'Field' => 'transcodingResult',
            'Type' => self::DEFAULT_TYPE,
            'Value' => self::TRANSCODINGRESULT_SUCCESS
        ];
        $products = $this->get_products([$locationfilter, $transcodingresultfilter], 'ets');

        foreach ($products as $product) {
            $productfamily = $product->get_productfamily();
            switch ($productfamily) {
                case self::MEDIATYPE_STANDARD_DEFINITION :
                    $locationpricing->set_sd_pricing($product->get_cost());
                    break;
                case self::MEDIATYPE_HIGH_DEFINITION :
                    $locationpricing->set_hd_pricing($product->get_cost());
                    break;
                default :
                    $locationpricing->set_audio_pricing($product->get_cost());
                    break;
            }
        }
        return $locationpricing;
    }
}
