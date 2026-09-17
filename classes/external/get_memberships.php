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

namespace local_cohortbadges\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_cohortbadges\local\membership_manager;

/**
 * AJAX endpoint for cohort memberships.
 *
 * @package    local_cohortbadges
 * @copyright  2026 MIA Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class get_memberships extends external_api {
    /**
     * Parameters definition.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id'),
            'userids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'User id'),
                'User ids currently displayed in the participants table'
            ),
        ]);
    }

    /**
     * Return memberships.
     *
     * @param int $courseid Course id.
     * @param int[] $userids User ids.
     * @return array
     */
    public static function execute(int $courseid, array $userids): array {
        global $CFG, $DB, $USER;

        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/group/lib.php');

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'userids' => $userids,
        ]);

        $context = \context_course::instance($params['courseid'], MUST_EXIST);
        self::validate_context($context);
        require_capability('local/cohortbadges:view', $context);

        $userids = array_values(array_unique(array_map('intval', $params['userids'])));
        if (count($userids) > 500) {
            throw new \moodle_exception('toomanyusers', 'local_cohortbadges');
        }

        if ($userids) {
            [$insql, $sqlparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'participant');
            $sqlparams['courseid'] = $params['courseid'];
            $allowedids = $DB->get_fieldset_sql(
                "SELECT DISTINCT u.id
                   FROM {user} u
                   JOIN {user_enrolments} ue ON ue.userid = u.id
                   JOIN {enrol} e ON e.id = ue.enrolid
                  WHERE e.courseid = :courseid
                    AND u.id {$insql}",
                $sqlparams
            );
            $userids = array_values(array_intersect($userids, array_map('intval', $allowedids)));
        }

        $course = get_course($params['courseid']);
        if (
            $userids
            && $course->groupmode == SEPARATEGROUPS
            && !has_capability('moodle/site:accessallgroups', $context)
        ) {
            $groups = groups_get_all_groups($course->id, $USER->id, 0, 'g.id');
            if (!$groups) {
                $userids = [];
            } else {
                [$usersql, $userparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'groupuser');
                [$groupsql, $groupparams] = $DB->get_in_or_equal(array_keys($groups), SQL_PARAMS_NAMED, 'groupid');
                $visibleids = $DB->get_fieldset_sql(
                    "SELECT DISTINCT gm.userid
                       FROM {groups_members} gm
                      WHERE gm.userid {$usersql}
                        AND gm.groupid {$groupsql}",
                    $userparams + $groupparams
                );
                $userids = array_values(array_intersect($userids, array_map('intval', $visibleids)));
            }
        }

        $showhidden = (bool) get_config('local_cohortbadges', 'showhidden');
        $memberships = membership_manager::get_memberships($userids, $showhidden);
        $result = [];
        foreach ($userids as $userid) {
            $result[] = [
                'userid' => $userid,
                'cohorts' => array_values($memberships[$userid] ?? []),
            ];
        }

        return $result;
    }

    /**
     * Return structure definition.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'userid' => new external_value(PARAM_INT, 'User id'),
                'cohorts' => new external_multiple_structure(
                    new external_single_structure([
                        'id' => new external_value(PARAM_INT, 'Cohort id'),
                        'name' => new external_value(PARAM_TEXT, 'Cohort name'),
                        'idnumber' => new external_value(PARAM_RAW, 'Cohort idnumber'),
                        'visible' => new external_value(PARAM_BOOL, 'Whether the cohort is visible'),
                        'label' => new external_value(PARAM_TEXT, 'Formatted display label'),
                    ])
                ),
            ])
        );
    }
}
