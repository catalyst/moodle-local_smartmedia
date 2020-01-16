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
 * Class for accessing AWS Elastic Transcode Services (ETS).
 *
 * @package     local_smartmedia
 * @author      Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright   2019 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_smartmedia;

use Aws\ElasticTranscoder\ElasticTranscoderClient;
use Aws\Exception\AwsException;

defined('MOODLE_INTERNAL') || die;

global $CFG;
// Autoload the SDK for AWS service usage.
require_once($CFG->dirroot . '/local/aws/sdk/aws-autoloader.php');

/**
 * Class for accessing AWS Elastic Transcode Services (ETS).
 *
 * @package     local_smartmedia
 * @author      Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright   2019 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class aws_elastic_transcoder {

    /**
     * @var \Aws\ElasticTranscoder\ElasticTranscoderClient
     */
    private $transcoderclient;

    /**
     * Transcoder presets for low quality video file conversion.
     *
     * @var array
     */
    public const LOW_PRESETS = array(
        '1351620000001-200045', // System preset: HLS Video - 600k.
        '1351620000001-500050' // System preset: MPEG-Dash Video - 600k.
    );

    /**
     * Transcoder presets for medium quality video file conversion.
     *
     * @var array
     */
    public const MEDIUM_PRESETS = array(
        '1351620000001-200035', // System preset: HLS Video - 1M.
        '1351620000001-500040' // System preset: MPEG-Dash Video - 1.2M.
    );

    /**
     * Transcoder presets for high quality video file conversion.
     *
     * @var array
     */
    public const HIGH_PRESETS = array(
        '1351620000001-200015', // System preset: HLS Video - 2M.
        '1351620000001-500030' // System preset: MPEG-Dash Video - 2.4M.
    );

    /**
     * Transcoder presets for audio file conversion.
     *
     * @var array
     */
    public const AUDIO_PRESETS = array(
        '1351620000001-300020' // System preset: Audio MP3 - 192 kilobits/second.
    );

    /**
     * Transcoder presets for video file download conversion.
     *
     * @var array
     */
    public const DOWNLOAD_PRESETS = array(
        '1351620000001-100070' // System preset: Facebook, SmugMug, Vimeo, YouTube.
    );

    /**
     * aws_ets_pricing_client constructor.
     *
     * @param \Aws\ElasticTranscoder\ElasticTranscoderClient $transcoderclient the client for accessing AWS ETS.
     */
    public function __construct(ElasticTranscoderClient $transcoderclient) {
        $this->transcoderclient = $transcoderclient;
    }

    /**
     * Read the details of an AWS Elastic Transcoder preset.
     *
     * @param string $presetid the AWS preset ID to read the preset for.
     *
     * @return mixed|null
     */
    private function read_preset(string $presetid) {
        $params = ['Id' => $presetid];
        $result = $this->transcoderclient->readPreset($params);
        $preset = $result->get('Preset');

        return $preset;
    }

    /**
     * Return an array of preset ids based on plugin configuration.
     *
     * @return array $presetids The preset ids.
     */
    public function get_preset_ids() : array {
        $pluginconfig = get_config('local_smartmedia');
        $presetids = [];

        // Collate enabled presets.
        if (!empty($pluginconfig->quality_low)) {
            $presetids = array_merge(self::LOW_PRESETS, $presetids);
        }

        if (!empty($pluginconfig->quality_medium)) {
            $presetids = array_merge(self::MEDIUM_PRESETS, $presetids);
        }

        if (!empty($pluginconfig->quality_high)) {
            $presetids = array_merge(self::HIGH_PRESETS, $presetids);
        }

        if (!empty($pluginconfig->audio_output)) {
            $presetids = array_merge(self::AUDIO_PRESETS, $presetids);
        }

        if (!empty($pluginconfig->download_files)) {
            $presetids = array_merge(self::DOWNLOAD_PRESETS, $presetids);
        }

        return $presetids;

    }

    /**
     * Get the presets based on the conversion settings.
     *
     * @return array $presets array of aws_ets_preset objects.
     * @throws \moodle_exception
     */
    public function get_presets(array $presetids=array()) : array {
        $presets = [];
        if (empty($presetids)) {
            $presetids = $this->get_preset_ids();
        }

        if (!empty($presetids)) {

            foreach ($presetids as $presetid) {
                try {
                    $presetdata = $this->read_preset($presetid);
                    $presets[] = new aws_ets_preset($presetdata);
                } catch (AwsException $e) {
                    debugging($e->getAwsErrorMessage());
                    throw new \moodle_exception("Invalid AWS Elastic Transcoder Preset ID in SmartMedia settings: '$presetid'");
                }
            }
        }
        return $presets;
    }
}
