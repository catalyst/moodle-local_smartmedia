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
 * Task to process conversions of mediafiles.
 * @copyright   2019 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class process_conversions extends scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('task:processconversions', 'local_smartmedia');
    }


    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {

        // Get SQS messages from AWS.
        mtrace('local_smartmedia: Getting SQS queue messages');

        $conversion = new \local_smartmedia\conversion();

        // Process new conversions.
        mtrace('local_smartmedia: Process new conversions');
        $processed = $conversion->process_conversions();

        mtrace('local_smartmedia: Total number of processed files: ' . count($processed));
        foreach ($processed as $key => $value) {
            if($value != \local_smartmedia\conversion::CONVERSION_IN_PROGRESS) {
                mtrace('local_smartmedia: Failed to start processing for file with conversion id: ' . $key);
            }

        }
        // Update pending conversions.
        mtrace('local_smartmedia: Updating pending conversions');
        $updated = $conversion->update_pending_conversions();

    }

}
