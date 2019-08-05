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
 * Filter renderable for selecting the pricing location to apply to report.
 *
 * @package     local_smartmedia
 * @author      Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright   2019 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_smartmedia\output;

use local_smartmedia\aws_api;
use local_smartmedia\aws_ets_pricing_client;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/aws/sdk/aws-autoloader.php');

/**
 * Filter renderable for selecting the pricing location to apply to report.
 *
 * @package     local_smartmedia
 * @author      Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright   2019 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pricing_location_filter extends \moodleform {

    /**
     * Form definition.
     */
    protected function definition() {

        $mform = $this->_form;

        // Check that we received a pricing client in custom data to get location data from.
        if (!empty($this->_customdata['pricingclient'])) {
            $pricingclient = $this->_customdata['pricingclient'];
            $locations = $pricingclient->get_attribute_values('location');
        } else {
            $errormsg = get_string('report:location:error:clientnotset', 'local_smartmedia');
            throw new \moodle_exception($errormsg);
        }

        // Encode the locations to remove whitespace and special chars for query string.
        $encodedlocations = [];
        foreach ($locations as $location) {
            $encodedlocations[urlencode($location)] = $location;
        }

        $mform->addElement('header', 'pricinglocationheader', get_string('report:location:header', 'local_smartmedia'));
        $mform->addElement('select', 'pricinglocation', get_string('report:location:select', 'local_smartmedia'),
            $encodedlocations);

        // If a region is already set, use that as the default selection.
        if (!empty($this->_customdata['pricinglocation'])) {
            $default = $this->_customdata['pricinglocation'];
        } else {
            $default = reset($encodedlocations);
        }
        $mform->setDefault('pricinglocation', $default);

        $this->add_action_buttons(false, get_string('report:location:submit', 'local_smartmedia'));
    }
}
