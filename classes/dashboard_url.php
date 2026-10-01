<?php
// This file is part of Moodle - http://moodle.org/
// Moodle is free software: you can redistribute it and/or modify it under the terms of
// the GNU General Public License as published by the Free Software Foundation,
// either version 3 of the License, or (at your option) any later version.

/**
 * Direct Clarity links.
 *
 * @package block_msclarity
 * @copyright 2026 Trinity Western University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_msclarity;

defined('MOODLE_INTERNAL') || die();

/** Construct consistently encoded links using the existing Variables filter convention. */
final class dashboard_url {
    /**
     * @param string|null $tag Custom tag to filter, or null for the site dashboard.
     * @param int|null $id Tag value.
     * @return \moodle_url Direct external dashboard URL.
     */
    public static function make(?string $tag = null, ?int $id = null): \moodle_url {
        $params = ['date' => 'Last 3 days'];
        if ($tag !== null && $id !== null) {
            $params['Variables'] = $tag . ':' . $id;
        }
        return new \moodle_url('https://clarity.microsoft.com/projects/view/' . configuration::project_id() . '/dashboard', $params);
    }
}
