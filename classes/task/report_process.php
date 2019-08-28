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
 * A scheduled task to gather data usee in plugin dashboards and reports.
 *
 * @package    local_smartmedia
 * @copyright  2019 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_smartmedia\task;

use core\task\scheduled_task;

defined('MOODLE_INTERNAL') || die();

/**
 * A scheduled task to gather data usee in plugin dashboards and reports.
 *
 * @copyright  2019 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_process extends scheduled_task {

    /**
     * Audio mime types.
     */
    private const AUDIO_MIME_TYPES = array(
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
    );

    /**
     * Video mime types.
     */
    private const VIDEO_MIME_TYPES = array(
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
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('task:reportprocess', 'local_smartmedia');
    }

    /**
     * Count all the files in the Moodle files table,
     * except those added by smart media.
     *
     * @return int $result Count of files.
     */
    private function get_all_file_count() : int {
        global $DB;

        $select = 'component <> ?';  // Don't count files added by smartmedia itself.
        $params = array('local_smartmedia');
        $result = $DB->count_records_select('files', $select, $params);

        return $result;

    }

    /**
     * Count all the audio files in the Moodle files table,
     * except those added by smart media.
     *
     * @return int $result Count of files.
     */
    private function get_audio_file_count() : int {
        global $DB;

        list($insql, $inparams) = $DB->get_in_or_equal(self::AUDIO_MIME_TYPES);
        $select = "mimetype $insql AND component <> ?";  // Don't count files added by smartmedia itself.
        $inparams[] = 'local_smartmedia';
        $result = $DB->count_records_select('files', $select, $inparams);

        return $result;
    }

    /**
     * Count all the video files in the Moodle files table,
     * except those added by smart media.
     *
     * @return int $result Count of files.
     */
    private function get_video_file_count() : int {
        global $DB;

        list($insql, $inparams) = $DB->get_in_or_equal(self::VIDEO_MIME_TYPES);
        $select = "mimetype $insql AND component <> ?";  // Don't count files added by smartmedia itself.
        $inparams[] = 'local_smartmedia';
        $result = $DB->count_records_select('files', $select, $inparams);

        return $result;
    }

    /**
     * Add a key value pair to the report database table.
     *
     * @param string $name Name of the value to store.
     * @param mixed $value Value to store.
     */
    private function update_report_data(string $name, $value) : void {
        global $DB;

        $datarecord = new \stdClass();
        $datarecord->name = $name;
        $datarecord->value = $value;

        try {
            $transaction = $DB->start_delegated_transaction();
            $namerecord = $DB->get_record('local_smartmedia_reports', array('name' => $name), 'id');

            if ($namerecord) {
                $datarecord->id = $namerecord->id;
                $DB->update_record('local_smartmedia_reports', $datarecord);
            } else {
                $DB->insert_record('local_smartmedia_reports', $datarecord);
            }

            $transaction->allow_commit();

        } catch (\Exception $e) {
            $transaction->rollback($e);
        }
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        mtrace('local_smartmedia: Processing media file data');
        $totalfiles = $this->get_all_file_count(); // Get count of all files in files table.
        $this->update_report_data('totalfiles', $totalfiles);

        $audiofiles = $this->get_audio_file_count(); // Get count of audio files in files table.
        $this->update_report_data('audiofiles', $audiofiles);

        $videofiles = $this->get_video_file_count(); // Get count of video files in files table.
        $this->update_report_data('videofiles', $videofiles);

    }

}
