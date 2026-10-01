<?php
// This file is part of Moodle - http://moodle.org/
// Moodle is free software: you can redistribute it and/or modify it under the terms of
// the GNU General Public License as published by the Free Software Foundation,
// either version 3 of the License, or (at your option) any later version.

/**
 * Site-wide output hook.
 *
 * @package block_msclarity
 * @copyright 2026 Trinity Western University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_msclarity;

defined('MOODLE_INTERNAL') || die();

/** Register tracking for ordinary browser HTML output. */
final class hook_callbacks {
    /**
     * @param \core\hook\output\before_standard_head_html_generation $hook Output hook.
     */
    public static function before_standard_head_html_generation(
        \core\hook\output\before_standard_head_html_generation $hook
    ): void {
        global $USER;
        if ((defined('CLI_SCRIPT') && CLI_SCRIPT) || (defined('AJAX_SCRIPT') && AJAX_SCRIPT) ||
                !configuration::project_id() || !configuration::enabled()) {
            return;
        }
        $page = $hook->renderer->get_page();
        $payload = tracking::payload($page, $USER, isloggedin() && !isguestuser());
        $url = new \moodle_url('/blocks/msclarity/js/tracking.js', ['v' => get_config('block_msclarity', 'version')]);
        $hook->add_html(\html_writer::tag('script', '', [
            'src' => $url->out(false),
            'defer' => 'defer',
            'data-msclarity' => json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
        ]));
    }
}
