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
 * Class for smart media utility operations.
 *
 * @package     local_smartmedia
 * @copyright   2019 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_smartmedia;

defined('MOODLE_INTERNAL') || die();

/**
 * Class for smart media utility operations.
 *
 * @package     local_smartmedia
 * @copyright   2019 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utility {

    /**
     * Due to the way VideoJS handles slash (/) arguments in URLs
     * we may need to clean up the provided args that are passed
     * back to Moodle from the VideoJS AJAX call.
     *
     * This method strips out any extra args added by
     * videoJS.
     *
     * @param array $args The array of arguments to check.
     * @return array $args The cleaned array of args.
     */
    public function update_args(array $args) : array {

        if (in_array('pluginfile.php', $args)) {
            // We need to clean up the args.
            // Done by simply removing unwanted elements.
            $filename = array_pop($args);
            array_splice($args, 3);
            $args[] = $filename;
        }

        return $args;
    }

}
