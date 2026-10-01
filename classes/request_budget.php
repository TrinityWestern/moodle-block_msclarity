<?php
// This file is part of Moodle - http://moodle.org/
// Moodle is free software: you can redistribute it and/or modify it under the terms of
// the GNU General Public License as published by the Free Software Foundation,
// either version 3 of the License, or (at your option) any later version.

/**
 * Shared per-project request allowance for scheduled and manual refreshes.
 *
 * @package block_msclarity
 * @copyright 2026 Trinity Western University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_msclarity;

defined('MOODLE_INTERNAL') || die();

/** Persist request attempts independently of snapshots and API token changes. */
final class request_budget {
    /** @var int Maximum requests in any rolling 24 hours; no reset timezone is assumed. */
    public const LIMIT = 10;
    /** @var int Prevent rapid repeat clicks without imposing the cron interval on manual runs. */
    public const COOLDOWN = MINSECS;

    /**
     * @param string $projectid Clarity project ID.
     * @param bool $manual Whether this is an immediate admin refresh.
     * @param int $now Current timestamp.
     * @return array Usage, blocking reason and next allowed time.
     */
    public static function check(string $projectid, bool $manual, int $now): array {
        $ledger = self::load();
        $project = $ledger['projects'][$projectid] ?? [];
        $attempts = self::recent($project['attempts'] ?? [], $now);
        $used = count($attempts);
        $next = 0;
        $reason = null;
        if ($attempts) {
            $next = max($attempts) + ($manual ? self::COOLDOWN : insights::INTERVAL);
            if ($next > $now) {
                $reason = $manual ? 'cooldown' : 'throttled';
            }
        }
        if ($used >= self::LIMIT) {
            $next = max($next, min($attempts) + DAYSECS);
            $reason = 'quota';
        }
        if (($project['retryafter'] ?? 0) > $now) {
            $next = max($next, $project['retryafter']);
            $reason = 'ratelimit';
        }
        return ['used' => $used, 'remaining' => max(0, self::LIMIT - $used),
            'reason' => $reason, 'nextallowed' => $reason === null ? 0 : $next];
    }

    /**
     * Must be called under the shared refresh lock, immediately before an HTTP request.
     * @param string $projectid Project ID.
     * @param int $now Current timestamp.
     */
    public static function record_attempt(string $projectid, int $now): void {
        $ledger = self::load();
        foreach ($ledger['projects'] as $id => &$project) {
            $project['attempts'] = self::recent($project['attempts'] ?? [], $now);
            if (empty($project['attempts']) && ($project['retryafter'] ?? 0) <= $now) {
                unset($ledger['projects'][$id]);
            }
        }
        unset($project);
        $ledger['projects'][$projectid]['attempts'][] = $now;
        self::save($ledger);
    }

    /**
     * Must be called under the shared refresh lock. A 429 can reflect other consumers' requests.
     * @param string $projectid Project ID.
     * @param int $now Current timestamp.
     */
    public static function rate_limited(string $projectid, int $now): void {
        $ledger = self::load();
        $ledger['projects'][$projectid]['retryafter'] = $now + DAYSECS;
        self::save($ledger);
    }

    /** Preserve the legacy last attempt and 429 backoff when upgrading from 1.0.0. */
    public static function migrate_legacy(): void {
        global $DB;
        if ($DB->get_field('config_plugins', 'value', ['plugin' => 'block_msclarity', 'name' => 'requestbudget']) === false) {
            self::save(self::load());
        }
    }

    /**
     * Read the database directly after acquiring the lock; request-local config caches can be stale.
     * @return array Project ledgers, with a conservative seed for legacy installations.
     */
    private static function load(): array {
        global $DB;
        $raw = $DB->get_field('config_plugins', 'value', ['plugin' => 'block_msclarity', 'name' => 'requestbudget']);
        if ($raw !== false) {
            $ledger = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
            if (!is_array($ledger) || !is_array($ledger['projects'] ?? null)) {
                throw new \UnexpectedValueException('Invalid Clarity request ledger.');
            }
            return $ledger;
        }
        $ledger = ['projects' => []];
        $state = json_decode((string)$DB->get_field('config_plugins', 'value',
            ['plugin' => 'block_msclarity', 'name' => 'reportstate']), true);
        $projectid = configuration::project_id();
        if ($projectid && is_array($state)) {
            // Version 1.0.0 stored only the last attempt and allowed one request per six hours.
            // Reserve four slots conservatively instead of forgetting earlier requests on upgrade.
            $ledger['projects'][$projectid] = [
                'attempts' => empty($state['attemptedat']) ? [] : array_fill(0, 4, (int)$state['attemptedat']),
                'retryafter' => (int)($state['retryafter'] ?? 0),
            ];
        }
        return $ledger;
    }

    /**
     * @param array $attempts Stored timestamps.
     * @param int $now Current timestamp.
     * @return array Unexpired attempts. Future timestamps remain counted after a clock correction.
     */
    private static function recent(array $attempts, int $now): array {
        return array_values(array_filter($attempts, static fn($time) => $time > $now - DAYSECS));
    }

    /**
     * @param array $ledger Per-project accounting without API credentials or response data.
     */
    private static function save(array $ledger): void {
        set_config('requestbudget', json_encode($ledger, JSON_THROW_ON_ERROR), 'block_msclarity');
    }
}
