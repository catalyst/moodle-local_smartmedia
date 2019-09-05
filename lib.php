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

defined('MOODLE_INTERNAL') || die();

// Minimum height above which media is considered high definition.
define('LOCAL_SMARTMEDIA_MINIMUM_HD_HEIGHT', 720);
define('LOCAL_SMARTMEDIA_MINIMUM_SD_HEIGHT', 1);
define('LOCAL_SMARTMEDIA_AUDIO_HEIGHT', 0);

// Media type constants.
define('LOCAL_SMARTMEDIA_TYPE_AUDIO', 'Audio');
define('LOCAL_SMARTMEDIA_TYPE_VIDEO', 'Video');

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

    // Make sure the filearea is one of those used by the plugin.
    if ($filearea !== 'media' && $filearea !== 'metadata') {
        return false;
    }

    // Make sure the user is logged in and has access to the module.
    require_login($course);

    // The id passed to the $options array is the id in the file table for the original source file.
    // We get this file to make sure:
    // - It is a valid file.
    // - It is the correct source file for the requested smartmedia file.
    // - The user is allowed to access the source file (which means they can access this smartmedia file.
    $fileid = array_shift($args);
    $fs = get_file_storage();
    $sourcefile = $fs->get_file_by_id($fileid);
    if (!$sourcefile) {
        return false; // Return early if sourcefile id is invalid.
    }

    // Extract the filename / filepath from the $args array.
    $filename = array_pop($args); // The last item in the $args array.
    if (!$args) {
        $filepath = '/'; // If $args is empty the path is '/'.
    } else {
        $filepath = '/'.implode('/', $args).'/'; // Var $args contains elements of the filepath.
    }
    $itemid = 0; // Item id is always zero for smartmedia files.

    $smartfile = $fs->get_file($context->id, 'local_smartmedia', $filearea, $itemid, $filepath, $filename);
    if (!$smartfile) {
        return false; // Return early if smartfile id is invalid.
    }

    $conversion = new \local_smartmedia\conversion();
    $filecheck = $conversion->check_smartmedia_file($sourcefile, $smartfile);
    if (!$filecheck) {
        return false; // Source file doesn't match smart file.
    }

    // TODO: add check to make sure user can access source file. (MDL-66006).

    // We can now send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering.
    send_stored_file($smartfile, 86400, 0, $forcedownload, $options);
}