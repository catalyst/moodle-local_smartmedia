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
class location_rekog_pricing {

    /**
     * @var float the cost per minute for label detection.
     */
    private $labeldetectionpricing;

    /**
     * @var float the cost per minute for content moderation.
     */
    private $contentmoderationpricing;

    /**
     * @var float the cost per minute for face detection.
     */
    private $facedetectionpricing;

    /**
     * @var float the cost per minute for person tracking.
     */
    private $persontrackingpricing;

    /**
     * Check if this has label detection pricing.
     *
     * @return bool
     */
    public function has_valid_label_detection_pricing() {
        $result = is_numeric($this->labeldetectionpricing);
        return $result;
    }

    /**
     * Check if this has content moderation pricing.
     *
     * @return bool
     */
    public function has_valid_content_moderation_pricing() {
        $result = is_numeric($this->contentmoderationpricing);
        return $result;
    }

    /**
     * Check if this has face detection pricing.
     *
     * @return bool
     */
    public function has_valid_face_detection_pricing() {
        $result = is_numeric($this->facedetectionpricing);
        return $result;
    }

    /**
     * Check if this has person tracking pricing.
     *
     * @return bool
     */
    public function has_valid_person_tracking_pricing() {
        $result = is_numeric($this->persontrackingpricing);
        return $result;
    }

    /**
     * Calculate the cost for label detection.
     *
     * @param float $duration in minutes of the media.
     *
     * @return float|null $result the total cost in US dollars, null if cost couldn't be calculated
     */
    public function calculate_label_detection_cost(float $duration) {
        $result = null;
        if ($this->has_valid_label_detection_pricing()) {
            $result = $duration * $this->labeldetectionpricing;
        }
        return $result;
    }

    /**
     * Calculate the cost for content moderation.
     *
     * @param float $duration in minutes of the media.
     *
     * @return float|null $result the total cost in US dollars, null if cost couldn't be calculated
     */
    public function calculate_content_moderation_cost(float $duration) {
        $result = null;
        if ($this->has_valid_content_moderation_pricing()) {
            $result = $duration * $this->contentmoderationpricing;
        }
        return $result;
    }

    /**
     * Calculate the cost for face detection.
     *
     * @param float $duration in minutes of the media.
     *
     * @return float|null $result the total cost in US dollars, null if cost couldn't be calculated
     */
    public function calculate_face_detection_cost(float $duration) {
        $result = null;
        if ($this->has_valid_face_detection_pricing()) {
            $result = $duration * $this->facedetectionpricing;
        }
        return $result;
    }

    /**
     * Calculate the cost for person tracking.
     *
     * @param float $duration in minutes of the media.
     *
     * @return float|null $result the total cost in US dollars, null if cost couldn't be calculated
     */
    public function calculate_person_tracking_cost(float $duration) {
        $result = null;
        if ($this->has_valid_person_tracking_pricing()) {
            $result = $duration * $this->persontrackingpricing;
        }
        return $result;
    }

    /**
     * Get the pricing per minute for label detection.
     *
     * @return float
     */
    public function get_label_detection_pricing(): float {
        return $this->labeldetectionpricing;
    }

    /**
     * Set label detection pricing per minute.
     *
     * @param float $labeldetectionpricing the cost per minute for label detection.
     */
    public function set_label_detection_pricing(float $labeldetectionpricing): void {
        $this->labeldetectionpricing = $labeldetectionpricing;
    }

    /**
     * Get the pricing per minute for content moderation.
     *
     * @return float
     */
    public function get_content_moderation_pricing(): float {
        return $this->contentmoderationpricing;
    }

    /**
     * Set content moderation pricing per minute.
     *
     * @param float $contentmoderationpricing the cost per minute for content moderation.
     */
    public function set_content_moderation_pricing(float $contentmoderationpricing): void {
        $this->contentmoderationpricing = $contentmoderationpricing;
    }

    /**
     * Get the pricing per minute for face detection.
     *
     * @return float
     */
    public function get_face_detection_pricing(): float {
        return $this->facedetectionpricing;
    }

    /**
     * Set face detection pricing per minute.
     *
     * @param float $facedetectionpricing the cost per minute for face detection.
     */
    public function set_face_detection_pricing(float $facedetectionpricing): void {
        $this->facedetectionpricing = $facedetectionpricing;
    }

    /**
     * Get the pricing per minute for person tracking.
     *
     * @return float
     */
    public function get_person_tracking_pricing(): float {
        return $this->persontrackingpricing;
    }

    /**
     * Set person tracking pricing per minute.
     *
     * @param float $persontrackingpricing the cost per minute for person tracking.
     */
    public function set_person_tracking_pricing(float $persontrackingpricing): void {
        $this->persontrackingpricing = $persontrackingpricing;
    }
}
