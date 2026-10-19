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
 * Read-only lookup of cohort sync enrolment instances.
 *
 * @package    local_cohortbadges
 * @copyright  2026 MIA Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class cohort_usage {
    /**
     * Check permission to inspect the use of a cohort in course enrolment settings.
     *
     * @param int $contextid Context owning the cohort.
     * @return bool
     */
    public static function can_view_cohort(int $contextid): bool {
        $context = \context::instance_by_id($contextid, IGNORE_MISSING);
        return $context && (
            has_capability('moodle/cohort:view', $context)
            || has_capability('moodle/cohort:assign', $context)
        );
    }

    /**
     * Query configured cohort sync instances for multiple cohorts in one batch.
     *
     * Only course enrolment managers who may inspect the cohort can see the data.
     * Disabled instances are included and labelled; user enrolment is not implied.
     *
     * @param int[] $cohortids Cohorts to inspect.
     * @return array Cohort ID => course ID => course and enrolment instance metadata.
     */
    public static function get_course_usages(array $cohortids): array {
        global $DB;

        $cohortids = array_values(array_unique(array_filter(array_map('intval', $cohortids))));
        if (!$cohortids) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal($cohortids, SQL_PARAMS_NAMED, 'cid');
        $params['enroltype'] = 'cohort';
        $sql = "SELECT e.id, e.customint1 AS cohortid, e.status, co.contextid,
                       c.id AS courseid, c.fullname, c.shortname, c.visible
                  FROM {enrol} e
                  JOIN {course} c ON c.id = e.courseid
                  JOIN {cohort} co ON co.id = e.customint1
                 WHERE e.enrol = :enroltype AND e.customint1 {$insql}
              ORDER BY e.customint1, c.fullname, c.id, e.id";
        $instances = $DB->get_records_sql($sql, $params);
        $usage = [];
        $contexts = [];
        $cohortpermissions = [];

        foreach ($instances as $instance) {
            $cohortid = (int) $instance->cohortid;
            if (!array_key_exists($cohortid, $cohortpermissions)) {
                $cohortpermissions[$cohortid] = self::can_view_cohort((int) $instance->contextid);
            }
            if (!$cohortpermissions[$cohortid]) {
                continue;
            }
            $courseid = (int) $instance->courseid;
            if (!array_key_exists($courseid, $contexts)) {
                $contexts[$courseid] = \context_course::instance($courseid, IGNORE_MISSING) ?: null;
            }
            $context = $contexts[$courseid];
            if (!self::can_view_course($instance, $context)) {
                continue;
            }
            self::add_instance($usage, $instance);
        }
        return $usage;
    }

    /**
     * Require the right to manage this enrolment method, including hidden-course visibility.
     *
     * @param \stdClass $instance Cohort enrolment instance joined with course.
     * @param \context|null $context Course context.
     * @return bool
     */
    private static function can_view_course(\stdClass $instance, ?\context $context): bool {
        if (!$context || !has_capability('enrol/cohort:config', $context)) {
            return false;
        }
        return (bool) $instance->visible
            || has_capability('moodle/course:viewhiddencourses', $context);
    }

    /**
     * Group multiple enrolment instances belonging to the same course.
     *
     * @param array $usage Accumulated usage records.
     * @param \stdClass $instance Instance joined with course.
     * @return void
     */
    private static function add_instance(array &$usage, \stdClass $instance): void {
        $cohortid = (int) $instance->cohortid;
        $courseid = (int) $instance->courseid;
        if (!isset($usage[$cohortid][$courseid])) {
            $usage[$cohortid][$courseid] = [
                'id' => $courseid,
                'fullname' => (string) $instance->fullname,
                'shortname' => (string) $instance->shortname,
                'enabled' => 0,
                'disabled' => 0,
            ];
        }
        if ((int) $instance->status === ENROL_INSTANCE_ENABLED) {
            $usage[$cohortid][$courseid]['enabled']++;
        } else {
            $usage[$cohortid][$courseid]['disabled']++;
        }
    }
}
