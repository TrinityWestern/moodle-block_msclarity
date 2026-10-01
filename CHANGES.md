# Release notes

## 1.0.2 — 1 October 2026

First public release of Microsoft Clarity for Moodle (`block_msclarity`).

- Site-wide asynchronous Clarity tracking, including pages without a block instance.
- Authenticated visitor identification and user, course, activity, and page context tags.
- Site-admin-only links to Clarity from course navigation and user profiles.
- Optional admin dashboard overview showing site-wide traffic, engagement, rage clicks, and dead clicks.
- Scheduled and manual report refreshes with a shared request ledger, concurrency lock, cooldowns, and rate-limit backoff.
- Privacy API declarations for data sent to Microsoft Clarity; only aggregate reports are stored locally.
- Packaged block icon, screenshot, documentation, and GPL v3-or-later license.

Requires Moodle 4.5 or later; declared support covers Moodle 4.5–5.2. Installation,
integration, and rendered-page checks on Moodle 4.5 and 5.2.2 are recorded in the README.
Clarity requires a project ID for tracking and an API token for the optional reports.

Copyright © 2026 Man Lung Ken Yeung <manlung.yeung@twu.ca>.
