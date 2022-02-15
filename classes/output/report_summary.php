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
 * Renderable summary for the AWS Elastic Transcode report.
 *
 * @package     local_smartmedia
 * @author      Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright   2019 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_smartmedia\output;

use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * Renderable summary for the AWS Elastic Transcode report.
 *
 * @package     local_smartmedia
 * @author      Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright   2019 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_summary implements renderable, templatable {
    /**
     * Get the file summary totals from the DB.
     * Used in generating chart data.
     *
     * @return array $totals The array of totals.
     */
    private function get_file_summary_totals() : array {
        global $DB;
        $totals = array();
        $totalfiles = 0;
        $videofiles = 0;
        $audiofiles = 0;
        $otherfiles = 0;

        // Get values for chart from the database.
        list($insql, $inparams) = $DB->get_in_or_equal(array('totalfiles', 'videofiles', 'audiofiles'));
        $select = "name $insql";
        $values = $DB->get_records_select('local_smartmedia_reports', $select, $inparams, '', 'name, value');

        if (!empty(($values))) { // Handle case where there is no data in table.
            $totalfiles = $values['totalfiles']->value;
            $videofiles = $values['videofiles']->value;
            $audiofiles = $values['audiofiles']->value;

            $otherfiles = $totalfiles - ($videofiles + $audiofiles);
            $totals = array($otherfiles, $videofiles, $audiofiles);
        }

        return $totals;
    }

    /**
     * Get the process summary totals from the DB.
     * Used in generating chart data.
     *
     * @return array $totals The array of totals.
     */
    private function get_process_summary_totals() : array {
        global $DB;
        $totals = array();
        $uniquemultimediaobjects = 0;
        $metadataprocessedfiles = 0;
        $transcodedfiles = 0;

        // Get values for chart from the database.
        $fields = array('uniquemultimediaobjects', 'metadataprocessedfiles', 'transcodedfiles');
        list($insql, $inparams) = $DB->get_in_or_equal($fields);
        $select = "name $insql";
        $values = $DB->get_records_select('local_smartmedia_reports', $select, $inparams, '', 'name, value');

        if (!empty(($values))) { // Handle case where there is no data in table.
            $uniquemultimediaobjects = $values['uniquemultimediaobjects']->value;
            $metadataprocessedfiles = $values['metadataprocessedfiles']->value;
            $transcodedfiles = $values['transcodedfiles']->value;

            $totals = array($uniquemultimediaobjects, $metadataprocessedfiles, $transcodedfiles);
        }

        return $totals;
    }

    /**
     * Generate the markup for the file summary chart,
     * used in the smart media dashboard.
     *
     * @return $output The generated chart to be fed to a template.
     */
    private function get_file_summary_chart() : string {
        global $OUTPUT;

        $values = $this->get_file_summary_totals();

        if (!empty(($values))) { // Handle case where there is no data in table.

            $series = new \core\chart_series(get_string('report:summary:filesummary:total', 'local_smartmedia'), $values);
            $labels = array(
                    get_string('report:summary:filesummary:otherfiles', 'local_smartmedia'),
                    get_string('report:summary:filesummary:videofiles', 'local_smartmedia'),
                    get_string('report:summary:filesummary:audiofiles', 'local_smartmedia')
            );

            $chart = new \core\chart_pie();
            $chart->set_doughnut(true); // Calling set_doughnut(true) we display the chart as a doughnut.
            $chart->add_series($series);
            $chart->set_labels($labels);

            $output = $OUTPUT->render_chart($chart);
        } else {
            $output = ''; // Empty string will be replaced in template.
        }

        return $output;
    }

    /**
     * Generate the markup for the process summary chart,
     * used in the smart media dashboard.
     *
     * @return $output The generated chart to be fed to a template.
     */
    private function get_process_summary_chart() : string {
        global $OUTPUT;

        $values = $this->get_process_summary_totals();

        if (!empty(($values))) { // Handle case where there is no data in table.

            $series1 = new \core\chart_series(
                get_string('report:summary:processsummary:uniquemultimediaobjects', 'local_smartmedia'), [$values[0]]);
            $series2 = new \core\chart_series(
                get_string('report:summary:processsummary:metadataprocessedfiles', 'local_smartmedia'), [$values[1]]);
            $series3 = new \core\chart_series(
                get_string('report:summary:processsummary:transcodedfiles', 'local_smartmedia'), [$values[2]]);
            $labels = array(get_string('report:summary:totals', 'local_smartmedia'));

            $chart = new \core\chart_bar();
            $chart->add_series($series1);
            $chart->add_series($series2);
            $chart->add_series($series3);
            $chart->set_labels($labels);

            $output = $OUTPUT->render_chart($chart);
        } else {
            $output = ''; // Empty string will be replaced in template.
        }

        return $output;

    }

    /**
     * Get the cost of not converted media.
     *
     * @return mixed|boolean $total the total cost of not converted media.
     */
    private function get_total_cost() {
        global $DB;

        $total = $DB->get_field('local_smartmedia_reports', 'value', array('name' => 'totalcost'));

        return $total;
    }

    /**
     * Get the cost of converted media.
     *
     * @return mixed|boolean $total the total cost of converted media.
     */
    private function get_converted_cost() {
        global $DB;

        $total = $DB->get_field('local_smartmedia_reports', 'value', array('name' => 'convertedcost'));

        return $total;
    }


    /**
     * Export the renderer data in a format that is suitable for a
     * mustache template.
     *
     * @param renderer_base $output Used to do a final render of any components that need to be rendered for export.
     *
     * @return stdClass $context for use in template rendering.
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public function export_for_template(renderer_base $output) {
        $context = new stdClass();

        $total = $this->get_total_cost();
        if (!empty($total)) {
            $context->total = '$' . number_format($total, 2);
        } else {
            $context->total = get_string('report:nocostdata', 'local_smartmedia');
        }

        $convertedtotal = $this->get_converted_cost();
        if (!empty($convertedtotal)) {
            $context->convertedtotal = '$' . number_format($convertedtotal, 2);
        } else {
            $context->convertedtotal = get_string('report:nocostdata', 'local_smartmedia');
        }

        $context->file_summary = $this->get_file_summary_chart();
        $context->process_summary = $this->get_process_summary_chart();

        return $context;
    }
}
