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

    // AWS Settings settings.
    $settings->add(new admin_setting_heading('awsheader',
        get_string('settings:aws:header', 'local_smartmedia'),
        get_string('settings:aws:header_desc', 'local_smartmedia')));

    $settings->add(new admin_setting_configtext('local_smartmedia/api_key',
        get_string('settings:aws:key', 'local_smartmedia'),
        get_string('settings:aws:key_help', 'local_smartmedia'),
        ''));

    $settings->add(new admin_setting_configpasswordunmask('local_smartmedia/api_secret',
        get_string('settings:aws:secret', 'local_smartmedia'),
        get_string('settings:aws:secret_help', 'local_smartmedia'),
        ''));

    // These are the only regions that AWS Elastic Transcoder is available in.
    $regionoptions = array(
        'us-east-1'      => 'US East (N. Virginia)',
        'us-west-1'      => 'US West (N. California)',
        'us-west-2'      => 'US West (Oregon)',
        'ap-northeast-1' => 'Asia Pacific (Tokyo)',
        'ap-south-1'     => 'Asia Pacific (Mumbai)',
        'ap-southeast-1' => 'Asia Pacific (Singapore)',
        'ap-southeast-2' => 'Asia Pacific (Sydney)',
        'eu-west-1'      => 'EU (Ireland)',
    );
    $settings->add(new admin_setting_configselect('local_smartmedia/api_region',
        get_string('settings:aws:region', 'local_smartmedia'),
        get_string('settings:aws:region_help', 'local_smartmedia'),
        'ap-southeast-2',
        $regionoptions));

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
