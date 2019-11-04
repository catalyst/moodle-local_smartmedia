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

    if ($oldversion < 2019110300) {

        // Define table local_smartmedia_report_over to be created.
        $table = new xmldb_table('local_smartmedia_report_over');

        // Adding fields to table local_smartmedia_report_over.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('contenthash', XMLDB_TYPE_CHAR, '40', null, null, null, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('format', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('resolution', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('duration', XMLDB_TYPE_NUMBER, '10, 3', null, XMLDB_NOTNULL, null, null);
        $table->add_field('filesize', XMLDB_TYPE_NUMBER, '10, 3', null, XMLDB_NOTNULL, null, null);
        $table->add_field('cost', XMLDB_TYPE_NUMBER, '10, 3', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('status', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('files', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_smartmedia_report_over.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table local_smartmedia_report_over.
        $table->add_index('contenthash', XMLDB_INDEX_UNIQUE, ['contenthash']);

        // Conditionally launch create table for local_smartmedia_report_over.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Smartmedia savepoint reached.
        upgrade_plugin_savepoint(true, 2019110300, 'local', 'smartmedia');
    }
    return true;
}