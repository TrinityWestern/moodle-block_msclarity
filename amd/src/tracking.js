// This file is part of Moodle - http://moodle.org/
// Licensed under the GNU GPL v3 or later.
/**
 * Clarity queue and context tags. Loaded by the global output hook, not block rendering.
 *
 * @module     block_msclarity/tracking
 * @copyright  2026 Man Lung Ken Yeung <manlung.yeung@twu.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Initialise Clarity for the current page.
 *
 * @param {Object} payload Tracking fields from PHP
 */
export const init = (payload) => {
    if (!payload || window.__moodleMsClarityInitialized) {
        return;
    }
    if (!/^[a-zA-Z0-9]{1,64}$/.test(payload.projectid)) {
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
    Object.keys(payload.tags || {}).forEach((key) => {
        window.clarity('set', key, String(payload.tags[key]));
    });
};
