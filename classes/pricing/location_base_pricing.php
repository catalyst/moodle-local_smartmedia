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
 * Class describing the pricing for an AWS region.
 *
 * @package     local_smartmedia
 * @author      Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright   2019 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_smartmedia\pricing;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/smartmedia/lib.php');

/**
 * Class describing the pricing for an AWS region.
 *
 * @package     local_smartmedia
 * @author      Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright   2019 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class location_base_pricing {

    /**
     * @var string the AWS region code this pricing is for.
     */
    private $region;

    /**
     * location_transcode_pricing constructor.
     *
     * @param string $region the AWS region code this pricing is for.
     */
    public function __construct(string $region) {
        $this->region = $region;
    }

    /**
     * Get the region to which this location_transcode_pricing applies.
     *
     * @return string AWS region code.
     */
    public function get_region() {
        return $this->region;
    }
}
