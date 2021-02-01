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
 * Service client for getting AWS pricing information for the Transcribe service.
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
 * Service client for getting AWS pricing information for the Transcribe service.
 *
 * @package     local_smartmedia
 * @author      Peter Burnett <peterburnett@catalyst-au.net>
 * @copyright   2020 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class aws_transcribe_pricing_client extends aws_base_pricing_client {

    /**
     * aws_transcribe_pricing_client constructor.
     *
     * @param \Aws\Pricing\PricingClient $pricingclient the client for making Pricing List API Calls.
     */
    public function __construct(PricingClient $pricingclient) {
        parent::__construct($pricingclient);
        $this->servicecode = 'Transcribe';
    }

    /**
     * Get the pricing for a specific transcode location.
     *
     * @param string $region the region code of a location to get pricing for.
     *
     * @return location_transcribe_pricing $locationpricing object containing pricing.
     */
    public function get_location_pricing($region) {
        $locationpricing = new location_transcribe_pricing($region);

        // Filter products by location.
        $locationfilter = ['Field' => 'location', 'Type' => self::DEFAULT_TYPE, 'Value' => self::REGION_LOCATIONS[$region]];

        // Filter only Transcription job audio services.
        $transcribefilter = [
            'Field' => 'productFamily',
            'Type' => self::DEFAULT_TYPE,
            'Value' => 'Transcription Job'
        ];
        $products = $this->get_products([$locationfilter, $transcribefilter], 'transcribe');

        // Filter for the usagetype that has no extras (medical, custom models, etc...)
        foreach ($products as $product) {
            if (strpos($product->get_usagetype(), '-TranscribeAudio') !== false) {
                $locationpricing->set_transcribe_pricing($product->get_cost());
            }
        }

        return $locationpricing;
    }
}
