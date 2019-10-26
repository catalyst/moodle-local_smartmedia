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

defined('MOODLE_INTERNAL') || die;

use local_smartmedia\pricing_calculator;
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
     * @var \local_smartmedia\pricing_calculator the pricing calculator for calculation pricing info.
     */
    private $pricingcalculator;

    /**
     * @var string the AWS region code.
     */
    private $region;

    /**
     * @var array of stdclass objects representing warnings.
     */
    private $warnings = [];

    /**
     * report_summary constructor.
     *
     * @param \local_smartmedia\pricing_calculator $pricingcalculator the pricing calculator for transcode cost calculation.
     * @param string $region the AWS region which this report summary applies to.
     */
    public function __construct(pricing_calculator $pricingcalculator, $region) {
        $this->pricingcalculator = $pricingcalculator;
        $this->region = $region;
    }


    /**
     * Validate that all location pricing is valid in pricing calculator, add warnings if not.
     *
     * @throws \coding_exception
     */
    private function validate_pricing() {

        if (!$this->pricingcalculator->is_high_definition_pricing_valid()) {
            $warning = new stdClass();
            $warning->message = get_string('report:summary:warning:nohdcost', 'local_smartmedia', $this->region);
            $this->warnings[] = $warning;
        }

        if (!$this->pricingcalculator->is_standard_definition_pricing_valid()) {
            $warning = new stdClass();
            $warning->message = get_string('report:summary:warning:nosdcost', 'local_smartmedia', $this->region);
            $this->warnings[] = $warning;
        }

        if (!$this->pricingcalculator->is_audio_pricing_valid()) {
            $warning = new stdClass();
            $warning->message = get_string('report:summary:warning:noaudiocost', 'local_smartmedia', $this->region);
            $this->warnings[] = $warning;
        }

        if (!$this->pricingcalculator->has_presets()) {
            $warning = new stdClass();
            $warning->message = get_string('report:summary:warning:invalidpresets', 'local_smartmedia', $this->region);
            $this->warnings[] = $warning;
        }

    }

    /**
     * Calculate the total cost of transcoding all media items.
     *
     * @return float|int|null $total cost for all transcoding across all presets, null if total cannot be calculated.
     *
     * @throws \dml_exception
     */
    private function calculate_total_cost() {
        global $DB;

        if (!$this->pricingcalculator->has_presets()) {
            $total = null;
        } else {
            // Get the duration of media type content (in seconds), zero if there is no media of type.
            $highdefinition = $DB->get_record_select('local_smartmedia_data', 'height >= ? AND videostreams > 0',
                [LOCAL_SMARTMEDIA_MINIMUM_HD_HEIGHT], 'COALESCE(SUM(duration), 0) as duration');
            $standarddefinition = $DB->get_record_select('local_smartmedia_data',
                '(height < ?) AND (height > 0) AND videostreams > 0',
                [LOCAL_SMARTMEDIA_MINIMUM_HD_HEIGHT], 'COALESCE(SUM(duration), 0) as duration');
            $audio = $DB->get_record_select('local_smartmedia_data',
                '((height = 0) OR (height IS NULL)) AND audiostreams > 0',
                null, 'COALESCE(SUM(duration), 0) as duration');

            $totalhdcost = $this->pricingcalculator->calculate_transcode_cost(LOCAL_SMARTMEDIA_MINIMUM_HD_HEIGHT,
                $highdefinition->duration);
            $totalsdcost = $this->pricingcalculator->calculate_transcode_cost(LOCAL_SMARTMEDIA_MINIMUM_SD_HEIGHT,
                $standarddefinition->duration);
            $totalaudiocost = $this->pricingcalculator->calculate_transcode_cost(LOCAL_SMARTMEDIA_AUDIO_HEIGHT,
                $audio->duration);
            $total = $totalhdcost + $totalsdcost + $totalaudiocost;
        }

        return $total;
    }

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
        $this->validate_pricing();

        $context = new stdClass();

        $total = $this->calculate_total_cost();
        if (!empty($total)) {
            $context->total = '$' . number_format($total, 4);
        } else {
            $context->total = get_string('report:nocostdata', 'local_smartmedia');
        }

        $context->warnings = $this->warnings;
        $context->file_summary = $this->get_file_summary_chart();
        $context->process_summary = $this->get_process_summary_chart();

        return $context;
    }
}
