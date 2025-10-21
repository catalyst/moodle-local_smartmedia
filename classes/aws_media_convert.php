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

namespace local_smartmedia;

use Aws\Exception\AwsException;
use Aws\MediaConvert\MediaConvertClient;
use core\exception\moodle_exception;

/**
 * Class for accessing AWS Elastic Transcode Services (ETS).
 *
 * @package     local_smartmedia
 * @author      Matthew Hilton <matthewhilton@catalyst-au.net>
 * @copyright   2025 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class aws_media_convert {
    /**
     * @var \Aws\MediaConvert\MediaConvertClient
     */
    private $mediaconvertclient;

    /**
     * Retrieved Preset ID information
     *
     * @var array
     */
    private $retrievedpresets;

    /**
     * @var string HLS Audio preset name
     * This is created in MediaConvert by provision script
     */
    public const PRESET_HLS_AUDIO = 'Smartmedia-HLS-Audio';

    /**
     * @var string Mpeg Dash audio preset name
     * This is created in MediaConvert by provision script
     */
    public const PRESET_MPD_AUDIO = 'Smartmedia-MPD-Audio';

    /**
     * @var string Mp3 (raw) audio preset name
     * This is created in MediaConvert by provision script
     */
    public const PRESET_MP3_AUDIO = 'Smartmedia-MP3-Audio';

    /**
     * @var string Web (audio and video) preset name
     * This is created in MediaConvert by provision script
     */
    public const PRESET_WEB = 'Smartmedia-Web';

    /**
     * @var string HLS Video 600k preset name
     * This is created in MediaConvert by provision script
     */
    public const PRESET_HLS_VIDEO_600K = 'Smartmedia-HLS-Video-600k';

    /**
     * @var string HLS Video 1m preset name
     * This is created in MediaConvert by provision script
     */
    public const PRESET_HLS_VIDEO_1M = 'Smartmedia-HLS-Video-1m';

    /**
     * @var string HLS Video 2m preset name
     * This is created in MediaConvert by provision script
     */
    public const PRESET_HLS_VIDEO_2M = 'Smartmedia-HLS-Video-2m';

    /**
     * @var string Mpeg Dash Video 600k preset name
     * This is created in MediaConvert by provision script
     */
    public const PRESET_MPD_VIDEO_600K = 'Smartmedia-MPD-Video-600k';

    /**
     * @var string Mpeg Dash Video 1.2m preset name
     * This is created in MediaConvert by provision script
     */
    public const PRESET_MPD_VIDEO_1_2M = 'Smartmedia-MPD-Video-1.2m';

    /**
     * @var string Mpeg Dash Video 2.4m preset name
     * This is created in MediaConvert by provision script
     */
    public const PRESET_MPD_VIDEO_2_4M = 'Smartmedia-MPD-Video-2.4m';

    /**
     * @var string Mpeg Dash Video 4.8m preset name
     * This is created in MediaConvert by provision script
     */
    public const PRESET_MPD_VIDEO_4_8M = 'Smartmedia-MPD-Video-4.8m';

    /**
     * @var array all the valid presets that the application can use
     */
    public const VALID_PRESETS = [
        self::PRESET_HLS_AUDIO,
        self::PRESET_MPD_AUDIO,
        self::PRESET_MP3_AUDIO,
        self::PRESET_WEB,
        self::PRESET_HLS_VIDEO_600K,
        self::PRESET_HLS_VIDEO_1M,
        self::PRESET_HLS_VIDEO_2M,
        self::PRESET_MPD_VIDEO_600K,
        self::PRESET_MPD_VIDEO_1_2M,
        self::PRESET_MPD_VIDEO_2_4M,
        self::PRESET_MPD_VIDEO_4_8M,
    ];

    /**
     * If a given preset is valid.
     * @param string $presetname
     * @return bool
     */
    public static function is_valid_preset(string $presetname): bool {
        return in_array($presetname, self::VALID_PRESETS);
    }

    /**
     * Transcoder presets for low quality video file conversion.
     *
     * @var array
     */
    public const LOW_PRESETS = [
        self::PRESET_HLS_VIDEO_600K,
        self::PRESET_MPD_VIDEO_600K,
    ];

    /**
     * Transcoder presets for medium quality video file conversion.
     *
     * @var array
     */
    public const MEDIUM_PRESETS = [
        self::PRESET_HLS_VIDEO_1M,
        self::PRESET_MPD_VIDEO_1_2M,
    ];

    /**
     * Transcoder presets for high quality video file conversion.
     *
     * @var array
     */
    public const HIGH_PRESETS = [
        self::PRESET_HLS_VIDEO_2M,
        self::PRESET_MPD_VIDEO_2_4M,
    ];

    /**
     * Transcoder presets for extra high quality video file conversion.
     *
     * @var array
     */
    public const EXTRA_HIGH_PRESETS = [
        self::PRESET_MPD_VIDEO_4_8M,
    ];

    /**
     * Transcoder presets for audio file conversion.
     *
     * @var array
     */
    public const AUDIO_PRESETS = [
        self::PRESET_MP3_AUDIO,
    ];

    /**
     * Transcoder presets for video file download conversion.
     *
     * @var array
     */
    public const DOWNLOAD_PRESETS = [
        self::PRESET_WEB,
    ];

    /**
     * Transcoder presets for HLS audio.
     *
     * @var array
     */
    public const HLS_AUDIO = [
        self::PRESET_HLS_AUDIO,
    ];

    /**
     * Transcoder presets for MPD audio.
     *
     * @var array
     */
    public const MPD_AUDIO = [
        self::PRESET_MPD_AUDIO,
    ];

    /**
     * Create
     * @param MediaConvertClient $mediaconvertclient
     */
    public function __construct(MediaConvertClient $mediaconvertclient) {
        $this->mediaconvertclient = $mediaconvertclient;
        $this->retrievedpresets = [];
    }

    /**
     * Return an array of preset ids based on plugin configuration.
     *
     * @return array $presetids The preset ids.
     */
    public function get_preset_ids(): array {
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

        // NOTE - Custom presets are disabled since MediaConvert migration.
        // Now we want to add any custom presets enabled for the account.
        // if (!empty($pluginconfig->usecustompresets)) {
        // $custompresets = explode(',', str_replace(' ', '', $pluginconfig->custompresets));
        // $presetids = array_merge($custompresets, $presetids);
        // }

        return array_unique($presetids);
    }

    /**
     * Get the presets based on the conversion settings.
     *
     * @param array $presetids Optional array of preset ids to get presets for.
     * @return array $presets array of aws_ets_preset objects.
     * @throws \core\exception\moodle_exception
     */
    public function get_presets(array $presetids = []): array {
        $presets = [];
        if (empty($presetids)) {
            $presetids = $this->get_preset_ids();
        }

        if (!empty($presetids)) {
            foreach ($presetids as $presetid) {
                try {
                    $presetdata = $this->read_preset($presetid);
                    $presets[] = new media_convert_preset($presetdata);
                } catch (AwsException $e) {
                    debugging($e->getAwsErrorMessage());
                    throw new moodle_exception("Invalid AWS MediaConvert preset name: '$presetid'");
                }
            }
        }
        return $presets;
    }

    /**
     * Read the details of an AWS Media convert preset.
     *
     * @param string $presetname
     * @return mixed|null
     */
    private function read_preset(string $presetname) {
        // Retrieve preset information if already stored.
        if (array_key_exists($presetname, $this->retrievedpresets)) {
            $preset = $this->retrievedpresets[$presetname];
        } else {
            $result = $this->mediaconvertclient->getPreset(["Name" => $presetname]);
            $preset = $result->get('Preset');
            // Store the info for later.
            $this->retrievedpresets[$presetname] = $preset;
        }

        return $preset;
    }
}
