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
 * @package     local_smartmedia
 * @author      Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright   2019 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_smartmedia\output;

defined('MOODLE_INTERNAL') || die;

use table_sql;
use renderable;

/**
 * Renderable class for index page of local_smartmedia plugin.
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
     * Minimum width above which transcoding is considered HD.
     */
    const MIN_HD_WIDTH = 720;

    /**
     * report_table constructor.
     *
     * @param string $uniqueid Unique id of table.
     * @param \moodle_url $url Url where this table is displayed.
     * @param \stdClass $params the parameters for table display.
     *
     * @throws \coding_exception
     */
    public function __construct($uniqueid, $url, $params) {
        parent::__construct($uniqueid);

        $this->set_attribute('id', 'local_smartmedia_report_table');
        $this->set_attribute('class', 'generaltable generalbox');
        $this->show_download_buttons_at(array(TABLE_P_BOTTOM));
        $this->is_downloading($params->download, 'smartmedia-report');
        $this->define_baseurl($url);
        $this->define_columns(array('videostreams', 'format', 'width', 'duration', 'size', 'cost'));
        $this->define_headers(array(
            get_string('report:type', 'local_smartmedia'),
            get_string('report:format', 'local_smartmedia'),
            get_string('report:resolution', 'local_smartmedia'),
            get_string('report:duration', 'local_smartmedia'),
            get_string('report:size', 'local_smartmedia'),
            get_string('report:transcodecost', 'local_smartmedia')
        ));
        $this->currpage = isset($params->page) ? $params->page : 0;
        $this->pagesize = isset($params->pagesize) ? $params->pagesize : 0;
        $this->sortable(true);
        $this->no_sorting('format');
        $this->no_sorting('cost');
        $this->set_sql(self::FIELDS, '{local_smartmedia_data}', 'TRUE');

    }

    /**
     * Get content for videostreams column.
     * We use `videostreams` field for sorting, output is determined by `videostreams` and
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
                // We should never get here unless there has been an error with ffprobe when running scheduled task.
                throw new \coding_exception(get_string('report:typeerror', 'local_smartmedia', $row->id));
            }
        } else {
            $format = get_string('report:typevideo', 'local_smartmedia');
        }
        return $this->format_text($format);
    }

    /**
     * Get content for format column.
     *
     * @param \stdClass $row
     *
     * @return string html used to display the type field.
     * @throws \moodle_exception
     */
    public function col_format($row) {
        $metadata = json_decode($row->metadata);
        return $this->format_text($metadata->formatname);
    }

    /**
     * Get content for width column.
     *
     * @param \stdClass $row
     *
     * @return string html used to display the column field.
     */
    public function col_width($row) {
        $resolution = $row->width . ' X ' . $row->height;
        return $this->format_text($resolution);
    }

    /**
     * Get content for duration column.
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
     *
     * @param \stdClass $row
     *
     * @return string html used to display the column field.
     */
    public function col_cost($row) {
        if ($row->width >= static::MIN_HD_WIDTH) {
            $cost = (float) $row->duration / 60 * 0.034;
        } else {
            $cost = (float) $row->duration / 60 * 0.017;
        }
        if (!$this->is_downloading()) {
            $cost = round($cost, 4);
        }
        return $this->format_text('$' . $cost);
    }

}

