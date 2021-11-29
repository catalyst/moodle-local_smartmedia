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
     * Retrieved Preset ID information
     *
     * @var array
     */
    private $retrievedpresets;

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
     * Transcoder presets for extra high quality video file conversion.
     *
     * @var array
     */
    public const EXTRA_HIGH_PRESETS = array(
        '1351620000001-500020', // System preset: MPEG-Dash Video - 4.8M
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

    public const HLS_AUDIO = array(
        '1351620000001-200060'  // System preset: HLS v3 and v4 Audio, 160 k
    );

    public const MPD_AUDIO = array(
        '1351620000001-500060' // System preset: MPEG-DASH Audio 128 k
    );

    /**
     * aws_ets_pricing_client constructor.
     *
     * @param \Aws\ElasticTranscoder\ElasticTranscoderClient $transcoderclient the client for accessing AWS ETS.
     */
    public function __construct(ElasticTranscoderClient $transcoderclient) {
        $this->transcoderclient = $transcoderclient;
        $this->retrievedpresets = array();
    }

    /**
     * Read the details of an AWS Elastic Transcoder preset.
     *
     * @param string $presetid the AWS preset ID to read the preset for.
     *
     * @return mixed|null
     */
    private function read_preset(string $presetid) {
        // Retrieve preset information if already stored.
        if (array_key_exists($presetid, $this->retrievedpresets)) {
            $preset = $this->retrievedpresets[$presetid];
        } else {
            $params = ['Id' => $presetid];
            $result = $this->transcoderclient->readPreset($params);
            $preset = $result->get('Preset');
            // Store the info for later.
            $this->retrievedpresets[$presetid] = $preset;
        }

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
            $presetids = array_merge(
                self::LOW_PRESETS,
                self::HLS_AUDIO,
                self::MPD_AUDIO,
                $presetids
            );
        }

        if (!empty($pluginconfig->quality_medium)) {
            $presetids = array_merge(
                self::MEDIUM_PRESETS,
                self::HLS_AUDIO,
                self::MPD_AUDIO,
                $presetids
            );
        }

        if (!empty($pluginconfig->quality_high)) {
            $presetids = array_merge(
                self::HIGH_PRESETS,
                self::HLS_AUDIO,
                self::MPD_AUDIO,
                $presetids
            );
        }

        if (!empty($pluginconfig->quality_extrahigh)) {
            $presetids = array_merge(
                self::EXTRA_HIGH_PRESETS,
                self::MPD_AUDIO,
                $presetids
            );
        }

        if (!empty($pluginconfig->audio_output)) {
            $presetids = array_merge(self::AUDIO_PRESETS, $presetids);
        }

        if (!empty($pluginconfig->download_files)) {
            $presetids = array_merge(self::DOWNLOAD_PRESETS, $presetids);
        }

        // Now do some special checks. Rekognition relies on the download preset as the base video.
        // Transcribe relies on the audio preset. If these settings are enabled,
        // We MUST add these presets if they arent already.
        $rekogenabled = $pluginconfig->detectlabels ||
            $pluginconfig->detectmoderation ||
            $pluginconfig->detectfaces ||
            $pluginconfig->detectpeople;

        if (empty($pluginconfig->download_files) && $rekogenabled) {
            $presetids = array_merge(self::DOWNLOAD_PRESETS, $presetids);
        }

        if (empty($pluginconfig->audio_output) && $pluginconfig->transcribe) {
            $presetids = array_merge(self::AUDIO_PRESETS, $presetids);
        }

        // Now we want to add any custom presets enabled for the account.
        if (!empty($pluginconfig->usecustompresets)) {
            $custompresets = explode(',', str_replace(' ', '', $pluginconfig->custompresets));
            $presetids = array_merge($custompresets, $presetids);
        }

        return array_unique($presetids);
    }

    /**
     * Get the presets based on the conversion settings.
     *
     * @param array $presetids Optional array of preset ids to get presets for.
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

    /**
     * This function gets all preset objects that can be used in the plugin.
     *
     * @return array
     */
    public function get_all_presets() : array {

        $presetids = array_merge(
            self::LOW_PRESETS,
            self::MEDIUM_PRESETS,
            self::HIGH_PRESETS,
            self::EXTRA_HIGH_PRESETS,
            self::AUDIO_PRESETS,
            self::DOWNLOAD_PRESETS
        );

        return $this->get_presets($presetids);
    }
}
