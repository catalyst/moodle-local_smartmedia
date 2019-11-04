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
     * Count all the unique file objects (contenthashes) for
     * multimedia files from the Moodle files table.
     *
     * @return int count of found records.
     */
    private function get_unique_multimedia_objects() : int {
        global $DB;

        $mimetypes = array_merge(self::AUDIO_MIME_TYPES, self::VIDEO_MIME_TYPES);
        list($insql, $inparams) = $DB->get_in_or_equal($mimetypes);
        $inparams[] = 'local_smartmedia';
        $sql = "SELECT COUNT(DISTINCT contenthash) AS count from {files} WHERE mimetype $insql AND component <> ?";
        $result = $DB->count_records_sql($sql, $inparams);

        return $result;
    }

    /**
     * Count all the multimedia file objects
     * that have had metadata extracted.
     *
     * @return int count of found records.
     */
    private function get_metadata_processed_files() : int {
        global $DB;

        $result = $DB->count_records('local_smartmedia_data');

        return $result;
    }

    /**
     * Count all the multimedia file objects
     * that have been transcoded.
     *
     * @return int count of found records.
     */
    private function get_transcoded_files() : int {
        global $DB;

        $conditions = array('status' => '200');
        $result = $DB->count_records('local_smartmedia_conv', $conditions);

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
     * Get the file type.
     *
     * @param \stdClass $record  Record from metadata table for file.
     * @throws \coding_exception
     * @return string $format File format.
     */
    private function get_file_type(\stdClass $record) : string {
        if (empty($record->videostreams)) {
            if (!empty($record->audiostreams)) {
                $format = get_string('report:typeaudio', 'local_smartmedia');
            } else {
                // We should never get here due to the WHERE clause excluding rows with no video or audio data.
                throw new \coding_exception(
                    'No audio or video stream in {local_smartmedia_data} contenthash' . $record->contenthash);
            }
        } else {
            $format = get_string('report:typevideo', 'local_smartmedia');
        }

        return $format;
    }

    /**
     * Get the cost to transcode a file with currently configured settings.
     *
     * @param \local_smartmedia\aws_ets_pricing_client $pricingclient
     * @param \local_smartmedia\aws_elastic_transcoder $transcoder
     * @param \stdClass $record Record from metadata table for file.
     * @return float $cost The calculated transcoding cost.
     */
    private function get_file_cost(
        \local_smartmedia\aws_ets_pricing_client $pricingclient,
        \local_smartmedia\aws_elastic_transcoder $transcoder,  \stdClass $record) : float {

        // Get the location pricing for the AWS region set.
        $locationpricing = $pricingclient->get_location_pricing(get_config('local_smartmedia', 'api_region'));
        // Get the Elastic Transcoder presets which have been set.
        $presets = $transcoder->get_presets();

        $pricingcalculator = new \local_smartmedia\pricing_calculator($locationpricing, $presets);

        $cost = $pricingcalculator->calculate_transcode_cost(
            $record->height, $record->duration, $record->videostreams, $record->audiostreams);

        return $cost;
    }

   /**
    * Convert the smartmedia conversion processing code
    * to a human readable value.
    *
    * @param int $code The status code.
    * @return string The human readable value.
    */
    private function get_file_status(int $code) : string {
        if ($code == 200) {
            $status = 'Finished';
        } else if ($code == 201) {
            $status = 'In Progress';
        } else if ($code == 202) {
            $status = 'In Progress';
        } else {
            $status = 'Error';
        }

        return $status;
    }

    /**
     * Get count of files that have the same contenthash
     * from the files table.
     *
     * @param string $contenthash THe contenthash to match.
     * @return int $count The count of file instances.
     */
    private function get_file_count(string $contenthash) : int {
        global $DB;

        $count = $DB->count_records('files',array('contenthash' => $contenthash));

        return $count;
    }

    /**
     * Populate the report overview table.
     *
     * @param \local_smartmedia\aws_ets_pricing_client $pricingclient
     * @param \local_smartmedia\aws_elastic_transcoder $transcoder
     */
    private function process_overview_report(\local_smartmedia\aws_ets_pricing_client $pricingclient,
        \local_smartmedia\aws_elastic_transcoder $transcoder) : void {
        global $DB;
        $reportrecords = array();

        // Get metadata and conversion data from DB.
        $sql = 'SELECT d.*, c.status
                  FROM {local_smartmedia_data} d
                  JOIN {local_smartmedia_conv} c ON c.contenthash = d.contenthash;';

        $rs = $DB->get_recordset_sql($sql);
        foreach ($rs as $record) { // Itterate through records.
            $metadata = json_decode($record->metadata);

            // Manipulate values to store.
            $reportrecord = new \stdClass();
            $reportrecord->contenthash = $record->contenthash;
            $reportrecord->type = $this->get_file_type($record);
            $reportrecord->format = $metadata->formatname;
            $reportrecord->resolution = $record->width . ' X ' . $record->height;;
            $reportrecord->duration = round($record->duration, 3);
            $reportrecord->filesize = round(($record->size / 1000000), 3);
            $reportrecord->cost = round($this->get_file_cost($pricingclient, $transcoder, $record), 3);
            $reportrecord->status = $this->get_file_status($record->status);
            $reportrecord->files = $this->get_file_count($record->contenthash);

            $reportrecords[] = $reportrecord;
        }
        $rs->close();

        // Store values in array before manipulating report table in DB in a transaction.
        try {
            $transaction = $DB->start_delegated_transaction();

            $DB->delete_records('local_smartmedia_report_over');
            $DB->insert_records('local_smartmedia_report_over', $reportrecords);

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

        $uniquemultimediaobjects = $this->get_unique_multimedia_objects(); // Get count of multimedia objects files table.
        $this->update_report_data('uniquemultimediaobjects', $uniquemultimediaobjects);

        $metadataprocessedfiles = $this->get_metadata_processed_files(); // Get count of processed multimedia files.
        $this->update_report_data('metadataprocessedfiles', $metadataprocessedfiles);

        $transcodedfiles = $this->get_transcoded_files(); // Get count of transcoded multimedia files.
        $this->update_report_data('transcodedfiles', $transcodedfiles);

        mtrace('local_smartmedia: Processing data for overview report');
        // Build the dependencies.
        $api = new \local_smartmedia\aws_api();
        $pricingclient = new \local_smartmedia\aws_ets_pricing_client($api->create_pricing_client());
        $transcoder = new \local_smartmedia\aws_elastic_transcoder($api->create_elastic_transcoder_client());
        $this->process_overview_report($pricingclient, $transcoder);
    }

}
