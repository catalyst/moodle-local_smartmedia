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
 * Main Admin settings form class.
 *
 * @package     local_smartmedia
 * @copyright   2019 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_smartmedia;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

/**
 * Main Admin settings form class.
 *
 * @package     local_smartmedia
 * @copyright   2019 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class index_form extends \moodleform {

    /**
     * Build form for the general setting admin page for plugin.
     */
    public function definition() {
        $config = get_config('local_smartmedia');
        $mform = $this->_form;
        $attributes = array('size' => '22');

        // AWS Settings settings.
        $mform->addElement('header', 'awsheader',
            get_string('settings:aws:header', 'local_smartmedia'),
            get_string('settings:aws:header_desc', 'local_smartmedia'));
        $mform->addElement('static', 'aws_description', get_string('settings:description', 'local_smartmedia'),
            get_string('settings:aws:description', 'local_smartmedia'));

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

        $mform->addElement('select', 'api_region', get_string('settings:aws:region', 'local_smartmedia'), $regionoptions);
        $mform->addHelpButton('api_region', 'settings:aws:region', 'local_smartmedia');
        if (isset($config->api_region)) {
            $mform->setDefault('api_region', $config->api_region);
        } else {
            $mform->setDefault('api_region', 'ap-southeast-2');
        }

        $mform->addElement('text', 'api_key',  get_string ('settings:aws:key', 'local_smartmedia'), $attributes);
        $mform->setType('api_key', PARAM_TEXT);
        $mform->addHelpButton('api_key', 'settings:aws:key', 'local_smartmedia');
        $mform->addRule('api_key', get_string ('required'), 'required', '', 'client');
        if (isset($config->api_key)) {
            $mform->setDefault('api_key', $config->api_key);
        } else {
            $mform->setDefault('api_key', '');
        }

        $mform->addElement('passwordunmask', 'api_secret',  get_string ('settings:aws:secret', 'local_smartmedia'));
        $mform->setType('api_secret', PARAM_TEXT);
        $mform->addHelpButton('api_secret', 'settings:aws:secret', 'local_smartmedia');
        $mform->addRule('api_secret', get_string ('required'), 'required', '', 'client');
        if (isset($config->api_secret)) {
            $mform->setDefault('api_secret', $config->api_secret);
        } else {
            $mform->setDefault('api_secret', '');
        }

        $mform->addElement('text', 's3_input_bucket',  get_string ('settings:aws:input_bucket', 'local_smartmedia'), $attributes);
        $mform->setType('s3_input_bucket', PARAM_ALPHANUMEXT);
        $mform->addHelpButton('s3_input_bucket', 'settings:aws:input_bucket', 'local_smartmedia');
        $mform->addRule('s3_input_bucket', get_string ('required'), 'required', '', 'client');
        if (isset($config->s3_input_bucket)) {
            $mform->setDefault('s3_input_bucket', $config->s3_input_bucket);
        } else {
            $mform->setDefault('s3_input_bucket', '');
        }

        $mform->addElement('text', 's3_output_bucket',  get_string ('settings:aws:output_bucket', 'local_smartmedia'), $attributes);
        $mform->setType('s3_output_bucket', PARAM_ALPHANUMEXT);
        $mform->addHelpButton('s3_output_bucket', 'settings:aws:output_bucket', 'local_smartmedia');
        $mform->addRule('s3_output_bucket', get_string ('required'), 'required', '', 'client');
        if (isset($config->s3_output_bucket)) {
            $mform->setDefault('s3_output_bucket', $config->s3_output_bucket);
        } else {
            $mform->setDefault('s3_output_bucket', '');
        }

        $mform->addElement('text', 'sqs_queue_url',  get_string ('settings:aws:sqs_queue_url', 'local_smartmedia'), ['size' => 50]);
        $mform->setType('sqs_queue_url', PARAM_URL);
        $mform->addHelpButton('sqs_queue_url', 'settings:aws:sqs_queue_url', 'local_smartmedia');
        $mform->addRule('sqs_queue_url', get_string ('required'), 'required', '', 'client');
        if (isset($config->sqs_queue_url)) {
            $mform->setDefault('sqs_queue_url', $config->sqs_queue_url);
        } else {
            $mform->setDefault('sqs_queue_url', '');
        }

        // Output settings.
        $mform->addElement('header', 'outputheader', get_string('settings:output:header', 'local_smartmedia'));
        $mform->addElement('static', 'output_description', get_string('settings:description', 'local_smartmedia'),
            get_string('settings:output:description', 'local_smartmedia'));

        $mform->addElement(
            'advcheckbox',
            'quality_low',
            get_string ('settings:output:quality_low', 'local_smartmedia'),
            get_string('enable'), array(), array(0, 1));
        $mform->setType('quality_low', PARAM_INT);
        $mform->addHelpButton('quality_low', 'settings:output:quality_low', 'local_smartmedia');
        $qualitylow = isset($config->quality_low) ? $config->quality_low : 1;
        $mform->setDefault('quality_low', $qualitylow);

        $mform->addElement(
            'advcheckbox',
            'quality_medium',
            get_string ('settings:output:quality_medium', 'local_smartmedia'),
            get_string('enable'), array(), array(0, 1));
        $mform->setType('quality_medium', PARAM_INT);
        $mform->addHelpButton('quality_medium', 'settings:output:quality_medium', 'local_smartmedia');
        $qualitymedium = isset($config->quality_medium) ? $config->quality_medium : 0;
        $mform->setDefault('quality_medium', $qualitymedium);

        $mform->addElement(
            'advcheckbox',
            'quality_high',
            get_string ('settings:output:quality_high', 'local_smartmedia'),
            get_string('enable'), array(), array(0, 1));
        $mform->setType('quality_high', PARAM_INT);
        $mform->addHelpButton('quality_high', 'settings:output:quality_high', 'local_smartmedia');
        $qualityhigh = isset($config->quality_high) ? $config->quality_high : 1;
        $mform->setDefault('quality_high', $qualityhigh);

        $mform->addElement(
            'advcheckbox',
            'audio_output',
            get_string ('settings:output:audio_output', 'local_smartmedia'),
            get_string('enable'), array(), array(0, 1));
        $mform->setType('audio_output', PARAM_INT);
        $mform->addHelpButton('audio_output', 'settings:output:audio_output', 'local_smartmedia');
        $audiooutput = isset($config->audio_output) ? $config->audio_output : 1;
        $mform->setDefault('audio_output', $audiooutput);

        $mform->addElement(
            'advcheckbox',
            'download_files',
            get_string ('settings:output:download_files', 'local_smartmedia'),
            get_string('enable'), array(), array(0, 1));
        $mform->setType('download_files', PARAM_INT);
        $mform->addHelpButton('download_files', 'settings:output:download_files', 'local_smartmedia');
        $downloadfiles = isset($config->download_files) ? $config->download_files : 1;
        $mform->setDefault('download_files', $downloadfiles);

        // Processing settings.
        $mform->addElement('header', 'processingheader', get_string('settings:processing:header', 'local_smartmedia'));
        $mform->addElement('static', 'processing_description', get_string('settings:description', 'local_smartmedia'),
            get_string('settings:processing:description', 'local_smartmedia'));

        $mform->addElement(
            'advcheckbox',
            'proactiveconversion',
            get_string ('settings:processing:proactiveconversion', 'local_smartmedia'),
            get_string('enable'), array(), array(0, 1));
        $mform->setType('proactiveconversion', PARAM_INT);
        $mform->addHelpButton('proactiveconversion', 'settings:processing:proactiveconversion', 'local_smartmedia');
        $proactiveconversion = isset($config->proactiveconversion) ? $config->proactiveconversion : 0;
        $mform->setDefault('proactiveconversion', $proactiveconversion);

        $mform->addElement(
            'advcheckbox',
            'viewconversion',
            get_string ('settings:processing:viewconversion', 'local_smartmedia'),
            get_string('enable'), array(), array(0, 1));
        $mform->setType('viewconversion', PARAM_INT);
        $mform->addHelpButton('viewconversion', 'settings:processing:viewconversion', 'local_smartmedia');
        $viewconversion = isset($config->viewconversion) ? $config->viewconversion : 1;
        $mform->setDefault('viewconversion', $viewconversion);

        $mform->addElement('date_selector', 'convertfrom', get_string ('settings:processing:convertfrom', 'local_smartmedia'));
        $mform->addHelpButton('convertfrom', 'settings:processing:convertfrom', 'local_smartmedia');
        $convertfrom = isset($config->convertfrom) ? $config->convertfrom : date('U', strtotime(date('Y-01-01')));
        $mform->setDefault('convertfrom', $convertfrom);

        // Enrichment settings.
        $mform->addElement('header', 'enrichmentheader', get_string('settings:enrichment:header', 'local_smartmedia'));
        $mform->addElement('static', 'enrichment_description', get_string('settings:description', 'local_smartmedia'),
            get_string('settings:enrichment:description', 'local_smartmedia'));

        $mform->addElement(
            'advcheckbox',
            'detectlabels',
            get_string ('settings:enrichment:detectlabels', 'local_smartmedia'),
            get_string('enable'), array(), array(0, 1));
        $mform->setType('detectlabels', PARAM_INT);
        $mform->addHelpButton('detectlabels', 'settings:enrichment:detectlabels', 'local_smartmedia');
        $detectlabels = isset($config->detectlabels) ? $config->detectlabels : 0;
        $mform->setDefault('detectlabels', $detectlabels);

        $mform->addElement(
            'advcheckbox',
            'detectmoderation',
            get_string ('settings:enrichment:detectmoderation', 'local_smartmedia'),
            get_string('enable'), array(), array(0, 1));
        $mform->setType('detectmoderation', PARAM_INT);
        $mform->addHelpButton('detectmoderation', 'settings:enrichment:detectmoderation', 'local_smartmedia');
        $detectmoderation = isset($config->detectmoderation) ? $config->detectmoderation : 0;
        $mform->setDefault('detectmoderation', $detectmoderation);

        $mform->addElement(
            'advcheckbox',
            'detectfaces',
            get_string ('settings:enrichment:detectfaces', 'local_smartmedia'),
            get_string('enable'), array(), array(0, 1));
        $mform->setType('detectfaces', PARAM_INT);
        $mform->addHelpButton('detectfaces', 'settings:enrichment:detectfaces', 'local_smartmedia');
        $detectfaces = isset($config->detectfaces) ? $config->detectfaces : 0;
        $mform->setDefault('detectfaces', $detectfaces);

        $mform->addElement(
            'advcheckbox',
            'detectpeople',
            get_string ('settings:enrichment:detectpeople', 'local_smartmedia'),
            get_string('enable'), array(), array(0, 1));
        $mform->setType('detectpeople', PARAM_INT);
        $mform->addHelpButton('detectpeople', 'settings:enrichment:detectpeople', 'local_smartmedia');
        $detectpeople = isset($config->detectpeople) ? $config->detectpeople : 0;
        $mform->setDefault('detectpeople', $detectpeople);

        $mform->addElement(
            'advcheckbox',
            'transcribe',
            get_string ('settings:enrichment:transcribe', 'local_smartmedia'),
            get_string('enable'), array(), array(0, 1));
        $mform->setType('transcribe', PARAM_INT);
        $mform->addHelpButton('transcribe', 'settings:enrichment:transcribe', 'local_smartmedia');
        $transcribe = isset($config->transcribe) ? $config->transcribe : 0;
        $mform->setDefault('transcribe', $transcribe);

        $mform->addElement(
            'advcheckbox',
            'detectsentiment',
            get_string ('settings:enrichment:detectsentiment', 'local_smartmedia'),
            get_string('enable'), array(), array(0, 1));
        $mform->setType('detectsentiment', PARAM_INT);
        $mform->addHelpButton('detectsentiment', 'settings:enrichment:detectsentiment', 'local_smartmedia');
        $mform->disabledIf('detectsentiment', 'transcribe', 'unchecked');
        $detectsentiment = isset($config->detectsentiment) ? $config->detectsentiment : 0;
        $mform->setDefault('detectsentiment', $detectsentiment);

        $mform->addElement(
            'advcheckbox',
            'detectphrases',
            get_string ('settings:enrichment:detectphrases', 'local_smartmedia'),
            get_string('enable'), array(), array(0, 1));
        $mform->setType('detectphrases', PARAM_INT);
        $mform->addHelpButton('detectphrases', 'settings:enrichment:detectphrases', 'local_smartmedia');
        $mform->disabledIf('detectphrases', 'transcribe', 'unchecked');
        $detectphrases = isset($config->detectphrases) ? $config->detectphrases : 0;
        $mform->setDefault('detectphrases', $detectphrases);

         $mform->addElement(
            'advcheckbox',
            'detectentities',
            get_string ('settings:enrichment:detectentities', 'local_smartmedia'),
            get_string('enable'), array(), array(0, 1));
        $mform->setType('detectentities', PARAM_INT);
        $mform->addHelpButton('detectentities', 'settings:enrichment:detectentities', 'local_smartmedia');
        $mform->disabledIf('detectentities', 'transcribe', 'unchecked');
        $detectentities = isset($config->detectentities) ? $config->detectentities : 0;
        $mform->setDefault('detectentities', $detectentities);

        // FFprobe settings.
        $mform->addElement('header', 'ffprobeheader', get_string('settings:ffprobe:header', 'local_smartmedia'));
        $mform->addElement('static', 'ffrprobe_description', get_string('settings:description', 'local_smartmedia'),
            get_string('settings:ffprobe:description', 'local_smartmedia'));

        $mform->addElement('text', 'pathtoffprobe',  get_string ('settings:ffprobe:pathtoffprobe', 'local_smartmedia'));
        $mform->setType('pathtoffprobe', PARAM_PATH);
        $mform->addHelpButton('pathtoffprobe', 'settings:aws:input_bucket', 'local_smartmedia');
        $mform->addRule('pathtoffprobe', get_string ('required'), 'required', '', 'client');
        if (isset($config->pathtoffprobe)) {
            $mform->setDefault('pathtoffprobe', $config->pathtoffprobe);
        } else {
            $mform->setDefault('pathtoffprobe', '/usr/bin/ffprobe');
        }

        $this->add_action_buttons();
    }

}
