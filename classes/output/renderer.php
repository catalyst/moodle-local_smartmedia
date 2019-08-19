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
 * Renderer class for audit table.
 *
 * @package     local_smartmedia
 * @author      Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright   2019 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_smartmedia\output;

defined('MOODLE_INTERNAL') || die;

use local_smartmedia\aws_ets_pricing_client;
use local_smartmedia\location_transcode_pricing;
use plugin_renderer_base;

/**
 * Renderer class for audit table.
 *
 * @package     local_smartmedia
 * @author      Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright   2019 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /**
     * Render the html for the report table.
     *
     * @param string $baseurl the base url to render the table on.
     * @param \local_smartmedia\location_transcode_pricing $locationpricing object containing the pricing for the set region.
     * @param int $page the page number for pagination.
     * @param int $perpage amount of records per page for pagination.
     * @param string $download dataformat type. One of csv, xhtml, ods, etc
     *
     * @return string $output html for display
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    private function render_report_table(string $baseurl, location_transcode_pricing $locationpricing, int $page = 0,
                                         int $perpage = 50, string $download = '') {

        $renderable = new report_table('local_smartmedia', $baseurl, $locationpricing, $perpage, $page, $download);
        ob_start();
        $renderable->out($renderable->pagesize, true);
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }

    /**
     * Render the html for the report summary.
     *
     * @param \local_smartmedia\location_transcode_pricing $locationpricing object containing the pricing for the set region.
     *
     * @return string html to display.
     * @throws \moodle_exception
     */
    public function render_report_summary(location_transcode_pricing $locationpricing) {
        $reportsummary = new report_summary($locationpricing);
        $context = $reportsummary->export_for_template($this);
        return $this->render_from_template('local_smartmedia/report-summary', $context);
    }

    /**
     * Get the html to render the local_smartmedia report.
     *
     * @param string $baseurl the base url to render this report on.
     * @param \local_smartmedia\location_transcode_pricing $locationpricing the pricing client for table and filter pricing queries.
     * @param int $page the page number for pagination.
     * @param int $perpage amount of records per page for pagination.
     * @param string|null $download dataformat type. One of csv, xhtml, ods, etc
     *
     * @return string $html the html to display.
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function render_report(string $baseurl, location_transcode_pricing $locationpricing, int $page = 0, int $perpage = 50,
                                  string $download = '') : string {
        $region = get_config('local_smartmedia', 'api_region');

        // Get the table output first to prevent output being buffered before download.
        $tablehtml = $this->render_report_table($baseurl, $locationpricing, $page, $perpage, $download);

        $html = '';
        $html .= $this->header();
        $html .= $this->heading(get_string('report:heading', 'local_smartmedia', $region));
        $html .= $this->render_report_summary($locationpricing);
        $html .= $tablehtml;
        $html .= $this->footer();

        return $html;
    }
}
