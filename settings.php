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
 * Exactly two site settings; no block-instance configuration.
 *
 * @package block_msclarity
 * @copyright 2026 Man Lung Ken Yeung <manlung.yeung@twu.ca>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

if (!is_siteadmin()) {
    $settings = null;
} else if ($ADMIN->fulltree) {
    $setting = new \block_msclarity\admin\project_id('block_msclarity/projectid',
        get_string('projectid', 'block_msclarity'), get_string('projectid_desc', 'block_msclarity'), '', PARAM_RAW_TRIMMED);
    $setting->set_updatedcallback([\block_msclarity\insights::class, 'invalidate']);
    $settings->add($setting);

    $setting = new \block_msclarity\admin\api_token('block_msclarity/apitoken',
        get_string('apitoken', 'block_msclarity'), get_string('apitoken_desc', 'block_msclarity'), '');
    $setting->set_updatedcallback([\block_msclarity\insights::class, 'invalidate']);
    $settings->add($setting);
}
