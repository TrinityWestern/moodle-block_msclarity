<?php
// This file is part of Moodle - http://moodle.org/
// Moodle is free software: you can redistribute it and/or modify it under the terms of
// the GNU General Public License as published by the Free Software Foundation,
// either version 3 of the License, or (at your option) any later version.

/**
 * Normalize the API response without guessing missing statistics.
 *
 * @package block_msclarity
 * @copyright 2026 Trinity Western University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_msclarity;

defined('MOODLE_INTERNAL') || die();

/** Only retain site-wide numeric values needed by the overview. */
final class metrics {
    /**
     * @param string $json Export response body.
     * @return array Normalized metrics; null means unavailable, not zero.
     * @throws \UnexpectedValueException When the response is not an export metrics list.
     */
    public static function parse(string $json): array {
        try {
            $data = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \UnexpectedValueException('Invalid export JSON.');
        }
        if (!str_starts_with(ltrim($json), '[') || !is_array($data) || !array_is_list($data)) {
            throw new \UnexpectedValueException('Expected an export metrics list.');
        }
        $rows = [];
        $recognized = ['traffic', 'engagementtime', 'rageclickcount', 'deadclickcount'];
        foreach ($data as $metric) {
            if (!is_array($metric) || !is_string($metric['metricName'] ?? null) ||
                    !is_array($metric['information'] ?? null) || !array_is_list($metric['information'])) {
                throw new \UnexpectedValueException('Invalid export metric.');
            }
            $name = strtolower(str_replace(' ', '', $metric['metricName']));
            if (!in_array($name, $recognized, true)) {
                continue;
            }
            // This request has no dimensions. Never sum distinct users or average dimension rows.
            if (isset($rows[$name]) || count($metric['information']) > 1) {
                throw new \UnexpectedValueException('Expected one site-wide row per metric.');
            }
            $row = $metric['information'] ? $metric['information'][0] : [];
            if (!is_array($row)) {
                throw new \UnexpectedValueException('Invalid export row.');
            }
            $rows[$name] = array_change_key_case($row, CASE_LOWER);
        }
        if ($data && !$rows) {
            throw new \UnexpectedValueException('No supported export metrics.');
        }
        $traffic = $rows['traffic'] ?? [];
        return [
            'sessions' => self::number($traffic, ['totalsessioncount'], true),
            // Microsoft's documentation uses "distantUserCount"; accept the live API spelling too.
            'users' => self::number($traffic, ['distinctusercount', 'distantusercount'], true),
            'pagespersession' => self::number($traffic, ['pagespersessionpercentage']),
            'engagement' => self::number($rows['engagementtime'] ?? [], ['activetime']),
            'rageclicks' => self::number($rows['rageclickcount'] ?? [], ['subtotal'], true),
            'deadclicks' => self::number($rows['deadclickcount'] ?? [], ['subtotal'], true),
            'ragepercent' => self::number($rows['rageclickcount'] ?? [], ['sessionswithmetricpercentage']),
            'deadpercent' => self::number($rows['deadclickcount'] ?? [], ['sessionswithmetricpercentage']),
        ];
    }

    /**
     * @param array $row A metric row.
     * @param array $keys Known field spellings, in priority order.
     * @param bool $integer Whether this is a count.
     * @return int|float|null A valid nonnegative number, or null.
     */
    private static function number(array $row, array $keys, bool $integer = false) {
        foreach ($keys as $key) {
            $value = $row[$key] ?? null;
            if ((!is_int($value) && !is_float($value) && !is_string($value)) || !is_numeric($value)) {
                continue;
            }
            $value = (float)$value;
            if (!is_finite($value) || $value < 0 || ($integer && ($value > PHP_INT_MAX || floor($value) !== $value))) {
                continue;
            }
            return $integer ? (int)$value : $value;
        }
        return null;
    }
}
