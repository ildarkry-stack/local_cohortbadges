<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Library callbacks for Cohort badges.
 *
 * @package    local_cohortbadges
 * @copyright  2026 MIA Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Add cohort memberships to the user profile page.
 *
 * @param \core_user\output\myprofile\tree $tree Profile tree.
 * @param stdClass $user Profile owner.
 * @param bool $iscurrentuser Whether this is the current user's profile.
 * @param stdClass|null $course Course, when profile is opened in course context.
 * @return void
 */
function local_cohortbadges_myprofile_navigation(
    core_user\output\myprofile\tree $tree,
    stdClass $user,
    bool $iscurrentuser,
    $course
): void {
    $enabled = get_config('local_cohortbadges', 'enabled');
    $showinprofile = get_config('local_cohortbadges', 'showinprofile');
    if (
        ($enabled !== false && !(bool) $enabled)
        || ($showinprofile !== false && !(bool) $showinprofile)
    ) {
        return;
    }

    $canview = $iscurrentuser;
    if ($course && !empty($course->id)) {
        $context = context_course::instance((int) $course->id, IGNORE_MISSING);
        $canview = $canview || ($context && has_capability('local/cohortbadges:view', $context));
    } else {
        $canview = $canview || has_capability('moodle/cohort:view', context_system::instance());
    }

    $canmanage = \local_cohortbadges\local\cohort_management::enabled()
        && (has_capability('moodle/cohort:assign', context_system::instance())
            || \core_course_category::has_capability_on_any('moodle/cohort:assign'));
    if (!$canview && !$canmanage) {
        return;
    }

    $showhidden = (bool) get_config('local_cohortbadges', 'showhidden');
    $cohorts = [];
    if ($canview) {
        $memberships = \local_cohortbadges\local\membership_manager::get_memberships([(int) $user->id], $showhidden);
        $cohorts = $memberships[(int) $user->id] ?? [];
    }
    if (!$cohorts && !$canmanage) {
        return;
    }

    $labels = [];
    foreach ($cohorts as $cohort) {
        $labels[] = html_writer::span(
            s($cohort['label']),
            'badge rounded-pill text-bg-secondary local-cohortbadges-profile-badge'
        );
    }
    $content = html_writer::div(implode(' ', $labels), 'local-cohortbadges-profile-list');
    if ($canmanage) {
        $url = new moodle_url('/local/cohortbadges/manage.php', ['userid' => (int) $user->id]);
        $content .= html_writer::div(
            html_writer::link($url, get_string('managecohorts', 'local_cohortbadges'), [
                'class' => 'btn btn-secondary btn-sm',
            ]),
            'local-cohortbadges-profile-manage'
        );
    }

    $node = new core_user\output\myprofile\node(
        'miscellaneous',
        'local_cohortbadges_memberships',
        get_string('profileheading', 'local_cohortbadges'),
        null,
        null,
        $content
    );
    $tree->add_node($node);
}
