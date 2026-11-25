<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_autoteacher_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2025111902) {
        // Define table local_autoteacher_session
        $table = new xmldb_table('local_autoteacher_session');
        
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('subject', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('level', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('goal', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('questions', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null); // JSON of all 3 questions
        $table->add_field('answers', XMLDB_TYPE_TEXT, null, null, null, null, null); // JSON of student answers
        $table->add_field('score', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('userid', XMLDB_INDEX_NOTUNIQUE, ['userid']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2025111902, 'local', 'autoteacher');
    }
    return true;
}
