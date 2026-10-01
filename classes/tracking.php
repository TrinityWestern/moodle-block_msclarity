<?php
// This file is part of Moodle - http://moodle.org/
// Moodle is free software: you can redistribute it and/or modify it under the terms of
// the GNU General Public License as published by the Free Software Foundation,
// either version 3 of the License, or (at your option) any later version.

/**
 * Browser payload, containing public tracking data only.
 *
 * @package block_msclarity
 * @copyright 2026 Trinity Western University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_msclarity;

defined('MOODLE_INTERNAL') || die();

/** Resolve tracking fields from Moodle instead of a browser context bridge. */
final class tracking {
    /**
     * @param \moodle_page $page Current page.
     * @param \stdClass $user Current visitor, never the viewed profile user.
     * @param bool $identified Whether the visitor is authenticated and not a guest.
     * @return array Browser-safe tracking fields.
     */
    public static function payload(\moodle_page $page, \stdClass $user, bool $identified): array {
        $tags = [];
        $userid = null;
        if ($identified && !empty($user->id)) {
            $tags['MoodleUserID'] = (string)$user->id;
            if (!empty($user->username)) {
                $userid = (string)$user->username;
                $tags['Username'] = \core_text::substr($user->username, 0, 255);
            }
            if (!empty($user->email)) {
                $tags['Email'] = \core_text::substr($user->email, 0, 255);
            }
        }
        $course = $page->course;
        if (!empty($course->id) && (int)$course->id !== (int)SITEID) {
            $tags['MoodleCourseID'] = (string)$course->id;
            if (!empty($course->category)) {
                $tags['MoodleCourseCategoryID'] = (string)$course->category;
            }
        }
        if ($cm = $page->cm) {
            $tags['MoodleCourseModuleID'] = (string)$cm->id;
            $tags['MoodleModuleType'] = (string)$cm->modname;
        }
        $tags['MoodleContextID'] = (string)$page->context->id;
        $tags['MoodlePageType'] = \core_text::substr((string)$page->pagetype, 0, 255);
        return ['projectid' => configuration::project_id(), 'userid' => $userid, 'tags' => $tags];
    }
}
