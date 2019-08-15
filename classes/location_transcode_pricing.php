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
 * Class describing the pricing for an AWS region.
 *
 * @package     local_smartmedia
 * @author      Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright   2019 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_smartmedia;

defined('MOODLE_INTERNAL') || die;

/**
 * Class describing the pricing for an AWS region.
 *
 * @package     local_smartmedia
 * @author      Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright   2019 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class location_transcode_pricing {

    /**
     * @var string the AWS region code this pricing is for.
     */
    private $region;

    /**
     * Minimum height above which (inclusive) transcoding is considered high definition.
     */
    const MIN_HD_HEIGHT = 720;

    /**
     * @var float the cost per minute for standard definition transcoding.
     */
    private $sdpricing;

    /**
     * @var float the cost per minute for high definition transcoding.
     */
    private $hdpricing;

    /**
     * @var float the cost per minute for audio transcoding.
     */
    private $audiopricing;

    /**
     * location_transcode_pricing constructor.
     *
     * @param string $region the AWS region code this pricing is for.
     */
    public function __construct(string $region) {
        $this->region = $region;
    }

    /**
     * Get the region to which this location_transcode_pricing applies.
     *
     * @return string AWS region code.
     */
    public function get_region() {
        return $this->region;
    }

    /**
     * Calculate the cost per minute for transcoding of media.
     *
     * @param int $height number of lines of resolution.
     * @param float $duration in seconds of media.
     *
     * @return float|int|null the cost per minute for transcoding, null if no pricing for product type.
     */
    public function calculate_transcode_cost(int $height, float $duration) {
        if ($height >= self::MIN_HD_HEIGHT) {
            $cost = $this->calculate_high_definition_cost($duration);
        } else if ($height > 0) {
            $cost = $this->calculate_standard_definition_cost($duration);
        } else {
            $cost = $this->calculate_audio_cost($duration);
        }
        return $cost;
    }

    /**
     * Calculate the cost for transcoding high definition media.
     *
     * @param float $duration in seconds of the media.
     *
     * @return float|null $result the total cost in US dollars, null if cost couldn't be calculated
     */
    public function calculate_high_definition_cost(float $duration) {
        $result = null;
        if (is_numeric($this->hdpricing)) {
            $result = $duration / 60 * $this->hdpricing;
        }
        return $result;
    }

    /**
     * Calculate the cost for transcoding standard definition media.
     *
     * @param float $duration in seconds of the media.
     *
     * @return float|null $result the total cost in US dollars, null if cost couldn't be calculated
     */
    public function calculate_standard_definition_cost(float $duration) {
        $result = null;
        if (is_numeric($this->sdpricing)) {
            $result = $duration / 60 * $this->sdpricing;
        }
        return $result;
    }

    /**
     * Calculate the cost for transcoding standard definition media.
     *
     * @param float $duration in seconds of the media.
     *
     * @return float|null $result the total cost in US dollars, null if cost couldn't be calculated
     */
    public function calculate_audio_cost(float $duration) {
        $result = null;
        if (is_numeric($this->audiopricing)) {
            $result = $duration / 60 * $this->audiopricing;
        }
        return $result;
    }

    /**
     * Get the pricing per minute for standard definition transcoding.
     *
     * @return float
     */
    public function get_sd_pricing(): float {
        return $this->sdpricing;
    }

    /**
     * Set standard definition pricing per minute.
     *
     * @param float $sdpricing the cost per minute for Standard Definition transcoding.
     */
    public function set_sd_pricing(float $sdpricing): void {
        $this->sdpricing = $sdpricing;
    }

    /**
     * Get the pricing per minute for high definition transcoding.
     *
     * @return float
     */
    public function get_hd_pricing(): float {
        return $this->hdpricing;
    }

    /**
     * Set high definition pricing per minute.
     *
     * @param float $hdpricing the cost per minute for High Definition transcoding.
     */
    public function set_hd_pricing(float $hdpricing): void {
        $this->hdpricing = $hdpricing;
    }

    /**
     * Get the pricing per minute for audio transcoding.
     *
     * @return float
     */
    public function get_audio_pricing(): float {
        return $this->audiopricing;
    }

    /**
     * Set audio pricing per minute.
     *
     * @param float $audiopricing
     */
    public function set_audio_pricing(float $audiopricing): void {
        $this->audiopricing = $audiopricing;
    }
}

