<?php
// This file is part of Moodle - http://moodle.org/
// Moodle is free software: you can redistribute it and/or modify it under the terms of
// the GNU General Public License as published by the Free Software Foundation,
// either version 3 of the License, or (at your option) any later version.

/**
 * English language strings.
 *
 * @package block_msclarity
 * @copyright 2026 Trinity Western University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Microsoft Clarity';
$string['msclarity:view'] = 'View Microsoft Clarity overview and contextual links (site admins only)';
$string['msclarity:addinstance'] = 'Add a Microsoft Clarity block (site admins only)';
$string['msclarity:myaddinstance'] = 'Add Microsoft Clarity to the dashboard (site admins only)';
$string['projectid'] = 'Project ID';
$string['projectid_desc'] = 'The project ID from Clarity Settings → Overview. A project ID enables site-wide browser tracking, including guest and logged-out pages. Leave empty to disable tracking. Remove the previous Clarity script from Additional HTML before enabling this plugin.';
$string['apitoken'] = 'API token';
$string['apitoken_desc'] = 'Generate a token in the same Clarity project under Settings → Data Export. Used only on the server for the overview. The last 72 hours are refreshed through Moodle cron every six hours. Leave empty to disable reporting; browser tracking still works.';
$string['invalidprojectid'] = 'Enter a project ID containing 1–64 letters or digits, or leave empty.';
$string['invalidtoken'] = 'The API token must be a single line.';
$string['viewcourse'] = 'View course in Clarity';
$string['viewuser'] = 'View user in Clarity';
$string['opendashboard'] = 'Open Clarity dashboard';
$string['refreshinsights'] = 'Refresh Microsoft Clarity overview';
$string['refreshnow'] = 'Refresh now';
$string['requestusage'] = 'Plugin API attempts in the last 24 hours: {$a->used}/{$a->limit}.';
$string['projectquotaunknown'] = 'Clarity project quota remaining: unknown (not provided by the API).';
$string['nextrefresh'] = 'Next manual refresh available: {$a}';
$string['quotashared'] = 'Other tools using this Clarity project share its API quota.';
$string['refresh_success'] = 'The Microsoft Clarity overview has been refreshed.';
$string['refresh_cooldown'] = 'A refresh was attempted recently. Wait one minute between manual refreshes.';
$string['refresh_throttled'] = 'A recent refresh already covers this scheduled interval.';
$string['refresh_quota'] = 'The plugin has used its 10-request allowance. Refreshes resume as requests leave the 24-hour window.';
$string['refresh_ratelimit'] = 'Clarity reported that the project’s API limit was reached. Refreshes are paused for 24 hours after that response.';
$string['refresh_locked'] = 'Another refresh is already running. Please wait for it to finish.';
$string['refresh_unconfigured'] = 'Enable the plugin and configure its project ID and API token before refreshing.';
$string['refresh_changed'] = 'The settings changed during the refresh. Its results were discarded; refresh again when the cooldown ends.';
$string['reportingwindow'] = 'Site overview · Last 72 hours at the time of refresh';
$string['lastupdated'] = 'Last updated:';
$string['notconfigured'] = 'Configure the project ID and API token in Site administration → Plugins → Blocks → Microsoft Clarity.';
$string['awaitingrefresh'] = 'Waiting for the first refresh. Use Refresh now or wait for Moodle cron.';
$string['staledata'] = 'These are previously collected statistics. A fresh overview is pending.';
$string['unavailable'] = 'Unavailable';
$string['metric_sessions'] = 'Sessions';
$string['metric_users'] = 'Unique users';
$string['metric_pagespersession'] = 'Pages per session';
$string['metric_engagement'] = 'Active engagement time (seconds, as reported by Clarity)';
$string['metric_rageclicks'] = 'Rage clicks';
$string['metric_deadclicks'] = 'Dead clicks';
$string['sessionspercentage'] = '{$a}% of sessions';
$string['error_authentication'] = 'Clarity rejected the API token. Update the token in the plugin settings.';
$string['error_authorization'] = 'The API token is not authorized to export this project’s statistics.';
$string['error_ratelimit'] = 'Clarity’s daily API request limit was reached. Reporting will retry after 24 hours.';
$string['error_network'] = 'Clarity could not be reached. Reporting will retry at the next scheduled refresh.';
$string['error_service'] = 'Clarity’s reporting service is unavailable. Reporting will retry at the next scheduled refresh.';
$string['error_request'] = 'Clarity rejected the reporting request. Check the plugin version and API configuration.';
$string['error_response'] = 'Clarity returned an unexpected response. Reporting will retry at the next scheduled refresh.';
$string['privacy:metadata:clarity'] = 'Microsoft Clarity receives browser analytics and session recordings. This plugin sends user identifiers and page-context tags, and stores only the latest site-wide aggregate reporting snapshot in Moodle.';
$string['privacy:metadata:clarity:userid'] = 'The current authenticated visitor’s numeric Moodle user ID, supplied as a readable MoodleUserID custom tag.';
$string['privacy:metadata:clarity:username'] = 'The current authenticated visitor’s Moodle username, supplied as the hashed Clarity custom user identifier and a readable Username custom tag.';
$string['privacy:metadata:clarity:email'] = 'The current authenticated visitor’s email address, supplied as a readable Email custom tag.';
$string['privacy:metadata:clarity:courseid'] = 'The course ID associated with the page being visited.';
$string['privacy:metadata:clarity:coursemoduleid'] = 'The course module ID of the activity being visited.';
$string['privacy:metadata:clarity:moduletype'] = 'The activity type associated with the page being visited.';
$string['privacy:metadata:clarity:categoryid'] = 'The category ID of the course being visited.';
$string['privacy:metadata:clarity:contextid'] = 'The Moodle context ID associated with the page being visited.';
$string['privacy:metadata:clarity:pagetype'] = 'The Moodle page type being visited.';
$string['privacy:metadata:clarity:browserdata'] = 'Data collected by the Clarity browser script, including visited URLs, browser and device information, interactions, cookies, and session recordings according to the Clarity project’s masking and consent policies.';
