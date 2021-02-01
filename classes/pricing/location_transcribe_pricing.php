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
class location_transcribe_pricing {

    /**
     * @var float the cost per minute for transcription.
     */
    private $transcribepricing;

    /**
     * Check if this has transcribe pricing.
     *
     * @return bool
     */
    public function has_valid_transcribe_pricing() {
        $result = is_numeric($this->transcribepricing);
        return $result;
    }

    /**
     * Calculate the cost for transcription.
     *
     * @param float $duration in minutes of the media.
     *
     * @return float|null $result the total cost in US dollars, null if cost couldn't be calculated
     */
    public function calculate_transcribe_cost(float $duration) {
        $result = null;
        if ($this->has_valid_transcribe_pricing()) {
            $result = $duration * $this->transcribepricing;
        }
        return $result;
    }

    /**
     * Get the pricing per minute for transcription.
     *
     * @return float
     */
    public function get_transcribe_pricing(): float {
        return $this->transcribepricing;
    }

    /**
     * Set transcribe pricing per minute.
     *
     * @param float $labeldetectionpricing the cost per minute for transcription.
     */
    public function set_transcribe_pricing(float $transcribepricing): void {
        $this->transcribepricing = $transcribepricing;
    }
}
