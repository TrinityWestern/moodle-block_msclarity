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
 * Runtime plugin state outside config_plugins.
 *
 * @package block_msclarity
 * @copyright 2026 Man Lung Ken Yeung <manlung.yeung@twu.ca>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_msclarity;

/**
 * Persist request ledger and metrics snapshot rows under the refresh lock.
 */
final class state_store {
    /**
     * Get.
     *
     * @param string $name State key (`requestbudget` or `reportstate`).
     * @return string|null Stored JSON, or null when absent.
     */
    public static function get(string $name): ?string {
        global $DB;
        $value = $DB->get_field('block_msclarity_state', 'value', ['name' => $name]);
        return $value === false ? null : (string)$value;
    }

    /**
     * Set.
     *
     * @param string $name State key.
     * @param string $value JSON payload.
     */
    public static function set(string $name, string $value): void {
        global $DB;
        $now = time();
        if ($existing = $DB->get_record('block_msclarity_state', ['name' => $name])) {
            $existing->value = $value;
            $existing->timemodified = $now;
            $DB->update_record('block_msclarity_state', $existing);
            return;
        }
        $DB->insert_record('block_msclarity_state', (object)[
            'name' => $name,
            'value' => $value,
            'timemodified' => $now,
        ]);
    }

    /**
     * Delete.
     *
     * @param string $name State key to remove.
     */
    public static function delete(string $name): void {
        global $DB;
        $DB->delete_records('block_msclarity_state', ['name' => $name]);
    }
}
