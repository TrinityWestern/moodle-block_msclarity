<?php
// Licensed under the GNU GPL v3 or later.
/**
 * Dependency-free export parser regression tests.
 * Run with: php tests/metrics.php
 *
 * @package block_msclarity
 * @copyright 2026 Trinity Western University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
define('MOODLE_INTERNAL', true);
require_once(__DIR__ . '/../classes/metrics.php');
require_once(__DIR__ . '/../classes/export_client.php');

/**
 * @param bool $condition Assertion.
 * @param string $message Failure message.
 */
function check(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$sample = [
    ['metricName' => 'Traffic', 'information' => [[
        'totalSessionCount' => '123', 'distinctUserCount' => '98', 'pagesPerSessionPercentage' => 4.25,
    ]]],
    ['metricName' => 'EngagementTime', 'information' => [['activeTime' => '42.5']]],
    ['metricName' => 'RageClickCount', 'information' => [['subTotal' => '2', 'sessionsWithMetricPercentage' => 1.63]]],
    ['metricName' => 'Dead Click Count', 'information' => [['subTotal' => '0']]],
    ['metricName' => 'NewMetric', 'information' => [['extra' => 'ignored']]],
];
$result = \block_msclarity\metrics::parse(json_encode($sample));
check($result['sessions'] === 123 && $result['users'] === 98, 'Numeric string traffic values.');
check($result['pagespersession'] === 4.25 && $result['engagement'] === 42.5, 'Fractional metrics.');
check($result['rageclicks'] === 2 && $result['ragepercent'] === 1.63, 'Click count and percentage stay separate.');
check($result['deadclicks'] === 0 && $result['deadpercent'] === null, 'Zero must differ from missing.');

$documented = [['metricName' => 'Traffic', 'information' => [[
    'totalSessionCount' => '9554', 'distantUserCount' => '189733', 'PagesPerSessionPercentage' => 1.0931,
]]]];
$result = \block_msclarity\metrics::parse(json_encode($documented));
check($result['users'] === 189733 && $result['pagespersession'] === 1.0931, 'Documented field spellings.');
check($result['rageclicks'] === null, 'Missing clicks cannot be zero.');

$invalidvalues = [['metricName' => 'Traffic', 'information' => [[
    'totalSessionCount' => '-1', 'distinctUserCount' => '3.5', 'pagesPerSessionPercentage' => 'NaN',
]]]];
$result = \block_msclarity\metrics::parse(json_encode($invalidvalues));
check($result['sessions'] === null && $result['users'] === null && $result['pagespersession'] === null,
    'Negative counts, fractional counts and nonnumbers must be unavailable.');
check(count(array_filter(\block_msclarity\metrics::parse('[]'), fn($v) => $v !== null)) === 0, 'Empty exports.');

$invalidresponses = ['not json', '{}', '{"error":"denied"}', '[{"metricName":"Traffic"}]',
    '[{"metricName":"Traffic","information":[{"totalSessionCount":"1"},{"totalSessionCount":"2"}]}]',
    '[{"metricName":"Traffic","information":[null]}]',
    '[{"metricName":"Unknown","information":[]}]'];
foreach ($invalidresponses as $body) {
    try {
        \block_msclarity\metrics::parse($body);
        throw new RuntimeException('Malformed or dimensioned responses must fail: ' . $body);
    } catch (UnexpectedValueException $e) {
        // Expected.
    }
}
foreach ([400 => 'request', 401 => 'authentication', 403 => 'authorization', 429 => 'ratelimit',
    302 => 'service', 500 => 'service'] as $status => $error) {
    check(\block_msclarity\export_client::decode_response($status, 'secret upstream response') === ['error' => $error],
        'HTTP errors must return only safe codes.');
}
check(\block_msclarity\export_client::decode_response(200, 'invalid JSON') === ['error' => 'response'], 'Invalid JSON response.');
check(\block_msclarity\export_client::decode_response(200, '[]', 28) === ['error' => 'network'], 'Timeout handling.');
check(\block_msclarity\export_client::decode_response(200, json_encode($sample))['metrics']['sessions'] === 123,
    'Successful responses are normalized.');
echo "Export parser and HTTP outcome regression tests passed.\n";
