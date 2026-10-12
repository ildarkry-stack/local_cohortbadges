# Cohort memberships in profile (`local_cohortbadges`)

Cohort memberships is a lightweight local plugin for Moodle 5.x that displays a user's existing cohort memberships in the standard Moodle user profile.

The plugin is read-only. It does not create, edit, enrol, or synchronise cohorts and it does not modify Moodle core.

## Features

- Displays cohort memberships in the standard user profile.
- Configurable label format: cohort name, cohort ID number, or both.
- Hidden cohorts are excluded by default.
- Users can view their own cohort memberships.
- Staff access follows Moodle capabilities and course context.
- No external services, API keys, subscriptions, or scheduled tasks are required.
- No custom database tables are created.
- No personal data is stored by the plugin.

## Requirements

- Moodle 5.0, 5.1, or 5.2.
- PHP version supported by the corresponding Moodle release.

## Installation

### From ZIP

1. Download the release ZIP.
2. In Moodle, go to **Site administration > Plugins > Install plugins**.
3. Upload the ZIP and complete the installation.
4. Visit **Site administration > Notifications** if prompted.

### Manual installation

Copy the `cohortbadges` directory to:

```text
local/cohortbadges
```

Then visit **Site administration > Notifications**.

## Configuration

Go to:

**Site administration > Plugins > Local plugins > Cohort memberships**

Available settings:

- Enable or disable cohort membership display.
- Choose the displayed label format: cohort name, ID number, or both.
- Choose whether hidden cohorts may be included.
- Enable or disable display in user profiles.

## Permissions

The plugin defines the capability:

```text
local/cohortbadges:view
```

It is available at course context and is allowed by default for managers, editing teachers, and teachers. Users can always see their own cohort memberships. When a profile is viewed outside a course context, Moodle's standard `moodle/cohort:view` capability is used.

## Privacy

The plugin does not create its own personal-data tables and does not send data to external services. It reads existing Moodle cohort membership information and displays it in the user profile. A Moodle Privacy API null provider is included.

## Dependencies

None beyond Moodle core.

## Support and issues

Report bugs and suggest improvements in the public, plugin-specific issue tracker:

https://github.com/ildarkry-stack/local_cohortbadges/issues

Public source code repository:

https://github.com/ildarkry-stack/local_cohortbadges

## License

GNU GPL v3 or later. See the full licence text in [LICENSE](LICENSE).
