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
 * A report to display a table of metadata for the multimedia assets in this Moodle instance.
 *
 * @package    local_smartmedia
 * @author     Tom Dickman
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

$id = required_param('id', PARAM_INT);

// Calls require_login and performs permissions checks for admin pages.
admin_externalpage_setup('local_smartmedia_report', '', null, '',
    array('pagelayout' => 'report'));

$title = get_string('pluginname', 'local_smartmedia');

$PAGE->set_title($title);
$PAGE->set_heading($title);

$output = $PAGE->get_renderer('local_smartmedia');

// Setup a transcoder to get all preset information and store it.
$api = new \local_smartmedia\aws_api;
$transcoderclient = $api->create_elastic_transcoder_client();
$transcoder = new \local_smartmedia\aws_elastic_transcoder($transcoderclient);
$presets = $transcoder->get_all_presets();

$sql = "SELECT f.filename, ro.type, ro.format, ro.resolution, ro.duration, ro.filesize, ro.cost, ro.status,
               ro.files, ro.timecreated, ro.timecompleted, conv.id as convid
          FROM {local_smartmedia_report_over} ro
          JOIN {local_smartmedia_conv} conv ON ro.contenthash = conv.contenthash
          JOIN {files} f ON f.contenthash = conv.contenthash AND f.pathnamehash = conv.pathnamehash
         WHERE ro.id = :id";
$record = $DB->get_record_sql($sql, ['id' => $id]);

// Get the preset ids being used for the conversion.
$select = 'convid = ?';
$presetids = $DB->get_fieldset_select('local_smartmedia_presets', 'preset', $select, [$record->convid]);

$outputs = [];
// Get the information from the presets used in the conversion.
foreach ($presets as $preset) {
    if (in_array($preset->get_id(), $presetids)) {
        $presetdata = $preset->get_data();
        if (array_key_exists('Video', $presetdata)) {
            // This is a video conversion.
            $codec = $presetdata['Video']['Codec'];
            $width = $presetdata['Video']['MaxWidth'];
            $height = $presetdata['Video']['MaxHeight'];

            // Now get the approximate filesize from the duration and bitrate.
            $size = $record->duration * (int) $presetdata['Video']['BitRate'];
            $formattedsize = display_size($size);

            $outputs[] = "{$codec}: {$width} X {$height} - {$formattedsize} ";
        } else {
            // This is only audio, just output codec and size.
            $size = $record->duration * (int) $presetdata['Audio']['BitRate'];
            $formattedsize = display_size($size);
            $outputs[] = $presetdata['Audio']['Codec'] . ' - ' . $formattedsize;
        }
    }
}

// Calculate the formatted duration.
$rawduration = $record->duration;
$hours = floor($rawduration / 3600);
$minutes = str_pad(floor(($rawduration / 60) % 60), 2, '0', STR_PAD_LEFT);
$seconds = str_pad($rawduration % 60, 2, '0', STR_PAD_LEFT);
$duration = "$hours:$minutes:$seconds";

$context = [
    'filename' => $record->filename,
    'type' => $record->type,
    'format' => $record->format,
    'sourceresolution' => $record->resolution,
    'outputs' => $outputs,
    'duration' => $duration,
    'size' => display_size($record->filesize),
    'transcodecost' => '$' . number_format($record->cost, 2),
    'files' => $record->files,
    'returnurl' => '/local/smartmedia/report.php'
];

echo $output->header();
echo $output->render_from_template('local_smartmedia/file-details', (object) $context);
echo $output->footer();