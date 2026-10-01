<?php
// This file is part of Moodle - http://moodle.org/
// Moodle is free software: you can redistribute it and/or modify it under the terms of
// the GNU General Public License as published by the Free Software Foundation,
// either version 3 of the License, or (at your option) any later version.

/**
 * Navigation callbacks, independent of block instances.
 *
 * @package block_msclarity
 * @copyright 2026 Trinity Western University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Add the whole-course Clarity filter to course navigation.
 * @param navigation_node $parentnode Course settings node.
 * @param stdClass $course Course being viewed.
 * @param context_course $context Course context.
 */
function block_msclarity_extend_navigation_course(navigation_node $parentnode, stdClass $course, context_course $context): void {
    if (!\block_msclarity\configuration::can_view() || !\block_msclarity\configuration::project_id() ||
            !\block_msclarity\configuration::enabled() || (int)$course->id === (int)SITEID) {
        return;
    }
    $label = get_string('viewcourse', 'block_msclarity');
    $link = new action_link(\block_msclarity\dashboard_url::make('MoodleCourseID', (int)$course->id),
        $label, null, ['target' => '_blank', 'rel' => 'noopener noreferrer']);
    $node = $parentnode->add($label, $link,
        navigation_node::TYPE_SETTING, null, 'msclarity', new pix_icon('i/report', ''));
    $node->set_force_into_more_menu(true);
}

/**
 * Add a link for the viewed profile user, not the current admin.
 * @param navigation_node $parentnode User navigation node.
 * @param stdClass $user Profile user.
 * @param context_user $context User context.
 * @param stdClass $course Course being viewed.
 * @param context $coursecontext Course context, or system context on a site profile.
 */
function block_msclarity_extend_navigation_user(
    navigation_node $parentnode,
    stdClass $user,
    context_user $context,
    stdClass $course,
    context $coursecontext
): void {
    if (!\block_msclarity\configuration::can_view() || !\block_msclarity\configuration::project_id() ||
            !\block_msclarity\configuration::enabled()) {
        return;
    }
    $label = get_string('viewuser', 'block_msclarity');
    $link = new action_link(\block_msclarity\dashboard_url::make('MoodleUserID', (int)$user->id),
        $label, null, ['target' => '_blank', 'rel' => 'noopener noreferrer']);
    $parentnode->add($label, $link,
        navigation_node::TYPE_SETTING, null, 'msclarity', new pix_icon('i/report', ''));
}

/**
 * Make the link visible in the modern user profile's Reports section.
 * @param \core_user\output\myprofile\tree $tree Profile tree.
 * @param stdClass $user Viewed user.
 * @param bool $iscurrentuser Whether this is the current user's profile.
 * @param stdClass|null $course Course context, if any.
 */
function block_msclarity_myprofile_navigation(
    \core_user\output\myprofile\tree $tree,
    stdClass $user,
    bool $iscurrentuser,
    ?stdClass $course
): void {
    if (!\block_msclarity\configuration::can_view() || !\block_msclarity\configuration::project_id() ||
            !\block_msclarity\configuration::enabled()) {
        return;
    }
    // The profile node API has no link-attribute parameter; its heading accepts rendered HTML.
    $link = html_writer::link(\block_msclarity\dashboard_url::make('MoodleUserID', (int)$user->id),
        get_string('viewuser', 'block_msclarity'), ['target' => '_blank', 'rel' => 'noopener noreferrer']);
    $tree->add_node(new \core_user\output\myprofile\node('reports', 'msclarity', $link));
}
