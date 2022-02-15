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

/**
 * Upgrade the plugin.
 *
 * @param int $oldversion
 * @return bool always true
 */
function xmldb_local_smartmedia_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2020011502) {
        // Add Smartmedia_report_over table if it doesn't exist.
        // Define table local_smartmedia_report_over to be created.
        $table = new xmldb_table('local_smartmedia_report_over');

        // Adding fields to table local_smartmedia_report_over.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('contenthash', XMLDB_TYPE_CHAR, '40', null, null, null, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('format', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('resolution', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('duration', XMLDB_TYPE_NUMBER, '10, 3', null, XMLDB_NOTNULL, null, null);
        $table->add_field('filesize', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('cost', XMLDB_TYPE_NUMBER, '10, 3', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('status', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('files', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecompleted', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');

        // Adding keys to table local_smartmedia_report_over.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table local_smartmedia_report_over.
        $table->add_index('contenthash', XMLDB_INDEX_UNIQUE, array('contenthash'));

        // Conditionally launch create table for local_smartmedia_report_over.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2020011502, 'local', 'smartmedia');
    }

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

        // Define field pathnamehash to be added to local_smartmedia_data.
        $table = new xmldb_table('local_smartmedia_data');
        $field = new xmldb_field('pathnamehash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null, 'contenthash');

        // Conditionally launch add field pathnamehash.
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

    if ($oldversion < 2020030200) {
        // Local_smartmedia_conv columns.

        // Define field contenthash to be added to local_smartmedia_conv.
        $table = new xmldb_table('local_smartmedia_conv');
        $field = new xmldb_field('contenthash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null, 'pathnamehash');

        // Conditionally launch add field contenthash.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define index status (not unique) to be dropped form local_smartmedia_conv.
        $table = new xmldb_table('local_smartmedia_conv');
        $index = new xmldb_index('status', XMLDB_INDEX_NOTUNIQUE, array('status'));

        // Conditionally launch drop index status.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Changing the default of field status on table local_smartmedia_conv to 202.
        $table = new xmldb_table('local_smartmedia_conv');
        $field = new xmldb_field('status', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '202', 'contenthash');

        // Launch change of default for field status.
        $dbman->change_field_default($table, $field);

        // Define index status (not unique) to be added to local_smartmedia_conv.
        $table = new xmldb_table('local_smartmedia_conv');
        $index = new xmldb_index('status', XMLDB_INDEX_NOTUNIQUE, array('status'));

        // Conditionally launch add index status.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define field transcoder_status to be added to local_smartmedia_conv.
        $table = new xmldb_table('local_smartmedia_conv');
        $field = new xmldb_field('transcoder_status', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '202', 'status');

        // Conditionally launch add field transcoder_status.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field transcribe_status to be added to local_smartmedia_conv.
        $table = new xmldb_table('local_smartmedia_conv');
        $field = new xmldb_field('transcribe_status', XMLDB_TYPE_INTEGER, '3', null,
            XMLDB_NOTNULL, null, '404', 'transcoder_status');

        // Conditionally launch add field transcribe_status.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field rekog_label_status to be added to local_smartmedia_conv.
        $table = new xmldb_table('local_smartmedia_conv');
        $field = new xmldb_field('rekog_label_status', XMLDB_TYPE_INTEGER, '3', null,
            XMLDB_NOTNULL, null, '404', 'transcribe_status');

        // Conditionally launch add field rekog_label_status.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field rekog_moderation_status to be added to local_smartmedia_conv.
        $table = new xmldb_table('local_smartmedia_conv');
        $field = new xmldb_field('rekog_moderation_status', XMLDB_TYPE_INTEGER, '3', null,
            XMLDB_NOTNULL, null, '404', 'rekog_label_status');

        // Conditionally launch add field rekog_moderation_status.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field rekog_face_status to be added to local_smartmedia_conv.
        $table = new xmldb_table('local_smartmedia_conv');
        $field = new xmldb_field('rekog_face_status', XMLDB_TYPE_INTEGER, '3', null,
            XMLDB_NOTNULL, null, '404', 'rekog_moderation_status');

        // Conditionally launch add field rekog_face_status.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field rekog_person_status to be added to local_smartmedia_conv.
        $table = new xmldb_table('local_smartmedia_conv');
        $field = new xmldb_field('rekog_person_status', XMLDB_TYPE_INTEGER, '3', null,
            XMLDB_NOTNULL, null, '404', 'rekog_face_status');

        // Conditionally launch add field rekog_person_status.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field detect_sentiment_status to be added to local_smartmedia_conv.
        $table = new xmldb_table('local_smartmedia_conv');
        $field = new xmldb_field('detect_sentiment_status', XMLDB_TYPE_INTEGER, '3', null,
            XMLDB_NOTNULL, null, '404', 'rekog_person_status');

        // Conditionally launch add field detect_sentiment_status.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field detect_phrases_status to be added to local_smartmedia_conv.
        $table = new xmldb_table('local_smartmedia_conv');
        $field = new xmldb_field('detect_phrases_status', XMLDB_TYPE_INTEGER, '3', null,
            XMLDB_NOTNULL, null, '404', 'detect_sentiment_status');

        // Conditionally launch add field detect_phrases_status.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

         // Define field detect_entities_status to be added to local_smartmedia_conv.
        $table = new xmldb_table('local_smartmedia_conv');
        $field = new xmldb_field('detect_entities_status', XMLDB_TYPE_INTEGER, '3', null,
            XMLDB_NOTNULL, null, '404', 'detect_phrases_status');

        // Conditionally launch add field detect_entities_status.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add Local_smartmedia_presets table if not exists.

        // Define table local_smartmedia_presets to be created.
        $table = new xmldb_table('local_smartmedia_presets');

        // Adding fields to table local_smartmedia_presets.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('convid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('preset', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('container', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_smartmedia_presets.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('convid', XMLDB_KEY_FOREIGN, array('convid'), 'local_smartmedia_conv', array('id'));

        // Adding indexes to table local_smartmedia_presets.
        $table->add_index('preset', XMLDB_INDEX_NOTUNIQUE, array('preset'));

        // Conditionally launch create table for local_smartmedia_presets.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table local_smartmedia_queue_msgs to be created.
        $table = new xmldb_table('local_smartmedia_queue_msgs');

        // Adding fields to table local_smartmedia_queue_msgs.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('objectkey', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
        $table->add_field('process', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('messagehash', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, null);
        $table->add_field('message', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('senttime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_smartmedia_queue_msgs.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table local_smartmedia_queue_msgs.
        $table->add_index('messagehash', XMLDB_INDEX_UNIQUE, array('messagehash'));

        // Conditionally launch create table for local_smartmedia_queue_msgs.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2020030200, 'local', 'smartmedia');
    }

    if ($oldversion < 2021031000) {

        // Define table local_smartmedia_data_fail to be created.
        $table = new xmldb_table('local_smartmedia_data_fail');

        // Adding fields to table local_smartmedia_data_fail.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('contenthash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '15', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('reason', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table local_smartmedia_data_fail.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('contenthashunique', XMLDB_KEY_UNIQUE, ['contenthash']);

        // Conditionally launch create table for local_smartmedia_data_fail.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Smartmedia savepoint reached.
        upgrade_plugin_savepoint(true, 2021031000, 'local', 'smartmedia');
    }

    if ($oldversion < 2021111001) {

        // Define key contenthash (foreign-unique) to be added to local_smartmedia_conv.
        $table = new xmldb_table('local_smartmedia_conv');
        $key = new xmldb_key('contenthash', XMLDB_KEY_FOREIGN_UNIQUE, ['contenthash'], 'local_smartmedia_data', ['contenthash']);

        // Launch add key contenthash.
        if (!$table->getKey($key->getName())) {
            $dbman->add_key($table, $key);
        }

        // Smartmedia savepoint reached.
        upgrade_plugin_savepoint(true, 2021111001, 'local', 'smartmedia');
    }

    return true;
}
