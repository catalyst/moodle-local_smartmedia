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
 * An AWS Elastic Transcode Service product.
 *
 * @package     local_smartmedia
 * @author      Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright   2019 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_smartmedia\pricing;

defined('MOODLE_INTERNAL') || die;

/**
 * An AWS Elastic Transcode Service product.
 *
 * @package     local_smartmedia
 * @author      Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright   2019 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class aws_ets_product extends aws_base_product {

    /**
     * @var string enumerated value of 'Error' if this product fails transcoding or
     * 'Success' if this product passes transcoding tests currently.
     */
    private $transcodingresult;

    /**
     * aws_product constructor.
     *
     * @param string $rawproduct json encoded raw product.
     */
    public function __construct($rawproduct) {
        $productobject = json_decode($rawproduct);
        $this->transcodingresult = $productobject->product->attributes->transcodingResult;
        parent::__construct($rawproduct);
    }

    /**
     * Set the transcode cost for this product based on AWS API data.
     *
     * @param object $productobject json decoded raw product from AWS Pricing List API.
     * @param string $terms the pricing terms to use in determining transcode cost.
     */
    protected function set_cost($productobject, $terms = 'OnDemand') : void {
        // Get the product terms as an array to make it easier to handle.
        $terms = json_decode(json_encode($productobject->terms->$terms), true);

        // There should only be one set of pricing for each set of terms, so use the first.
        $termspricing = reset($terms);
        // Each term should only have one price dimension as well.
        $pricingdimension = reset($termspricing['priceDimensions']);

        // Always use US Dollars as our baseline for costing.
        $transcodecost = $pricingdimension['pricePerUnit']['USD'];

        $this->cost = $transcodecost;
    }

    /**
     * Get the transcoding result for this product.
     *
     * @return string enumerated value of 'Error' if this product fails transcoding or
     * 'Success' if this product passes transcoding currently.
     */
    public function get_transcodingresult() {
        return $this->transcodingresult;
    }
}