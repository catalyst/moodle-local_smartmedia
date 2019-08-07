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

    $settings = new admin_settingpage('local_smartmedia', get_string('pluginname', 'local_smartmedia'));
    $ADMIN->add('localplugins', $settings);

    // FFprobe settings.
    $settings->add(new admin_setting_heading('ffprobeheader',
        get_string('settings:ffprobe:header', 'local_smartmedia'),
        get_string('settings:ffprobe:header_desc', 'local_smartmedia')));
    $settings->add(new admin_setting_configexecutable('local_smartmedia/pathtoffprobe',
        get_string('settings:ffprobe:pathtoffprobe', 'local_smartmedia'),
        get_string('settings:ffprobe:pathtoffprobe_desc', 'local_smartmedia'), '/usr/bin/ffprobe'));


    $ADMIN->add('reports', new admin_externalpage('local_smartmedia_report',
        get_string('pluginname', 'local_smartmedia'), "$CFG->wwwroot/local/smartmedia/report.php"));
}
