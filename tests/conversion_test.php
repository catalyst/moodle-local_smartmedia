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
 * Unit test for the filter_smartmedia
 *
 * @package    filter
 * @subpackage smartmedia
 * @copyright  2019 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


class local_smartmedia_conversion_testcase extends advanced_testcase {

    /**
     *
     */
    function test_get_smart_media() {
        $this->resetAfterTest(true);
        $conversion = new \local_smartmedia\conversion();

        $href = new \moodle_url('http://moodle.local/pluginfile.php/1461/mod_label/intro/SampleVideo1mb.mp4');


        $smartmedia = $conversion->get_smart_media($href, false);

        //$this->assertEquals(1, $proxy);
    }

    /**
     * Test argument extraction from various plugin types.
     */
    function test_get_arguments() {
        $this->resetAfterTest(true);
        $conversion = new \local_smartmedia\conversion();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_smartmedia\conversion', 'get_arguments');
        $method->setAccessible(true); // Allow accessing of private method.

        $href = new \moodle_url('http://moodle.local/pluginfile.php/1461/mod_label/intro/SampleVideo1mb.mp4');
        $result = $method->invoke($conversion, $href); // Get result of invoked method.

        $this->assertEquals(1461, $result->contextid);
        $this->assertEquals('mod_label', $result->component);
        $this->assertEquals('intro', $result->filearea);
        $this->assertEquals(0, $result->itemid);
        $this->assertEquals('SampleVideo1mb.mp4', $result->filename);


        $href = new \moodle_url('http://moodle.local/pluginfile.php/31/mod_forum/attachment/2/SampleVideo1mb.mp4?forcedownload=1');
        $result = $method->invoke($conversion, $href); // Get result of invoked method.

        $this->assertEquals(31, $result->contextid);
        $this->assertEquals('mod_forum', $result->component);
        $this->assertEquals('attachment', $result->filearea);
        $this->assertEquals(2, $result->itemid);
        $this->assertEquals('SampleVideo1mb.mp4', $result->filename);
    }

    /**
     * Test method that gets conversion status when there is no existing
     * conversion record in the database.
     */
    function test_get_conversion_status_no_record() {
        $this->resetAfterTest(true);
        $conversion = new \local_smartmedia\conversion();

        $itemhash = '7eaaf63136bfcfe8be4978e72bdbad68453dbd72';

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_smartmedia\conversion', 'get_conversion_status');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($conversion, $itemhash);

        $this->assertEquals(404, $result);

    }

    /**
     * Test that initial conversion records are successfully created.
     */
    function test_create_conversion() {
        $this->resetAfterTest(true);
        global $DB;

        $conversion = new \local_smartmedia\conversion();
        $hrefarguments = new \stdClass();
        $hrefarguments->contextid = 31;
        $hrefarguments->component = 'mod_forum';
        $hrefarguments->filearea = 'attachement';
        $hrefarguments->itemid = 2;
        $hrefarguments->filename = 'SampleVideo1mb.mp4';
        $hrefarguments->itemhash = '7eaaf63136bfcfe8be4978e72bdbad68453dbd72';

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_smartmedia\conversion', 'create_conversion');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($conversion, $hrefarguments);
        $result = $method->invoke($conversion, $hrefarguments);  // Invoke twice to check error handling

        $result = $DB->record_exists('local_smartmedia_conv', array('itemhash' => '7eaaf63136bfcfe8be4978e72bdbad68453dbd72'));

        $this->assertTrue($result);

    }

}
