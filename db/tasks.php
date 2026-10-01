<?php
// This file is part of Moodle - http://moodle.org/
// Moodle is free software: you can redistribute it and/or modify it under the terms of
// the GNU General Public License as published by the Free Software Foundation,
// either version 3 of the License, or (at your option) any later version.

/**
 * Refresh the shared last-72-hours snapshot four times daily.
 *
 * @package block_msclarity
 * @copyright 2026 Trinity Western University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => '\block_msclarity\task\refresh_insights',
        'blocking' => 0,
        'minute' => '17',
        'hour' => '*/6',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
];
