# Changelog

## 1.0.2 - 2026-09-17

- Fixed Moodle GPL boilerplate formatting in all PHP files for CodeSniffer compliance.
- Updated GPL URL to the canonical HTTPS form expected by Moodle coding standards.

## 1.0.1 - 2026-09-17

- Fixed Moodle CodeSniffer boilerplate and PSR-12 issues reported by GitHub Actions.
- Removed the unnecessary `MOODLE_INTERNAL` guard from `lib.php`.
- Replaced stale generated AMD artefacts with a directly loaded RequireJS entry point.
- Kept `core/ajax` for membership requests while eliminating the `amd/build` stale-file failure.

## 1.0.0 - 2026-09-17

- Initial Moodle 5.x release.
- Cohort badges on the standard course participants page.
- Dynamic table refresh support.
- User profile display.
- Configurable badge format and hidden-cohort visibility.
- GitHub Actions CI for Moodle 5.0, 5.1, and 5.2.
- PHPUnit coverage for cohort label formatting.

## 1.0.3 - 2026-09-17
- Load the participants enhancer from the footer hook after the table exists.
- Make participant/user detection independent from theme-specific table cell classes.
- Add browser console diagnostics when the AJAX request fails.
