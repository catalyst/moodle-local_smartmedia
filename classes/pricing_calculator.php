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

use local_smartmedia\pricing\location_rekog_pricing;
use local_smartmedia\pricing\location_transcode_pricing;
use local_smartmedia\pricing\location_transcribe_pricing;

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
     * @var location_transcode_pricing object containing location pricing information.
     */
    private $transcodelocationpricing;

    /**
     * @var location_rekog_pricing object containing location pricing information.
     */
    private $rekoglocationpricing;

    /**
     * @var location_transcribe_pricing object containing location pricing information.
     */
    private $transcribelocationpricing;

    /**
     * @var array $presets array of aws_ets_preset objects containing preset transcode output information.
     */
    private $presets;

    /**
     * @var array $rekog array of settings for rekognition billing.
     */
    private $rekogsettings;

    /**
     * @var bool $transcribe array of settings for rekognition billing.
     */
    private $transcribe;

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
    public function __construct(
        location_transcode_pricing $transcodelocationpricing,
        location_rekog_pricing $rekoglocationpricing,
        location_transcribe_pricing $transcribelocationpricing,
        array $presets = [],
        array $rekogsettings = [],
        $transcribe = false) {

        $this->transcodelocationpricing = $transcodelocationpricing;
        $this->rekoglocationpricing = $rekoglocationpricing;
        $this->transcribelocationpricing = $transcribelocationpricing;
        $this->region = $transcodelocationpricing->get_region();
        $this->presets = $presets;
        $this->rekogsettings = $rekogsettings;
        $this->transcribe = $transcribe;
    }

    /**
     * Check that high definition location pricing is valid.
     *
     * @return bool
     */
    public function is_high_definition_pricing_valid() : bool {
        $result = $this->transcodelocationpricing->has_valid_high_definition_pricing();
        return $result;
    }

    /**
     * Check that standard definition location pricing is valid.
     *
     * @return bool
     */
    public function is_standard_definition_pricing_valid() : bool {
        $result = $this->transcodelocationpricing->has_valid_standard_definition_pricing();
        return $result;
    }

    /**
     * Check that audio location pricing is valid.
     *
     * @return bool
     */
    public function is_audio_pricing_valid() : bool {
        $result = $this->transcodelocationpricing->has_valid_audio_pricing();
        return $result;
    }

    /**
     * Check that this price calculator has presets to calculate pricing from.
     *
     * @return bool
     */
    public function has_presets() : bool {
        $result = false;
        if (!empty($this->presets)) {
            $result = true;
        }
        return $result;
    }

    /**
     * Calculate the transcode cost across all presets for media of a set height and duration.
     *
     * @param int $height the height in pixels of the input media being transcoded.
     * @param float $duration the duration in seconds of the input media being transcoded.
     * @param int $videostreams the count of video streams the input media has.
     * @param int $audiostreams the count of audio streams the input media has.
     *
     * @return float|null $cost the total cost in US Dollars to perform all preset transcodes.
     */
    public function calculate_transcode_cost(int $height, float $duration, int $videostreams = 1, int $audiostreams = 1) {

        if ($this->has_presets()) {
            $cost = 0;

            // From https://aws.amazon.com/elastictranscoder/pricing/
            // each output file is billed in whole minute increments
            // where each partial minute is rounded up to the next full minute.
            $durationminutes = ceil($duration / 60);

            foreach ($this->presets as $preset) {
                // All video media can be transcoded by standard definition presets providing it has at least one video stream.
                if ($preset->is_output_standard_definition() && $preset->is_input_video($height) && !empty($videostreams)) {
                    $cost += $this->transcodelocationpricing->calculate_standard_definition_cost($durationminutes);
                } else if ($preset->is_output_high_definition() && !empty($videostreams)) {
                    // Only high definition video can be transcoded by high definition presets.
                    if ($preset->is_input_high_definition($height)) {
                        $cost += $this->transcodelocationpricing->calculate_high_definition_cost($durationminutes);
                    } else if ($preset->is_input_video($height)) {
                        $cost += $this->transcodelocationpricing->calculate_standard_definition_cost($durationminutes);
                    }
                } else if ($preset->is_output_audio() && !empty($audiostreams)) {
                    // All media can be trancoded to audio, providing it has at least one audio stream.
                    $cost += $this->transcodelocationpricing->calculate_audio_cost($durationminutes);
                }
            }
        } else {
            // If there are no presets, or no audio or video streams, no transcoding could be conducted.
            $cost = null;
        }
        return $cost;
    }

    /**
     * Calculates the cost of all rekognition services selected for the duration supplied.
     *
     * @param float $duration
     * @return float $cost the cost in USD to perform all rekognition analysis.
     */
    public function calculate_rekog_cost(float $duration) {
        // From https://aws.amazon.com/rekognition/pricing/
        // each output file is billed in whole minute increments
        // where each partial minute is rounded up to the next full minute.
        $durationminutes = ceil($duration / 60);

        $cost = 0;
        foreach ($this->rekogsettings as $setting => $value) {
            if ($value) {
                $methodname = 'calculate_' . $setting . '_cost';
                if (method_exists($this->rekoglocationpricing, $methodname)) {
                    $cost += $this->rekoglocationpricing->$methodname($durationminutes);
                }
            }
        }
        return $cost;
    }

    /**
     * Calculates the cost of all rekognition services selected for the duration supplied.
     *
     * @param float $duration the duration in seconds
     * @return float $cost the cost in USD to perform all transcription.
     */
    public function calculate_transcribe_cost(float $duration) {
        // From https://aws.amazon.com/transcribe/pricing/
        // each output file is billed in seconds.
        $cost = 0;
        if ($this->transcribe) {
            $cost += $this->transcribelocationpricing->calculate_transcribe_cost($duration);
        }
        return $cost;
    }
}
