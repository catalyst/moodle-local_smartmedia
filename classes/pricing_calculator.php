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
 * Pricing Calculator for determining media transcoding costs.
 *
 * @package     local_smartmedia
 * @author      Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright   2019 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_smartmedia;

defined('MOODLE_INTERNAL') || die;

/**
 * Pricing Calculator for determining media transcoding costs.
 *
 * @package     local_smartmedia
 * @author      Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright   2019 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pricing_calculator {

    /**
     * @var \local_smartmedia\location_transcode_pricing object containing location pricing information.
     */
    private $locationpricing;

    /**
     * @var array $presets array of aws_ets_preset objects containing preset transcode output information.
     */
    private $presets;

    /**
     * @var string $region the AWS region applying to this calculator.
     */
    private $region;

    /**
     * pricing_calculator constructor.
     *
     * @param location_transcode_pricing $locationpricing object containing pricing information for region.
     * @param array $presets array of aws_ets_preset objects containing preset transcode output settings.
     */
    public function __construct(location_transcode_pricing $locationpricing, array $presets) {
        $this->locationpricing = $locationpricing;
        $this->region = $locationpricing->get_region();
        $this->presets = $presets;
    }

    /**
     * Check that high definition location pricing is valid.
     *
     * @return bool
     */
    public function is_high_definition_pricing_valid() : bool {
        $result = $this->locationpricing->has_valid_high_definition_pricing();
        return $result;
    }

    /**
     * Check that standard definition location pricing is valid.
     *
     * @return bool
     */
    public function is_standard_definition_pricing_valid() : bool {
        $result = $this->locationpricing->has_valid_standard_definition_pricing();
        return $result;
    }

    /**
     * Check that audio location pricing is valid.
     *
     * @return bool
     */
    public function is_audio_pricing_valid() : bool {
        $result = $this->locationpricing->has_valid_audio_pricing();
        return $result;
    }

    /**
     * Calculate the transcode cost across all presets for media of a set height and duration.
     *
     * @param int $height the height in pixels of the input media being transcoded.
     * @param float $duration the duration in seconds of the input media being transcoded.
     *
     * @return float $cost the total cost in US Dollars to perform all preset transcodes.
     */
    public function calculate_transcode_cost(int $height, float $duration) : float {
        $cost = 0;

        foreach ($this->presets as $preset) {
            // All video media can be transcoded by standard definition presets.
            if ($preset->is_output_standard_definition() && $preset->is_input_video($height)) {
                $cost += $this->locationpricing->calculate_standard_definition_cost($duration);
            } else if ($preset->is_output_high_definition()) {
                // Only high definition video can be transcoded by high definition presets.
                if ($preset->is_input_high_definition($height)) {
                    $cost += $this->locationpricing->calculate_high_definition_cost($duration);
                } else if ($preset->is_input_video($height)) {
                    $cost += $this->locationpricing->calculate_standard_definition_cost($duration);
                } else {
                    $cost += $this->locationpricing->calculate_audio_cost($duration);
                }
            } else {
                // All media can be trancoded to audio.
                $cost += $this->locationpricing->calculate_audio_cost($duration);
            }
        }
        return $cost;
    }
}