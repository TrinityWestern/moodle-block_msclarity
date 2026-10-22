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
 * Site-wide output hook.
 *
 * @package block_msclarity
 * @copyright 2026 Man Lung Ken Yeung <manlung.yeung@twu.ca>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_msclarity;

/**
 * Register tracking for ordinary browser HTML output.
 */
final class hook_callbacks {
    /**
     * Before standard head html generation.
     *
     * @param \core\hook\output\before_standard_head_html_generation $hook Output hook.
     */
    public static function before_standard_head_html_generation(
        \core\hook\output\before_standard_head_html_generation $hook
    ): void {
        global $USER;
        if (
            (defined('CLI_SCRIPT') && CLI_SCRIPT) || (defined('AJAX_SCRIPT') && AJAX_SCRIPT) ||
                !configuration::project_id() || !configuration::enabled() || !configuration::tracking_allowed()
        ) {
            return;
        }
        $page = $hook->renderer->get_page();
        $payload = tracking::payload($page, $USER, isloggedin() && !isguestuser());
        $page->requires->js_call_amd('block_msclarity/tracking', 'init', [$payload]);
    }
}
