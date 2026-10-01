# Releasing Microsoft Clarity for Moodle

## First Marketplace submission

The Marketplace API adds versions to an existing plugin maintained by the token's
account. It cannot create the initial `block_msclarity` listing.

1. Build the ZIP with `python3 tools/package.py`.
2. Log in at https://marketplace.moodle.com/ and submit a new plugin using its web interface.
3. Use the listing text and links in [MARKETPLACE.md](MARKETPLACE.md), upload
   `dist/block_msclarity-1.0.2.zip`, and attach `screenshot.png`.
4. Complete the Marketplace review process. A version submission is queued for
   prechecks; acceptance of an upload alone does not mean it has been published.
5. Once the listing can accept new versions, create a token at
   https://marketplace.moodle.com/account/security under your maintainer account.
6. Add the token as the repository Actions secret `MOODLE_MARKETPLACE_TOKEN` at
   https://github.com/TrinityWestern/moodle-block_msclarity/settings/secrets/actions.

Do not commit tokens or include them in release notes or ZIP files. Without the
repository secret, the release workflow skips Marketplace submission and explains
the missing setup in its job summary.

## Subsequent releases

1. Increase `$plugin->version` and `$plugin->release` in `version.php`. Update
   `$plugin->supported` when support changes, and add the release notes to `CHANGES.md`.
2. Run the checks and build the package:

   ```sh
   php tests/metrics.php
   node --test tests/tracking.test.cjs
   python3 tools/package.py
   ```

3. Run installation and integration checks in disposable Moodle sites for runtime
   changes, following the README. Inspect the archive before uploading it.
4. Commit the release, push the branch, and create the version tag. For example,
   for the next release `1.0.3`:

   ```sh
   git tag -a v1.0.3 -m 'Microsoft Clarity 1.0.3'
   git push origin v1.0.3
   gh release create v1.0.3 dist/block_msclarity-1.0.3.zip \
     --verify-tag --title 'Microsoft Clarity 1.0.3' \
     --notes-file /path/to/1.0.3-notes.md --draft
   ```

   Review and publish the GitHub draft when ready.

5. The `v*` tag workflow calls the pinned official
   [moodlehq/moodle-plugin-release workflow](https://github.com/moodlehq/moodle-plugin-release).
   It builds the Marketplace ZIP using `git archive`, respecting `.gitattributes`,
   and reads notes from the GitHub release or falls back to the root changelog.
6. Check the workflow's API response and the Marketplace review status.

To submit a tag after adding the secret, run **Release to Moodle Marketplace**
manually from GitHub Actions and enter the existing tag. Submit only versions that
have not already been uploaded: duplicate build numbers are rejected. Do not run
this for `v1.0.2` if that build was already uploaded during the first submission.

API reference: https://moodledev.io/general/community/plugincontribution/moodlemarketplaceapi.
