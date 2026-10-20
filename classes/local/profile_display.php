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

namespace local_cohortbadges\local;

/**
 * Build the user profile cohort membership section.
 *
 * @package    local_cohortbadges
 * @copyright  2026 MIA Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class profile_display {
    /**
     * Add the cohort section to the standard Moodle user profile.
     *
     * @param \core_user\output\myprofile\tree $tree Profile tree.
     * @param \stdClass $user Profile owner.
     * @param \stdClass|null $course Course, if any.
     * @param string $viewer Self or other, from Moodle's profile callback.
     * @return void
     */
    public static function add_to_tree(
        \core_user\output\myprofile\tree $tree,
        \stdClass $user,
        $course,
        string $viewer
    ): void {
        if (!self::is_enabled()) {
            return;
        }

        $canview = self::can_view($viewer, $course);
        $canmanage = self::can_manage();
        if (!$canview && !$canmanage) {
            return;
        }

        $userid = (int) $user->id;
        $cohorts = self::load_memberships($userid, $canview);
        if (!$cohorts && !$canmanage) {
            return;
        }

        $content = self::render_content($cohorts, $userid, $canmanage, $canview);

        $node = new \core_user\output\myprofile\node(
            'miscellaneous',
            'local_cohortbadges_memberships',
            get_string('profileheading', 'local_cohortbadges'),
            null,
            null,
            $content
        );
        $tree->add_node($node);
    }

    /**
     * Retrieve existing cohort membership badges if profile viewing is permitted.
     *
     * @param int $userid Profile user identifier.
     * @param bool $canview Whether membership labels may be shown.
     * @return array
     */
    private static function load_memberships(int $userid, bool $canview): array {
        if (!$canview) {
            return [];
        }
        $memberships = membership_manager::get_memberships([$userid]);
        return $memberships[$userid] ?? [];
    }

    /**
     * Build profile content including optional management and read-only course usage.
     *
     * @param array $cohorts Current visible memberships.
     * @param int $userid Profile owner identifier.
     * @param bool $canmanage Whether membership may be edited.
     * @param bool $canview Whether membership labels may be shown.
     * @return string
     */
    private static function render_content(array $cohorts, int $userid, bool $canmanage, bool $canview): string {
        $mode = $canmanage ? 'manage' : 'read';
        $usages = self::load_course_usages($cohorts, $canview);
        $content = self::render_badges($cohorts, $userid, $mode, $usages);
        if ($canmanage) {
            $content .= self::render_manage_button($userid);
        }
        return $content;
    }

    /**
     * Load all cohort sync course instances with a single database query.
     *
     * @param array $cohorts Current visible memberships.
     * @param bool $canview Whether membership labels may be shown.
     * @return array
     */
    private static function load_course_usages(array $cohorts, bool $canview): array {
        if (!$canview || !$cohorts) {
            return [];
        }
        $setting = get_config('local_cohortbadges', 'showcohortcourses');
        if ($setting !== false && !(bool) $setting) {
            return [];
        }
        return cohort_usage::get_course_usages(array_column($cohorts, 'id'));
    }

    /**
     * Check display settings, retaining compatibility with unset legacy defaults.
     *
     * @return bool
     */
    private static function is_enabled(): bool {
        $enabled = get_config('local_cohortbadges', 'enabled');
        $showinprofile = get_config('local_cohortbadges', 'showinprofile');
        return ($enabled === false || (bool) $enabled)
            && ($showinprofile === false || (bool) $showinprofile);
    }

    /**
     * Check profile viewing permissions without exposing other users' memberships.
     *
     * @param string $viewer Self or other.
     * @param \stdClass|null $course Course context when known.
     * @return bool
     */
    private static function can_view(string $viewer, $course): bool {
        if ($viewer === 'self') {
            return true;
        }
        if ($course && !empty($course->id)) {
            $context = \context_course::instance((int) $course->id, IGNORE_MISSING);
            return $context && has_capability('local/cohortbadges:view', $context);
        }
        return has_capability('moodle/cohort:view', \context_system::instance());
    }

    /**
     * Check the optional management setting and coarse-grained capability.
     *
     * The management endpoint separately checks capability in each cohort's context.
     *
     * @return bool
     */
    private static function can_manage(): bool {
        if (!cohort_management::enabled()) {
            return false;
        }
        return has_capability('moodle/cohort:assign', \context_system::instance())
            || \core_course_category::has_capability_on_any('moodle/cohort:assign');
    }

    /**
     * Render cohort badges, optionally with permitted removal links.
     *
     * @param array $cohorts Existing cohort memberships.
     * @param int $userid Profile owner identifier.
     * @param string $mode Either read or manage.
     * @param array $usages Visible cohort sync course mappings.
     * @return string
     */
    private static function render_badges(array $cohorts, int $userid, string $mode, array $usages): string {
        $labels = [];
        foreach ($cohorts as $cohort) {
            $labels[] = self::render_badge($cohort, $userid, $mode, $usages);
        }
        return \html_writer::div(implode(' ', $labels), 'local-cohortbadges-profile-list');
    }

    /**
     * Render an individual cohort badge and its optional removal action.
     *
     * @param array $cohort Cohort membership record.
     * @param int $userid Profile owner identifier.
     * @param string $mode Either read or manage.
     * @param array $usages Visible cohort sync course mappings.
     * @return string
     */
    private static function render_badge(array $cohort, int $userid, string $mode, array $usages): string {
        $badge = \html_writer::span(
            s($cohort['label']),
            'badge rounded-pill text-bg-secondary local-cohortbadges-profile-badge'
        );
        $context = null;
        if ($mode === 'manage' && empty($cohort['component'])) {
            $context = \context::instance_by_id((int) $cohort['contextid'], IGNORE_MISSING);
        }

        if ($context && has_capability('moodle/cohort:assign', $context)) {
            $removeurl = new \moodle_url('/local/cohortbadges/manage.php', [
                'userid' => $userid,
                'cohortid' => (int) $cohort['id'],
                'action' => 'remove',
            ]);
            $badge .= \html_writer::link($removeurl, get_string('remove'), [
                'class' => 'local-cohortbadges-profile-remove',
                'aria-label' => get_string('removefromcohort', 'local_cohortbadges', $cohort['name']),
            ]);
        }

        $content = \html_writer::div($badge, 'local-cohortbadges-profile-item');
        $showcourses = get_config('local_cohortbadges', 'showcohortcourses');
        if ($showcourses === false || (bool) $showcourses) {
            if (cohort_usage::can_view_cohort((int) $cohort['contextid'])) {
                $content .= self::render_cohort_courses($usages[$cohort['id']] ?? []);
            }
        }
        return \html_writer::div($content, 'local-cohortbadges-profile-cohort');
    }

    /**
     * Render the link to the cohort search and membership management page.
     *
     * @param int $userid Profile owner identifier.
     * @return string
     */
    private static function render_manage_button(int $userid): string {
        $url = new \moodle_url('/local/cohortbadges/manage.php', ['userid' => $userid]);
        return \html_writer::div(
            \html_writer::link($url, get_string('managecohorts', 'local_cohortbadges'), [
                'class' => 'btn btn-secondary btn-sm',
            ]),
            'local-cohortbadges-profile-manage'
        );
    }

    /**
     * Display a collapsible list of courses that use this cohort for enrolment.
     *
     * @param array $courses Visible and grouped cohort-sync instances.
     * @return string
     */
    private static function render_cohort_courses(array $courses): string {
        $items = [];
        foreach ($courses as $course) {
            $context = \context_course::instance((int) $course['id']);
            $name = format_string($course['fullname'], true, ['context' => $context]);
            $url = new \moodle_url('/course/view.php', ['id' => $course['id']]);
            $statuskey = $course['enabled'] ? 'syncenabled' : 'syncdisabled';
            $label = get_string($statuskey, 'local_cohortbadges');
            $items[] = \html_writer::tag(
                'li',
                \html_writer::link($url, $name) . ' — ' . s($label)
            );
        }
        if (!$items) {
            $items[] = \html_writer::tag('li', get_string('nocohortcourses', 'local_cohortbadges'));
        }
        $summary = get_string('cohortcoursescount', 'local_cohortbadges', count($courses));
        return \html_writer::tag(
            'details',
            \html_writer::tag('summary', $summary)
                . \html_writer::tag('ul', implode('', $items)),
            ['class' => 'local-cohortbadges-sync-courses']
        );
    }
}
