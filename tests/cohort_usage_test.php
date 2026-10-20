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

namespace local_cohortbadges;

use local_cohortbadges\local\cohort_usage;

/**
 * Tests for cohort sync course usage display.
 *
 * @package    local_cohortbadges
 * @copyright  2026 MIA Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\local_cohortbadges\local\cohort_usage::class)]
final class cohort_usage_test extends \advanced_testcase {
    /**
     * Existing enrolment instances are grouped per course and reflect instance status.
     *
     * @return void
     */
    public function test_course_usages_group_instances_and_statuses(): void {
        global $DB, $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();
        require_once($CFG->dirroot . '/cohort/lib.php');

        $generator = $this->getDataGenerator();
        $cohort = $generator->create_cohort();
        $othercohort = $generator->create_cohort();
        $first = $generator->create_course(['fullname' => 'Course A']);
        $second = $generator->create_course(['fullname' => 'Course B']);

        $DB->insert_record('enrol', (object) [
            'enrol' => 'cohort',
            'courseid' => $first->id,
            'customint1' => $cohort->id,
            'status' => ENROL_INSTANCE_ENABLED,
        ]);
        $DB->insert_record('enrol', (object) [
            'enrol' => 'cohort',
            'courseid' => $first->id,
            'customint1' => $cohort->id,
            'status' => ENROL_INSTANCE_DISABLED,
        ]);
        $DB->insert_record('enrol', (object) [
            'enrol' => 'cohort',
            'courseid' => $second->id,
            'customint1' => $cohort->id,
            'status' => ENROL_INSTANCE_DISABLED,
        ]);
        $DB->insert_record('enrol', (object) [
            'enrol' => 'cohort',
            'courseid' => $second->id,
            'customint1' => $othercohort->id,
            'status' => ENROL_INSTANCE_ENABLED,
        ]);

        $usages = cohort_usage::get_course_usages([$cohort->id]);
        $this->assertCount(2, $usages[$cohort->id]);
        $this->assertSame(1, $usages[$cohort->id][$first->id]['enabled']);
        $this->assertSame(1, $usages[$cohort->id][$first->id]['disabled']);
        $this->assertSame(0, $usages[$cohort->id][$second->id]['enabled']);
        $this->assertSame(1, $usages[$cohort->id][$second->id]['disabled']);
        $this->assertArrayNotHasKey($othercohort->id, $usages);
    }

    /**
     * Students must not see cohort sync configuration, even for their own cohort.
     *
     * @return void
     */
    public function test_regular_user_cannot_read_course_usage(): void {
        global $DB, $CFG;

        $this->resetAfterTest();
        require_once($CFG->dirroot . '/cohort/lib.php');

        $generator = $this->getDataGenerator();
        $cohort = $generator->create_cohort();
        $course = $generator->create_course();
        $DB->insert_record('enrol', (object) [
            'enrol' => 'cohort',
            'courseid' => $course->id,
            'customint1' => $cohort->id,
            'status' => ENROL_INSTANCE_ENABLED,
        ]);

        $student = $generator->create_user();
        $this->setUser($student);
        $this->assertFalse(cohort_usage::can_view_cohort(\context_system::instance()->id));
        $this->assertSame([], cohort_usage::get_course_usages([$cohort->id]));
    }

    /**
     * A cohort viewer sees only course instances they can configure.
     *
     * @return void
     */
    public function test_course_capability_limits_visible_results(): void {
        global $DB, $CFG;

        $this->resetAfterTest();
        require_once($CFG->dirroot . '/cohort/lib.php');

        $generator = $this->getDataGenerator();
        $cohort = $generator->create_cohort();
        $visiblecourse = $generator->create_course();
        $restrictedcourse = $generator->create_course();
        $hiddencourse = $generator->create_course(['visible' => 0]);
        foreach ([$visiblecourse, $restrictedcourse, $hiddencourse] as $course) {
            $DB->insert_record('enrol', (object) [
                'enrol' => 'cohort',
                'courseid' => $course->id,
                'customint1' => $cohort->id,
                'status' => ENROL_INSTANCE_ENABLED,
            ]);
        }

        $staff = $generator->create_user();
        $roleid = create_role('Cohort sync viewer', 'cohortsyncviewer', '');
        $systemcontext = \context_system::instance();
        assign_capability('moodle/cohort:view', CAP_ALLOW, $roleid, $systemcontext->id);
        assign_capability('enrol/cohort:config', CAP_ALLOW, $roleid, $systemcontext->id);
        role_assign($roleid, $staff->id, $systemcontext->id);
        $this->setUser($staff);

        $usages = cohort_usage::get_course_usages([$cohort->id]);
        $this->assertArrayHasKey($visiblecourse->id, $usages[$cohort->id]);
        $this->assertArrayHasKey($restrictedcourse->id, $usages[$cohort->id]);
        $this->assertArrayNotHasKey($hiddencourse->id, $usages[$cohort->id]);

        unassign_capability('enrol/cohort:config', $roleid, $systemcontext->id);
        $this->assertSame([], cohort_usage::get_course_usages([$cohort->id]));
    }

    /**
     * An empty cohort list causes no cohort-enrolment lookups.
     *
     * @return void
     */
    public function test_empty_input_returns_empty_result(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->assertSame([], cohort_usage::get_course_usages([]));
    }
}
