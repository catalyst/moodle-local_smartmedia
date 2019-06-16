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
     * @param \stdClass $hrefarguments
     */
    private function create_conversion(\stdClass $hrefarguments) : void {
        global $DB;
        $now = time();

        $hrefarguments->status = $this::CONVERSION_ACCEPTED;
        $hrefarguments->timecreated = $now;
        $hrefarguments->timemodified = $now;

        // Race conditions mean that we could try to create a conversion record multiple times.
        // This is OK and expected, we will handle the error.
        try {
            $DB->insert_record('local_smartmedia_conv', $hrefarguments);
        } catch (\dml_write_exception $e) {
            // If error is anything else but a duplicate insert, this is unexected,
            // so re-throw the error.
            if(!strpos($e->getMessage(), 'locasmarconv_ite_uix')){
                throw $e;
            }
        }
    }

    /**
     * Get the smart media conversion status for a given resource.
     *
     * @param string $itemhash The item hash of the asset.
     * @return int $status The response status to the request.
     */
    private function get_conversion_status(string $itemhash) : int {
        global $DB;

        $conditions = array('itemhash' => $itemhash);
        $status = $DB->get_field('local_smartmedia_conv', 'status', $conditions);

        if (!$status) {
            $status = self::CONVERSION_NOT_FOUND;
        }

        return $status;
    }

    /**
     * Given a Moodle URL etract the relevant arguments for
     * further processing.
     *
     * @param \moodle_url $href Plugin file url to extract from.
     * @return \stdClass $arguments The extracted arguments.
     */
    private function get_arguments(\moodle_url $href) : \stdClass {
        $arguments = new \stdClass();

        // Extract the elements we need from the Moodle URL.
        $argumentsstring = $href->get_path(true);
        $rawarguments = explode('/', $argumentsstring);

        $arguments->contextid = $rawarguments[2];
        $arguments->component = clean_param($rawarguments[3], PARAM_COMPONENT);
        $arguments->filearea = clean_param($rawarguments[4], PARAM_AREA);

        if (count($rawarguments) > 6 ) {
            $arguments->itemid = (int)$rawarguments[5];
        } else {
            $arguments->itemid = 0;
        }

        $arguments->filename = end($rawarguments);

        // This is NOT the same as the pathname hash in the files table.
        $arguments->itemhash = sha1(
            $arguments->contextid . $arguments->component
            . $arguments->filearea . $arguments->itemid . $arguments->filename);

        return $arguments;
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
        $hrefarguments = $this->get_arguments($href);

        // Query conversion table for status.
        $conversionstatus = $this->get_conversion_status($hrefarguments->itemhash);

        // If no record in table and trigger conversion is true add record.
        if($triggerconversion && $conversionstatus == self::CONVERSION_NOT_FOUND) {
            $this->create_conversion($hrefarguments);
        }

        // If processing complete get all urls and data for source href.

        // TODO: Cache the result for a very long time as once processing is finished it will never change
        // and when processing is finished we will explictly clear the cache.


        return $smartmedia;

    }

}