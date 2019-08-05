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
     * @param aws_ets_pricing_client $pricingclient the pricing client for table pricing queries.
     * @param int $page the page number for pagination.
     * @param int $perpage amount of records per page for pagination.
     * @param string|null $download dataformat type. One of csv, xhtml, ods, etc
     * @param string|null $pricinglocation url encoded pricing location.
     *
     * @return string $output html for display
     */
    private function render_report_table(string $baseurl, aws_ets_pricing_client $pricingclient, int $page = 0,
                                         int $perpage = 50, string $download = null, string $pricinglocation = null) {

        $renderable = new report_table('local_smartmedia', $baseurl, $pricingclient, $perpage, $page, $download, $pricinglocation);
        ob_start();
        $renderable->out($renderable->pagesize, true);
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }

    /**
     * Render the pricing location filter or redirect based on form data.
     *
     * @param string $baseurl the base url to render the filter on.
     * @param aws_ets_pricing_client $pricingclient the pricing client for filter pricing queries.
     * @param int $page the page number for pagination.
     * @param int $perpage amount of records per page for pagination.
     * @param string|null $download dataformat type. One of csv, xhtml, ods, etc
     * @param string|null $pricinglocation url encoded pricing location.
     *
     * @return string
     * @throws \moodle_exception
     */
    private function render_pricing_location_filter(string $baseurl, aws_ets_pricing_client $pricingclient,
                                                    int $page = 0, int $perpage = 50, string $download = null,
                                                    string $pricinglocation = null) {
        // Build parameters for redirect.
        $attributes = [
            'page' => $page,
            'perpage' => $perpage,
            'download' => $download,
            'pricinglocation' => $pricinglocation,
        ];
        // Pass custom data to the filter form including aws_ets_pricing_client dependency.
        $customdata = array_merge($attributes, ['pricingclient' => $pricingclient]);

        $filter = new pricing_location_filter(null, $customdata);

        // If we've set a filter, redirect to apply it.
        if ($data = $filter->get_data()) {
            $url = new \moodle_url($baseurl, $attributes);
            redirect($url);
        } else {
            return $filter->render();
        }
    }

    /**
     * Get the html to render the local_smartmedia report.
     *
     * @param string $baseurl the base url to render this report on.
     * @param aws_ets_pricing_client $pricingclient the pricing client for table and filter pricing queries.
     * @param int $page the page number for pagination.
     * @param int $perpage amount of records per page for pagination.
     * @param string|null $download dataformat type. One of csv, xhtml, ods, etc
     * @param string|null $pricinglocation location
     *
     * @return string $html the html to display.
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function render_report(string $baseurl, aws_ets_pricing_client $pricingclient, int $page = 0, int $perpage = 50,
                                  string $download = null, string $pricinglocation = null) : string {
        // Get the table output first to prevent output being buffered before download.
        $tablehtml = $this->render_report_table($baseurl, $pricingclient, $page, $perpage, $download, $pricinglocation);
        // Get the filter html before header to prevent output buffering before redirect.
        $filterhtml = $this->render_pricing_location_filter($baseurl, $pricingclient, $page, $perpage, $download, $pricinglocation);

        $html = '';
        $html .= $this->header();
        $html .= $filterhtml;
        $html .= $this->heading(get_string('report:heading', 'local_smartmedia', urldecode($pricinglocation)));
        $html .= $tablehtml;
        $html .= $this->footer();

        return $html;
    }
}
