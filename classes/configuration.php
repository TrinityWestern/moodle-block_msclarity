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
 * Shared configuration and access checks.
 *
 * @package block_msclarity
 * @copyright 2026 Man Lung Ken Yeung <manlung.yeung@twu.ca>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_msclarity;

defined('MOODLE_INTERNAL') || die();

/** Shared configuration and access checks. */
final class configuration {
    /** @return string Valid project ID, or an empty string when not configured. */
    public static function project_id(): string {
        $value = trim((string)get_config('block_msclarity', 'projectid'));
        return preg_match('/\A[a-zA-Z0-9]{1,64}\z/', $value) ? $value : '';
    }

    /** @return string API token; never pass this to browser output. */
    public static function api_token(): string {
        $value = trim((string)get_config('block_msclarity', 'apitoken'));
        return preg_match('/[\r\n]/', $value) ? '' : $value;
    }

    /** @return bool Whether the block plugin is enabled in Manage blocks. */
    public static function enabled(): bool {
        $enabled = \core\plugininfo\block::get_enabled_plugins();
        return isset($enabled['msclarity']);
    }

    /**
     * An explicitly granted capability cannot bypass site-admin-only access.
     * @return bool Whether the current visitor may see Clarity reports and links.
     */
    public static function can_view(): bool {
        return is_siteadmin() && has_capability('block/msclarity:view', \context_system::instance());
    }

    /** @return string A credential fingerprint for detecting changes made outside the settings UI. */
    public static function fingerprint(): string {
        return hash('sha256', self::project_id() . "\0" . self::api_token());
    }
}
