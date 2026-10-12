# Change log

## 1.0.7 - 2026-10-12

- Included the complete, unmodified GNU GPL v3 licence text in `LICENSE` to address Moodle Marketplace review MMR-293.
- Added direct public source repository and plugin-specific issue tracker links to the README.
- No runtime behaviour or database schema changes.

## 1.0.6 - 2026-09-17

- Prepared the plugin for public Marketplace release.
- Removed obsolete empty hook and external-service definition files.
- Removed bundled non-English language pack; translations should be maintained through Moodle AMOS after publication.
- Updated English privacy string to use a single supported language-string assignment.
- Expanded installation, configuration, privacy, permissions, and support documentation.

## 1.0.5 - 2026-09-17

- Removed cohort badges from the Course > Participants table.
- Removed the participant-table JavaScript enhancer.
- Removed the AJAX external function used by the participants table.
- Removed the output hook used to load participant-table code.
- Kept cohort membership display in the standard user profile.
- Updated plugin settings and language strings to match the reduced scope.

## 1.0.4 - 2026-09-17

- Fixed participant-table JavaScript initialisation through Moodle AMD footer handling.
