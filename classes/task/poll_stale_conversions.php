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
use local_smartmedia\aws_api;
use local_smartmedia\aws_elastic_transcoder;

class poll_stale_conversions extends scheduled_task {

    public function get_name() {
        return get_string('task:poll_conversions', 'local_smartmedia');
    }

    public function execute() {
        mtrace('Starting to poll stale conversions...');

        $records = self::get_stale_conversions();
        mtrace("Found {count($records)} stale conversions to poll");

        $api = new aws_api();
        $transcoder = new aws_elastic_transcoder($api->create_elastic_transcoder_client());
        $conversion = new \local_smartmedia\conversion($transcoder);

        foreach ($records as $record) {
            // Here we should attempt to pull files, as if we had a completion message from a service.
            if ($record->transcoder_status == \local_smartmedia\conversion::CONVERSION_IN_PROGRESS ||
                    $record->transcoder_status == \local_smartmedia\conversion::CONVERSION_ACCEPTED) {

                // Get Elastic Transcoder files. If we found some, this was a win.
                $files = $conversion->get_transcode_files($record);

                $record->transcoder_status = $files > 0
                    ? \local_smartmedia\conversion::CONVERSION_FINISHED
                    :\local_smartmedia\conversion::CONVERSION_ERROR;
            }

            $services = [
                'transcribe_status' => 'TranscribeComplete',
                'rekog_label_status' => 'StartLabelDetection',
                'rekog_moderation_status' => 'StartContentModeration',
                'rekog_face_status' => 'StartFaceDetection',
                'rekog_person_status' => 'StartPersonTracking',
                'detect_sentiment_status' => 'SentimentComplete',
                'detect_phrases_status' => 'PhrasesComplete',
                'detect_entities_status' => 'EntitiesComplete'
            ];
            // Now we want to check all of the pending enrichment types.
            foreach ($services as $service => $filecode) {
                if ($record->$service == \local_smartmedia\conversion::CONVERSION_IN_PROGRESS ||
                    $record->$service == \local_smartmedia\conversion::CONVERSION_ACCEPTED) {

                // Get Elastic Transcoder files. If we found some, this was a win.
                $success = $conversion->get_data_file($record, $filecode);

                $statusfield = \local_smartmedia\conversion::SERVICE_MAPPING[$filecode][0];
                $record->$statusfield = $success
                    ? \local_smartmedia\conversion::CONVERSION_FINISHED
                    :\local_smartmedia\conversion::CONVERSION_ERROR;
                }
            }

            // And now, the status of all pending is completed, mark finished.
            $conversion->update_completion_status($record);

            mtrace("Finished polling stale conversion {$record->contenthash}");
        }

        mtrace("Finished polling stale conversions");
    }

    private function get_stale_conversions(): array {
        global $DB;

        // Start from the back forwards, and check for pending conversions with no completion or error messages.
        $endstatus = ['SUCCEEDED', 'COMPLETED', 'ERROR'];
        list($in, $inparams) = $DB->get_in_or_equal($endstatus, SQL_PARAMS_NAMED);
        $sql = "SELECT *
                  FROM {local_smartmedia_conv} conv
                      msg.objectkey
                 WHERE conv.timecreated < :timeboundary
                   AND conv.status = :status
                   AND (
                    SELECT COUNT(*)
                      FROM {local_smartmedia_queue_msgs} msgs
                     WHERE msgs.objectkey = conv.contenthash
                       AND msgs.status $in
                       ) = 0";

        $params = array_merge($inparams, [
            'timeboundary' => time() - 7 * DAYSECS,
            'status' => \local_smartmedia\conversion::CONVERSION_IN_PROGRESS
        ]);

        return $DB->get_records_sql($sql, $params, 0, 1000);
    }

}
