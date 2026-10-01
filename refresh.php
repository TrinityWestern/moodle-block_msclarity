<?php
// This file is part of Moodle - http://moodle.org/
// Moodle is free software: you can redistribute it and/or modify it under the terms of
// the GNU General Public License as published by the Free Software Foundation,
// either version 3 of the License, or (at your option) any later version.

/**
 * Admin-only manual execution of the overview refresh.
 *
 * @package block_msclarity
 * @copyright 2026 Trinity Western University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');

require_login();
if (!\block_msclarity\configuration::can_view()) {
    throw new required_capability_exception(context_system::instance(), 'block/msclarity:view', 'nopermissions', '');
}
if (!data_submitted()) {
    throw new moodle_exception('invalidrequest', 'error');
}
require_sesskey();

$task = new \block_msclarity\task\refresh_insights();
$result = $task->run_now();
$messages = ['success', 'cooldown', 'throttled', 'quota', 'ratelimit', 'locked', 'unconfigured', 'changed'];
$message = in_array($result, $messages, true) ? get_string('refresh_' . $result, 'block_msclarity') :
    get_string('error_' . $result, 'block_msclarity');
$type = $result === 'success' ? \core\output\notification::NOTIFY_SUCCESS : \core\output\notification::NOTIFY_WARNING;
redirect(new moodle_url('/my/index.php'), $message, null, $type);
