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

    $settings->add(new admin_setting_configtext('local_smartmedia/s3_input_bucket',
            get_string('settings:aws:input_bucket', 'local_smartmedia'),
            get_string('settings:aws:input_bucket_help', 'local_smartmedia'),
            ''));

    $settings->add(new admin_setting_configtext('local_smartmedia/s3_output_bucket',
            get_string('settings:aws:output_bucket', 'local_smartmedia'),
            get_string('settings:aws:output_bucket_help', 'local_smartmedia'),
            ''));

    $regionoptions = array(
        'us-east-1'      => 'us-east-1 (N. Virginia)',
        'us-east-2'      => 'us-east-2 (Ohio)',
        'us-west-1'      => 'us-west-1 (N. California)',
        'us-west-2'      => 'us-west-2 (Oregon)',
        'ap-northeast-1' => 'ap-northeast-1 (Tokyo)',
        'ap-northeast-2' => 'ap-northeast-2 (Seoul)',
        'ap-northeast-3' => 'ap-northeast-3 (Osaka)',
        'ap-south-1'     => 'ap-south-1 (Mumbai)',
        'ap-southeast-1' => 'ap-southeast-1 (Singapore)',
        'ap-southeast-2' => 'ap-southeast-2 (Sydney)',
        'ca-central-1'   => 'ca-central-1 (Canda Central)',
        'cn-north-1'     => 'cn-north-1 (Beijing)',
        'cn-northwest-1' => 'cn-northwest-1 (Ningxia)',
        'eu-central-1'   => 'eu-central-1 (Frankfurt)',
        'eu-west-1'      => 'eu-west-1 (Ireland)',
        'eu-west-2'      => 'eu-west-2 (London)',
        'eu-west-3'      => 'eu-west-3 (Paris)',
        'sa-east-1'      => 'sa-east-1 (Sao Paulo)'
    );

    $settings->add(new admin_setting_configselect('local_smartmedia/api_region',
            get_string('settings:aws:region', 'local_smartmedia'),
            get_string('settings:aws:region_help', 'local_smartmedia'),
            'ap-southeast-2',
            $regionoptions));

    $settings->add(new admin_setting_configtext('local_smartmedia/sqs_queue_url',
        get_string('settings:aws:sqs_queue_url', 'local_smartmedia'),
        get_string('settings:aws:sqs_queue_url_help', 'local_smartmedia'),
        '',
        PARAM_URL));

    // Processing settings.
    $settings->add(new admin_setting_heading('processingheader',
        get_string('settings:processing:header', 'local_smartmedia'),
        get_string('settings:processing:header_desc', 'local_smartmedia')));

    $settings->add(new admin_setting_configtextarea('local_smartmedia/transcodepresets',
        get_string('settings:processing:transcodepresets', 'local_smartmedia'),
        get_string('settings:processing:transcodepresets_desc', 'local_smartmedia'), ''));
    $settings->add(new admin_setting_configcheckbox('local_smartmedia/detectlabels',
        get_string('settings:processing:detectlabels', 'local_smartmedia'),
        get_string('settings:processing:detectlabels_desc', 'local_smartmedia'), 1));
    $settings->add(new admin_setting_configcheckbox('local_smartmedia/detectmoderation',
        get_string('settings:processing:detectmoderation', 'local_smartmedia'),
        get_string('settings:processing:detectmoderation_desc', 'local_smartmedia'), 1));
    $settings->add(new admin_setting_configcheckbox('local_smartmedia/detectfaces',
        get_string('settings:processing:detectfaces', 'local_smartmedia'),
        get_string('settings:processing:detectfaces_desc', 'local_smartmedia'), 1));
    $settings->add(new admin_setting_configcheckbox('local_smartmedia/detectpeople',
        get_string('settings:processing:detectpeople', 'local_smartmedia'),
        get_string('settings:processing:detectpeople_desc', 'local_smartmedia'), 1));
    $settings->add(new admin_setting_configcheckbox('local_smartmedia/transcribe',
        get_string('settings:processing:transcribe', 'local_smartmedia'),
        get_string('settings:processing:transcribe_desc', 'local_smartmedia'), 1));
    // TODO: figure out how to disable these settings if transcribe is disabled.
    $settings->add(new admin_setting_configcheckbox('local_smartmedia/detectsentiment',
        get_string('settings:processing:detectsentiment', 'local_smartmedia'),
        get_string('settings:processing:detectsentiment_desc', 'local_smartmedia'), 1));
    $settings->add(new admin_setting_configcheckbox('local_smartmedia/detectphrases',
        get_string('settings:processing:detectphrases', 'local_smartmedia'),
        get_string('settings:processing:detectphrases_desc', 'local_smartmedia'), 1));
    $settings->add(new admin_setting_configcheckbox('local_smartmedia/detectentities',
        get_string('settings:processing:detectentities', 'local_smartmedia'),
        get_string('settings:processing:detectentities_desc', 'local_smartmedia'), 1));

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

    // Processing settings.
    $settings->add(new admin_setting_heading('processingheader',
        get_string('settings:processing:header', 'local_smartmedia'),
        get_string('settings:processing:header_desc', 'local_smartmedia')));
    $settings->add(new admin_setting_configtextarea('local_smartmedia/transcodepresets',
        get_string('settings:processing:transcodepresets', 'local_smartmedia'),
        get_string('settings:processing:transcodepresets_desc', 'local_smartmedia'), ''));

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
