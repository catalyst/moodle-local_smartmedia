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
 * upgrade for smartmedia plugin.
 *
 * @package     local_smartmedia
 * @category    string
 * @copyright   2019 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the plugin.
 *
 * @param int $oldversion
 * @return bool always true
 */
function xmldb_local_smartmedia_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2020011503) {

        // Changing type of field filesize on table local_smartmedia_report_over to int.
        $table = new xmldb_table('local_smartmedia_report_over');
        $field = new xmldb_field('filesize', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null, 'duration');

        // Launch change of type for field filesize.
        $dbman->change_field_type($table, $field);

        // Smartmedia savepoint reached.
        upgrade_plugin_savepoint(true, 2020011503, 'local', 'smartmedia');
    }

    if ($oldversion < 2020011504) {

        // Define field timecreated to be added to local_smartmedia_report_over.
        $table = new xmldb_table('local_smartmedia_report_over');
        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0, 'files');

        // Conditionally launch add field timecreated.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('timecompleted', XMLDB_TYPE_INTEGER, '10', null, null, null, 0, 'timecreated');

        // Conditionally launch add field timecompleted.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Smartmedia savepoint reached.
        upgrade_plugin_savepoint(true, 2020011504, 'local', 'smartmedia');
    }

    if ($oldversion < 2020011700) {

        // Define field timecreated to be added to local_smartmedia_data.
        $table = new xmldb_table('local_smartmedia_data');
        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'metadata');

        // Conditionally launch add field timecreated.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add the missing timestamps for files that already have metadata records.
        $sql = 'UPDATE {local_smartmedia_data} lsd
                   SET timecreated = f.timecreated
                  FROM {files} f
                 WHERE f.pathnamehash = lsd.pathnamehash';
        $DB->execute($sql);

        // Smartmedia savepoint reached.
        upgrade_plugin_savepoint(true, 2020011700, 'local', 'smartmedia');
    }

    if ($oldversion < 2020022400) {
        // Upgrade convertfrom to a duration.
        require_once('upgradelib.php');
        update_convertfrom_to_duration();
        upgrade_plugin_savepoint(true, 2020022400, 'local', 'smartmedia');
    }
    return true;
}
