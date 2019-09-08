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

require_once($CFG->dirroot . '/local/smartmedia/lib.php');

/**
 * An AWS Elastic Transcode Service product.
 *
 * @package     local_smartmedia
 * @author      Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright   2019 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class aws_ets_preset {


    /**
     * @var string $id the AWS preset ID.
     */
    private $id;

    /**
     * @var string $container the file container type of preset outputs.
     */
    private $container;

    /**
     * @var string $type media type of 'Audio' or 'Video'.
     */
    private $type;

    /**
     * @var int $height media resolution height of preset outputs.
     */
    private $height;

    /**
     * @var int $width media resolution width of preset outputs.
     */
    private $width;

    /**
     * aws_ets_preset constructor.
     *
     * @param array $preset preset structure from AWS SDK.
     * https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-elastictranscoder-2012-09-25.html#shape-preset
     */
    public function __construct(array $preset) {
        $this->id = $preset['Id'];
        $this->container = $preset['Container'];
        if (isset($preset['Video']) && $preset['Video']['MaxHeight'] > 0) {
            $this->type = LOCAL_SMARTMEDIA_TYPE_VIDEO;
            $this->height = $preset['Video']['MaxHeight'];
            $this->width = $preset['Video']['MaxWidth'];
        } else {
            $this->type = LOCAL_SMARTMEDIA_TYPE_AUDIO;
            $this->height = 0;
            $this->width = 0;
        }
    }

    /**
     * Check if this preset is for high definition output.
     *
     * @return bool true if this preset outputs high definition transcodes, false otherwise.
     */
    public function is_output_high_definition() {
        $result = false;
        if ($this->height >= LOCAL_SMARTMEDIA_MINIMUM_HD_HEIGHT) {
            $result = true;
        }
        return $result;
    }

    /**
     * Check if this preset is for standard definition output.
     *
     * @return bool true if this preset outputs standard definition transcodes, false otherwise.
     */
    public function is_output_standard_definition() {
        $result = false;
        if ($this->height < LOCAL_SMARTMEDIA_MINIMUM_HD_HEIGHT && $this->height > 0) {
            $result = true;
        }
        return $result;
    }

    /**
     * Check if this preset is for audio output.
     *
     * @return bool true if this preset outputs audio, false otherwise.
     */
    public function is_output_audio() {
        $result = $this->type == LOCAL_SMARTMEDIA_TYPE_AUDIO;
        return $result;
    }

    /**
     * Check if this preset is for video output.
     *
     * @return bool true if this preset outputs video, false otherwise.
     */
    public function is_output_video() {
        $result = $this->type == LOCAL_SMARTMEDIA_TYPE_VIDEO;
        return $result;
    }

    /**
     * Check if an input media element is audio.
     *
     * @param int $height resolution height (number of lines) of input media to check.
     *
     * @return bool
     */
    public function is_input_audio($height) {
        $result = $height == 0;
        return $result;
    }

    /**
     * Check if an input media element is video.
     *
     * @param int $height resolution height (number of lines) of input media to check.
     *
     * @return bool
     */
    public function is_input_video($height) {
        $result = $height > 0;
        return $result;
    }

    /**
     * Check if an input media element is standard definition.
     *
     * @param int $height resolution height (number of lines) of input media to check.
     *
     * @return bool
     */
    public function is_input_standard_definition($height) {
        $result = ($height > 0 && $height < LOCAL_SMARTMEDIA_MINIMUM_HD_HEIGHT);
        return $result;
    }

    /**
     * Check if an input media element is high definition.
     *
     * @param int $height resolution height (number of lines) of input media to check.
     *
     * @return bool
     */
    public function is_input_high_definition($height) {
        $result = ($height >= LOCAL_SMARTMEDIA_MINIMUM_HD_HEIGHT);
        return $result;
    }

    /**
     * Does this preset output fragmented media?
     * (Fragmented output means one input may be transcoded to deliver segmented outputs, such as for adaptive bitrate streaming)
     *
     * @return bool true if the output is fragmented, false otherwise.
     */
    public function is_output_fragmented() {
        $result = false;
        if (in_array($this->container, LOCAL_SMARTMEDIA_PRESET_OUTPUT_FRAGMENTED_CONTAINERS)) {
            $result = true;
        }
        return $result;
    }

    /**
     * Getter for this preset's id.
     *
     * @return string AWS preset id.
     */
    public function get_id() : string {
        return $this->id;
    }

    /**
     * Getter for this preset's container type.
     *
     * @return string AWS preset output file container type.
     */
    public function get_container() : string {
        return $this->container;
    }
}
