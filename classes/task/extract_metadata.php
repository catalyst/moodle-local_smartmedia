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
 * A scheduled task.
 *
 * @package    local_smartmedia
 * @copyright  2019 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_smartmedia\task;

use core\task\scheduled_task;

defined('MOODLE_INTERNAL') || die();

/**
 * Task to extract metadata from mediafiles.
 * @copyright   2019 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class extract_metadata extends scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('task:extractmetadata', 'local_smartmedia');
    }

    /**
     * Get the max id from the smartmedia data table,
     * used to determine the start point for the next
     * metadata processing scan.
     *
     * @return int $startid The id from the smartmedia data table.
     */
    private function get_start_id() : int {
        global $DB;

        $sql = 'SELECT max(id) FROM {local_smartmedia_data}';
        $startfileid = $DB->get_field_sql($sql);

        if(!$startfileid) {
            $startfileid = 0;
        }

        return $startfileid;
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $DB;
        mtrace('local_smartmedia: Processing media file metadata');

        $startfileid = $this->get_start_id(); // Get highest file ID from the metadata table.

        // Select a stack of files higher than that id.
        $fs = get_file_storage();


        // Process the metadata for the selected files.

        // Remove files from metadata table, this is likely to be a nasty join.


    }

}
