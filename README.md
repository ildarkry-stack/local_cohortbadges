# Cohort memberships (`local_cohortbadges`)

A small Moodle 5.x local plugin that displays a user's cohort memberships in the standard Moodle user profile.

## Features

- Works without modifying Moodle core.
- Displays cohort memberships in the standard user profile.
- Configurable display text: cohort name, ID number, or both.
- Hidden cohorts are excluded by default.
- Users can always view their own cohort memberships.
- Managers and permitted staff can view memberships according to Moodle capabilities.
- Does not create its own personal-data tables.
- Does not modify course participant tables.

## Installation

Copy the `cohortbadges` directory into:

`local/cohortbadges`

Then visit **Site administration > Notifications**.

Settings are available under **Site administration > Plugins > Local plugins > Cohort memberships**.

## Moodle versions

Requires Moodle 5.0 or later.

## Continuous integration

The repository includes `.github/workflows/moodle-ci.yml`.
GitHub Actions tests the plugin against Moodle 5.0, 5.1, and 5.2 using PostgreSQL and supported PHP versions.
