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
     *
     * @var string
     */
    const FIELDS = 'id, type, format, resolution, duration, filesize, cost, status, files';

    /**
     * The default WHERE clause to exclude records without at least one video or audio stream.
     *
     * @var string
     */
    const DEFAULT_WHERE = 'duration > 0';

    /**
     * report_table constructor.
     *
     * @param string $uniqueid Unique id of table.
     * @param string $baseurl the base url to render this report on.
     * @param int $page the page number for pagination.
     * @param int $perpage amount of records per page for pagination.
     * @param string $download dataformat type. One of csv, xhtml, ods, etc
     *
     * @throws \coding_exception
     */
    public function __construct(string $uniqueid, string $baseurl, int $page = 0,
                                int $perpage = 50, string $download = '') {
        parent::__construct($uniqueid);

        $this->set_attribute('id', 'local_smartmedia_report_table');
        $this->set_attribute('class', 'generaltable generalbox');
        $this->show_download_buttons_at(array(TABLE_P_BOTTOM));
        $this->is_downloading($download, 'smartmedia-report');
        $this->define_baseurl($baseurl);
        $this->define_columns(array('type', 'format', 'resolution', 'duration', 'filesize', 'cost', 'status', 'files'));
        $this->define_headers(array(
            get_string('report:type', 'local_smartmedia'),
            get_string('report:format', 'local_smartmedia'),
            get_string('report:resolution', 'local_smartmedia'),
            get_string('report:duration', 'local_smartmedia'),
            get_string('report:size', 'local_smartmedia'),
            get_string('report:transcodecost', 'local_smartmedia'),
            get_string('report:status', 'local_smartmedia'),
            get_string('report:files', 'local_smartmedia')
        ));
        $this->column_class('duration', 'mdl-right');
        $this->column_class('filesize', 'mdl-right');
        $this->column_class('cost', 'mdl-right');
        // Setup pagination.
        $this->currpage = $page;
        $this->pagesize = $perpage;
        $this->sortable(true);
        $this->set_sql(self::FIELDS, '{local_smartmedia_report_over}', self::DEFAULT_WHERE);

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
    public function col_type($row) {
        return $this->format_text($row->type);
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
        return $this->format_text($row->format);
    }

    /**
     * Get content for width column.
     * We use `width` for sorting purposes, requires `width` and `height` fields.
     *
     * @param \stdClass $row
     *
     * @return string html used to display the column field.
     */
    public function col_resolution($row) {
        return $this->format_text($row->resolution);
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
        $rawduration = $row->duration;

        $hours = floor($rawduration / 3600);
        $minutes = str_pad(floor(($rawduration / 60) % 60), 2, '0', STR_PAD_LEFT);
        $seconds = str_pad($rawduration % 60, 2, '0', STR_PAD_LEFT);

        return $this->format_text("$hours:$minutes:$seconds");
    }

    /**
     * Get content for size column.
     * Size displayed in Megabytes (Mb).
     *
     * @param \stdClass $row
     *
     * @return string html used to display the column field.
     */
    public function col_filesize($row) {
        $bytes = $row->filesize;
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);


        return $this->format_text(round($bytes, 2) . ' ' . $units[$pow]);
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
        return $this->format_text('$' . number_format($row->cost, 2));
    }

    /**
     * Get content for size column.
     * Size displayed in Megabytes (Mb).
     *
     * @param \stdClass $row
     *
     * @return string html used to display the column field.
     */
    public function col_status($row) {
        return $this->format_text($row->status);
    }

    /**
     * Get content for size column.
     * Size displayed in Megabytes (Mb).
     *
     * @param \stdClass $row
     *
     * @return string html used to display the column field.
     */
    public function col_files($row) {
        return $this->format_text($row->files);
    }
}

