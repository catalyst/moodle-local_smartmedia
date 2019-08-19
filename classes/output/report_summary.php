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

use local_smartmedia\location_transcode_pricing;
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
     * @var \local_smartmedia\location_transcode_pricing the pricing
     */
    private $locationpricing;

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
     * @param \local_smartmedia\location_transcode_pricing $locationpricing
     */
    public function __construct(location_transcode_pricing $locationpricing) {
        $this->locationpricing = $locationpricing;
        $this->region = $locationpricing->get_region();
    }

    /**
     * Calculate the total cost of transcoding all media items.
     *
     * @return float|int $total
     *
     * @throws \dml_exception
     * @throws \coding_exception
     */
    private function calculate_total_cost() {
        global $DB;

        // Get the duration of media type content (in seconds) for cost calculation.
        $highdefinition = $DB->get_record_select('local_smartmedia_data', 'height >= 720',
            null, 'SUM(duration)');
        $standarddefinition = $DB->get_record_select('local_smartmedia_data', '(height < 720) AND (height > 0)',
            null, 'SUM(duration)');
        $audio = $DB->get_record_select('local_smartmedia_data', '(height = 0) OR (height IS NULL)',
            null, 'SUM(duration)');

        $total = 0;

        $totalhdcost = $this->locationpricing->calculate_high_definition_cost($highdefinition->sum);
        if (!is_null($totalhdcost)) {
            $total += $totalhdcost;
        } else {
            $warning = new stdClass();
            $warning->message = get_string('report:summary:warning:nohdcost', 'local_smartmedia', $this->region);
            $this->warnings[] = $warning;
        }

        $totalsdcost = $this->locationpricing->calculate_standard_definition_cost($standarddefinition->sum);
        if (!is_null($totalsdcost)) {
            $total += $totalsdcost;
        } else {
            $warning = new stdClass();
            $warning->message = get_string('report:summary:warning:nosdcost', 'local_smartmedia', $this->region);
            $this->warnings[] = $warning;
        }

        $totalaudiocost = $this->locationpricing->calculate_audio_cost($audio->sum);
        if (!is_null($totalaudiocost)) {
            $total += $totalaudiocost;
        } else {
            $warning = new stdClass();
            $warning->message = get_string('report:summary:warning:noaudiocost', 'local_smartmedia', $this->region);
            $this->warnings[] = $warning;
        }

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
        $context->total = '$' . number_format($this->calculate_total_cost(), 4);
        $context->warnings = $this->warnings;

        return $context;
    }
}
