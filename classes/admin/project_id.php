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
 * Project ID validation.
 *
 * @package block_msclarity
 * @copyright 2026 Man Lung Ken Yeung <manlung.yeung@twu.ca>
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
