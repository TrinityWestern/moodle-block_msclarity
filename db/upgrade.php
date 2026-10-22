<?php
// This file is part of Moodle - http://moodle.org/
// Moodle is free software: you can redistribute it and/or modify it under the terms of
// the GNU General Public License as published by the Free Software Foundation,
// either version 3 of the License, or (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
// without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License along with Moodle.
// If not, see <https://www.gnu.org/licenses/>.

/**
 * Preserve request pacing when upgrading an existing plugin installation.
 *
 * @package block_msclarity
 * @copyright 2026 Man Lung Ken Yeung <manlung.yeung@twu.ca>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * @param int $oldversion Previously installed version.
 * @return bool Upgrade result.
 */
function xmldb_block_msclarity_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026102200) {
        $table = new xmldb_table('block_msclarity_state');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, null);
        $table->add_field('value', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('nameuk', XMLDB_KEY_UNIQUE, ['name']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        foreach (['requestbudget', 'reportstate'] as $name) {
            $raw = $DB->get_field('config_plugins', 'value', ['plugin' => 'block_msclarity', 'name' => $name]);
            if ($raw !== false) {
                \block_msclarity\state_store::set($name, (string)$raw);
                unset_config($name, 'block_msclarity');
            }
        }
        // Seeds requestbudget from a legacy reportstate row when only that key existed.
        \block_msclarity\request_budget::migrate_legacy();
        upgrade_block_savepoint(true, 2026102200, 'msclarity');
    }

    return true;
}
