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

    }

    /**
     * Calculate the total cost of transcoding all media items.
     *
     * @return float|int $total
     *
     * @throws \dml_exception
     */
    private function calculate_total_cost() {
        global $DB;

        // Get the duration of media type content (in seconds), zero if there is no media of type.
        $highdefinition = $DB->get_record_select('local_smartmedia_data', 'height >= ?',
            [LOCAL_SMARTMEDIA_MINIMUM_HD_HEIGHT], 'COALESCE(SUM(duration), 0) as duration');
        $standarddefinition = $DB->get_record_select('local_smartmedia_data', '(height < ?) AND (height > 0)',
            [LOCAL_SMARTMEDIA_MINIMUM_HD_HEIGHT], 'COALESCE(SUM(duration), 0) as duration');
        $audio = $DB->get_record_select('local_smartmedia_data', '(height = 0) OR (height IS NULL)',
            null, 'COALESCE(SUM(duration), 0) as duration');

        $totalhdcost = $this->pricingcalculator->calculate_transcode_cost(LOCAL_SMARTMEDIA_MINIMUM_HD_HEIGHT,
            $highdefinition->duration);
        $totalsdcost = $this->pricingcalculator->calculate_transcode_cost(LOCAL_SMARTMEDIA_MINIMUM_SD_HEIGHT,
            $standarddefinition->duration);
        $totalaudiocost = $this->pricingcalculator->calculate_transcode_cost(LOCAL_SMARTMEDIA_AUDIO_HEIGHT,
            $audio->duration);
        $total = $totalhdcost + $totalsdcost + $totalaudiocost;

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
        $this->validate_pricing();

        $context = new stdClass();
        $context->total = '$' . number_format($this->calculate_total_cost(), 4);
        $context->warnings = $this->warnings;

        return $context;
    }
}