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
 * Unit test for local_smartmedia queue process class.
 *
 * @package    local_smartmedia
 * @copyright  2019 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/aws/sdk/aws-autoloader.php');

use Aws\Result;
use Aws\MockHandler;
use Aws\CommandInterface;
use Psr\Http\Message\RequestInterface;
use Aws\S3\Exception\S3Exception;

/**
 * Unit test for local_smartmedia queue process class.
 *
 * @package    local_smartmedia
 * @copyright  2019 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      local_smartmedia
 */
class local_smartmedia_queue_process_testcase extends advanced_testcase {

    /**
     * @var array Fixtures used in this test.
     */
    public $fixture;

    /*
     * Set up method for this test suite.
     */
    public function setUp() {
        global $CFG;

        // Get fixture for tests.
        $this->fixture = require($CFG->dirroot . '/local/smartmedia/tests/fixtures/queue_process_test_fixture.php');
    }

    /**
     * Test getting messages from SQS queue.
     */
    public function test_get_queue_messages() {
        $this->resetAfterTest(true);
        global $CFG;

        // Cook site id to match fixture as it will changes between test sites.
        // We can't change in fixture as it will break MD5 check.
        $CFG->siteidentifier = 'wck1bOkID2Nj6mCG3bsQqUwxPz54eQaxmoodle.local';

        // Set up the AWS mock.
        $mock = new MockHandler();
        $mock->append(new Result($this->fixture['sqsmessages']));
        $mock->append(new Result($this->fixture['sqsmessages']));
        $mock->append(new Result(array()));

        $queueprocess = new \local_smartmedia\queue_process();
        $queueprocess->create_client($mock);

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_smartmedia\queue_process', 'get_queue_messages');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($queueprocess);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('b804a5e3-0086-4da6-be10-966ebf6083ea', $result);
        $this->assertArrayHasKey('ecd44ebd-d5b6-4b6f-bda6-f9374995c3ac', $result);
    }

    /**
     * Test store messages in DB.
     */
    public function test_store_messages() {
        $this->resetAfterTest(true);
        global $DB;

        $messages = $this->fixture['receviedmessages'];
        $queueprocess = new \local_smartmedia\queue_process();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_smartmedia\queue_process', 'store_messages');
        $method->setAccessible(true); // Allow accessing of private method.
        $method->invoke($queueprocess, $messages);

        $result = $DB->get_records('local_smartmedia_queue_msgs');

        $this->assertCount(2, $result);

    }

    /**
     * Test deleting messages from SQS queue.
     */
    public function test_delete_queue_messages() {
        $this->resetAfterTest(true);

        // Set up the AWS mock.
        $mock = new MockHandler();
        $mock->append(new Result(array()));
        $mock->append(new Result(array()));

        $queueprocess = new \local_smartmedia\queue_process();
        $queueprocess->create_client($mock);

        $messages = $this->fixture['receviedmessages'];

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_smartmedia\queue_process', 'delete_queue_messages');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($queueprocess, $messages);

        $this->assertCount(2, $result);
    }

}
