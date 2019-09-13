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
 * @copyright   2019 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_smartmedia;

defined('MOODLE_INTERNAL') || die();

use Aws\S3\Exception\S3Exception;
use Aws\MockHandler;
use moodle_url;

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
    private const SQS_MESSAGE_STATES = array(
        'SUCCEEDED', // Rekognition success status.
        'COMPLETED', // Elastic Transcoder success status.
        'ERROR', // Elastic Transcoder error status.
    );

    /**
     * The mapping betweeen what AWS calls the service events and their corresponding DB field names.
     *
     * @var array
     */
    private const SERVICE_MAPPING = array(
        'elastic_transcoder' => array('transcoder_status'),
        'StartLabelDetection' => array('rekog_label_status', 'Labels'),
        'StartContentModeration' => array('rekog_moderation_status', 'ModerationLabels'),
        'StartFaceDetection' => array('rekog_face_status', 'Faces'),
        'StartPersonTracking' => array('rekog_person_status', 'Persons'),

    );

    /**
     * @var mixed hash-like object of settings for local_smartmedia.
     */
    private $config;

    /**
     * @var \local_smartmedia\aws_elastic_transcoder the transcoder for accessing communicating with the
     * AWS Elastic Transcoding Service.
     */
    private $transcoder;

    /**
     * Class constructor.
     *
     * @param \local_smartmedia\aws_elastic_transcoder $transcoder
     *
     * @throws \dml_exception
     */
    public function __construct(aws_elastic_transcoder $transcoder) {
        $this->config = get_config('local_smartmedia');
        $this->transcoder = $transcoder;
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
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    private function get_preset_records(int $convid, string $contenthash) : array {
        global $DB;
        $presetrecords = array();
        $presetids = $this->transcoder->get_preset_ids();

        // Get metadata for file from database.
        $streams = $DB->get_record('local_smartmedia_data', array('contenthash' => $contenthash), 'videostreams, audiostreams');

        // If file is video only remove audio streams.
        if ($streams && $streams->audiostreams == 0) {
            $audiostreams = aws_elastic_transcoder::AUDIO_PRESETS;
            $presetids = array_diff($presetids, $audiostreams);
        }

        // If file is audio only remove video streams.
        if ($streams && $streams->videostreams == 0) {
            $videostreams = array_merge(
                aws_elastic_transcoder::LOW_PRESETS,
                aws_elastic_transcoder::MEDIUM_PRESETS,
                aws_elastic_transcoder::HIGH_PRESETS,
                aws_elastic_transcoder::DOWNLOAD_PRESETS
                );
            $presetids = array_diff($presetids, $videostreams);
        }

        // Get all configured presets available.
        $presets = $this->transcoder->get_presets();

        foreach ($presets as $preset) {
            // Only add records for presets which weren't filtered out based on stream data.
            if (in_array($preset->get_id(), $presetids)) {
                $record = new \stdClass();
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
     * @param \stored_file $file The file object to create the conversion for.
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \dml_write_exception
     * @throws \moodle_exception
     */
    private function create_conversion(\stored_file $file) : void {
        global $DB;
        $now = time();
        $convid = 0;

        $cnvrec = new \stdClass();
        $cnvrec->pathnamehash = $file->get_pathnamehash();
        $cnvrec->contenthash = $file->get_contenthash();

        // All conversions will always have an overall status
        // and will always use elastic transcoder.
        $cnvrec->status = $this::CONVERSION_ACCEPTED;
        $cnvrec->transcoder_status = $this::CONVERSION_ACCEPTED;

        // Map the database schema to the plugin settings.
        $settingsmap = array(
                'transcribe_status' => 'transcribe',
                'rekog_label_status' => 'detectlabels',
                'rekog_moderation_status' => 'detectmoderation',
                'rekog_face_status' => 'detectfaces',
                'rekog_person_status' => 'detectpeople',
                'detect_sentiment_status' => 'detectsentiment',
                'detect_phrases_status' => 'detectphrases',
                'detect_entities_status' => 'detectentities',
        );

        // Process the settings.
        foreach ($settingsmap as $field => $setting) {
            $cnvrec->$field = $this->config->$setting == 1 ? $this::CONVERSION_ACCEPTED : $this::CONVERSION_NOT_FOUND;
        }

        $cnvrec->timecreated = $now;
        $cnvrec->timemodified = $now;

        // Race conditions mean that we could try to create a conversion record multiple times.
        // This is OK and expected, we will handle the error.
        try {
            $convid = $DB->insert_record('local_smartmedia_conv', $cnvrec);

        } catch (\dml_write_exception $e) {
            // If error is anything else but a duplicate insert, this is unexected,
            // so re-throw the error.
            if (!strpos($e->getMessage(), 'locasmarconv_pat_uix') && !strpos($e->getMessage(), 'locasmarconv_con_uix')) {
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
     * @param \stored_file $file The Moodle file object of the asset.
     * @return \stdClass $result object containing the status of each conversion process.
     */
    private function get_conversion_statuses(\stored_file $file) : \stdClass {
        global $DB;

        $contenthash = $file->get_contenthash();
        $conditions = array('contenthash' => $contenthash);
        $result = $DB->get_record('local_smartmedia_conv', $conditions,
            'status, transcoder_status, transcribe_status,
            rekog_label_status, rekog_moderation_status, rekog_face_status, rekog_person_status,
            detect_sentiment_status, detect_phrases_status, detect_entities_status');

        if (!$result) {
            $result = new \stdClass();
            $result->status = self::CONVERSION_NOT_FOUND;
        }

        return $result;
    }

    /**
     * Given a Moodle URL check file exists in the Moodle file table
     * and retreive the file object.
     * This requires some horrible reverse engineering.
     *
     * @param \moodle_url $href Plugin file url to extract from.
     * @return \stored_file $file The Moodle file object.
     */
    private function get_file_from_url(\moodle_url $href) : \stored_file {
        // Extract the elements we need from the Moodle URL.
        $argumentsstring = $href->get_path(true);
        $rawarguments = explode('/', $argumentsstring);
        $pluginfileposition = array_search('pluginfile.php', $rawarguments);
        $hrefarguments = array_slice($rawarguments, ($pluginfileposition + 1));
        $argumentcount = count($hrefarguments);

        $contextid = $hrefarguments[0];
        $component = clean_param($hrefarguments[1], PARAM_COMPONENT);
        $filearea = clean_param($hrefarguments[2], PARAM_AREA);
        $filename = $hrefarguments[($argumentcount - 1)];

        // Sensible defaults for item id and filepath.
        $itemid = 0;
        $filepath = '/';

        // If item id is non zero then it will be the fourth element in the array.
        if ($argumentcount > 4 ) {
            $itemid = (int)$hrefarguments[3];
        }

        // Handle complex file paths in href.
        if ($argumentcount > 5 ) {
            $filepatharray = array_slice($hrefarguments, 4, -1);
            $filepath = '/' . implode('/', $filepatharray) . '/';
        }

        // Use the information we have extracted to get the pathname hash.
        $fs = get_file_storage();
        $file = $fs->get_file($contextid, $component, $filearea, $itemid, $filepath, $filename);

        return $file;
    }

    /**
     * Get smart media for file.
     *
     * @param \moodle_url $href the url of the file to find smart media for.
     * @param bool $triggerconversion true if conversion should be triggered by this method, false otherwise.
     * @return array $smartmedia 2D array of \stored_file objects for the smart media associated with the $href file,
     *                  converted media is contained in 'media' element, metadata and other smart media files in the
     *                  'data' element.
     *                  Example:
     *                      ['media' => [\stored_file $file1, ...], 'data' => [\stored_file $file2, ...]]
     */
    public function get_smart_media(\moodle_url $href, bool $triggerconversion = false) : array {
        $smartmedia = array();

        // Split URL up into components.
        $file = $this->get_file_from_url($href);

        // Query conversion table for status.
        $conversionstatuses = $this->get_conversion_statuses($file);

        // If no record in table and trigger conversion is true add record.
        if ($triggerconversion && $conversionstatuses->status == self::CONVERSION_NOT_FOUND) {
            $this->create_conversion($file);
        }

        // If processing complete get all urls and data for source href.
        if ($conversionstatuses->status == self::CONVERSION_IN_PROGRESS ||
                $conversionstatuses->status == self::CONVERSION_FINISHED) {

            $fs = get_file_storage();

            $files = $fs->get_area_files(1, 'local_smartmedia', 'media', 0);
            $mediafilepath = '/' . $file->get_contenthash() . '/conversions/';
            $mediafiles = $this->filter_files_by_filepath($files, $mediafilepath);
            $smartmedia['media'] = $this->map_files_to_urls($mediafiles, $file->get_id());

            $files = $fs->get_area_files(1, 'local_smartmedia', 'metadata', 0);
            $datafilepath = '/' . $file->get_contenthash() . '/metadata/';
            $datafiles = $this->filter_files_by_filepath($files, $datafilepath);
            $smartmedia['data'] = $this->map_files_to_urls($datafiles, $file->get_id());
        }

        // TODO: Cache the result for a very long time as once processing is finished it will never change
        // and when processing is finished we will explictly clear the cache.

        return $smartmedia;

    }

    /**
     * Get all stored files which are within a particular filepath.
     *
     * @param array $files \stored_file objects to filter.
     * @param string $filepath the filepath to filter \stored_file objects by.
     *
     * @return array $filteredfiles of \stored_file objects in the $filepath.
     */
    private function filter_files_by_filepath($files, $filepath) {

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
    private function get_conversion_records(int $status) : array {
        global $DB;

        $conditions = array('status' => $status);
        $limit = self::MAX_FILES;
        $fields = 'id, pathnamehash, contenthash, status, transcoder_status, transcribe_status,
                  rekog_label_status, rekog_moderation_status, rekog_face_status, rekog_person_status,
                  detect_sentiment_status, detect_phrases_status, detect_entities_status';

        $filerecords = $DB->get_records('local_smartmedia_conv', $conditions, '', $fields, 0, $limit);

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
    private function map_files_to_urls($files, int $fileid) : array {
        $urls = [];
        foreach ($files as $file) {
            $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(),
                $fileid, $file->get_filepath(), $file->get_filename());
            $urls[] = $url;
        }
        return $urls;
    }

    /**
     * Get the configured covnersion for this conversion record in a format that will
     * be sent to AWS for processing.
     *
     * @param \stdClass $conversionrecord The cponversion record to get the settings for.
     * @return array $settings The conversion record settings.
     */
    private function get_conversion_settings(\stdClass $conversionrecord) : array {
        global $DB, $CFG;
        $settings = array();

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

        $presets = $DB->get_records('local_smartmedia_presets');

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
     * @return string $metadata json encoded string.
     */
    private function create_presets_metadata(array $presets) : string {
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
     * @param \stored_file $file The file to upload for conversion.
     * @param array $settings Settings to be used for file conversion.
     * @param \Aws\MockHandler|null $handler Optional handler.
     * @return int $status The status code of the upload.
     */
    private function send_file_for_processing(\stored_file $file, array $settings, $handler=null) : int {
        $awss3 = new \local_smartmedia\aws_s3();
        $s3client = $awss3->create_client($handler);

        $uploadparams = array(
            'Bucket' => $this->config->s3_input_bucket, // Required.
            'Key' => $file->get_contenthash(), // Required.
            'Body' => $file->get_content_file_handle(), // Required.
            'Metadata' => $settings
        );

        try {
            $result = $s3client->putObject($uploadparams);
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
    private function update_conversion_records(array $results) : void {
        global $DB;

        // Check if we are going to be performing multiple inserts.
        if (count($results) > 1) {
            $expectbulk = true;
        } else {
            $expectbulk = false;
        }

        // Update the records in the database.
        foreach ($results as $key => $result) {
            $updaterecord = new \stdClass();
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
    public function process_conversions() : array {
        global $DB;

        $results = array();
        $fs = get_file_storage();
        $conversionrecords = $this->get_conversion_records(self::CONVERSION_ACCEPTED); // Get not yet started conversion records.

        foreach ($conversionrecords as $conversionrecord) { // Itterate through not yet started records.
            $settings = $this->get_conversion_settings($conversionrecord); // Get convession settings.
            $file = $fs->get_file_by_hash($conversionrecord->pathnamehash); // Get the file to process.
            $results[$conversionrecord->id] = $this->send_file_for_processing($file, $settings); // Send for processing.
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
     * @param \stdClass $conversionrecord The conversion record to get messages for.
     * @return array $queuemessages The matching queue messages.
     */
    private function get_queue_messages(\stdClass $conversionrecord) : array {
        global $DB;

        // Using the conversion record determine which services we are looking for messages from.
        // Only get messages for conversions that have not yet finished.
        $services = array();

        if ($conversionrecord->transcoder_status == self::CONVERSION_ACCEPTED
            || $conversionrecord->transcoder_status == self::CONVERSION_IN_PROGRESS) {
                $services[] = 'elastic_transcoder';
        }
        if ($conversionrecord->rekog_label_status == self::CONVERSION_ACCEPTED
            || $conversionrecord->rekog_label_status == self::CONVERSION_IN_PROGRESS) {
            $services[] = 'StartLabelDetection';
        }
        if ($conversionrecord->rekog_moderation_status == self::CONVERSION_ACCEPTED
            || $conversionrecord->rekog_moderation_status == self::CONVERSION_IN_PROGRESS) {
            $services[] = 'StartContentModeration';
        }
        if ($conversionrecord->rekog_face_status == self::CONVERSION_ACCEPTED
            || $conversionrecord->rekog_face_status == self::CONVERSION_IN_PROGRESS) {
            $services[] = 'StartFaceDetection';
        }
        if ($conversionrecord->rekog_person_status == self::CONVERSION_ACCEPTED
            || $conversionrecord->rekog_person_status == self::CONVERSION_IN_PROGRESS) {
            $services[] = 'StartPersonTracking';
        }

        // Get all queue messages for this object.
        list($processinsql, $processinparams) = $DB->get_in_or_equal($services);
        list($statusinsql, $statusinparams) = $DB->get_in_or_equal(self::SQS_MESSAGE_STATES);
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
     * @param \stored_file $sourcefile The source file we want to check against.
     * @param \stored_file $smartfile The smartmedia file we want to make sure is associated with the source.
     * @return bool True if the checks are valid, false otherwise.
     */
    public function check_smartmedia_file(\stored_file $sourcefile, \stored_file $smartfile) : bool {
        global $DB;

        // The contenthash of the source file should have a matching entry in the local_smartmedia_conv table.
        $select = 'contenthash = ? AND status <> ?';
        $params = array($sourcefile->get_contenthash(), self::CONVERSION_ERROR);
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
     * @param \stdClass $conversionrecord The conversion record from the database.
     * @param \Aws\MockHandler|null $handler Optional handler.
     *
     * @return array $transcodedfiles Array of \stored_file objects.
     */
    private function get_transcode_files(\stdClass $conversionrecord, $handler=null) : array {
        $awss3 = new \local_smartmedia\aws_s3();
        $s3client = $awss3->create_client($handler);
        $transcodedfiles = [];

        // Transcoding could have made many files, but the job only calls success when all files are generated.
        // So first we get a list of the files.
        $listparams = array(
                'Bucket' => $this->config->s3_output_bucket,
                'MaxKeys' => 1000,  // The maximum allowed before we need to page, we should NEVER have this many.
                'Prefix' => $conversionrecord->contenthash . '/conversions/',  // Location in the S3 bucket where the files live.
        );
        $availableobjects = $s3client->listObjects($listparams);

        // Then we iterate over that list and get all the files available.
        $fs = get_file_storage();
        foreach ($availableobjects->get('Contents') as $availableobject) {
            $filename = basename($availableobject['Key']);
            $filerecord = array(
                'contextid' => 1, // Put files in the site level context as they aren't associated with a specific context.
                'component' => 'local_smartmedia',
                'filearea' => 'media',
                'itemid' => 0,
                'filepath' => '/' . $conversionrecord->contenthash . '/conversions/',
                'filename' => $filename,
            );

            $downloadparams = array(
                'Bucket' => $this->config->s3_output_bucket, // Required.
                'Key' => $availableobject['Key'], // Required.
            );

            $getobject = $s3client->getObject($downloadparams);
            $filecontent = $getobject->get('Body');

            $tmpfile = tmpfile();
            fwrite($tmpfile, $filecontent);
            $tmppath = stream_get_meta_data($tmpfile)['uri'];

            // The playlist files (including iframe playlists) created in s3 transcoding contain
            // relative file paths to Variant Streams for adaptive bitsteaming media, these need to be amended
            // to have Moodle plugin filepaths.
            if ($this->is_file_playlist($filename)) {
                $newfile = $this->replace_playlist_urls_with_pluginfile_urls($tmpfile, $conversionrecord->contenthash);
                $tmppath = stream_get_meta_data($newfile)['uri'];
            }

            $trancodedfile = $fs->create_file_from_pathname($filerecord, $tmppath);
            fclose($tmpfile);
            if (isset($newfile)) {
                fclose($newfile);
                unset($newfile); // Make sure we aren't overriding the same resource every time.
            }
            $transcodedfiles[] = $trancodedfile;
        }
        return $transcodedfiles;
        // TODO: Also remove files from AWS.
    }

    /**
     * Replace relative urls in a media playlist with pluginfile urls so the playlist may serve files in Moodle.
     *
     * @param resource $filehandle the handle for the file to replace urls in.
     * @param string $contenthash the content hash for conversion to search for and replace.
     *
     * @return resource $newfile file handle for a new file created with amended playlist data/
     */
    private function replace_playlist_urls_with_pluginfile_urls($filehandle, string $contenthash) {
        global $CFG;

        rewind($filehandle);
        $newfile = tmpfile();
        $pluginfilepath = $CFG->wwwroot . "/pluginfile.php/1/local_smartmedia/media/0/$contenthash/conversions/";

        while (!feof($filehandle)) {
            $line = fgets($filehandle);
            // Replace all matching content hashes with the plugin file path for smartmedia with this content.
            $line = preg_replace('/' . $contenthash . '_/', $pluginfilepath, $line);
            fwrite($newfile, $line);
        }
        return $newfile;
    }


    /**
     * Check if a file is a playlist file by filename.
     *
     * @param string $filename the file to check (including file extension)
     *
     * @return bool true if the file is playlist, false otherwise.
     */
    private function is_file_playlist(string $filename) : bool {
        $result = false;

        if (preg_match('/.m3u8|.mpd/', $filename)) {
            $result = true;
        }

        return $result;
    }

    /**
     * Get the file from AWS for a given conversion process.
     *
     * @param \stdClass $conversionrecord The conversion record from the database.
     * @param string $process The process to get the file for.
     * @param \Aws\MockHandler|null $handler Optional handler.
     */
    private function get_data_file(\stdClass $conversionrecord, string $process, $handler=null) {
        $awss3 = new \local_smartmedia\aws_s3();
        $s3client = $awss3->create_client($handler);

        $objectkey = self::SERVICE_MAPPING[$process][1];
        $fs = get_file_storage();

        $filerecord = array(
            'contextid' => 1, // Put files in the site level context as they aren't associated with a specific context.
            'component' => 'local_smartmedia',
            'filearea' => 'metadata',
            'itemid' => 0,
            'filepath' => '/' . $conversionrecord->contenthash . '/metadata/',
            'filename' => $objectkey . '.json'
        );

        $downloadparams = array(
                'Bucket' => $this->config->s3_output_bucket, // Required.
                'Key' => $conversionrecord->contenthash . '/metadata/' . $objectkey . '.json', // Required.
        );

        $getobject = $s3client->getObject($downloadparams);

        $tmpfile = tmpfile();
        fwrite($tmpfile, $getobject['Body']);
        $tmppath = stream_get_meta_data($tmpfile)['uri'];

        $fs->create_file_from_pathname($filerecord, $tmppath);
        fclose($tmpfile);

        // TODO: Also remove files from AWS.
    }

    /**
     * Process the conversion records and get the files from AWS.
     *
     * @param \stdClass $conversionrecord The conversion record from the database.
     * @param array $queuemessages Queue messages from the database relating to this conversion record.
     * @param \Aws\MockHandler|null $handler Optional handler.
     * @return \stdClass $conversionrecord The updated conversion record.
     */
    private function process_conversion(\stdClass $conversionrecord, array $queuemessages, $handler=null) : \stdClass {
        global $DB;

        foreach ($queuemessages as $message) {
            if ($message->status == 'ERROR' && $message->process == 'elastic_transcoder') {
                // If Elastic Transcoder conversion has failed then all other conversions have also failed.
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

            } else if ($message->status == 'COMPLETED' || $message->status == 'SUCCEEDED') {
                // For each successful status get the file/s for the conversion.
                if ($message->process == 'elastic_transcoder') {
                    // Get Elastic Transcoder files.
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
     * @param \stdClass $updatedrecord The record to check the completion status for.
     * @return \stdClass $updatedrecord The updated completion record.
     */
    private function update_completion_status(\stdClass $updatedrecord) : \stdClass {
        global $DB;

        // Only set the final completion status if all other processes are finished.
        if (($updatedrecord->transcoder_status == self::CONVERSION_FINISHED
                || $updatedrecord->transcoder_status == self::CONVERSION_NOT_FOUND )
            && ($updatedrecord->rekog_label_status == self::CONVERSION_FINISHED
                || $updatedrecord->rekog_label_status == self::CONVERSION_NOT_FOUND)
            && ($updatedrecord->rekog_moderation_status == self::CONVERSION_FINISHED
                || $updatedrecord->rekog_moderation_status == self::CONVERSION_NOT_FOUND)
            && ($updatedrecord->rekog_face_status == self::CONVERSION_FINISHED
                || $updatedrecord->rekog_face_status == self::CONVERSION_NOT_FOUND)
            && ($updatedrecord->rekog_person_status == self::CONVERSION_FINISHED)
                || $updatedrecord->rekog_person_status == self::CONVERSION_NOT_FOUND) {

                $updatedrecord->status = self::CONVERSION_FINISHED;
                $updatedrecord->timemodified = time();
                $updatedrecord->timecompleted = time();
                // Update the database with the modified conversion record.

                $DB->update_record('local_smartmedia_conv', $updatedrecord);

                // TODO: Also delete file from AWS.
        }

        return $updatedrecord;
    }

    /**
     * Update pending conversions.
     *
     * @return array $results The results of the processing.
     */
    public function update_pending_conversions() : array {
        global $DB;

        $results = array();
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
     * Get the pathnamehashes for files that have metadata extracted,
     * but that do not have conversion records.
     *
     * @return array $pathnamehashes Array of pathnamehashes.
     */
    private function get_pathnamehashes() : array {
        global $DB;

        $limit = self::MAX_FILES;
        $sql = "SELECT lsd.id, lsd.pathnamehash
                  FROM {local_smartmedia_data} lsd
             LEFT JOIN {local_smartmedia_conv} lsc ON lsd.contenthash = lsc.contenthash
                 WHERE lsc.contenthash IS NULL";
        $pathnamehashes = $DB->get_records_sql($sql, null, 0, $limit);

        return $pathnamehashes;

    }

    /**
     * Create conversion records for files that have metadata,
     * but don't have conversion records.
     *
     * @return array
     */
    public function create_conversions() : array {
        $pathnamehashes = $this->get_pathnamehashes(); // Get pathnamehashes for conversions.
        $fs = get_file_storage();

        foreach ($pathnamehashes as $pathnamehash) {
            $file = $fs->get_file_by_hash($pathnamehash->pathnamehash);
            $this->create_conversion($file);
        }

        return $pathnamehashes;
    }

}