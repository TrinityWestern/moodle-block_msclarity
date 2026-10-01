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
 * External data metadata; no per-user records are stored by this plugin in Moodle.
 *
 * @package block_msclarity
 * @copyright 2026 Man Lung Ken Yeung <manlung.yeung@twu.ca>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_msclarity\privacy;

use core_privacy\local\metadata\collection;

defined('MOODLE_INTERNAL') || die();

/** Disclose the browser data transmitted to Microsoft Clarity. */
class provider implements \core_privacy\local\metadata\provider {
    /**
     * @param collection $collection Metadata collection.
     * @return collection External data declarations.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_external_location_link('clarity', [
            'userid' => 'privacy:metadata:clarity:userid',
            'username' => 'privacy:metadata:clarity:username',
            'email' => 'privacy:metadata:clarity:email',
            'courseid' => 'privacy:metadata:clarity:courseid',
            'coursemoduleid' => 'privacy:metadata:clarity:coursemoduleid',
            'moduletype' => 'privacy:metadata:clarity:moduletype',
            'categoryid' => 'privacy:metadata:clarity:categoryid',
            'contextid' => 'privacy:metadata:clarity:contextid',
            'pagetype' => 'privacy:metadata:clarity:pagetype',
            'browserdata' => 'privacy:metadata:clarity:browserdata',
        ], 'privacy:metadata:clarity');
        return $collection;
    }
}
