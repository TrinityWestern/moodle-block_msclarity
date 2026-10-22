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
defined('MOODLE_INTERNAL') || die();
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
