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

namespace local_smartmedia;

use stdClass;
use stored_file;
use dml_exception;
use core\url;
use core\exception\moodle_exception;
use Exception;
use Aws\S3\Exception\S3Exception;
use core\context as CoreContext;

/**
 * Class for smart media conversion operations.
 *
 * @package     local_smartmedia
 * @copyright   2019 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class conversion {
    /**
     * @var array Audio mimetypes
     */
    private const AUDIO_MIMETYPES = [
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
    ];

    /**
     * Smart media conversion finished without error.
     *
     * @var integer
     */
    public const CONVERSION_FINISHED = 200;

    /**
     * Smart media conversion is in progres.
     *
     * @var integer
     */
    public const CONVERSION_IN_PROGRESS = 201;

    /**
     * Smart media conversion job has been created but processing has not yet started.
     *
     * @var integer
     */
    public const CONVERSION_ACCEPTED = 202;

    /**
     * No smart media conversion record found.
     *
     * @var integer
     */
    public const CONVERSION_NOT_FOUND = 404;

    /**
     * Smart media conversion finished with error.
     *
     * @var integer
     */
    public const CONVERSION_ERROR = 500;

    /**
     * Max files to get from Moodle files table per processing run.
     *
     * @var integer
     */
    private const MAX_FILES = 1000;

    /**
     * The message states we want to check for in messages received from the SQS queue.
     * We only care about successes and failures.
     * In normal operation we ignore progress and other messages.
     *
     * @var array
     */
    private const SQS_MESSAGE_STATES = [
        'SUCCEEDED', // Rekognition success status.
        'COMPLETE', // Media convert success status.
        'ERROR', // Media convert error status.
    ];

    /**
     * The mapping betweeen what AWS calls the service events and their corresponding DB field names.
     *
     * @var array
     */
    public const SERVICE_MAPPING = [
        'mediaconvert' => ['transcoder_status'],
        'StartLabelDetection' => ['rekog_label_status', 'Labels'],
        'StartContentModeration' => ['rekog_moderation_status', 'ModerationLabels'],
        'StartFaceDetection' => ['rekog_face_status', 'Faces'],
        'StartPersonTracking' => ['rekog_person_status', 'Persons'],
        'TranscribeComplete' => ['transcribe_status', 'transcription'],
        'SentimentComplete' => ['detect_sentiment_status', 'sentiment'],
        'PhrasesComplete' => ['detect_phrases_status', 'phrases'],
        'EntitiesComplete' => ['detect_entities_status', 'entities'],
    ];

    /**
     *  The file is not found on disk to transcode.
     */
    private const FILE_NOT_FOUND = 3;

    /**
     * @var mixed hash-like object of settings for local_smartmedia.
     */
    private $config;

    /**
     * @var aws_media_convert
     */
    private $mediaconvert;

    /**
     * Class constructor.
     *
     * @param aws_media_convert $mediaconvert
     *
     * @throws dml_exception
     */
    public function __construct(aws_media_convert $mediaconvert) {
        $this->config = get_config('local_smartmedia');
        $this->mediaconvert = $mediaconvert;
    }

    /**
     * Given a conversion id create records for each configured transcoding preset id,
     * ready to be stored in the Moodle database.
     *
     * @param int $convid The conversion id to create the preset records for.
     * @param string $contenthash The contenthash of the file to filter presets by based on streams.
     *
     * @return array $presetrecords The preset records to insert into the Moodle database.
     *
     * @throws dml_exception
     * @throws \core\exception\moodle_exception
     */
    private function get_preset_records(int $convid, string $contenthash): array {
        global $DB;
        $presetrecords = [];
        $presetids = $this->mediaconvert->get_preset_ids();

        // Get metadata for file from database.
        $streams = $DB->get_record('local_smartmedia_data', ['contenthash' => $contenthash], 'videostreams, audiostreams');

        // If file is video only remove audio streams.
        if ($streams && $streams->audiostreams == 0) {
            $audiostreams = array_merge(
                aws_media_convert::AUDIO_PRESETS,
                aws_media_convert::MPD_AUDIO,
                aws_media_convert::HLS_AUDIO
            );
            $presetids = array_diff($presetids, $audiostreams);
        }

        // If file is audio only remove video streams.
        if ($streams && $streams->videostreams == 0) {
            $videostreams = array_merge(
                aws_media_convert::LOW_PRESETS,
                aws_media_convert::MEDIUM_PRESETS,
                aws_media_convert::HIGH_PRESETS,
                aws_media_convert::EXTRA_HIGH_PRESETS,
                aws_media_convert::DOWNLOAD_PRESETS
            );
            $presetids = array_diff($presetids, $videostreams);
        }

        // Get all configured presets available.
        $presets = $this->mediaconvert->get_presets();

        foreach ($presets as $preset) {
            // Only add records for presets which weren't filtered out based on stream data.
            if (in_array($preset->get_id(), $presetids)) {
                $record = new stdClass();
                $record->convid = $convid;
                $record->preset = $preset->get_id();
                $record->container = $preset->get_container();

                $presetrecords[] = $record;
            }
        }

        return $presetrecords;
    }

    /**
     * Create the smart media conversion record.
     * These records will be processed by a scheduled task.
     *
     * @param stored_file $file The file object to create the conversion for.
     *
     * @throws \coding_exception
     * @throws dml_exception
     * @throws \dml_write_exception
     * @throws \core\exception\moodle_exception
     */
    private function create_conversion(stored_file $file): void {
        global $DB;
        $now = time();
        $convid = 0;

        $cnvrec = new stdClass();
        $cnvrec->pathnamehash = $file->get_pathnamehash();
        $cnvrec->contenthash = $file->get_contenthash();

        // All conversions will always have an overall status
        // and will always use elastic transcoder.
        $cnvrec->status = $this::CONVERSION_ACCEPTED;
        $cnvrec->transcoder_status = $this::CONVERSION_ACCEPTED;

        // Map the database schema to the plugin settings.
        $settingsmap = [
                'transcribe_status' => 'transcribe',
                'rekog_label_status' => 'detectlabels',
                'rekog_moderation_status' => 'detectmoderation',
                'rekog_face_status' => 'detectfaces',
                'rekog_person_status' => 'detectpeople',
                'detect_sentiment_status' => 'detectsentiment',
                'detect_phrases_status' => 'detectphrases',
                'detect_entities_status' => 'detectentities',
        ];

        // If this is an audio file, we must force disable all rekognition video API services.
        $audioonly = in_array($file->get_mimetype(), $this::AUDIO_MIMETYPES);

        // Process the settings.
        foreach ($settingsmap as $field => $setting) {
            // If we are audio only, we should only write the setting if it is not 'rekog' label.
            if (!$audioonly || (strpos($field, 'rekog') === false)) {
                $cnvrec->$field = $this->config->$setting == 1 ? $this::CONVERSION_ACCEPTED : $this::CONVERSION_NOT_FOUND;
            } else {
                $cnvrec->$field = $this::CONVERSION_NOT_FOUND;
            }
        }

        $cnvrec->timecreated = $now;
        $cnvrec->timemodified = $now;

        // Race conditions mean that we could try to create a conversion record multiple times.
        // This is OK and expected, we will handle the error.
        try {
            $convid = $DB->insert_record('local_smartmedia_conv', $cnvrec);
        } catch (dml_exception $e) {
            // If error is anything else but a duplicate insert, this is unexected,
            // so re-throw the error.
            // Postgres / Mysql error messages.
            if (stripos($e->getMessage(), 'duplicate') === false) {
                throw $e;
            }
        }

        // If we have a valid conversion record from the insert, then create the presets record.
        // With the above logic we shouldn't get race conditions here.
        if ($convid > 0) {
            $presetrecords = $this->get_preset_records($convid, $cnvrec->contenthash);
            $DB->insert_records('local_smartmedia_presets', $presetrecords);
        }
    }

    /**
     * Get the smart media conversion statuses for a given resource.
     *
     * @param stored_file $file The Moodle file object of the asset.
     * @return stdClass $result object containing the status of each conversion process.
     */
    private function get_conversion_statuses(stored_file $file): stdClass {
        global $DB;

        $contenthash = $file->get_contenthash();
        $conditions = ['contenthash' => $contenthash];
        $result = $DB->get_record(
            'local_smartmedia_conv',
            $conditions,
            'status, transcoder_status, transcribe_status,
            rekog_label_status, rekog_moderation_status, rekog_face_status, rekog_person_status,
            detect_sentiment_status, detect_phrases_status, detect_entities_status'
        );

        if (!$result) {
            $result = new stdClass();
            $result->status = self::CONVERSION_NOT_FOUND;
        }

        return $result;
    }

    /**
     * Given a Moodle URL check file exists in the Moodle file table
     * and retreive the file object.
     * This requires some horrible reverse engineering.
     *
     * @param \core\url $href Plugin file url to extract from.
     * @return \stored_file || bool $file The Moodle file object or false if file not found.
     */
    public function get_file_from_url(url $href) {
        // Extract the elements we need from the Moodle URL.
        $argumentsstring = $href->get_path(true);
        $rawarguments = explode('/', $argumentsstring);
        $pluginfileposition = array_search('pluginfile.php', $rawarguments);
        // Not a normalised pluginfile.php URL.
        if ($pluginfileposition === false) {
            return false;
        }
        $hrefarguments = array_slice($rawarguments, ($pluginfileposition + 1));
        $argumentcount = count($hrefarguments);

        $contextid = $hrefarguments[0];
        $component = clean_param($hrefarguments[1], PARAM_COMPONENT);
        $filearea = clean_param($hrefarguments[2], PARAM_AREA);
        // Unescape URL encoding inside filename here.
        $filename = clean_param(urldecode($hrefarguments[($argumentcount - 1)]), PARAM_FILE);

        // Sensible defaults for item id and filepath.
        $itemid = 0;
        $filepath = '/';

        // If item id is non zero then it will be the fourth element in the array.
        if ($argumentcount > 4) {
            $itemid = (int)$hrefarguments[3];
        }

        // Handle complex file paths in href.
        if ($argumentcount > 5) {
            $filepatharray = array_slice($hrefarguments, 4, -1);
            $filepath = '/' . implode('/', $filepatharray) . '/';
        }

        // Use the information we have extracted to get the pathname hash.
        $fs = get_file_storage();
        $file = $fs->get_file($contextid, $component, $filearea, $itemid, $filepath, $filename);

        // If there is a resolution failure, try getting all area files and matching on filename.
        // There are edge cases where the provided itemid may not direct match to the DB itemid.
        if (!$file) {
            $files = $fs->get_area_files($contextid, $component, $filearea);
            foreach ($files as $file) {
                if ($file->get_filename() === $filename) {
                    return $file;
                }
            }
            return false;
        }

        return $file;
    }

    /**
     * Finds files downloaded from MediaConvert that we have stored locally.
     *
     * @param string $contenthash
     * @return array $mediafiles
     */
    private function get_downloaded_converted_files(string $contenthash): array {
        global $DB;

        // Get all media files for this source file.
        $fs = get_file_storage();

        // This is basically the fetch used by file_storage:get_area_files() except it also includes
        // a filepath filter which should drastically reduce the number of records loaded into memory.
        // When the number of media files start to exceed 100,000, this extra filter is required to
        // reduce page loading times.

        // ORDER BY mimetype is added to ensure the ordering is consistent
        // (order matters to the Videojs player - it tries to play them in order).
        $sql = "SELECT " . self::instance_sql_fields() . "
                  FROM {files} f
             LEFT JOIN {files_reference} r
                    ON f.referencefileid = r.id
                 WHERE f.contextid = 1
                   AND f.component = 'local_smartmedia'
                   AND f.filearea = 'media'
                   AND f.itemid = 0
                   AND f.filepath = ?
                   ORDER BY mimetype DESC
                   ";
        $filepath = "/$contenthash/conversions/";
        $filerecords = $DB->get_records_sql($sql, [$filepath]);
        $mediafiles = [];
        foreach ($filerecords as $filerecord) {
            $mediafiles[$filerecord->pathnamehash] = $fs->get_file_instance($filerecord);
        }

        return $mediafiles;
    }

    /**
     * Get smart media for file.
     *
     * @param \core\url $href the url of the file to find smart media for.
     * @param bool $triggerconversion true if conversion should be triggered by this method, false otherwise.
     * @param bool $rawfiles return the file objects instead of the download urls. Used for downloading metadata from smartmedia.
     * @return array $smartmedia 2D array of \stored_file objects for the smart media associated with the $href file,
     *                  converted media is contained in 'media' element, metadata and other smart media files in the
     *                  'data' element.
     *                  Example:
     *                      ['media' => [\stored_file $file1, ...], 'data' => [\stored_file $file2, ...]]
     */
    public function get_smart_media(url $href, bool $triggerconversion = false, bool $rawfiles = false): array {
        $smartmedia = ['context' => null];
        $viewconversion = (bool)get_config('local_smartmedia', 'viewconversion');

        // Get the file record from the Moodle URL.
        $file = $this->get_file_from_url($href);

        if (!$file) {
            // If URL doesn't correspond to a real file in Moodle return early.
            return $smartmedia;
        }
        // Keep a hold of the context so we know where we are targeting the file.
        $smartmedia['context'] = CoreContext::instance_by_id($file->get_contextid());

        // Query conversion table for status.
        $conversionstatuses = $this->get_conversion_statuses($file);

        // If no record in table and trigger conversion is true add record.
        if ($triggerconversion && $conversionstatuses->status == self::CONVERSION_NOT_FOUND) {
            $this->create_conversion($file);
        } else if ($conversionstatuses->status == self::CONVERSION_NOT_FOUND && $viewconversion) {
            // If no record in table and convert on view is set add record.
            $this->create_conversion($file);
        }

        // If processing complete get all urls and data for source href.
        if ($conversionstatuses->status == self::CONVERSION_FINISHED) {
            $convertedfiles = $this->get_downloaded_converted_files($file->get_contenthash());
            $playerfiles = array_filter($convertedfiles, fn($f) => $this->is_player_file($f));
            $downloadfiles = array_filter($convertedfiles, fn($f) => $this->is_standalone_file($f));

            // Get media files.
            $smartmedia['media'] = $rawfiles ? $playerfiles : $this->map_files_to_urls($playerfiles, $file->get_id());
            $smartmedia['download'] = $rawfiles ? $downloadfiles : $this->map_files_to_urls($downloadfiles, $file->get_id());

            // Get data files.
            $fs = get_file_storage();
            $files = $fs->get_area_files(1, 'local_smartmedia', 'metadata', 0);
            $datafilepath = '/' . $file->get_contenthash() . '/metadata/';
            $datafiles = $this->filter_files_by_filepath($files, $datafilepath);
            $smartmedia['data'] = $rawfiles ? $datafiles : $this->map_files_to_urls($datafiles, $file->get_id());
        }

        // TODO: Cache the result for a very long time as once processing is finished it will never change
        // and when processing is finished we will explictly clear the cache.

        return $smartmedia;
    }

    /**
     * If file is meant to be played in the player.
     * I.e. top level playlists
     *
     * @param stored_file $file
     * @return bool
     */
    private function is_player_file(stored_file $file): bool {
        // Is top level playlist?
        // This works as sub level playlists have the preset name before the file extension (e.g. _mpegdash_playlistSmartmedia-HLS-600k.mpd)
        // By checking just _mpegdash_playlist.mpd we exclude these.
        $toplevelplaylistendings = [
            '_mpegdash_playlist.mpd',
            '_hls_playlist.m3u8',
        ];

        foreach ($toplevelplaylistendings as $toplevelplaylistending) {
            if (str_ends_with($file->get_filename(), $toplevelplaylistending)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Is this a standalone Mp4 or mp3 (i.e. not a playlist, or file referenced by a playlist)
     *
     * @param stored_file $file
     * @return bool
     */
    private function is_standalone_file(stored_file $file): bool {
        // Is this a standalone mp4 or mp3?
        // Note, playlists also have Mp4's but these are NOT standalone.
        // So we look for files with the standalone preset name in their name.
        // TODO for this to be backwards compatibler, we need to update the old preset its to the new names.
        $standalonepresetnames = [
            aws_media_convert::PRESET_MP3_AUDIO . '.mp3',
            aws_media_convert::PRESET_WEB . '.mp4',

            // Legacy backwards compatibility.
            // These are the same as above, but the old Elastic Transcode versions.
            '1351620000001-300020.mp3', // MP3 audio
            '1351620000001-100070.mp4', // MP4 "Web" standalone
        ];

        foreach ($standalonepresetnames as $standalonepresetname) {
            if (str_ends_with($file->get_filename(), (string) $standalonepresetname)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all stored files which are within a particular filepath.
     *
     * @param array $files \stored_file objects to filter.
     * @param string $filepath the filepath to filter \stored_file objects by.
     *
     * @return array $filteredfiles of \stored_file objects in the $filepath.
     */
    private function filter_files_by_filepath($files, $filepath): array {

        $filteredfiles = [];

        foreach ($files as $file) {
            if ($file->get_filepath() == $filepath && !$file->is_directory()) {
                $filteredfiles[] = $file;
            }
        }
        return $filteredfiles;
    }

    /**
     * Get conversion records to process smartmedia conversions.
     *
     * @param int $status Status of records to get.
     * @return array $filerecords Records to process.
     */
    private function get_conversion_records(int $status): array {
        global $DB;

        $conditions = ['status' => $status];
        $limit = self::MAX_FILES;
        $fields = 'id, pathnamehash, contenthash, status, transcoder_status, transcribe_status,
                  rekog_label_status, rekog_moderation_status, rekog_face_status, rekog_person_status,
                  detect_sentiment_status, detect_phrases_status, detect_entities_status';

        // We should check forwards back, and prioritise immediate latency on new small files.
        // If something is taking a long time, we can clear many small newer files without blocking the queue.
        $filerecords = $DB->get_records('local_smartmedia_conv', $conditions, 'timecreated DESC', $fields, 0, $limit);

        return $filerecords;
    }

    /**
     * Map all passed in files to moodle urls for linking to the files.
     *
     * @param array $files an array of plugin files to map to urls.
     * @param int $fileid the file id of the parent moodle file for these conversion files.
     *
     * @return array $urls of \moodle_url objects for the files.
     */
    private function map_files_to_urls($files, int $fileid): array {
        $urls = [];
        foreach ($files as $file) {
            // Build the custom serve URL.
            $url = url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $fileid,
                $file->get_filepath(),
                $file->get_filename()
            );
            $urls[] = $url;
        }
        return $urls;
    }

    /**
     * Get the configured conversion for this conversion record in a format that will
     * be sent to AWS for processing.
     *
     * @param stdClass $conversionrecord The conversion record to get the settings for.
     * @return array $settings The conversion record settings.
     */
    private function get_conversion_settings(stdClass $conversionrecord): array {
        global $CFG;
        $settings = [];

        // Metadata space per S3 object is limited so do some dirty encoding
        // of the processes we want to carry out on the file. These are
        // interpereted on the AWS side.

        $processes = '';
        $processes .= $conversionrecord->transcribe_status == self::CONVERSION_ACCEPTED ? '1' : '0';
        $processes .= $conversionrecord->rekog_label_status == self::CONVERSION_ACCEPTED ? '1' : '0';
        $processes .= $conversionrecord->rekog_moderation_status == self::CONVERSION_ACCEPTED ? '1' : '0';
        $processes .= $conversionrecord->rekog_face_status == self::CONVERSION_ACCEPTED ? '1' : '0';
        $processes .= $conversionrecord->rekog_person_status == self::CONVERSION_ACCEPTED ? '1' : '0';
        $processes .= $conversionrecord->detect_sentiment_status == self::CONVERSION_ACCEPTED ? '1' : '0';
        $processes .= $conversionrecord->detect_phrases_status == self::CONVERSION_ACCEPTED ? '1' : '0';
        $processes .= $conversionrecord->detect_entities_status == self::CONVERSION_ACCEPTED ? '1' : '0';

        $presets = $this->get_preset_records($conversionrecord->id, $conversionrecord->contenthash);

        $settings['processes'] = $processes;
        $settings['presets'] = $this->create_presets_metadata($presets);
        $settings['siteid'] = $CFG->siteidentifier;

        return $settings;
    }

    /**
     * Create a json encoded string of preset data where AWS ETS preset id is the key and the container type
     * is the value.
     * Example: "{'1351620000001-100070': 'mp4', '1351620000001-500030': 'fmp4'}"
     *
     * @param array $presets array of preset records.
     *
     * @return string $metadata json encoded string.
     */
    private function create_presets_metadata(array $presets): string {
        $presetarray = [];

        foreach ($presets as $preset) {
            $presetarray[$preset->preset] = $preset->container;
        }
        $metadata = json_encode($presetarray);
        return $metadata;
    }

    /**
     * Send file for conversion processing in AWS.
     *
     * @param stored_file $file The file to upload for conversion.
     * @param array $settings Settings to be used for file conversion.
     * @param \Aws\MockHandler|null $handler Optional handler.
     * @return int $status The status code of the upload.
     */
    private function send_file_for_processing(stored_file $file, array $settings, $handler = null): int {
        $awss3 = new aws_s3();
        $s3client = $awss3->create_client($handler);

        $uploadparams = [
            'Bucket' => $this->config->s3_input_bucket, // Required.
            'Key' => $file->get_contenthash(), // Required.
            'Body' => $file->get_content_file_handle(), // Required.
            'Metadata' => $settings,
        ];

        try {
            $s3client->putObject($uploadparams);
            $status = self::CONVERSION_IN_PROGRESS;
        } catch (S3Exception $e) {
            $status = self::CONVERSION_ERROR;
        }

        // TODO: add event for file sending include status etc.

        return $status;
    }

    /**
     * Update conversion records in the Moodle database.
     *
     * @param array $results The result details to update the records.
     */
    private function update_conversion_records(array $results): void {
        global $DB;

        // Check if we are going to be performing multiple inserts.
        if (count($results) > 1) {
            $expectbulk = true;
        } else {
            $expectbulk = false;
        }

        // Update the records in the database.
        foreach ($results as $key => $result) {
            $updaterecord = new stdClass();
            $updaterecord->id = $key;
            $updaterecord->status = $result;
            $updaterecord->timemodified = time();

            $DB->update_record('local_smartmedia_conv', $updaterecord, $expectbulk);
        }
    }

    /**
     * Process not yet started conversions.
     *
     * @return array $results The results of the processing.
     */
    public function process_conversions(): array {
        $results = [];
        $fs = get_file_storage();
        $conversionrecords = $this->get_conversion_records(self::CONVERSION_ACCEPTED); // Get not yet started conversion records.

        foreach ($conversionrecords as $conversionrecord) { // Itterate through not yet started records.
            $settings = $this->get_conversion_settings($conversionrecord); // Get convession settings.
            $file = $fs->get_file_by_hash($conversionrecord->pathnamehash); // Get the file to process.
            // Skip file conversion if file not found.
            if ($file === false) {
                $results[$conversionrecord->id] = self::FILE_NOT_FOUND;
            } else {
                $results[$conversionrecord->id] = $this->send_file_for_processing($file, $settings); // Send for processing.
            }
            $this->update_conversion_records($results); // Update conversion records.
        }

        return $results;
    }

    /**
     * Given a conversion record get all the messages from the sqs queue message table
     * that are for this contenthash (object id).
     * We only get "success" and "failure" messages we don't care about pending or warning messages.
     * Only check for messages relating to configured conversions for this record that haven't
     * already succeed or failed.
     *
     * @param stdClass $conversionrecord The conversion record to get messages for.
     * @return array $queuemessages The matching queue messages.
     */
    private function get_queue_messages(stdClass $conversionrecord): array {
        global $DB;

        // Using the conversion record determine which services we are looking for messages from.
        // Only get messages for conversions that have not yet finished.
        $services = [];

        if (
            $conversionrecord->transcoder_status == self::CONVERSION_ACCEPTED
            || $conversionrecord->transcoder_status == self::CONVERSION_IN_PROGRESS
        ) {
                $services[] = 'mediaconvert';
        }
        if (
            $conversionrecord->rekog_label_status == self::CONVERSION_ACCEPTED
            || $conversionrecord->rekog_label_status == self::CONVERSION_IN_PROGRESS
        ) {
            $services[] = 'StartLabelDetection';
        }
        if (
            $conversionrecord->rekog_moderation_status == self::CONVERSION_ACCEPTED
            || $conversionrecord->rekog_moderation_status == self::CONVERSION_IN_PROGRESS
        ) {
            $services[] = 'StartContentModeration';
        }
        if (
            $conversionrecord->rekog_face_status == self::CONVERSION_ACCEPTED
            || $conversionrecord->rekog_face_status == self::CONVERSION_IN_PROGRESS
        ) {
            $services[] = 'StartFaceDetection';
        }
        if (
            $conversionrecord->rekog_person_status == self::CONVERSION_ACCEPTED
            || $conversionrecord->rekog_person_status == self::CONVERSION_IN_PROGRESS
        ) {
            $services[] = 'StartPersonTracking';
        }
        if (
            $conversionrecord->transcribe_status == self::CONVERSION_ACCEPTED
            || $conversionrecord->transcribe_status == self::CONVERSION_IN_PROGRESS
        ) {
            $services[] = 'TranscribeComplete';
        }
        if (
            $conversionrecord->detect_sentiment_status == self::CONVERSION_ACCEPTED
            || $conversionrecord->detect_sentiment_status == self::CONVERSION_IN_PROGRESS
        ) {
            $services[] = 'SentimentComplete';
        }
        if (
            $conversionrecord->detect_phrases_status == self::CONVERSION_ACCEPTED
            || $conversionrecord->detect_phrases_status == self::CONVERSION_IN_PROGRESS
        ) {
            $services[] = 'PhrasesComplete';
        }
        if (
            $conversionrecord->detect_entities_status == self::CONVERSION_ACCEPTED
            || $conversionrecord->detect_entities_status == self::CONVERSION_IN_PROGRESS
        ) {
            $services[] = 'EntitiesComplete';
        }

        // Get all queue messages for this object.
        [$processinsql, $processinparams] = $DB->get_in_or_equal($services);
        [$statusinsql, $statusinparams] = $DB->get_in_or_equal(self::SQS_MESSAGE_STATES);
        $params = array_merge($processinparams, $statusinparams);
        $params[] = $conversionrecord->contenthash;

        $sql = "SELECT *
                  FROM {local_smartmedia_queue_msgs}
                 WHERE process $processinsql
                       AND status $statusinsql
                       AND objectkey = ?";
        $queuemessages = $DB->get_records_sql($sql, $params);

        return $queuemessages;
    }

    /**
     * Given a source file and a smartmedia file object,
     * check that the two files are correctly related to each other.
     * That is the smartmedia file was derived from the source file.
     *
     * This is used in checking that the smartmedia file is OK to
     * send to an end user.
     *
     * @param stored_file $sourcefile The source file we want to check against.
     * @param stored_file $smartfile The smartmedia file we want to make sure is associated with the source.
     * @return bool True if the checks are valid, false otherwise.
     */
    public function check_smartmedia_file(stored_file $sourcefile, stored_file $smartfile): bool {
        global $DB;

        // The contenthash of the source file should have a matching entry in the local_smartmedia_conv table.
        $select = 'contenthash = ? AND status <> ?';
        $params = [$sourcefile->get_contenthash(), self::CONVERSION_ERROR];
        $sourcehashexists = $DB->record_exists_select('local_smartmedia_conv', $select, $params);
        if (!$sourcehashexists) {
            return false;
        }

        // The contenthash of the source file should match the contenthash part of the smartmedia file filepath.
        $smartfilepath = $smartfile->get_filepath();
        $patharray = explode('/', $smartfilepath);
        if ($sourcefile->get_contenthash() != $patharray[1]) {
            return false;
        }

        return true;
    }

    /**
     * Get the transcoded media files from AWS S3,
     *
     * @param stdClass $conversionrecord The conversion record from the database.
     * @param \Aws\MockHandler|null $handler Optional handler.
     *
     * @return array $transcodedfiles Array of \stored_file objects.
     */
    public function get_transcode_files(stdClass $conversionrecord, $handler = null): array {
        $awss3 = new aws_s3();
        $s3client = $awss3->create_client($handler);
        $transcodedfiles = [];

        // Transcoding could have made many files, but the job only calls success when all files are generated.
        // So first we get a list of the files.
        $listparams = [
                'Bucket' => $this->config->s3_output_bucket,
                'MaxKeys' => 1000, // The maximum allowed before we need to page, we should NEVER have this many.
                'Prefix' => $conversionrecord->contenthash . '/conversions/', // Location in the S3 bucket where the files live.
        ];
        $availableobjects = $s3client->listObjects($listparams)->get('Contents') ?? [];

        // Then we iterate over that list and get all the files available.
        $fs = get_file_storage();
        $requestdir = make_request_directory();
        foreach ($availableobjects as $availableobject) {
            $filename = basename($availableobject['Key']);
            $filerecord = [
                'contextid' => 1, // Put files in the site level context as they aren't associated with a specific context.
                'component' => 'local_smartmedia',
                'filearea' => 'media',
                'itemid' => 0,
                'filepath' => '/' . $conversionrecord->contenthash . '/conversions/',
                'filename' => $filename,
            ];
            $downloadparams = [
                'Bucket' => $this->config->s3_output_bucket, // Required.
                'Key' => $availableobject['Key'], // Required.
            ];

            try {
                // Playlist files (MPD and HLS) link to other playlists and video streams inside of them
                // So we need to update these references to be pluginfile links instead.
                if (str_ends_with($filename, '.mpd') || str_ends_with($filename, '.m3u8')) {
                    // This is small enough that we can just pull direct to memory.
                    $result = $s3client->getObject($downloadparams);
                    $filecontent = $result->get('Body');
                    $filecontent = $this->replace_playlist_urls_with_pluginfile_urls($filecontent, $conversionrecord->contenthash);

                    $transcodedfile = $fs->create_file_from_string($filerecord, $filecontent);
                } else {
                    // Video file handling. Might be too big for memory, write to disk first.
                    $filetarget = $requestdir . '/' . $filename;
                    $downloadparams['SaveAs'] = $filetarget;
                    $s3client->getObject($downloadparams);

                    $transcodedfile = $fs->create_file_from_pathname($filerecord, $filetarget);
                    if (file_exists($filetarget)) {
                        unlink($filetarget);
                    }
                }
            } catch (moodle_exception $e) {
                // This file may already exist. Perhaps a reprocessed queue message.
                // Either way, there isn't anything we can do about it.
                // Move on.
                continue;
            }
            $transcodedfiles[] = $transcodedfile;
        }
        return $transcodedfiles;
    }

    /**
     * Replace relative urls in a media playlist with pluginfile urls so the playlist may serve files in Moodle.
     *
     * @param string $filecontent the handle for the file to replace urls in.
     * @param string $contenthash the content hash for conversion to search for and replace.
     *
     * @return string $updatedcontent The updated file content.
     */
    private function replace_playlist_urls_with_pluginfile_urls($filecontent, string $contenthash): string {
        // New path.
        $pluginfilepath = "/pluginfile.php/1/local_smartmedia/media/0/$contenthash/conversions/$contenthash";

        // Replace contenthash (prefix used in all playlist references)
        // to be a pluginfile url instead.
        $updatedcontent = preg_replace('/' . $contenthash . '/', $pluginfilepath, $filecontent);

        return $updatedcontent;
    }

    /**
     * Delete processed files from the AWS S3 input and output buckets.
     *
     * @param string $key The key of the file in the AWS S3 buckets.
     * @param \Aws\MockHandler|null $handler Optional handler.
     * @return array $keys The keys (paths) of the deleted objects.
     */
    private function cleanup_aws_files(string $key, $handler = null): array {
        $awss3 = new aws_s3();
        $s3client = $awss3->create_client($handler);
        $keys = [];

        // Delete original file from input bucket.
        try {
            $s3client->deleteObject([
                'Bucket' => $this->config->s3_input_bucket,
                'Key' => $key,
            ]);
        } catch (S3Exception $e) {
            debugging('local_smartmedia: Failed to delete object with key: ' . $key . ' from input bucket.');
        }

        // Delete all converted files from output bucket.
        // This is a bit convoluted as you can't delete objects
        // by prefix.
        // TODO: Make this work for more than 1000 objects.

        // Get the keys.
        $objectlist = $s3client->listObjects([
            'Bucket' => $this->config->s3_output_bucket,
        ]);

        if (!empty($objectlist['Contents'])) {
            foreach ($objectlist['Contents'] as $object) {
                if (str_starts_with($object['Key'], $key)) { // Check if list key starts with the given has.
                    $keys[] = ['Key' => $object['Key']];
                }
            }
        }

        // Delete the objects.
        if (!empty($keys)) {
            try {
                $s3client->deleteObjects([
                    'Bucket' => $this->config->s3_output_bucket,
                    'Delete' => [
                        'Objects' => $keys,
                    ],
                ]);
            } catch (S3Exception $e) {
                debugging('local_smartmedia: Failed to delete objects with key: ' . $key . ' from output bucket.');
            }
        }

        return $keys;
    }

    /**
     * Get the file from AWS for a given conversion process.
     *
     * @param stdClass $conversionrecord The conversion record from the database.
     * @param string $process The process to get the file for.
     * @param \Aws\MockHandler|null $handler Optional handler.
     */
    public function get_data_file(stdClass $conversionrecord, string $process, $handler = null): bool {
        $awss3 = new aws_s3();
        $s3client = $awss3->create_client($handler);

        $objectkey = self::SERVICE_MAPPING[$process][1];
        $fs = get_file_storage();

        $filerecord = [
            'contextid' => 1, // Put files in the site level context as they aren't associated with a specific context.
            'component' => 'local_smartmedia',
            'filearea' => 'metadata',
            'itemid' => 0,
            'filepath' => '/' . $conversionrecord->contenthash . '/metadata/',
            'filename' => $objectkey . '.json',
        ];

        $downloadparams = [
                'Bucket' => $this->config->s3_output_bucket, // Required.
                'Key' => $conversionrecord->contenthash . '/metadata/' . $objectkey . '.json', // Required.
        ];

        try {
            $getobject = $s3client->getObject($downloadparams);
        } catch (Exception $e) {
            // This key must not exist, or similar. Handle all exceptions and return false.
            debugging("Failed getting specified data file {$downloadparams['Key']} from output bucket.");
            return false;
        }

        $tmpfile = tmpfile();
        if (empty($getobject['Body'])) {
            // Null object received from S3... nothing to do here but return false.
            return false;
        }
        fwrite($tmpfile, $getobject['Body']);
        $tmppath = stream_get_meta_data($tmpfile)['uri'];

        try {
            $fs->create_file_from_pathname($filerecord, $tmppath);
        } catch (moodle_exception $e) {
            // This data file may already exist. Perhaps a reprocessed queue message.
            // Either way, there isn't anything we can do about it.
            // Move on.
            fclose($tmpfile);
            return false;
        }
        fclose($tmpfile);
        return true;
    }

    /**
     * Process the conversion records and get the files from AWS.
     *
     * @param stdClass $conversionrecord The conversion record from the database.
     * @param array $queuemessages Queue messages from the database relating to this conversion record.
     * @param \Aws\MockHandler|null $handler Optional handler.
     * @return stdClass $conversionrecord The updated conversion record.
     */
    private function process_conversion(stdClass $conversionrecord, array $queuemessages, $handler = null): stdClass {
        global $DB;

        // If there are no queue messages exit early.
        if (empty($queuemessages)) {
            return $conversionrecord;
        }

        foreach ($queuemessages as $message) {
            if ($message->status == 'ERROR' && $message->process == 'mediaconvert') {
                // If MediaConvert conversion has failed then all other conversions have also failed.
                // It is also highly likely this will be the only message recevied.
                $conversionrecord->status = self::CONVERSION_ERROR;
                $conversionrecord->transcoder_status = self::CONVERSION_ERROR;
                $conversionrecord->rekog_label_status = self::CONVERSION_ERROR;
                $conversionrecord->rekog_moderation_status = self::CONVERSION_ERROR;
                $conversionrecord->rekog_face_status = self::CONVERSION_ERROR;
                $conversionrecord->rekog_person_status = self::CONVERSION_ERROR;
                $conversionrecord->timecreated = time();
                $conversionrecord->timecompleted = time();

                break;
            } else if ($message->status == 'COMPLETE' || $message->status == 'SUCCEEDED') {
                // For each successful status get the file/s for the conversion.
                if ($message->process == 'mediaconvert') {
                    // Get converted files.
                    $this->get_transcode_files($conversionrecord, $handler);

                    $conversionrecord->transcoder_status = self::CONVERSION_FINISHED;
                } else {
                    // Get other process data files.
                    $this->get_data_file($conversionrecord, $message->process, $handler);

                    $statusfield = self::SERVICE_MAPPING[$message->process][0];
                    $conversionrecord->{$statusfield} = self::CONVERSION_FINISHED;
                }
            } else if ($message->status == 'ERROR') {
                // For each failed status mark it as failed in the record.
                $statusfield = self::SERVICE_MAPPING[$message->process][0];
                $conversionrecord->{$statusfield} = self::CONVERSION_ERROR;
            }
        }

        // Update the database with the modified conversion record.
        $DB->update_record('local_smartmedia_conv', $conversionrecord);

        return $conversionrecord;
    }

    /**
     * Update the overall completion status for a completion record.
     * Overall conversion record is finished when all the individual conversions are finished.
     *
     *
     * @param stdClass $record The record to check the completion status for.
     * @param \Aws\MockHandler|null $handler Optional handler.
     * @return stdClass $updatedrecord The updated completion record.
     */
    public function update_completion_status(stdClass $record, $handler = null): stdClass {
        global $DB;

        $completionfields = [
            'transcoder_status',
            'transcribe_status',
            'rekog_label_status',
            'rekog_moderation_status',
            'rekog_face_status',
            'rekog_person_status',
            'detect_sentiment_status',
            'detect_phrases_status',
            'detect_entities_status',
        ];

        $completionstatus = true;
        // Assume completion is true. Iterate through every field.
        // Errors or finished, either way the record is no longer pending.
        foreach ($completionfields as $field) {
            $completionstatus &= (
                $record->$field == self::CONVERSION_FINISHED ||
                $record->$field == self::CONVERSION_NOT_FOUND ||
                $record->$field == self::CONVERSION_ERROR
            );
        }

        if (!empty($record->timemodified)) {
            $timeout = $record->timemodified < (time() - DAYSECS);
        } else {
            $timeout = false;
        }

        // Only set the final completion status if all other processes are finished.
        if ($completionstatus || $timeout) {
            $record->status = self::CONVERSION_FINISHED;
            $record->timemodified = time();
            $record->timecompleted = time();

            // Update the database with the modified conversion record.
            $DB->update_record('local_smartmedia_conv', $record);

            // Delete the related files from AWS.
            $this->cleanup_aws_files($record->contenthash, $handler);
        }

        return $record;
    }

    /**
     * Update pending conversions.
     *
     * @return array $results The results of the processing.
     */
    public function update_pending_conversions(): array {
        $results = [];
        $conversionrecords = $this->get_conversion_records(self::CONVERSION_IN_PROGRESS); // Get pending conversion records.

        foreach ($conversionrecords as $conversionrecord) { // Itterate through pending records.
            // Get recevied messages for this conversion record that are not related to already completed processes.
            $queuemessages = $this->get_queue_messages($conversionrecord);

            // Process the messages and get files from AWS as required.
            $updatedrecord = $this->process_conversion($conversionrecord, $queuemessages);

            // If all conversions have reached a final state (complete or failed) update overall conversion status.
            $results[] = $this->update_completion_status($updatedrecord);
        }

        return $results;
    }

    /**
     * Get the fileids for files that have metadata extracted,
     * but that do not have conversion records.
     *
     * @return array $fileids Array of File IDs.
     */
    private function get_fileids(): array {
        global $DB;
        $convertfrom = time() - (int)get_config('local_smartmedia', 'convertfrom');

        $limit = self::MAX_FILES;
        $sql = "SELECT MAX(f.id) AS id, lsd.contenthash
                  FROM {local_smartmedia_data} lsd
             LEFT JOIN {local_smartmedia_conv} lsc ON lsd.contenthash = lsc.contenthash
             LEFT JOIN (SELECT * FROM {files} ORDER BY timecreated DESC) f ON lsd.contenthash = f.contenthash
                 WHERE lsc.contenthash IS NULL
                   AND f.timecreated > ?
                   AND f.component <> ?
                   AND f.filearea <> ?
                   AND f.filename <> ?
              GROUP BY lsd.contenthash";
        $params = [
            $convertfrom,
            'local_smartmedia',
            'draft',
            '.',
        ];
        $fileids = $DB->get_records_sql($sql, $params, 0, $limit);

        return $fileids;
    }

    /**
     * Create conversion records for files that have metadata,
     * but don't have conversion records.
     *
     * @return array
     */
    public function create_conversions(): array {
        $fileids = $this->get_fileids(); // Get File ids for conversions.
        $fs = get_file_storage();

        foreach ($fileids as $key => $id) {
            $file = $fs->get_file_by_id($id->id);
            if ($file === false) {
                // If file not found, remove this element from hash array.
                unset($fileids[$key]);
            } else {
                try {
                    $this->create_conversion($file);
                } catch (dml_exception $e) {
                    // Likely duplicate record, unset and move on.
                    unset($fileids[$key]);
                }
            }
        }

        return $fileids;
    }

    /**
     * Check if a given Moodle URL will be converted to smartmedia.
     *
     * @param \core\url $href The Moodle URL to check.
     * @return int The status of the check.
     */
    public function will_convert(url $href): int {
        // Get the file record from the Moodle URL.
        $file = $this->get_file_from_url($href);

        if (!$file) {
            // If URL doesn't correspond to a real file in Moodle return early.
            return self::CONVERSION_ERROR;
        }

        // Check for an existing conversion record.
        $statuses = $this->get_conversion_statuses($file);
        if ($statuses->status != self::CONVERSION_NOT_FOUND) {
            return $statuses->status;
        }

        // Check conversions are enabled (view and background)
        // file is newer than from config.
        $background = get_config('local_smartmedia', 'proactiveconversion');
        $view = get_config('local_smartmedia', 'viewconversion');
        $convertfrom = time() - (int)get_config('local_smartmedia', 'convertfrom');
        if (($background || $view) && ($file->get_timecreated() > $convertfrom)) {
            return self::CONVERSION_ACCEPTED;
        }

        return self::CONVERSION_NOT_FOUND;
    }

    /**
     * Taken from \file_storage.
     *
     * Get the sql formated fields for a file instance to be created from a
     * {files} and {files_refernece} join.
     *
     * @param string $filesprefix the table prefix for the {files} table
     * @param string $filesreferenceprefix the table prefix for the {files_reference} table
     * @return string the sql to go after a SELECT
     */
    private static function instance_sql_fields(string $filesprefix = 'f', string $filesreferenceprefix = 'r') {
        // Note, these fieldnames MUST NOT overlap between the two tables,
        // else problems like MDL-33172 occur.
        $filefields = ['contenthash', 'pathnamehash', 'contextid', 'component', 'filearea',
            'itemid', 'filepath', 'filename', 'userid', 'filesize', 'mimetype', 'status', 'source',
            'author', 'license', 'timecreated', 'timemodified', 'sortorder', 'referencefileid'];

        $referencefields = ['repositoryid' => 'repositoryid',
            'reference' => 'reference',
            'lastsync' => 'referencelastsync'];

        // id is specifically named to prevent overlapping between the two tables.
        $fields = [];
        $fields[] = $filesprefix . '.id AS id';
        foreach ($filefields as $field) {
            $fields[] = "{$filesprefix}.{$field}";
        }

        foreach ($referencefields as $field => $alias) {
            $fields[] = "{$filesreferenceprefix}.{$field} AS {$alias}";
        }

        return implode(', ', $fields);
    }
}
