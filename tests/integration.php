<?php
// Licensed under the GNU GPL v3 or later.
/**
 * Real Moodle integration checks. Use ONLY a disposable Moodle installation.
 * Run: php blocks/msclarity/tests/integration.php /path/to/test/moodle/config.php
 * This script creates test users, a course, activity and dashboard block.
 *
 * @package block_msclarity
 * @copyright 2026 Man Lung Ken Yeung <manlung.yeung@twu.ca>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
define('CLI_SCRIPT', true);
if (empty($argv[1])) {
    fwrite(STDERR, "Pass a disposable Moodle installation's config.php.\n");
    exit(1);
}
require($argv[1]);
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once(__DIR__ . '/../lib.php');

/**
 * @param bool $condition Assertion.
 * @param string $message Description.
 */
function check(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo "PASS: $message\n";
}

/** An export mock makes zero external requests. */
class mock_export extends \block_msclarity\export_client {
    /** @var int Calls made. */
    public int $calls = 0;
    /** @var array Next result. */
    public array $result = [];
    /** @var bool Change credentials during the request to test race protection. */
    public bool $change = false;
    /**
     * @param string $token Unused secret.
     * @return array Mock result.
     */
    public function fetch(string $token): array {
        $this->calls++;
        if ($this->change) {
            set_config('apitoken', 'changed-during-refresh', 'block_msclarity');
        }
        return $this->result;
    }
}

$admin = get_admin();
\core\session\manager::set_user($admin);
$system = context_system::instance();
$PAGE->set_url('/my/index.php');
$PAGE->set_context($system);
$PAGE->set_pagelayout('mydashboard');
check(\block_msclarity\configuration::enabled(), 'Plugin is installed and enabled.');
check(!$DB->record_exists_select('role_capabilities', 'capability LIKE ?', ['block/msclarity:%']),
    'No role has a default Clarity capability grant.');
check(\block_msclarity\configuration::can_view(), 'Site admin has access without assigning a role.');

set_config('projectid', 'w3y4plwlqv', 'block_msclarity');
set_config('apitoken', 'test-secret-never-expose', 'block_msclarity');
unset_config('reportstate', 'block_msclarity');
set_config('requestbudget', json_encode(['projects' => []]), 'block_msclarity');
$settingspage = admin_get_root(true, true)->locate('blocksettingmsclarity');
check($settingspage !== false && count((array)$settingspage->settings) === 2, 'Exactly two admin settings.');
$allsettings = array_values((array)$settingspage->settings);
$token = $allsettings[1];
check($token->write_setting("bad\r\nheader") !== '', 'Multiline tokens are rejected.');
$original = $token->get_setting();
check($token->write_setting('test-secret-never-expose-updated') === '', 'Opaque single-line token is accepted.');
$token->post_write_settings($original);
$logs = $DB->get_records('config_log', ['plugin' => 'block_msclarity', 'name' => 'apitoken']);
check(count($logs) > 0 && !str_contains(json_encode($logs), 'test-secret'), 'Setting audit logs mask token values.');
$projectsetting = $allsettings[0];
check($projectsetting->validate('x<script>') !== true && $projectsetting->validate('') === true,
    'Project validation rejects HTML and permits disabling tracking.');

// Upgrade retains a conservative allowance for the four possible legacy scheduled requests.
unset_config('requestbudget', 'block_msclarity');
set_config('reportstate', json_encode(['attemptedat' => time() - 30, 'retryafter' => time() + DAYSECS]), 'block_msclarity');
\block_msclarity\request_budget::migrate_legacy();
$legacy = \block_msclarity\request_budget::check('w3y4plwlqv', true, time());
check($legacy['used'] === 4 && $legacy['reason'] === 'ratelimit', 'Upgrade preserves legacy attempts and upstream backoff.');
set_config('requestbudget', json_encode(['projects' => []]), 'block_msclarity');
unset_config('reportstate', 'block_msclarity');

$suffix = time();
$course = create_course((object)['fullname' => 'Clarity test', 'shortname' => 'msc' . $suffix, 'category' => 1]);
$profileid = user_create_user((object)[
    'username' => 'mscstudent' . $suffix, 'firstname' => 'Clarity', 'lastname' => 'Student',
    'email' => 'student' . $suffix . '@example.invalid', 'auth' => 'manual', 'password' => 'TestOnlyMsclarity123!',
    'confirmed' => 1, 'mnethostid' => $CFG->mnet_localhost_id,
]);
$profile = $DB->get_record('user', ['id' => $profileid], '*', MUST_EXIST);
$page = new moodle_page();
$page->set_url('/course/view.php', ['id' => $course->id]);
$page->set_course($course);
$page->set_context(context_course::instance($course->id));
$payload = \block_msclarity\tracking::payload($page, $admin, true);
check($payload['userid'] === $admin->username && $payload['tags']['Username'] === $admin->username &&
    $payload['tags']['Email'] === $admin->email && $payload['tags']['MoodleUserID'] === (string)$admin->id,
    'Tracking identifies the visitor by username and includes email and the numeric Moodle tag.');
check($payload['tags']['MoodleCourseID'] === (string)$course->id &&
    $payload['tags']['MoodleCourseCategoryID'] === '1', 'Course and category context tags.');
check(!str_contains(json_encode($payload), 'test-secret'), 'Browser tracking payload has no API token.');
$guestpayload = \block_msclarity\tracking::payload($page, guest_user(), false);
check($guestpayload['userid'] === null && !isset($guestpayload['tags']['Username']) && !isset($guestpayload['tags']['Email']),
    'Guests have no shared identity, username or email tags.');
$sitepage = new moodle_page();
$sitepage->set_url('/');
$sitepage->set_course($SITE);
$sitepage->set_context($system);
$sitepayload = \block_msclarity\tracking::payload($sitepage, $admin, true);
check(!isset($sitepayload['tags']['MoodleCourseID']), 'The site course is omitted from course tags.');

$module = add_moduleinfo((object)[
    'modulename' => 'page', 'module' => $DB->get_field('modules', 'id', ['name' => 'page']),
    'name' => 'Clarity page', 'section' => 0, 'visible' => 1, 'intro' => '', 'introformat' => FORMAT_HTML,
    'content' => 'Test content', 'contentformat' => FORMAT_HTML, 'display' => 5,
    'printintro' => 0, 'printlastmodified' => 0, 'completion' => 0,
], $course);
$cm = get_coursemodule_from_id('page', $module->coursemodule, $course->id, false, MUST_EXIST);
$activitypage = new moodle_page();
$activitypage->set_url('/mod/page/view.php', ['id' => $cm->id]);
$activitypage->set_cm($cm, $course);
$activitypage->set_context(context_module::instance($cm->id));
$activitypayload = \block_msclarity\tracking::payload($activitypage, $admin, true);
check($activitypayload['tags']['MoodleCourseModuleID'] === (string)$cm->id &&
    $activitypayload['tags']['MoodleCourseID'] === (string)$course->id &&
    $activitypayload['tags']['MoodleModuleType'] === 'page', 'Activity uses course_modules.id and retains course context.');

$coursenode = navigation_node::create('Course');
block_msclarity_extend_navigation_course($coursenode, $course, context_course::instance($course->id));
$courseurl = $coursenode->get('msclarity')->action->url;
check($courseurl->get_param('Variables') === 'MoodleCourseID:' . $course->id, 'Course link filters the whole course.');
check($coursenode->get('msclarity')->action->attributes['target'] === '_blank' &&
    $coursenode->get('msclarity')->action->attributes['rel'] === 'noopener noreferrer', 'Course navigation opens Clarity in a safe new tab.');
$usernode = navigation_node::create('User');
block_msclarity_extend_navigation_user($usernode, $profile, context_user::instance($profile->id), $course,
    $system);
check($usernode->get('msclarity')->action->url->get_param('Variables') === 'MoodleUserID:' . $profile->id,
    'Site profile link accepts system context and targets the viewed user, not the admin.');
$tree = new \core_user\output\myprofile\tree();
$tree->add_category(new \core_user\output\myprofile\category('reports', 'Reports', null));
block_msclarity_myprofile_navigation($tree, $profile, false, $course);
check(isset($tree->nodes['msclarity']), 'Modern profile tree includes Clarity.');

$client = new mock_export();
$client->result = ['metrics' => \block_msclarity\metrics::parse('[{"metricName":"Traffic","information":[{"totalSessionCount":"125","distinctUserCount":"90","pagesPerSessionPercentage":2.5}]}]')];
$now = time();
check(\block_msclarity\insights::refresh($client, $now) === 'success' && $client->calls === 1, 'Refresh stores a normalized snapshot.');
check(\block_msclarity\insights::refresh($client, $now + 60) === 'throttled' && $client->calls === 1,
    'Repeated or manually triggered refresh is throttled.');
$block = block_instance('msclarity');
$block->page = $PAGE;
$content = $block->get_content()->text;
check(str_contains($content, '125') && str_contains($content, 'Unavailable') && !str_contains($content, 'test-secret'),
    'Overview renders metrics, marks absent values unavailable, and exposes no token.');
check($client->calls === 1, 'Block rendering makes no export request.');
$holder = proc_open([PHP_BINARY, __DIR__ . '/lock_holder.php', $argv[1]],
    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
try {
    check(trim(fgets($pipes[1])) === 'ready', 'Separate process acquired the reporting lock.');
    check(\block_msclarity\insights::refresh($client, $now + DAYSECS) === 'locked' && $client->calls === 1,
        'Concurrent refreshes share a lock.');
} finally {
    fwrite($pipes[0], "release\n");
    foreach ($pipes as $pipe) {
        fclose($pipe);
    }
    proc_close($holder);
}
$client->result = ['error' => 'authentication'];
check(\block_msclarity\insights::refresh($client, $now + \block_msclarity\insights::INTERVAL) === 'authentication' &&
    \block_msclarity\insights::state()['metrics']['sessions'] === 125, 'Failed refresh preserves the previous successful snapshot.');
$block->content = null;
check(str_contains($block->get_content()->text, 'previously collected') && str_contains($block->content->text, 'rejected the API token'),
    'Overview explains stale results and authentication errors.');
$client->result = ['error' => 'ratelimit'];
check(\block_msclarity\insights::refresh($client, $now + 2 * \block_msclarity\insights::INTERVAL) === 'ratelimit',
    'Rate limit is recorded.');
check(\block_msclarity\insights::refresh($client, $now + 3 * \block_msclarity\insights::INTERVAL) === 'ratelimit',
    'Rate-limited requests wait 24 hours.');
set_config('apitoken', 'replacement-test-token', 'block_msclarity');
check(!isset(\block_msclarity\insights::state()['metrics']), 'Programmatic credential changes invalidate old results.');
\block_msclarity\insights::invalidate();
check(isset(\block_msclarity\insights::state()['attemptedat']), 'Credential changes preserve request pacing.');
$client->result = ['metrics' => ['sessions' => 10]];
$client->change = true;
check(\block_msclarity\insights::refresh($client, $now + 2 * DAYSECS) === 'changed' &&
    !isset(\block_msclarity\insights::state()['metrics']), 'Credentials changed during a request cannot store an outdated snapshot.');

// Scheduled runs, manual refreshes and failures all consume one persistent project allowance.
set_config('requestbudget', json_encode(['projects' => []]), 'block_msclarity');
unset_config('reportstate', 'block_msclarity');
$client->change = false;
$client->calls = 0;
$client->result = ['metrics' => ['sessions' => 10]];
check(\block_msclarity\insights::refresh($client, $now) === 'success', 'Scheduled request consumes the shared allowance.');
check(\block_msclarity\insights::refresh($client, $now + 1, true) === 'cooldown' && $client->calls === 1,
    'Double-clicks consume no additional API allowance.');
check(\block_msclarity\insights::refresh($client, $now + MINSECS, true) === 'success' && $client->calls === 2,
    'Manual refresh bypasses the six-hour schedule while respecting the cooldown.');
$client->result = ['error' => 'network'];
check(\block_msclarity\insights::refresh($client, $now + 2 * MINSECS, true) === 'network' &&
    \block_msclarity\request_budget::check('w3y4plwlqv', true, $now + 2 * MINSECS)['used'] === 3,
    'Failed HTTP attempts also consume the allowance.');
$client->result = ['metrics' => ['sessions' => 10]];
for ($i = 3; $i < 10; $i++) {
    check(\block_msclarity\insights::refresh($client, $now + $i * MINSECS, true) === 'success',
        'Within-budget manual request ' . ($i + 1) . '.');
}
check(\block_msclarity\insights::refresh($client, $now + 10 * MINSECS, true) === 'quota' && $client->calls === 10,
    'The eleventh manual request cannot reach Clarity.');
check(\block_msclarity\insights::refresh($client, $now + \block_msclarity\insights::INTERVAL) === 'quota' && $client->calls === 10,
    'Cron cannot bypass a quota exhausted by manual refreshes.');
set_config('apitoken', 'rotated-token', 'block_msclarity');
\block_msclarity\insights::invalidate();
check(\block_msclarity\insights::refresh($client, $now + 10 * MINSECS, true) === 'quota',
    'Changing tokens cannot reset project accounting.');
set_config('projectid', 'differentproject', 'block_msclarity');
check(\block_msclarity\insights::refresh($client, $now + 10 * MINSECS, true) === 'success', 'A different project has its own allowance.');
set_config('projectid', 'w3y4plwlqv', 'block_msclarity');
check(\block_msclarity\insights::refresh($client, $now + 11 * MINSECS, true) === 'quota',
    'Switching back to a project retains its exhausted allowance.');
check(\block_msclarity\request_budget::check('w3y4plwlqv', true, $now + 12 * HOURSECS)['remaining'] === 0,
    'Crossing midnight or changing display timezone cannot reset the rolling allowance.');
check(\block_msclarity\insights::refresh($client, $now + DAYSECS, true) === 'success' &&
    \block_msclarity\request_budget::check('w3y4plwlqv', true, $now + DAYSECS)['used'] === 10,
    'A request expires exactly at 24 hours and frees one slot.');

// A request that read config before another process reserved slot ten must not use a stale cache.
set_config('requestbudget', json_encode(['projects' => ['w3y4plwlqv' =>
    ['attempts' => array_fill(0, 9, $now)]]]), 'block_msclarity');
get_config('block_msclarity', 'requestbudget');
$holder = proc_open([PHP_BINARY, __DIR__ . '/lock_holder.php', $argv[1], 'w3y4plwlqv', (string)($now + MINSECS)],
    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
try {
    check(trim(fgets($pipes[1])) === 'ready', 'A separate process reserved the tenth request under the shared lock.');
} finally {
    fwrite($pipes[0], "release\n");
    foreach ($pipes as $pipe) {
        fclose($pipe);
    }
    proc_close($holder);
}
$callsbefore = $client->calls;
check(\block_msclarity\insights::refresh($client, $now + 2 * MINSECS, true) === 'quota' && $client->calls === $callsbefore,
    'A stale request-local configuration cache cannot allow an eleventh request.');

// Remote project consumers can exhaust Clarity before our local ten requests.
set_config('requestbudget', json_encode(['projects' => []]), 'block_msclarity');
$client->result = ['error' => 'ratelimit'];
check(\block_msclarity\insights::refresh($client, $now, true) === 'ratelimit', 'An upstream 429 installs a project backoff.');
set_config('apitoken', 'another-token', 'block_msclarity');
\block_msclarity\insights::invalidate();
check(\block_msclarity\insights::refresh($client, $now + HOURSECS, true) === 'ratelimit',
    'A token change cannot bypass an upstream 429 backoff.');
$client->result = ['metrics' => ['sessions' => 10]];
check(\block_msclarity\insights::refresh($client, $now + DAYSECS, true) === 'success', 'Upstream backoff expires after 24 hours.');

$managerrole = array_values(get_archetype_roles('manager'))[0];
role_assign($managerrole->id, $profile->id, $system->id);
foreach (['view', 'addinstance', 'myaddinstance'] as $cap) {
    assign_capability('block/msclarity:' . $cap, CAP_ALLOW, $managerrole->id, $system->id);
}
try {
    \core\session\manager::set_user($profile);
    check(has_capability('block/msclarity:view', $system) && !\block_msclarity\configuration::can_view(),
        'Manager with explicit view permission is still denied.');
    check($block->get_content()->text === '' && $block->get_content_for_output($PAGE->get_renderer('core')) === null,
        'Non-admins receive no block content or header, including previously cached admin content.');
    check($block->get_content_for_external($PAGE->get_renderer('core'))->title === null &&
        $block->get_config_for_external()->plugin == new stdClass(), 'External block output exposes neither title nor configuration.');
    check(!$block->user_can_addto($PAGE) && !$block->instance_can_be_edited(), 'Explicit manager grants cannot add or edit the block.');
    $deniednode = navigation_node::create('Course');
    block_msclarity_extend_navigation_course($deniednode, $course, context_course::instance($course->id));
    check(!$deniednode->get('msclarity'), 'Non-admin course navigation has no Clarity link.');
    $settings = new admin_settingpage('testmsclarity', 'Test');
    $ADMIN = admin_get_root(false, true);
    require(__DIR__ . '/../settings.php');
    check($settings === null, 'Non-admins cannot access the settings page.');
} finally {
    foreach (['view', 'addinstance', 'myaddinstance'] as $cap) {
        unassign_capability('block/msclarity:' . $cap, $managerrole->id, $system->id);
    }
    \core\session\manager::set_user($admin);
}
$DB->set_field('block', 'visible', 0, ['name' => 'msclarity']);
check(!\block_msclarity\configuration::enabled() && \block_msclarity\insights::refresh($client) === 'unconfigured',
    'Disabling the plugin stops report refreshes.');
$DB->set_field('block', 'visible', 1, ['name' => 'msclarity']);

// Leave a representative snapshot and dashboard instance for browser smoke tests.
set_config('apitoken', 'test-secret-never-expose', 'block_msclarity');
unset_config('reportstate', 'block_msclarity');
set_config('requestbudget', json_encode(['projects' => []]), 'block_msclarity');
$client->change = false;
$client->result = ['metrics' => ['sessions' => 125, 'users' => 90, 'pagespersession' => 2.5, 'engagement' => 42.5,
    'rageclicks' => 2, 'deadclicks' => 0, 'ragepercent' => 1.6, 'deadpercent' => 0]];
\block_msclarity\insights::refresh($client);
// Keep HTTP tests from contacting Clarity: direct POSTs must stop at the exhausted allowance.
set_config('requestbudget', json_encode(['projects' => ['w3y4plwlqv' =>
    ['attempts' => array_fill(0, \block_msclarity\request_budget::LIMIT, time())]]]), 'block_msclarity');
if (!$DB->record_exists('block_instances', ['blockname' => 'msclarity', 'parentcontextid' => $system->id,
    'pagetypepattern' => 'my-index'])) {
    $PAGE->blocks->add_block('msclarity', $PAGE->blocks->get_default_region(), 0, false, 'my-index', null);
}
file_put_contents($CFG->dataroot . '/msclarity-test-fixture.json', json_encode([
    'courseid' => $course->id, 'profileid' => $profile->id, 'cmid' => $cm->id, 'managerusername' => $profile->username,
    'manageremail' => $profile->email, 'adminemail' => $admin->email,
]));
echo "Moodle integration checks passed.\n";
