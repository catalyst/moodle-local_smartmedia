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
     * Create the smart media conversion record.
     * These records will be processed by a scheduled task.
     *
     * @param string pathnamehash The pathname hash of the reference file.
     */
    private function create_conversion(string $pathnamehash) : void {
        global $DB;
        $now = time();

        $record = new \stdClass();
        $record->pathnamehash = $pathnamehash;
        $record->status = $this::CONVERSION_ACCEPTED;
        $record->timecreated = $now;
        $record->timemodified = $now;

        // Race conditions mean that we could try to create a conversion record multiple times.
        // This is OK and expected, we will handle the error.
        try {
            $DB->insert_record('local_smartmedia_conv', $record);
        } catch (\dml_write_exception $e) {
            // If error is anything else but a duplicate insert, this is unexected,
            // so re-throw the error.
            if(!strpos($e->getMessage(), 'locasmarconv_pat_uix')){
                throw $e;
            }
        }
    }

    /**
     * Get the smart media conversion status for a given resource.
     *
     * @param string $pathnamehash The pathname hash of the asset.
     * @return int $status The response status to the request.
     */
    private function get_conversion_status(string $pathnamehash) : int {
        global $DB;

        $conditions = array('pathnamehash' => $pathnamehash);
        $status = $DB->get_field('local_smartmedia_conv', 'status', $conditions);

        if (!$status) {
            $status = self::CONVERSION_NOT_FOUND;
        }

        return $status;
    }

    /**
     * Given a Moodle URL check file exists in the Moodle file table
     * and retreive the pathnamehash.
     * This requires some horrible reverse engineering.
     *
     * @param \moodle_url $href Plugin file url to extract from.
     * @return string $pathnamehash The pathname hash of the file.
     */
    /**
     */
    private function get_pathnamehash(\moodle_url $href) : string {
        // Extract the elements we need from the Moodle URL.
        $argumentsstring = $href->get_path(true);
        $rawarguments = explode('/', $argumentsstring);
        $pluginfileposition = array_search('pluginfile.php', $rawarguments);
        $hrefarguments = array_slice($rawarguments, ($pluginfileposition+1));
        $argumentcount = count($hrefarguments);

        $contextid = $hrefarguments[0];
        $component = clean_param($hrefarguments[1], PARAM_COMPONENT);
        $filearea = clean_param($hrefarguments[2], PARAM_AREA);
        $filename = $hrefarguments[($argumentcount -1)];

        // Sensible defaults for item id and filepath
        $itemid = 0;
        $filepath = '/';

        // If item id is non zero then it will be the fourth element in the array.
        if ($argumentcount > 4 ) {
            $itemid = (int)$hrefarguments[3];
        }

        //  Handle complex file paths in href.
        if ($argumentcount > 5 ) {
            $filepatharray = array_slice($hrefarguments, 4, -1);
            $filepath = '/' . implode('/', $filepatharray) . '/';
        }

        // Use the information we have extracted to get the pathname hash.
        $fs = new \file_storage();  // TDO refactor to use get_file storage.
        $file = $fs->get_file($contextid, $component, $filearea, $itemid, $filepath, $filename);
        $pathnamehash = $file->get_pathnamehash();

        return $pathnamehash;
    }

    /**
     *
     * @param \moodle_url $href
     * @param boolean $triggerconversion
     * @return array
     */
    public function get_smart_media(\moodle_url $href, bool $triggerconversion = false) : array {
        $smartmedia = array();

        // Split URL up into components.
        $pathnamehash = $this->get_pathnamehash($href);

        // Query conversion table for status.
        $conversionstatus = $this->get_conversion_status($pathnamehash);

        // If no record in table and trigger conversion is true add record.
        if($triggerconversion && $conversionstatus == self::CONVERSION_NOT_FOUND) {
            $this->create_conversion($pathnamehash);
        }

        // If processing complete get all urls and data for source href.

        // TODO: Cache the result for a very long time as once processing is finished it will never change
        // and when processing is finished we will explictly clear the cache.


        return $smartmedia;


    }


    private function send_file_for_processing() : void {

    }

    public function process_conversions() : void {
        // Get not yet started conversion records.
        // Itterate through not yet started records.
        // Sending them all for processing.

        // Get pending conversion records.
        // Itterate through pending records.
        // Check AWS for the completion status
    }

}