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
     * Get the presets available for transcoding.
     *
     * @param string $presetsettings comma delimited string of AWS Elastic Transcoder preset ids.
     *
     * @return array $presets array of aws_ets_preset objects.
     */
    public function get_presets(string $presetsettings) {
        $rawids = $presetsettings; // Get the raw ids.
        $untrimmedids = explode(',', $rawids); // Split ids into an array of strings by comma.
        $presetids = array_map('trim', $untrimmedids); // Remove whitespace from each id in array.

        foreach ($presetids as $presetid) {
            // Remove any additional whitespace to avoid API errors.
            $presetid = trim($presetid);
            $presetdata = $this->read_preset($presetid);
            $presets[] = new aws_ets_preset($presetdata);
        }
        return $presets;
    }
}
