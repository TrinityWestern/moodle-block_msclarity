# Release notes

## 1.1.0 — 22 October 2026

- Privacy API request provider so the component is reported compliant (empty export/delete; no per-user Moodle storage).
- Admin settings to opt in to sending username and email to Clarity (off by default); Identify uses the numeric Moodle user ID otherwise.
- Optional gate that loads Clarity only after site policy agreement when a policy is defined.
- Request ledger and metrics snapshot stored in `block_msclarity_state` instead of `config_plugins`.
- Tracking JavaScript moved to a Moodle AMD module (`block_msclarity/tracking`).
- GitHub Actions workflow for moodle-plugin-ci on push and pull requests.

Requires Moodle 4.5 or later; declared support covers Moodle 4.5–5.2.

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
