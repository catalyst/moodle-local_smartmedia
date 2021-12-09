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
 * Low overhead file serving for chunked smartmedia streaming.
 *
 * @package    local_smartmedia
 * @author     Peter Burnett
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// Disable the use of sessions/cookies - we recreate $USER for every call.
define('NO_MOODLE_COOKIES', true);
define('NO_DEBUG_DISPLAY', true);

require_once('../../config.php');
require_once($CFG->libdir . '/filelib.php');

$cachekey = required_param('key', PARAM_RAW);
$cache = cache::make('local_smartmedia', 'serve');
$cachedata = $cache->get($cachekey);

if ($cachedata) {
    // If there is something that is not a stored file, its likely malformed or false.
    if ($cachedata instanceof \stored_file) {
        send_stored_file($cachedata, 86400);
    }
}

// This shouldnt happen unless someone paths here with a bad key, or cache expiry.
send_file_not_found();
