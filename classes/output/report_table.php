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

use local_smartmedia\aws_api;
use local_smartmedia\aws_elastic_transcoder;
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
    const FIELDS = 'f.filename, ro.contenthash, ro.id, ro.type, ro.cost, ro.status,
        ro.files, ro.timecreated, ro.timecompleted, conv.id as convid';

    /**
     * The tables to select from.
     */
    const FROM = '{local_smartmedia_report_over} ro
        JOIN {local_smartmedia_conv} conv ON ro.contenthash = conv.contenthash
        JOIN {files} f on f.contenthash = conv.contenthash AND f.pathnamehash = conv.pathnamehash';

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
        $this->define_columns(
            array(
                'filename',
                'status',
                'type',
                'cost',
                'files',
                'timecreated',
                'timecompleted'
            ));
        $this->define_headers(array(
            get_string('filename', 'repository'),
            get_string('report:status', 'local_smartmedia'),
            get_string('report:type', 'local_smartmedia'),
            get_string('report:transcodecost', 'local_smartmedia'),
            get_string('report:files', 'local_smartmedia'),
            get_string('report:created', 'local_smartmedia'),
            get_string('report:completed', 'local_smartmedia'),
        ));
        $this->column_class('cost', 'mdl-right');
        $this->column_class('files', 'mdl-right');
        // Setup pagination.
        $this->currpage = $page;
        $this->pagesize = $perpage;
        $this->sortable(true, 'timecreated', SORT_DESC);
        $this->set_sql(self::FIELDS, self::FROM, self::DEFAULT_WHERE);

        // Setup a transcoder to get all preset information and store it.
        $api = new aws_api;
        $transcoderclient = $api->create_elastic_transcoder_client();
        $transcoder = new aws_elastic_transcoder($transcoderclient);
        $this->presets = $transcoder->get_all_presets();
    }

    /**
     * Display filename with link to file details.
     *
     * @param \stdClass $row
     *
     * @return string html used to display the filename field.
     */
    public function col_filename($row) {
        $url = new \moodle_url('/local/smartmedia/report_details.php', ['hash' => $row->contenthash]);
        return \html_writer::link($url, $row->filename);
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
     * Get content for status column.
     * Displays the status of the smartmedia conversion.
     *
     * @param \stdClass $row
     *
     * @return string html used to display the column field.
     */
    public function col_status($row) {
        return $this->format_text($row->status);
    }

    /**
     * Get content for files column.
     * Displays how many Moodle file records relate to the conversion.
     *
     * @param \stdClass $row
     *
     * @return string html used to display the column field.
     */
    public function col_files($row) {
        return $this->format_text($row->files);
    }

    /**
     * Get content for created column.
     * Displays when the conversion was started
     *
     * @param \stdClass $row
     *
     * @return string html used to display the column field.
     */
    public function col_timecreated($row) {
        $date = userdate($row->timecreated, get_string('strftimedate', 'langconfig'));
        return $this->format_text($date);
    }

    /**
     * Get content for completed column.
     * Displays when the conversion finished.
     * @param \stdClass $row
     *
     * @return string html used to display the column field.
     */
    public function col_timecompleted($row) {
        if ($row->timecompleted == 0) {
            $date = '-';
        } else {
            $date = userdate($row->timecompleted, get_string('strftimedate', 'langconfig'));
        }

        return $this->format_text($date);
    }

}
