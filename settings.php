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

    $settings->add(new admin_setting_configcheckbox('local_smartmedia/usesdkcreds',
        get_string('settings:aws:usesdkcreds', 'local_smartmedia'),
        get_string('settings:aws:usesdkcreds_desc', 'local_smartmedia'), false));

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

    $settings->add(new admin_setting_configtext('local_smartmedia/sqs_queue_url',
        get_string('settings:aws:sqs_queue_url', 'local_smartmedia'),
        get_string('settings:aws:sqs_queue_url_help', 'local_smartmedia'),
        '',
        PARAM_URL));

    $settings->add(new admin_setting_configcheckbox('local_smartmedia/useproxy',
        get_string('settings:useproxy', 'local_smartmedia'),
        get_string('settings:useproxy_help', 'local_smartmedia'), 0));

    $settings->add(new admin_setting_configcheckbox('local_smartmedia/lowlatency',
        get_string('settings:lowlatency', 'local_smartmedia'),
        get_string('settings:lowlatency_help', 'local_smartmedia'), 0));

    // Output settings.
    $settings->add(new admin_setting_heading('outputheader',
        get_string('settings:output:header', 'local_smartmedia'),
        get_string('settings:output:header_desc', 'local_smartmedia')));

    $settings->add(new admin_setting_configcheckbox('local_smartmedia/quality_low',
        get_string('settings:output:quality_low', 'local_smartmedia'),
        get_string('settings:output:quality_low_help', 'local_smartmedia'), 1));

    $settings->add(new admin_setting_configcheckbox('local_smartmedia/quality_medium',
        get_string('settings:output:quality_medium', 'local_smartmedia'),
        get_string('settings:output:quality_medium_help', 'local_smartmedia'), 0));

    $settings->add(new admin_setting_configcheckbox('local_smartmedia/quality_high',
        get_string('settings:output:quality_high', 'local_smartmedia'),
        get_string('settings:output:quality_high_help', 'local_smartmedia'), 1));

    $settings->add(new admin_setting_configcheckbox('local_smartmedia/quality_extrahigh',
        get_string('settings:output:quality_extrahigh', 'local_smartmedia'),
        get_string('settings:output:quality_extrahigh_help', 'local_smartmedia'), 0));

    $settings->add(new admin_setting_configcheckbox('local_smartmedia/audio_output',
        get_string('settings:output:audio_output', 'local_smartmedia'),
        get_string('settings:output:audio_output_help', 'local_smartmedia'), 1));

    $settings->add(new admin_setting_configcheckbox('local_smartmedia/download_files',
        get_string('settings:output:download_files', 'local_smartmedia'),
        get_string('settings:output:download_files_help', 'local_smartmedia'), 1));

    // Processing settings.
    $settings->add(new admin_setting_heading('processingheader',
        get_string('settings:processing:header', 'local_smartmedia'),
        get_string('settings:processing:header_desc', 'local_smartmedia')));
    $settings->add(new admin_setting_configcheckbox('local_smartmedia/proactiveconversion',
        get_string('settings:processing:proactiveconversion', 'local_smartmedia'),
        get_string('settings:processing:proactiveconversion_help', 'local_smartmedia'), 0));

    $settings->add(new admin_setting_configcheckbox('local_smartmedia/viewconversion',
        get_string('settings:processing:viewconversion', 'local_smartmedia'),
        get_string('settings:processing:viewconversion_help', 'local_smartmedia'), 1));

    $settings->add(new admin_setting_configduration('local_smartmedia/convertfrom',
        get_string('settings:processing:convertfrom', 'local_smartmedia'),
        get_string('settings:processing:convertfrom_help', 'local_smartmedia'), WEEKSECS, WEEKSECS));

    $settings->add(new admin_setting_configduration('local_smartmedia/maxruntime',
        get_string('settings:processing:maxruntime', 'local_smartmedia'),
        get_string('settings:processing:maxruntime_help', 'local_smartmedia'), 5 * MINSECS, MINSECS));

    // Enrichment settings.
    $settings->add(new admin_setting_heading('enrichmentheader',
        get_string('settings:enrichment:header', 'local_smartmedia'),
        get_string('settings:enrichment:description', 'local_smartmedia')));
    $settings->add(new admin_setting_configcheckbox('local_smartmedia/detectlabels',
        get_string('settings:enrichment:detectlabels', 'local_smartmedia'),
        get_string('settings:enrichment:detectlabels_help', 'local_smartmedia'), 0));
    $settings->add(new admin_setting_configcheckbox('local_smartmedia/detectmoderation',
        get_string('settings:enrichment:detectmoderation', 'local_smartmedia'),
        get_string('settings:enrichment:detectmoderation_help', 'local_smartmedia'), 0));
    $settings->add(new admin_setting_configcheckbox('local_smartmedia/detectfaces',
        get_string('settings:enrichment:detectfaces', 'local_smartmedia'),
        get_string('settings:enrichment:detectfaces_help', 'local_smartmedia'), 0));
    $settings->add(new admin_setting_configcheckbox('local_smartmedia/detectpeople',
        get_string('settings:enrichment:detectpeople', 'local_smartmedia'),
        get_string('settings:enrichment:detectpeople_help', 'local_smartmedia'), 0));
    $settings->add(new admin_setting_configcheckbox('local_smartmedia/transcribe',
        get_string('settings:enrichment:transcribe', 'local_smartmedia'),
        get_string('settings:enrichment:transcribe_help', 'local_smartmedia'), 0));
    // TODO: figure out how to disable these settings if transcribe is disabled.
    $settings->add(new admin_setting_configcheckbox('local_smartmedia/detectsentiment',
        get_string('settings:enrichment:detectsentiment', 'local_smartmedia'),
        get_string('settings:enrichment:detectsentiment_help', 'local_smartmedia'), 0));
    $settings->add(new admin_setting_configcheckbox('local_smartmedia/detectphrases',
        get_string('settings:enrichment:detectphrases', 'local_smartmedia'),
        get_string('settings:enrichment:detectphrases_help', 'local_smartmedia'), 0));
    $settings->add(new admin_setting_configcheckbox('local_smartmedia/detectentities',
        get_string('settings:enrichment:detectentities', 'local_smartmedia'),
        get_string('settings:enrichment:detectentities_help', 'local_smartmedia'), 0));

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
