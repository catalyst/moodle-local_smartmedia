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
    private const MAX_FILES = 5000;

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
        'audio/x-matroska',
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
        'video/x-matroska',
        'video/x-matroska-3d',
        'video/MP2T'.
        'video/x-sgi-movie',
    );
    /**
     * The maximum run time for the task in seconds.
     * The task will cleanup and exit after this time.
     *
     * @var int
     */
    private $maxruntime;

    /**
     * Time that the task started execution.
     *
     * @var int
     */
    private $starttime;

    /**
     * Constructor for the task. Sets the max runtime from config.
     */
    public function __construct() {
        $this->maxruntime = get_config('local_smartmedia', 'maxruntime');
    }

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
     * Get the pathnamehash and contenthash of the files we want
     * to extract metadata from.
     *
     * @return array $filehashes The hashes of the files we want to process
     */
    private function get_files_to_process() : array {
        global $DB;

        // Danger! Joins on file table.
        // We want to get the pathnamehash and contenthash of files in the
        // moodle file table, where contenthash isn't in
        // the local_smart_media table. Limit the results to MAX_FILES.

        // We are not using a recordset here as we are getting a limited number of records,
        // a small number of fields and processing the results can take time so we don't
        // want to hold a transaction open for a long period.
        $mimetypes = $this->get_supported_mime_types(true);
        $limit = self::MAX_FILES;
        $params = array(
            'local_smartmedia',
            'draft',
            '.',
        );

        $sql = "SELECT f.id, f.pathnamehash, f.contenthash, f.timecreated
                  FROM {files} f
             LEFT JOIN {local_smartmedia_data} lsd ON f.contenthash = lsd.contenthash
                 WHERE mimetype IN ($mimetypes)
                       AND lsd.contenthash IS NULL
                       AND f.component <> ?
                       AND filearea <> ?
                       AND filename <> ?
              ORDER BY f.timecreated DESC";
        $filehashes = $DB->get_records_sql($sql, $params, 0, $limit);

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
        $duplicatehashes = array();
        $duplicatecount = 0;

        $fs = get_file_storage();
        $ffprobe = new \local_smartmedia\ffprobe();

        $count = count($filehashes);
        mtrace("local_smartmedia: Found {$count} file(s) to process");

<<<<<<< HEAD
        // Lets split the array into chunks, for more granular processing
        $chunksize = 20;
        $filehashes = array_chunk($filehashes, $chunksize, true);

        foreach ($filehashes as $chunkindex => $filehashchunk) {
            // Output an indicator for every chunk processed
            $filecount = ($chunkindex * $chunksize) + 1;
            $batchlimit = ($count < $chunksize) ? $count : ($chunkindex + 1) * $chunksize;
=======
        // Lets split the array into chunks, for more granular processing.
        $filehashes = array_chunk($filehashes, 100, true);

        foreach ($filehashes as $chunkindex => $filehashchunk) {
            // Output an indicator for every chunk processed.
            $filecount = ($chunkindex * 100) + 1;
            $batchlimit = ($count < 100) ? $count : ($chunkindex + 1) * 100;
>>>>>>> d372c58... Added support for transcribing service
            mtrace("local_smartmedia: Now processing $filecount - $batchlimit / $count ");

            foreach ($filehashchunk as $filehash) {
                // Check if we have already processed this content hash this run and exit early if so.
                // We do it here instead of in the SQL query that gets the candidate hashes, because of
                // the large ammount of entries that can be in the database.
                if (in_array($filehash->contenthash, $duplicatehashes)) {
                    $duplicatecount++;
                    continue; // Duplicate found, skip the cycle.
                } else {
                    $duplicatehashes[] = $filehash->contenthash;
                }

                $file = $fs->get_file_by_hash($filehash->pathnamehash);
                $filemetadata = $ffprobe->get_media_metadata($file);

                // Setup initial metadata record.
                $metadatarecord = new \stdClass();
                $metadatarecord->contenthash = $file->get_contenthash();
                $metadatarecord->pathnamehash = $file->get_pathnamehash();
                $metadatarecord->duration = 0;
                $metadatarecord->bitrate = 0;
                $metadatarecord->size = 0;
                $metadatarecord->videostreams = 0;
                $metadatarecord->audiostreams = 0;
                $metadatarecord->width = 0;
                $metadatarecord->height = 0;
                $metadatarecord->metadata = '{}';
                $metadatarecord->timecreated = $filehash->timecreated;

                if ($filemetadata['status'] == 'success') {
                    // Process sucessful metadata.
                    $successcount++;

                    $metadatarecord->duration = $filemetadata['data']['duration'];
                    $metadatarecord->bitrate = $filemetadata['data']['bitrate'];
                    $metadatarecord->size = $filemetadata['data']['size'];
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
                    $failhashses[$filehash->pathnamehash] = $filemetadata['reason']; // Record the failed hashes for logging.
                }
            }

            // Now check if we have exceeded the task runtime. If so, break.
            if (time() >= $this->starttime + $this->maxruntime) {
                mtrace('local_smartmedia: Maximum task runtime reached, metadata extraction halted.');
                break;
            }
        }

        // Insert records into database.
        if (!empty($metadatarecords)) {
            $DB->insert_records('local_smartmedia_data', $metadatarecords);
        }

        $results = array(
            'successcount' => $successcount,
            'failcount' => $failcount,
            'failedhashes' => $failhashses,
            'duplicatecount' => $duplicatecount
        );

        return $results;
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
        mtrace('local_smartmedia: Processing media file metadata');

        // Get the starttime for the task.
        $this->starttime = time();

        $filehashes = $this->get_files_to_process();  // Select a stack of files.
        // If already past the time limit, print a message and exit.
        if (time() >= $this->starttime + $this->maxruntime) {
            mtrace('local_smartmedia: Task time limit exceeded before processing files. Raise time limit or lower lookback time.');
        }
        $processresults = $this->process_files($filehashes); // Process the metadata for the selected files.

        // Output processing results.
        mtrace('local_smartmedia: Number files successfully processed: ' . $processresults['successcount']);
        mtrace('local_smartmedia: Number files with process failures: ' . $processresults['failcount']);
        mtrace('local_smartmedia: ' . $processresults['duplicatecount'] . ' duplicate file entries were skipped.');
        foreach ($processresults['failedhashes'] as $failedhash => $reason) {
            mtrace('local_smartmedia: Failed to process file with hash: ' . $failedhash . ': ' . $reason);
        }

        // Remove files from metadata table.
        mtrace('local_smartmedia: Cleaning metadata table');
        $toremove = $this->get_files_to_remove();
        if (!empty($toremove)) {
            mtrace('local_smartmedia: Count of metadata records to remove: ' . count($toremove));
            $this->remove_metadata_records($toremove);
        }

    }

}
