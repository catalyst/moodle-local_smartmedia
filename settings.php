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
 * Plugin administration pages are defined here.
 *
 * @package     local_smartmedia
 * @copyright   2019 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    // Plugin settings link.
    $pluginsettings = new admin_externalpage(
        'local_smartmedia_settings',
        get_string('pluginname', 'local_smartmedia'),
        new moodle_url('/local/smartmedia/index.php'));
    $ADMIN->add('localplugins', $pluginsettings);

    // Dashboard link.
    $ADMIN->add('reports', new admin_externalpage('local_smartmedia_report',
        get_string('pluginname', 'local_smartmedia'), "$CFG->wwwroot/local/smartmedia/report.php"));
}
