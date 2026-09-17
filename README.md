# Cohort badges (`local_cohortbadges`)

A small Moodle 5.x local plugin that displays a user's cohort memberships as badges next to their name in the standard **Course > Participants** table.

## Features

- Works without modifying Moodle core.
- Uses Moodle Hooks API and `core/ajax`.
- Survives dynamic participant table filtering/pagination.
- Displays cohort memberships in the standard user profile.
- Configurable badge text: cohort name, ID number, or both.
- Hidden cohorts are excluded by default.
- View permission is granted to managers, editing teachers, and teachers by default.
- Does not create its own personal-data tables.

## Installation

Copy the `cohortbadges` directory into:

`local/cohortbadges`

Then visit **Site administration > Notifications**.

Settings are available under **Site administration > Plugins > Local plugins > Cohort badges**.

## Moodle versions

Requires Moodle 5.0 or later.

## Continuous integration

The repository includes `.github/workflows/moodle-ci.yml`.
GitHub Actions tests the plugin against Moodle 5.0, 5.1, and 5.2 using PostgreSQL and supported PHP versions.

Blocking checks include:

- PHP lint
- Moodle CodeSniffer (`phpcs --max-warnings 0`)
- Moodle PHPDoc checker
- Plugin validation
- Upgrade savepoints
- Mustache lint
- Grunt / JavaScript lint
- PHPUnit (`--fail-on-warning`)

PHP Mess Detector is also executed as an advisory non-blocking check.
