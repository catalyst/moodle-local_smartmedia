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
 * Service client for getting AWS pricing information for the Rekognition service.
 *
 * @package     local_smartmedia
 * @author      Peter Burnett <peterburnett@catalyst-au.net>
 * @copyright   2020 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_smartmedia\pricing;

use Aws\Pricing\PricingClient;

defined('MOODLE_INTERNAL') || die;

global $CFG;
// Autoload the SDK for AWS service usage.
require_once($CFG->dirroot . '/local/aws/sdk/aws-autoloader.php');

/**
 * A client for getting AWS pricing information for the Rekognition service.
 *
 * @package     local_smartmedia
 * @author      Peter Burnett <peterburnett@catalyst-au.net>
 * @copyright   2020 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class aws_rekog_pricing_client extends aws_base_pricing_client {

    /**
     * aws_rekog_pricing_client constructor.
     *
     * @param \Aws\Pricing\PricingClient $pricingclient the client for making Pricing List API Calls.
     */
    public function __construct(PricingClient $pricingclient) {
        parent::__construct($pricingclient);
        $this->servicecode = 'AmazonRekognition';
    }

    /**
     * Get the pricing for a specific location.
     *
     * @param string $region the region code of a location to get pricing for.
     *
     * @return location_rekog_pricing $locationpricing object containing pricing.
     */
    public function get_location_pricing($region) {
        $locationpricing = new location_rekog_pricing($region);

        // Filter products by location.
        $locationfilter = ['Field' => 'location', 'Type' => self::DEFAULT_TYPE, 'Value' => self::REGION_LOCATIONS[$region]];

        // Filter only Rekognition video services.
        $rekogvideofilter = [
            'Field' => 'productFamily',
            'Type' => self::DEFAULT_TYPE,
            'Value' => 'Rekognition Video API - Archived Content'
        ];
        $products = $this->get_products([$locationfilter, $rekogvideofilter], 'rekog');

        foreach ($products as $product) {
            $desc = $product->get_description();
            if (strpos($desc, 'FaceDetection') !== false) {
                // AWS has a fixed rate for all rekog services leveraged.
                $locationpricing->set_face_detection_pricing($product->get_cost());
            }
            if (strpos($desc, 'ContentModeration') !== false) {
                // AWS has a fixed rate for all rekog services leveraged.
                $locationpricing->set_content_moderation_pricing($product->get_cost());
            }
            if (strpos($desc, 'PersonTracking') !== false) {
                // AWS has a fixed rate for all rekog services leveraged.
                $locationpricing->set_person_tracking_pricing($product->get_cost());
            }
            if (strpos($desc, 'LabelDetection') !== false) {
                // AWS has a fixed rate for all rekog services leveraged.
                $locationpricing->set_label_detection_pricing($product->get_cost());
            }
        }
        return $locationpricing;
    }
}
