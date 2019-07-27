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

        $sql = "SELECT f.pathnamehash, f.contenthash
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
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $DB;
        mtrace('local_smartmedia: Processing media file metadata');

        $startfileid = $this->get_start_id(); // Get highest file ID from the metadata table.

        // Select a stack of files higher than that id.
        $filehashes = $this->get_files_to_process();
        $fs = get_file_storage();

        // Process the metadata for the selected files.

        // Remove files from metadata table, this is likely to be a nasty join.


    }

}
