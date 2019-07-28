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
 * Class for smart media metadata extraction operations.
 *
 * @package     local_smartmedia
 * @copyright   2018 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_smartmedia;

defined('MOODLE_INTERNAL') || die();

/**
 * Class for smart media metadata extraction operations.
 *
 * @package     local_smartmedia
 * @copyright   2019 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ffprobe {

    /**
     * Class constructor
     */
    public function __construct() {
        $this->ffprobe_path = get_config('local_smartmedia', 'pathtoffprobe');

        // Explode if we don't have a valid path to FFProbe.
        if (!file_exists($this->ffprobe_path) || is_dir($this->ffprobe_path) || !file_is_executable($this->ffprobe_path)) {
            throw new \moodle_exception('ffprobe:invalidpath', 'local_smartmedia', '');
        }
    }

    /**
     * Given the results from an FFProbe inspection extract
     * relevant media data.
     *
     * @param array $resultobject Array of raw JSON from FFProbe.
     * @return array $metadata The metadata array with extracted media file data.
     */
    private function decode_ffprobe_json($resultobject) : array {
        $metadata = array(
            'status' => 'success',
            'reason' => 'FFProbe inspection succeded',
            'data' => array()
        );

        // Format data.
        $formatname = $resultobject->format->format_name; // Format name, e.g. "mov,mp4,m4a,3gp,3g2,mj2".
        $formatlingname = $resultobject->format->format_long_name; // Format long name, e.g. "QuickTime / MOV".
        $duration = $resultobject->format->duration; // Duration in decimal seconds, e.g. "5.312".
        $bitrate = $resultobject->format->bit_rate; // Encoded bitrate, e.g. "1589963".
        $probescore = $resultobject->format->probe_score; // Confidence of metadata extracted, e.g. "100".

        // Stream data.
        // Files can have multiple streams, not just one audo and one video.
        $totalstreams = count($resultobject->streams);
        $totalvideostreams = 0;
        $totalaudiostreams = 0;
        $metadata['data']['videostreams'] = array();
        $metadata['data']['audiostreams'] = array();

        // Grab data from the available streams.
        foreach ($resultobject->streams as $stream) {
            if ($stream->codec_type == 'video') {
                $totalvideostreams++;
                $metadata['data']['videostreams'][] = array(
                    'codecname' => $stream->codec_name,
                    'width' => $stream->width,
                    'height' => $stream->height,
                    'aspectratio' => $stream->display_aspect_ratio,
                    'framerate' => $stream->avg_frame_rate,
                    'bitrate' => $stream->bit_rate,
                );
            }

            if ($stream->codec_type == 'audio') {
                $totalaudiostreams++;
                $metadata['data']['audiostreams'][] = array(
                    'codecname' => $stream->codec_name,
                    'samplerate' => $stream->sample_rate,
                    'channels' => $stream->channels,
                    'bitrate' => $stream->bit_rate,
                );
            }

        }

        // Populate general data.
        $metadata['data']['formatname'] = $formatname;
        $metadata['data']['formatlingname'] = $formatlingname;
        $metadata['data']['duration'] = $duration;
        $metadata['data']['bitrate'] = $bitrate;
        $metadata['data']['probescore'] = $probescore;
        $metadata['data']['totalstreams'] = $totalstreams;
        $metadata['data']['totalvideostreams'] = $totalvideostreams;
        $metadata['data']['totalaudiostreams'] = $totalaudiostreams;

        return $metadata;

    }

    /**
     * Given a Moodle stored file object, get the file metadata using FFProbe.
     * @param \stored_file $file Moodle stored file object.
     * @return array $metadata The metadata retrieved from the file.
     */
    public function get_media_metadata(\stored_file $file) : array {
        $metadata = array(
            'status' => 'failed',
            'reason' => 'FFProbe inspection failed',
            'data' => array()
        );
        $rawresults = null;
        $jsonresults = null;

        // We need to make an explicit temp file on the filesystem as
        // ffprobe will not take a stream or a file object.
        // This is not ideal when dealing with massive files or object storage,
        // but there isn't anything that can be done about it.
        $tempfile = $file->copy_content_to_temp();

        // Execute the FFProbe command to get file metadata.
        $command = $this->ffprobe_path . ' -of json -v error -show_format -show_streams ' .  escapeshellarg($tempfile);
        $rawresults = shell_exec($command);
        unlink($tempfile); // Remove temp file.

        if ($rawresults) {
            $resultobject = json_decode($rawresults);
        } else {
            return $metadata;  // Return early if we couldn't get any data from FFProbe.
        }

        if ($resultobject) {
            $metadata = $this->decode_ffprobe_json($resultobject);
        } else {
            $metadata['reason'] = 'JSON Decoding failed';
        }

        return $metadata;
    }

}