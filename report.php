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

$page = optional_param('page', 0, PARAM_INT);
$download = optional_param('download', '', PARAM_RAW);
$perpage = optional_param('perpage', 50, PARAM_INT);
$pricinglocation = optional_param('pricinglocation', '', PARAM_RAW);
$baseurl = $CFG->wwwroot . "/local/smartmedia/report.php";

// Calls require_login and performs permissions checks for admin pages.
admin_externalpage_setup('local_smartmedia_report', '', null, '',
    array('pagelayout' => 'report'));

$title = get_string('pluginname', 'local_smartmedia');
$url = new moodle_url($baseurl);

$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_title($title);
$PAGE->set_heading($title);

$output = $PAGE->get_renderer('local_smartmedia');
// TODO: Replace region with setting from admin page.
$api = new \local_smartmedia\aws_api('us-east-1');
$pricingclient = new \local_smartmedia\aws_ets_pricing_client($api->get_pricing_client());
echo $output->render_report($baseurl, $pricingclient, $page, $perpage, $download, $pricinglocation);
