<?php
// Licensed under the GNU GPL v3 or later.
/**
 * Separate database connection for the integration test's concurrency check.
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
require($argv[1]);
$lock = \core\lock\lock_config::get_lock_factory('block_msclarity')->get_lock('refresh', 0);
if (!$lock) {
    exit(1);
}
try {
    if (isset($argv[2], $argv[3])) {
        \block_msclarity\request_budget::record_attempt($argv[2], (int)$argv[3]);
    }
    fwrite(STDOUT, "ready\n");
    fgets(STDIN);
} finally {
    $lock->release();
}
