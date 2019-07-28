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
     * Max files to get from Moodle files table per processing run.
     */
    private const MAX_FILES = 1000;

    /**
     * Metadata extraction is supported for the following mime types.
     */
    private const SUPPORTED_MIME_TYPES = array(
        'audio/aac',
        'audio/au',
        'audio/mp3',
        'audio/mp4',
        'audio/ogg',
        'audio/wav',
        'audio/x-aiff',
        'audio/x-mpegurl',
        'audio/x-ms-wma',
        'audio/x-pn-realaudio-plugin',
        'video/mp4',
        'video/mpeg',
        'video/ogg',
        'video/quicktime',
        'video/webm',
        'video/x-dv',
        'video/x-flv',
        'video/x-ms-asf',
        'video/x-ms-wm',
        'video/x-ms-wmv',
    );

    /**
     * Get the mime types that support extraction.
     *
     * @param boolean $asstring If true return types as string, false return array.
     * @return boolean | string $mimetypes The supported mime types.
     */
    private function get_supported_mime_types($asstring=false) {

        if ($asstring) {
            $mimetypes = "'" . implode("','", self::SUPPORTED_MIME_TYPES) . "'";;
        } else {
            $mimetypes = self::SUPPORTED_MIME_TYPES;
        }

        return $mimetypes;
    }

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

        $startfileid = get_config('local_smartmedia', 'startfileid');

        if(!$startfileid) {
            $startfileid = 0;
            set_config('startfileid', 0, 'local_smartmedia');
        }

        return $startfileid;
    }

    /**
     * Get the pathnamehash and contenthash of the files we want
     * to extract metadata from.
     *
     * @return array $filehashes The hashes of the files we want to process
     */
    private function get_files_to_process() : array {
        global $DB;

        // Danger! Joins on file table.
        // We want to get the pathnamehash and contenthash of files in the
        // moodle file table, where id is greater than startfileid and contenthash isn't in
        // the local_smart_media table. Limit the results to MAX_FILES.

        // We are not using a recordset here as we are getting a limit number of records,
        // a small number of fields and processing the results can take time so we don't
        // want to hold a transaction open for a long period.
        $mimetypes = $this->get_supported_mime_types(true);
        $startid = $this->get_start_id();
        $limit = self::MAX_FILES;
        $params = array(
            $startid,
            $limit
        );

        $sql = "SELECT f.id, f.pathnamehash, f.contenthash
                  FROM {files} f
             LEFT JOIN {local_smartmedia_data} lsd ON f.contenthash = lsd.contenthash
                 WHERE mimetype IN ($mimetypes)
                       AND lsd.contenthash IS NULL
                       AND f.id > ?
                 LIMIT ?";
        $filehashes = $DB->get_records_sql($sql, $params);

        return $filehashes;
    }

    /**
     * Get contenthashes to remove from the smartmedia data table
     * as the corresponding records have been removed from the
     * file table.
     *
     * @return array $deletehashes The hashes to remove.
     */
    private function get_files_to_remove() : array {
        global $DB;

        // Danger! Joins on file table.
        $sql = "SELECT lsd.contenthash
                  FROM {files} f
            RIGHT JOIN {local_smartmedia_data} lsd ON f.contenthash = lsd.contenthash
                 WHERE f.contenthash IS NULL";

        $deletehashes = $DB->get_records_sql($sql);

        return $deletehashes;

    }

    /**
     * Given a list of pathname hashes, extract the metadata and
     * store the result in the database.
     *
     * @param array $filehashes Filehases to process.
     * @return array $results Results of file processing.
     */
    private function process_files(array $filehashes) : array {
        global $DB;

        $successcount = 0;
        $failcount = 0;
        $metadatarecords = array();
        $failhashses = array();

        $fs = get_file_storage();
        $ffprobe = new \local_smartmedia\ffprobe();

        foreach ($filehashes as $filehash) {
            $file = $fs->get_file_by_hash($filehash->pathnamehash);
            $filemetadata = $ffprobe->get_media_metadata($file);

            // Setup initial metadata record.
            $metadatarecord = new \stdClass();
            $metadatarecord->contenthash = $file->get_contenthash();
            $metadatarecord->duration = 0;
            $metadatarecord->bitrate = 0;
            $metadatarecord->videostreams = 0;
            $metadatarecord->audiostreams = 0;
            $metadatarecord->width = 0;
            $metadatarecord->height = 0;
            $metadatarecord->metadata = '{}';

            if ($filemetadata['status'] == 'success') {
                // Process sucessful metadata.
                $successcount++;

                $metadatarecord->duration = $filemetadata['data']['duration'];
                $metadatarecord->bitrate = $filemetadata['data']['bitrate'];
                $metadatarecord->videostreams = $filemetadata['data']['totalvideostreams'];
                $metadatarecord->audiostreams = $filemetadata['data']['totalaudiostreams'];
                $metadatarecord->metadata = json_encode($filemetadata['data']);

                // Get width and height from primary video stream if we have one.
                if ($filemetadata['data']['totalvideostreams'] > 0) {
                    $metadatarecord->width = $filemetadata['data']['videostreams'][0]['width'];
                    $metadatarecord->height = $filemetadata['data']['videostreams'][0]['height'];
                }

                $metadatarecords[] = $metadatarecord;
            } else {
                $failcount++;
                $failhashses[] = $filehash->pathnamehash; // Record the failed hashes for logging.
            }

            // Insert records into database;
            if (!empty($metadatarecords)) {
                $DB->insert_records('local_smartmedia_data', $metadatarecords);
            }

            $results = array(
                'successcount' => $successcount,
                'failcount' => $failcount,
                'failedhashes' => $failhashses
            );

            return $results;
        }
    }

    /**
     * Remove records from metadata table that no longer have corresponding
     * records in the Moodle file table.
     *
     * @param array $toremove
     */
    private function remove_metadata_records(array $toremove) : void {
        global $DB;

        $removelist = array_keys($toremove);
        $DB->delete_records_list('local_smartmedia_data', 'contenthash', $removelist);
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $DB;
        mtrace('local_smartmedia: Processing media file metadata');

        $startfileid = $this->get_start_id(); // Get highest file ID from the metadata table.
        $filehashes = $this->get_files_to_process();  // Select a stack of files.
        $processresults = $this->process_files($filehashes); // Process the metadata for the selected files.

        // Output processing results;
        mtrace('local_smartmedia: Number files successfully processed: ' . $processresults['successcount']);
        mtrace('local_smartmedia: Number files with process failures: ' . $processresults['failcount']);
        foreach ($processresults['failedhashes'] as $failedhash) {
            mtrace('local_smartmedia: Failed to process file with hash: ' . $failedhash);
        }

        // Remove files from metadata table.
        mtrace('local_smartmedia: Cleaning metadata table');
        $toremove = $this->get_files_to_remove();
        if(!empty($toremove)) {
            mtrace('local_smartmedia: Count of metadata records to remove: ' . count($toremove));
            $this->remove_metadata_records($toremove);
        }

        // Update the start ID ready for next processing run.
        if(!empty($processresults)) {
            $endresult = array_pop($processresults);
            $endid = $endresult->id;
            set_config('startfileid', $endid, 'local_smartmedia');
        }

    }

}
