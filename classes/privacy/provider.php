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
 * External data metadata; no per-user records are stored by this plugin in Moodle.
 *
 * @package block_msclarity
 * @copyright 2026 Man Lung Ken Yeung <manlung.yeung@twu.ca>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_msclarity\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;

/**
 * Disclose browser data sent to Microsoft Clarity; store no personal data in Moodle.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Get metadata.
     *
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

    /**
     * Get contexts for userid.
     *
     * @param int $userid The user to search.
     * @return contextlist Empty; this plugin stores no per-user Moodle data.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        return new contextlist();
    }

    /**
     * Get users in context.
     *
     * @param userlist $userlist Userlist for the context.
     */
    public static function get_users_in_context(userlist $userlist): void {
    }

    /**
     * Export user data.
     *
     * @param approved_contextlist $contextlist Approved contexts to export.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
    }

    /**
     * Delete data for all users in context.
     *
     * @param \context $context Context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
    }

    /**
     * Delete data for user.
     *
     * @param approved_contextlist $contextlist Approved contexts for the user.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
    }

    /**
     * Delete data for users.
     *
     * @param approved_userlist $userlist Approved users in a context.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
    }
}
