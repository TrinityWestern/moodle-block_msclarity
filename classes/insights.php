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
 * Persistent shared reporting snapshot and request throttling.
 *
 * @package block_msclarity
 * @copyright 2026 Man Lung Ken Yeung <manlung.yeung@twu.ca>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_msclarity;

defined('MOODLE_INTERNAL') || die();

/** Store only aggregate metrics, fixed error codes and refresh metadata. */
final class insights {
    /** @var int Scheduled refresh interval; manual runs use the shared request budget and a short cooldown. */
    public const INTERVAL = 6 * HOURSECS;

    /**
     * @return array Stored state, excluding snapshots collected under other credentials.
     */
    public static function state(): array {
        global $DB;
        $state = json_decode((string)$DB->get_field('config_plugins', 'value',
            ['plugin' => 'block_msclarity', 'name' => 'reportstate']), true);
        if (!is_array($state)) {
            return [];
        }
        if (($state['fingerprint'] ?? '') !== configuration::fingerprint()) {
            // Retain request pacing when credentials are changed programmatically.
            return array_intersect_key($state, array_flip(['attemptedat', 'retryafter']));
        }
        return $state;
    }

    /** Clear snapshots when either user-visible setting changes; preserve rate-limit pacing. */
    public static function invalidate(): void {
        request_budget::migrate_legacy();
        $state = self::state();
        self::save(array_intersect_key($state, array_flip(['attemptedat', 'retryafter'])));
    }

    /**
     * Refresh independently of block rendering; manual runs must use the same lock and quota as cron.
     * @param export_client|null $client Optional client for testing.
     * @param int|null $now Optional current time for testing.
     * @param bool $manual Whether this is an explicit immediate refresh.
     * @return string Fixed result code, safe to write to task logs.
     */
    public static function refresh(?export_client $client = null, ?int $now = null, bool $manual = false): string {
        if (!configuration::enabled() || !configuration::project_id() || !configuration::api_token()) {
            return 'unconfigured';
        }
        $factory = \core\lock\lock_config::get_lock_factory('block_msclarity');
        $lock = $factory->get_lock('refresh', 0);
        if (!$lock) {
            return 'locked';
        }
        try {
            $now = $now ?? time();
            $projectid = configuration::project_id();
            $budget = request_budget::check($projectid, $manual, $now);
            if ($budget['reason'] !== null) {
                return $budget['reason'];
            }
            $state = self::state();
            $fingerprint = configuration::fingerprint();
            $state['fingerprint'] = $fingerprint;
            $state['attemptedat'] = $now;
            // Save before contacting Microsoft: crashes must not trigger unbounded repeated requests.
            request_budget::record_attempt($projectid, $now);
            self::save($state);
            $result = ($client ?? new export_client())->fetch(configuration::api_token());
            // Keep a 429 backoff even when credentials changed during the request.
            if (($result['error'] ?? '') === 'ratelimit') {
                request_budget::rate_limited($projectid, $now);
            }
            if (configuration::fingerprint() !== $fingerprint) {
                self::invalidate();
                return 'changed';
            }
            if (isset($result['error'])) {
                $state['error'] = $result['error'];
                if ($result['error'] === 'ratelimit') {
                    $state['retryafter'] = $now + DAYSECS;
                }
            } else {
                $state['metrics'] = $result['metrics'];
                $state['updatedat'] = $now;
                unset($state['error'], $state['retryafter']);
            }
            self::save($state);
            return $state['error'] ?? 'success';
        } finally {
            $lock->release();
        }
    }

    /**
     * @param array $state Aggregate snapshot and refresh metadata.
     */
    private static function save(array $state): void {
        set_config('reportstate', json_encode($state, JSON_THROW_ON_ERROR), 'block_msclarity');
    }
}
