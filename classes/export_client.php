<?php
// This file is part of Moodle - http://moodle.org/
// Moodle is free software: you can redistribute it and/or modify it under the terms of
// the GNU General Public License as published by the Free Software Foundation,
// either version 3 of the License, or (at your option) any later version.

/**
 * Server-side Clarity Data Export client.
 *
 * @package block_msclarity
 * @copyright 2026 Trinity Western University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_msclarity;

defined('MOODLE_INTERNAL') || die();

/** One HTTP request; no retries or credential-bearing redirects. */
class export_client {
    /** @var string Fixed Microsoft API endpoint. */
    public const ENDPOINT = 'https://www.clarity.ms/export-data/api/v1/project-live-insights?numOfDays=3';

    /**
     * @param string $token API access token.
     * @return array An error code or a normalized metrics snapshot.
     */
    public function fetch(string $token): array {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        $curl = new \curl();
        $curl->setHeader(['Authorization: Bearer ' . $token, 'Accept: application/json']);
        try {
            $body = $curl->get(self::ENDPOINT, [], [
                'CURLOPT_TIMEOUT' => 20,
                'CURLOPT_CONNECTTIMEOUT' => 10,
                'CURLOPT_FOLLOWLOCATION' => false,
            ]);
        } catch (\Throwable $e) {
            // HTTP exceptions can contain credentials or response details. Do not log them.
            return ['error' => 'network'];
        }
        $status = (int)($curl->get_info()['http_code'] ?? 0);
        return self::decode_response($status, (string)$body, $curl->get_errno());
    }

    /**
     * Convert HTTP outcomes into safe error codes without retaining response bodies.
     * @param int $status HTTP status.
     * @param string $body Response body.
     * @param int $errno Transport error, if any.
     * @return array An error code or normalized metrics.
     */
    public static function decode_response(int $status, string $body, int $errno = 0): array {
        if ($errno) {
            return ['error' => 'network'];
        }
        $errors = [400 => 'request', 401 => 'authentication', 403 => 'authorization', 429 => 'ratelimit'];
        if ($status !== 200) {
            return ['error' => $errors[$status] ?? 'service'];
        }
        try {
            return ['metrics' => metrics::parse($body)];
        } catch (\UnexpectedValueException $e) {
            return ['error' => 'response'];
        }
    }
}
