<?php
// Licensed under the GNU GPL v3 or later.
/**
 * Rendered-page checks for a disposable Moodle site populated by integration.php.
 * Run: php tests/http.php http://localhost:8197 /path/to/test/moodledata/msclarity-test-fixture.json
 *
 * @package block_msclarity
 * @copyright 2026 Trinity Western University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$baseurl = $argv[1] ?? '';
$fixture = json_decode(file_get_contents($argv[2]), true);
if (!preg_match('~^http://localhost:[0-9]+$~', $baseurl)) {
    throw new RuntimeException('Use a disposable site on localhost.');
}
$cookies = tempnam(sys_get_temp_dir(), 'msclarity-http-');
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_COOKIEJAR => $cookies, CURLOPT_COOKIEFILE => $cookies,
    CURLOPT_USERAGENT => 'MicrosoftClarityPluginTest/1.0',
    CURLOPT_TIMEOUT => 20,
]);

/**
 * @param string $path Relative test URL.
 * @param array|null $post Form data.
 * @param bool $expecterror Whether a Moodle error response is expected.
 * @return string Response HTML.
 */
function request(string $path, ?array $post = null, bool $expecterror = false): string {
    global $curl, $baseurl;
    curl_setopt($curl, CURLOPT_URL, $baseurl . $path);
    curl_setopt($curl, CURLOPT_POST, $post !== null);
    if ($post !== null) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $body = curl_exec($curl);
    if ($body === false || (!$expecterror && curl_getinfo($curl, CURLINFO_HTTP_CODE) !== 200)) {
        throw new RuntimeException('Unexpected HTTP result for ' . $path);
    }
    if (str_contains($body, 'test-secret-never-expose')) {
        throw new RuntimeException('Token exposed on ' . $path);
    }
    return $body;
}

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

/**
 * @param string $html Page HTML.
 * @return array Tracking payload from the global head hook.
 */
function payload(string $html): array {
    preg_match('/data-msclarity="([^"]+)"/', $html, $matches);
    if (empty($matches[1])) {
        throw new RuntimeException('Missing head tracking payload.');
    }
    return json_decode(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5), true, 512, JSON_THROW_ON_ERROR);
}

/**
 * @param string $username Test fixture user.
 */
function login(string $username): void {
    $html = request('/login/index.php');
    preg_match('/name="logintoken" value="([^"]+)"/', $html, $matches);
    check(!empty($matches[1]), 'Login form is available.');
    request('/login/index.php', ['username' => $username, 'password' => 'TestOnlyMsclarity123!', 'logintoken' => $matches[1]]);
}

/**
 * @param string $html Rendered page.
 * @param string $filter Expected URL filter.
 */
function check_new_tab(string $html, string $filter): void {
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML($html);
    libxml_clear_errors();
    $found = 0;
    foreach ($doc->getElementsByTagName('a') as $link) {
        if (str_contains($link->getAttribute('href'), $filter)) {
            check($link->getAttribute('target') === '_blank' &&
                $link->getAttribute('rel') === 'noopener noreferrer', 'Rendered Clarity link opens in a safe new tab.');
            $found++;
        }
    }
    check($found > 0, 'Rendered Clarity link is present.');
}

try {
    $html = request('/login/index.php');
    check(payload($html)['userid'] === null, 'Logged-out HTML loads site-wide tracking without a block instance.');
    check(substr_count($html, 'data-msclarity=') === 1, 'One tracking loader per rendered page.');
    login('admin');
    $html = request('/course/view.php?id=' . $fixture['courseid']);
    $context = payload($html);
    check($context['userid'] === 'admin' && $context['tags']['MoodleUserID'] === '2' &&
        $context['tags']['Email'] === $fixture['adminemail'] && $context['tags']['MoodleCourseID'] === (string)$fixture['courseid'],
        'Admin course HTML identifies the visitor and course.');
    check(str_contains($html, 'MoodleCourseID%3A' . $fixture['courseid']) && str_contains($html, 'View course in Clarity'),
        'Boost displays the whole-course Clarity navigation link.');
    check_new_tab($html, 'MoodleCourseID%3A' . $fixture['courseid']);
    $html = request('/mod/page/view.php?id=' . $fixture['cmid']);
    $context = payload($html);
    check($context['tags']['MoodleCourseModuleID'] === (string)$fixture['cmid'] &&
        $context['tags']['MoodleCourseID'] === (string)$fixture['courseid'], 'Activity HTML carries activity and course IDs.');
    $html = request('/user/profile.php?id=' . $fixture['profileid']);
    check(payload($html)['userid'] === 'admin' && str_contains($html, 'MoodleUserID%3A' . $fixture['profileid']),
        'Profile identifies the visitor while its visible Clarity link targets the profile owner.');
    check_new_tab($html, 'MoodleUserID%3A' . $fixture['profileid']);
    $html = request('/my/index.php');
    check(str_contains($html, 'block-msclarity-overview') && str_contains($html, 'Unique users'),
        'Admin dashboard displays the overview block.');
    check(str_contains($html, 'Refresh now') && str_contains($html, 'method="post"') &&
        str_contains($html, 'Clarity project quota remaining: unknown') &&
        !str_contains($html, 'Remaining local allowance'), 'Admin block has a POST refresh button and honest quota feedback.');
    preg_match('/name="sesskey" value="([^"]+)"/', $html, $keys);
    check(!empty($keys[1]), 'Refresh form carries a CSRF session key.');
    $refreshpath = '/blocks/msclarity/refresh.php';
    $error = request($refreshpath, ['sesskey' => 'invalid'], true);
    check(str_contains($error, 'invalidsesskey'), 'Refresh rejects invalid CSRF session keys.');
    $error = request($refreshpath, null, true);
    check(str_contains($error, 'invalidrequest'), 'Refresh rejects GET requests.');
    // The fixture has just refreshed; the real endpoint must honor its cooldown without an API request.
    $result = request($refreshpath, ['sesskey' => $keys[1]]);
    check(str_contains($result, 'Wait one minute') || str_contains($result, '10-request allowance'),
        'Manual task endpoint respects the shared request budget.');
    // A fresh client prevents reusing the admin session.
    $curl = null;
    unlink($cookies);
    $cookies = tempnam(sys_get_temp_dir(), 'msclarity-http-');
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $cookies, CURLOPT_COOKIEFILE => $cookies,
        CURLOPT_USERAGENT => 'MicrosoftClarityPluginTest/1.0',
        CURLOPT_TIMEOUT => 20,
    ]);
    login($fixture['managerusername']);
    $html = request('/my/index.php');
    check(payload($html)['userid'] === $fixture['managerusername'] &&
        payload($html)['tags']['Email'] === $fixture['manageremail'], 'Manager login identifies the manager by username and email.');
    check(!str_contains($html, 'block-msclarity-overview'), 'Manager cannot see the shared default dashboard block.');
    $error = request('/blocks/msclarity/refresh.php', ['sesskey' => 'invalid'], true);
    check(str_contains($error, 'nopermissions'), 'Manager cannot run a refresh through a direct POST.');
    $html = request('/course/view.php?id=' . $fixture['courseid']);
    check(!str_contains($html, 'View course in Clarity'), 'Manager course HTML contains no Clarity navigation link.');
    $html = request('/user/profile.php?id=' . $fixture['profileid']);
    check(!str_contains($html, 'View user in Clarity'), 'Manager profile HTML contains no Clarity navigation link.');
    echo "Rendered-page checks passed.\n";
} finally {
    curl_close($curl);
    unlink($cookies);
}
