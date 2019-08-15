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
 * Renderable table for the AWS Elastic Transcode report.
 *
 * @package     local_smartmedia
 * @author      Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright   2019 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_smartmedia\output;

defined('MOODLE_INTERNAL') || die;

use local_smartmedia\aws_ets_pricing_client;
use moodle_url;
use table_sql;
use renderable;

/**
 * Renderable table for the AWS Elastic Transcode report.
 *
 * @package     local_smartmedia
 * @author      Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright   2019 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_table extends table_sql implements renderable {

    /**
     * The required fields from the DB for this report_table.
     */
    const FIELDS = 'id, duration, videostreams, audiostreams, width, height, size, metadata';

    /**
     * The default WHERE clause to exclude records without at least one video or audio stream.
     */
    const DEFAULT_WHERE = '(videostreams > 0) OR (audiostreams > 0)';

    /**
     * @var \local_smartmedia\location_transcode_pricing instance containing pricing for various transcode types.
     */
    private $locationpricing;

    /**
     * report_table constructor.
     *
     * @param string $uniqueid Unique id of table.
     * @param string $baseurl the base url to render this report on.
     * @param aws_ets_pricing_client $pricingclient
     * @param int $page the page number for pagination.
     * @param int $perpage amount of records per page for pagination.
     * @param string $download dataformat type. One of csv, xhtml, ods, etc
     * @param string|null $pricinglocation url encoded pricing location.
     *
     * @throws \coding_exception
     * @throws \dml_exception if aws credentials aren't set correctly in this plugin's settings.
     * @throws \moodle_exception if there is an issue defining the baseline url.
     */
    public function __construct(string $uniqueid, string $baseurl, aws_ets_pricing_client $pricingclient, int $page = 0,
                                int $perpage = 50, string $download = '', string $pricinglocation = null) {
        parent::__construct($uniqueid);

        $this->set_attribute('id', 'local_smartmedia_report_table');
        $this->set_attribute('class', 'generaltable generalbox');
        $this->show_download_buttons_at(array(TABLE_P_BOTTOM));
        $this->is_downloading($download, 'smartmedia-report');
        // Include the location parameter in base url for calculation of dynamic pricing.
        if (!empty($pricinglocation)) {
            $this->define_baseurl($baseurl, ['pricinglocation' => $pricinglocation]);
        } else {
            $this->define_baseurl($baseurl);
        }
        $this->define_columns(array('videostreams', 'format', 'height', 'duration', 'size', 'cost'));
        $this->define_headers(array(
            get_string('report:type', 'local_smartmedia'),
            get_string('report:format', 'local_smartmedia'),
            get_string('report:resolution', 'local_smartmedia'),
            get_string('report:duration', 'local_smartmedia'),
            get_string('report:size', 'local_smartmedia'),
            get_string('report:transcodecost', 'local_smartmedia')
        ));
        // Setup pagination.
        $this->currpage = $page;
        $this->pagesize = $perpage;
        $this->set_location_pricing($pricinglocation, $pricingclient);
        $this->sortable(true);
        $this->no_sorting('format');
        $this->no_sorting('cost');
        $this->set_sql(self::FIELDS, '{local_smartmedia_data}', self::DEFAULT_WHERE);

    }

    /**
     * Override parent method for defining base url.
     * (Base method does not include parameters, required for dynamic pricing calculation.)
     *
     * @param moodle_url|string $url - the moodle url or string of url for baseline.
     * @param array|null $params an array of query parameters for url, null if none.
     *
     * @throws \moodle_exception
     */
    public function define_baseurl($url, $params = null) {
        $this->baseurl = new \moodle_url($url, $params);
    }

    /**
     * Set the pricing to use for this report table.
     *
     * @param string|null $urlencodedlocation a url encoded string of the location to get pricing for, null if no
     * location specified.
     * @param aws_ets_pricing_client $pricingclient aws_ets_pricing_client the pricing client to use for querying AWS Pricing API.
     */
    private function set_location_pricing($urlencodedlocation, aws_ets_pricing_client $pricingclient) {
        if (empty($urlencodedlocation)) {
            $locationpricing = null;
        } else {
            $location = urldecode($urlencodedlocation);
            $locationpricing = $pricingclient->get_location_pricing($location);
        }
        $this->locationpricing = $locationpricing;
    }

    /**
     * Get content for videostreams column.
     * We use `videostreams` field for sorting, requires `videostreams` and
     * `audiostreams` fields.
     *
     * @param \stdClass $row
     *
     * @return string html used to display the video field.
     *
     * @throws \moodle_exception
     */
    public function col_videostreams($row) {
        if (empty($row->videostreams)) {
            if (!empty($row->audiostreams)) {
                $format = get_string('report:typeaudio', 'local_smartmedia');
            } else {
                // We should never get here due to the WHERE clause excluding rows with no video or audio data.
                throw new \coding_exception('No audio or video stream in {local_smartmedia_data} row id#' . $row->id);
            }
        } else {
            $format = get_string('report:typevideo', 'local_smartmedia');
        }
        return $this->format_text($format);
    }

    /**
     * Get content for format column.
     * Requires `metadata` field.
     *
     * @param \stdClass $row
     *
     * @return string html used to display the type field.
     */
    public function col_format($row) {
        $metadata = json_decode($row->metadata);
        return $this->format_text($metadata->formatname);
    }

    /**
     * Get content for width column.
     * We use width for sorting purposes, requires `width` and `height` fields.
     *
     * @param \stdClass $row
     *
     * @return string html used to display the column field.
     */
    public function col_height($row) {
        $resolution = $row->width . ' X ' . $row->height;
        return $this->format_text($resolution);
    }

    /**
     * Get content for duration column.
     * Duration in seconds.
     *
     * @param \stdClass $row
     *
     * @return string html used to display the column field.
     */
    public function col_duration($row) {
        return $this->format_text($row->duration);
    }

    /**
     * Get content for size column.
     * Size displayed in Megabytes (Mb).
     *
     * @param \stdClass $row
     *
     * @return string html used to display the column field.
     */
    public function col_size($row) {
        $sizeinmb = $row->size / 1000000;
        if (!$this->is_downloading()) {
            $sizeinmb = round($sizeinmb, 2);
        }
        return $this->format_text($sizeinmb);
    }

    /**
     * Get content for cost column.
     * Calculated cost for transcoding of audio/video file.
     * Requires `height` and `duration` fields.
     *
     * @param \stdClass $row
     *
     * @return string html used to display the column field.
     * @throws \coding_exception
     */
    public function col_cost($row) {
        $cost = null;
        if (!empty($this->locationpricing)) {
            $cost = $this->locationpricing->calculate_transcode_cost($row->height, $row->duration);
            // Round the result for better display if we aren't downloading the data.
            if (!$this->is_downloading()) {
                $cost = round($cost, 4);
            }
        }
        // If there is no cost or the cost is zero, there is no cost data for this location.
        if (empty($cost)) {
            $cost = get_string('report:nocostdata', 'local_smartmedia');
            $displaycost = $this->format_text($cost);
        } else {
            $displaycost = $this->format_text('$' . $cost);
        }
        return $displaycost;
    }

}

