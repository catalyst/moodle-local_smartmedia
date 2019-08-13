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
 * Class for smart media conversion operations.
 *
 * @package     local_smartmedia
 * @copyright   2018 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_smartmedia;

defined('MOODLE_INTERNAL') || die();

/**
 * Class for smart media conversion operations.
 *
 * @package     local_smartmedia
 * @copyright   2019 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class conversion {

    /**
     * Smart media conversion finished without error.
     *
     * @var integer
     */
    const CONVERSION_FINISHED = 200;

    /**
     * Smart media conversion is in progres.
     *
     * @var integer
     */
    const CONVERSION_IN_PROGRESS = 201;

    /**
     * Smart media conversion job has been created but processing has not yet started.
     *
     * @var integer
     */
    const CONVERSION_ACCEPTED = 202;

    /**
     * No smart media conversion record found.
     *
     * @var integer
     */
    const CONVERSION_NOT_FOUND = 404;

    /**
     * Smart media conversion finished with error.
     *
     * @var integer
     */
    const CONVERSION_ERROR = 500;

    /**
     * Class constructor
     */
    public function __construct() {
        $this->config = get_config('local_smartmedia');
    }


    /**
     *  Get the configured transcoding presets as an array.
     *
     * @return array $idarray Trimmed array of transcoding presets.
     */
    private function get_preset_ids() : array {
        $rawids = $this->config->transcodepresets; // Get the raw ids.
        $untrimmedids = preg_split('/$\R?^/m', $rawids, -1, PREG_SPLIT_NO_EMPTY); // Split ids into an array of strings by newline.
        $idarray = array_map('trim',$untrimmedids); // Remove whitespace from each id in array.

        return $idarray;
    }

    /**
     * Given a conversion id create records for each configured transcoding preset id,
     * ready to be stored in the Moodle database.
     *
     * @param int $convid The conversion id to create the preset records for.
     * @return array $presetrecords The preset records to insert into the Moodle database.
     */
    private function get_preset_records(int $convid) : array {
        $presetrecords = array();
        $presetids = $this->get_preset_ids();

        foreach ($presetids as $presetid) {
            $record = new \stdClass();
            $record->convid = $convid;
            $record->preset = $presetid;

            $presetrecords[] = $record;
        }

        return $presetrecords;
    }

    /**
     * Create the smart media conversion record.
     * These records will be processed by a scheduled task.
     *
     * @param \stored_file $file The file object to create the converion for.
     */
    private function create_conversion(\stored_file $file) : void {
        global $DB;
        $now = time();
        $convid = 0;

        $convrecord = new \stdClass();
        $convrecord->pathnamehash = $file->get_pathnamehash();
        $convrecord->contenthash = $file->get_contenthash();
        $convrecord->status = $this::CONVERSION_ACCEPTED;
        $convrecord->transcribe = $this->config->transcribe;
        $convrecord->rekog_label = $this->config->detectlabels;
        $convrecord->rekog_moderation = $this->config->detectmoderation;
        $convrecord->rekog_face = $this->config->detectfaces;
        $convrecord->rekog_person = $this->config->detectpeople;
        $convrecord->detect_sentiment = $this->config->detectsentiment;
        $convrecord->detect_phrases = $this->config->detectphrases;
        $convrecord->detect_entities = $this->config->detectentities;
        $convrecord->timecreated = $now;
        $convrecord->timemodified = $now;

        // Race conditions mean that we could try to create a conversion record multiple times.
        // This is OK and expected, we will handle the error.
        try {
            $convid = $DB->insert_record('local_smartmedia_conv', $convrecord);

        } catch (\dml_write_exception $e) {
            // If error is anything else but a duplicate insert, this is unexected,
            // so re-throw the error.
            if (!strpos($e->getMessage(), 'locasmarconv_pat_uix') && !strpos($e->getMessage(), 'locasmarconv_con_uix')) {
                throw $e;
            }
        }

        // If we have a valid conversion record from the insert, then create the presets record.
        // With the above logic we shouldn't get race conditions here.
        if ($convid > 0) {
            $presetrecords = $this->get_preset_records($convid);
            $DB->insert_records('local_smartmedia_presets', $presetrecords);
        }
    }

    /**
     * Get the smart media conversion status for a given resource.
     *
     * @param \stored_file $file The Moodle file object of the asset.
     * @return int $status The response status to the request.
     */
    private function get_conversion_status(\stored_file $file) : int {
        global $DB;

        $pathnamehash = $file->get_pathnamehash();
        $conditions = array('pathnamehash' => $pathnamehash);
        $status = $DB->get_field('local_smartmedia_conv', 'status', $conditions);

        if (!$status) {
            $status = self::CONVERSION_NOT_FOUND;
        }

        return $status;
    }

    /**
     * Given a Moodle URL check file exists in the Moodle file table
     * and retreive the file object.
     * This requires some horrible reverse engineering.
     *
     * @param \moodle_url $href Plugin file url to extract from.
     * @return \stored_file $file The Moodle file object.
     */
    private function get_file_from_url(\moodle_url $href) : \stored_file {
        // Extract the elements we need from the Moodle URL.
        $argumentsstring = $href->get_path(true);
        $rawarguments = explode('/', $argumentsstring);
        $pluginfileposition = array_search('pluginfile.php', $rawarguments);
        $hrefarguments = array_slice($rawarguments, ($pluginfileposition + 1));
        $argumentcount = count($hrefarguments);

        $contextid = $hrefarguments[0];
        $component = clean_param($hrefarguments[1], PARAM_COMPONENT);
        $filearea = clean_param($hrefarguments[2], PARAM_AREA);
        $filename = $hrefarguments[($argumentcount - 1)];

        // Sensible defaults for item id and filepath.
        $itemid = 0;
        $filepath = '/';

        // If item id is non zero then it will be the fourth element in the array.
        if ($argumentcount > 4 ) {
            $itemid = (int)$hrefarguments[3];
        }

        // Handle complex file paths in href.
        if ($argumentcount > 5 ) {
            $filepatharray = array_slice($hrefarguments, 4, -1);
            $filepath = '/' . implode('/', $filepatharray) . '/';
        }

        // Use the information we have extracted to get the pathname hash.
        $fs = get_file_storage();
        $file = $fs->get_file($contextid, $component, $filearea, $itemid, $filepath, $filename);

        return $file;
    }

    /**
     * Get smart media for file.
     *
     * @param \moodle_url $href
     * @param bool $triggerconversion
     * @return array
     */
    public function get_smart_media(\moodle_url $href, bool $triggerconversion = false) : array {
        $smartmedia = array();

        // Split URL up into components.
        $file = $this->get_file_from_url($href);

        // Query conversion table for status.
        $conversionstatus = $this->get_conversion_status($file);

        // If no record in table and trigger conversion is true add record.
        if ($triggerconversion && $conversionstatus == self::CONVERSION_NOT_FOUND) {
            $this->create_conversion($file);
        }

        // If processing complete get all urls and data for source href.

        // TODO: Cache the result for a very long time as once processing is finished it will never change
        // and when processing is finished we will explictly clear the cache.

        return $smartmedia;

    }

    /**
     * Send file for processing.
     */
    private function send_file_for_processing() : void {

    }

    /**
     * Process pending conversions.
     */
    public function process_conversions() : void {
        // Get not yet started conversion records.
        // Itterate through not yet started records.
        // Sending them all for processing.

        // Get pending conversion records.
        // Itterate through pending records.
        // Check AWS for the completion status.
    }

}