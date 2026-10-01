<?php
// This file is part of Moodle - http://moodle.org/
// Moodle is free software: you can redistribute it and/or modify it under the terms of
// the GNU General Public License as published by the Free Software Foundation,
// either version 3 of the License, or (at your option) any later version.

/**
 * Project ID validation.
 *
 * @package block_msclarity
 * @copyright 2026 Trinity Western University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_msclarity\admin;

defined('MOODLE_INTERNAL') || die();

/** Reject malformed project IDs rather than silently altering them. */
class project_id extends \admin_setting_configtext {
    /**
     * @param mixed $data Submitted setting value.
     * @return true|string Validation result.
     */
    public function validate($data) {
        return $data === '' || preg_match('/\A[a-zA-Z0-9]{1,64}\z/', $data)
            ? true : get_string('invalidprojectid', 'block_msclarity');
    }
}
