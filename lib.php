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
 * Library functions and constants for local_smartmedia.
 *
 * @package     local_smartmedia
 * @author      Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright   2019 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_smartmedia\aws_api;
use local_smartmedia\aws_elastic_transcoder;

defined('MOODLE_INTERNAL') || die();

// Minimum height above which media is considered high definition.
define('LOCAL_SMARTMEDIA_MINIMUM_HD_HEIGHT', 720);
define('LOCAL_SMARTMEDIA_MINIMUM_SD_HEIGHT', 1);
define('LOCAL_SMARTMEDIA_AUDIO_HEIGHT', 0);

// Media type constants.
define('LOCAL_SMARTMEDIA_TYPE_AUDIO', 'Audio');
define('LOCAL_SMARTMEDIA_TYPE_VIDEO', 'Video');

// Preset Container types which have fragmented outputs for adaptive bitrate streaming.
define('LOCAL_SMARTMEDIA_PRESET_OUTPUT_FRAGMENTED_CONTAINERS', ['ts', 'fmp4']);

// Valid container types for preset output files.
define('LOCAL_SMARTMEDIA_PRESET_OUTPUT_CONTAINER_TYPES',
    ['flac', 'flv', 'fmp4', 'gif', 'mp3', 'mp4', 'mpg', 'mxf', 'oga', 'ogg', 'ts', 'webm']);

/**
 * Serve the files from the local smartmedia file areas.
 *
 * @param stdClass $course The course object.
 * @param stdClass $cm The course module object.
 * @param stdClass $context The context.
 * @param string $filearea The name of the file area.
 * @param array $args Extra arguments (itemid, path).
 * @param bool $forcedownload Whether or not force download.
 * @param array $options Additional options affecting the file serving.
 * @return bool False if the file not found, just send the file otherwise and do not return anything.
 */
function local_smartmedia_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $DB;

    // Make sure the filearea is one of those used by the plugin.
    if ($filearea !== 'media' && $filearea !== 'metadata') {
        return false;
    }

    // Due to the way VideoJS handles slash (/) arguments in URLs
    // we may need to clean up the provided args that are passed
    // back to Moodle from the VideoJS AJAX call.
    $utility = new \local_smartmedia\utility();
    $args = $utility->update_args($args);
    $itemid = array_shift($args);
    $filename = array_pop($args); // The last item in the $args array.
    $lowlatency = get_config('local_smartmedia', 'lowlatency');

    if ($lowlatency) {
        // Setup cache for content.
        $cachekey = sha1($filename . sesskey());
        $cache = cache::make('local_smartmedia', 'serve');
        $cachedata = $cache->get($cachekey);
        if ($cachedata) {
            $url = new moodle_url('/local/smartmedia/serve.php', ['key' => $cachekey]);
            // Set cache headers for this redirection, safe to cache in browser only.
            @header('Expires: '. gmdate('D, d M Y H:i:s', time() + 3600) .' GMT');
            @header_remove('Pragma');
            @header('Cache-Control: private, max-age=3600');
            @header($_SERVER['SERVER_PROTOCOL'] . ' 302 Found');
            @header('Location: '.$url->out());
            exit;
        }
    }

    // Make sure the user is logged in and has access to the module.
    require_login();

    // The id passed to the $options array is the id in the file table for the original source file.
    // We get this file to make sure:
    // - It is a valid file.
    // - It is the correct source file for the requested smartmedia file.
    // - The user is allowed to access the source file (which means they can access this smartmedia file.
    $contenthash = $args[0];
    $fs = get_file_storage();
    $sourceids = $DB->get_fieldset_select('files', 'id', 'contenthash = ?', [$contenthash]);
    $valid = false;
    foreach ($sourceids as $source) {
        $sourcefile = $fs->get_file_by_id($source);
        if ($sourcefile) {
            // Now we need to check the containing  context, and check that atleast a login is valid for that context.
            $valid = true;
            break;
        }
    }
    if (!$valid) {
        send_file_not_found();
    }

    // Extract the filepath from the $args array.
    if (!$args) {
        $filepath = '/'; // If $args is empty the path is '/'.
    } else {
        $filepath = '/'.implode('/', $args).'/'; // Var $args contains elements of the filepath.
    }

    // We need to handle playlist files and media files differently.
    $fileparts = pathinfo($filename);
    $fileextension = $fileparts['extension'];

    if ($fileextension != 'mpd' && $fileextension != 'm3u8') {
        $itemid = 0; // There is only one source of truth for media (non playlist files).
    }

    $smartfile = $fs->get_file($context->id, 'local_smartmedia', $filearea, $itemid, $filepath, $filename);
    if (!$smartfile) {
        return false; // Return early if smartfile id is invalid.
    }

    $api = new aws_api();
    $transcoder = new aws_elastic_transcoder($api->create_elastic_transcoder_client());
    $conversion = new \local_smartmedia\conversion($transcoder);
    $filecheck = $conversion->check_smartmedia_file($sourcefile, $smartfile);
    if (!$filecheck) {
        return false; // Source file doesn't match smart file.
    }

    if ($lowlatency) {
        $cache->set($cachekey, $smartfile);
    }

    // We can now send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering.
    send_stored_file($smartfile, 86400, 0, $forcedownload, $options);
}
