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
 * Unit tesst for local_smartmedia ffprobe class.
 *
 * @package    local_smartmedia
 * @copyright  2019 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


class local_smartmedia_ffprobe_testcase extends advanced_testcase {

    protected $ffprobedata = '{
    "streams": [
        {
            "index": 0,
            "codec_name": "h264",
            "codec_long_name": "H.264 / AVC / MPEG-4 AVC / MPEG-4 part 10",
            "profile": "Main",
            "codec_type": "video",
            "codec_time_base": "1/50",
            "codec_tag_string": "avc1",
            "codec_tag": "0x31637661",
            "width": 1280,
            "height": 720,
            "coded_width": 1280,
            "coded_height": 720,
            "has_b_frames": 0,
            "sample_aspect_ratio": "1:1",
            "display_aspect_ratio": "16:9",
            "pix_fmt": "yuv420p",
            "level": 31,
            "chroma_location": "left",
            "refs": 1,
            "is_avc": "true",
            "nal_length_size": "4",
            "r_frame_rate": "25/1",
            "avg_frame_rate": "25/1",
            "time_base": "1/12800",
            "start_pts": 0,
            "start_time": "0.000000",
            "duration_ts": 67584,
            "duration": "5.280000",
            "bit_rate": "1205959",
            "bits_per_raw_sample": "8",
            "nb_frames": "132",
            "disposition": {
                "default": 1,
                "dub": 0,
                "original": 0,
                "comment": 0,
                "lyrics": 0,
                "karaoke": 0,
                "forced": 0,
                "hearing_impaired": 0,
                "visual_impaired": 0,
                "clean_effects": 0,
                "attached_pic": 0,
                "timed_thumbnails": 0
            },
            "tags": {
                "creation_time": "1970-01-01T00:00:00.000000Z",
                "language": "und",
                "handler_name": "VideoHandler"
            }
        },
        {
            "index": 1,
            "codec_name": "aac",
            "codec_long_name": "AAC (Advanced Audio Coding)",
            "profile": "LC",
            "codec_type": "audio",
            "codec_time_base": "1/48000",
            "codec_tag_string": "mp4a",
            "codec_tag": "0x6134706d",
            "sample_fmt": "fltp",
            "sample_rate": "48000",
            "channels": 6,
            "channel_layout": "5.1",
            "bits_per_sample": 0,
            "r_frame_rate": "0/0",
            "avg_frame_rate": "0/0",
            "time_base": "1/48000",
            "start_pts": 0,
            "start_time": "0.000000",
            "duration_ts": 254976,
            "duration": "5.312000",
            "bit_rate": "384828",
            "max_bit_rate": "400392",
            "nb_frames": "249",
            "disposition": {
                "default": 1,
                "dub": 0,
                "original": 0,
                "comment": 0,
                "lyrics": 0,
                "karaoke": 0,
                "forced": 0,
                "hearing_impaired": 0,
                "visual_impaired": 0,
                "clean_effects": 0,
                "attached_pic": 0,
                "timed_thumbnails": 0
            },
            "tags": {
                "creation_time": "1970-01-01T00:00:00.000000Z",
                "language": "und",
                "handler_name": "SoundHandler"
            }
        }
    ],
    "format": {
        "filename": "/var/lib/sitedata/phpunitdata/temp/files/tempup_0tqLR2",
        "nb_streams": 2,
        "nb_programs": 0,
        "format_name": "mov,mp4,m4a,3gp,3g2,mj2",
        "format_long_name": "QuickTime / MOV",
        "start_time": "0.000000",
        "duration": "5.312000",
        "size": "1055736",
        "bit_rate": "1589963",
        "probe_score": 100,
        "tags": {
            "major_brand": "isom",
            "minor_version": "512",
            "compatible_brands": "isomiso2avc1mp41",
            "creation_time": "1970-01-01T00:00:00.000000Z",
            "encoder": "Lavf53.24.2"
        }
    }
}
';

    public function setUp() {
        $this->resetAfterTest();

        // Allow setting of FFProbe via Env Var or define
        // to cater for mulitiple test setups.
        $pathtoffprobe = getenv('TEST_LOCAL_SMARTMEDIA_FFPROBE');

        if (!$pathtoffprobe && defined('TEST_LOCAL_SMARTMEDIA_FFPROBE')) {
            $pathtoffprobe = TEST_LOCAL_SMARTMEDIA_FFPROBE;
        }

        set_config('pathtoffprobe', (string)$pathtoffprobe, 'local_smartmedia');
    }

    /**
     * Test get media metadata method.
     */
    function test_get_media_metadata() {
        global $CFG;

        // Skip if no valid FFProbe executable.
        if(get_config('local_smartmedia', 'pathtoffprobe') == '') {
            $this->markTestSkipped('Test skipped as no valid FFProbe executable set');
        }

        // Setup for testing.
        $fs = new file_storage();
        $filerecord = array(
            'contextid' =>  1461,
            'component' => 'mod_label',
            'filearea' => 'intro',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'SampleVideo1mb.mp4');
        $pathname = $CFG->dirroot . '/local/smartmedia/tests/fixtures/SampleVideo1mb.mp4';

        $file = $fs->create_file_from_pathname($filerecord, $pathname);

        $ffprobe = new \local_smartmedia\ffprobe();
        $metadata = $ffprobe->get_media_metadata($file);

        $this->assertEquals('success', $metadata['status']);
    }

    /**
     * Test decoding FFProbe results.
     */
    function test_decode_ffprobe_json() {
        // Disable the class contructor for this test.
        $builder = $this->getMockBuilder('\local_smartmedia\ffprobe');
        $builder->disableOriginalConstructor();
        $stub = $builder->getMock();

        $resultobject = json_decode($this->ffprobedata);

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_smartmedia\ffprobe', 'decode_ffprobe_json');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke($stub, $resultobject); // Get result of invoked method.

        error_log(print_r($proxy, true));
    }

}
