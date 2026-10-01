# First Marketplace submission

These fields are ready to copy into the Moodle Marketplace submission form.

## Name

Microsoft Clarity

## Component and type

`block_msclarity` — Block

## Short description

Connect Moodle to Microsoft Clarity with site-wide tracking, course and user dashboard
links for site admins, and an optional overview of site-wide engagement statistics.

## Full description

Microsoft Clarity for Moodle adds site-wide asynchronous Clarity tracking to Moodle
4.5–5.2. Tracking works on ordinary browser pages even when no block instance has
been added. Authenticated visitors are identified by Moodle username, with user,
course, activity, and page context tags to help filter sessions in Clarity.

Site admins can open filtered Clarity dashboards from course navigation and user
profiles. An optional admin dashboard block displays site-wide sessions, unique
users, pages per session, active engagement time, rage clicks, and dead clicks.
Reports refresh through Moodle cron, with a manual refresh button, persistent
request limits, and rate-limit backoff. Reports and contextual links are restricted
to Moodle site administrators.

A Microsoft Clarity account and project are required. Enter the project ID from
Clarity Settings → Overview. The optional reporting feature also requires an API
token generated in Settings → Data Export. No additional Moodle plugin dependency
is required.

The plugin sends authenticated visitors' numeric Moodle user IDs, usernames, and
email addresses to Microsoft Clarity as readable custom tags. It also sends course,
activity, and page context tags when available. Logged-out and guest pages are
tracked without an authenticated visitor identity. The plugin declares transmitted
data through Moodle's Privacy API and stores only the latest aggregate report and
refresh metadata locally. Administrators should configure their Clarity project's
masking and consent behavior and describe this integration in their site's privacy
information.

Install the ZIP through Site administration → Plugins → Install plugins. Remove
any existing Clarity snippet from Additional HTML before configuring the plugin to
avoid duplicate tracking. Add the optional Microsoft Clarity block to an admin
dashboard to view reports.

Copyright © 2026 Man Lung Ken Yeung <manlung.yeung@twu.ca>.
Licensed under GNU GPL v3 or later.

## Submission assets and links

- Package: `dist/block_msclarity-1.0.2.zip`
- Release: `1.0.2`; build: `2026100102`; maturity: stable.
- Supported Moodle branches: 4.5, 5.0, 5.1, 5.2.
- Repository: https://github.com/TrinityWestern/moodle-block_msclarity
- Documentation: https://github.com/TrinityWestern/moodle-block_msclarity/blob/master/README.md
- Issue tracker: https://github.com/TrinityWestern/moodle-block_msclarity/issues
- Screenshot: `screenshot.png`
- Release notes: `CHANGES.md`
- Maintainer contact: Man Lung Ken Yeung — manlung.yeung@twu.ca.

Provide a dedicated demonstration site or Clarity project privately to reviewers
if the submission form requests access. The package contains no Clarity credentials.
