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

namespace local_smartmedia;

defined('MOODLE_INTERNAL') || die;

/**
 * An AWS Elastic Transcode Service product.
 *
 * @package     local_smartmedia
 * @author      Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright   2019 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class aws_ets_product {

    /**
     * @var string unique identifier of product.
     */
    private $productid;

    /**
     * @var string enumerated value out of ['High Definition', 'Standard Definition', 'Audio']
     */
    private $productfamily;

    /**
     * @var string enumerated value of 'Error' if this product fails transcoding or
     * 'Success' if this product passes transcoding tests currently.
     */
    private $transcodingresult;

    /**
     * @var string the AWS location of this product.
     */
    private $location;

    /**
     * @var string the AWS Service Code of this product.
     */
    private $servicecode;

    /**
     * @var float|int the cost per minute of this product.
     */
    private $transcodecost;

    /**
     * aws_product constructor.
     *
     * @param string $rawproduct json encoded raw product.
     */
    public function __construct($rawproduct) {
        $productobject = json_decode($rawproduct);
        $this->productid = $productobject->product->sku;
        $this->productfamily = $productobject->product->productFamily;
        $this->transcodingresult = $productobject->product->attributes->transcodingResult;
        $this->location = $productobject->product->attributes->location;
        $this->servicecode = $productobject->serviceCode;
        $this->set_transcodecost($productobject);

    }

    /**
     * Set the transcode cost for this product based on AWS API data.
     *
     * @param object $productobject json decoded raw product from AWS Pricing List API.
     * @param string $terms the pricing terms to use in determining transcode cost.
     */
    private function set_transcodecost($productobject, $terms = 'OnDemand') : void {
        // Get the product terms as an array to make it easier to handle.
        $terms = json_decode(json_encode($productobject->terms->$terms), true);

        // There should only be one set of pricing for each set of terms, so use the first.
        $termspricing = reset($terms);
        // Each term should only have one price dimension as well.
        $pricingdimension = reset($termspricing['priceDimensions']);

        // Always use US Dollars as our baseline for costing.
        $transcodecost = $pricingdimension['pricePerUnit']['USD'];

        $this->transcodecost = $transcodecost;
    }

    /**
     * Get the transcode cost for this product.
     *
     * @return float|int the cost per minute of this product.
     */
    public function get_transcodecost() {
        return $this->transcodecost;
    }

    /**
     * Get the product family for this product.
     *
     * @return string enumerated value out of ['High Definition', 'Standard Definition', 'Audio']
     */
    public function get_productfamily() {
        return $this->productfamily;
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

    /**
     * Get the location for this product.
     *
     * @return string the AWS location of this product.
     */
    public function get_location() {
        return $this->location;
    }

    /**
     * Get the servicecode of this product.
     *
     * @return string the AWS Service Code of this product.
     */
    public function get_servicecode() {
        return $this->servicecode;
    }

}