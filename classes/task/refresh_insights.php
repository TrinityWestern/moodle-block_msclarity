<?php
// This file is part of Moodle - http://moodle.org/
// Moodle is free software: you can redistribute it and/or modify it under the terms of
// the GNU General Public License as published by the Free Software Foundation,
// either version 3 of the License, or (at your option) any later version.

/**
 * Scheduled reporting refresh.
 *
 * @package block_msclarity
 * @copyright 2026 Trinity Western University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_msclarity\task;

defined('MOODLE_INTERNAL') || die();

/** Fetch the shared overview using cron or an explicit site-admin refresh. */
class refresh_insights extends \core\task\scheduled_task {
    /** @return string Translated task name. */
    public function get_name(): string {
        return get_string('refreshinsights', 'block_msclarity');
    }

    /** Fetch and log a fixed result code without response bodies or credentials. */
    public function execute(): void {
        mtrace('Microsoft Clarity overview: ' . \block_msclarity\insights::refresh());
    }

    /**
     * Run the same refresh immediately with the manual cooldown, retaining the quota and lock.
     * @return string Safe result code for the POST handler.
     */
    public function run_now(): string {
        return \block_msclarity\insights::refresh(null, null, true);
    }
}
