// This file is part of Moodle - http://moodle.org/
// Licensed under the GNU GPL v3 or later.
/**
 * Clarity queue and context tags. Loaded by the global output hook, not block rendering.
 * @package block_msclarity
 * @copyright 2026 Man Lung Ken Yeung <manlung.yeung@twu.ca>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
(function(window, document) {
    'use strict';

    const script = document.currentScript;
    if (!script || window.__moodleMsClarityInitialized) {
        return;
    }

    let payload;
    try {
        payload = JSON.parse(script.getAttribute('data-msclarity'));
    } catch (error) {
        return;
    }
    if (!payload || !/^[a-zA-Z0-9]{1,64}$/.test(payload.projectid)) {
        return;
    }
    window.__moodleMsClarityInitialized = true;
    window.clarity = window.clarity || function() {
        (window.clarity.q = window.clarity.q || []).push(arguments);
    };

    if (!document.querySelector('script[src^="https://www.clarity.ms/tag/"]')) {
        const tag = document.createElement('script');
        tag.async = true;
        tag.src = 'https://www.clarity.ms/tag/' + payload.projectid;
        document.head.appendChild(tag);
    }

    if (typeof window.clarity !== 'function') {
        return;
    }
    if (payload.userid) {
        window.clarity('identify', payload.userid);
    }
    Object.entries(payload.tags || {}).forEach(function([key, value]) {
        window.clarity('set', key, String(value));
    });
})(window, document);
