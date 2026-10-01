<?php
// This file is part of Moodle - http://moodle.org/
// Moodle is free software: you can redistribute it and/or modify it under the terms of
// the GNU General Public License as published by the Free Software Foundation,
// either version 3 of the License, or (at your option) any later version.

/**
 * Preserve request pacing when upgrading an existing plugin installation.
 *
 * @package block_msclarity
 * @copyright 2026 Trinity Western University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * @param int $oldversion Previously installed version.
 * @return bool Upgrade result.
 */
function xmldb_block_msclarity_upgrade(int $oldversion): bool {
    if ($oldversion < 2026100101) {
        \block_msclarity\request_budget::migrate_legacy();
        upgrade_block_savepoint(true, 2026100101, 'msclarity');
    }
    return true;
}
