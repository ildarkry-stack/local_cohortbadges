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

use local_cohortbadges\local\membership_manager;

/**
 * Tests for the cohort membership manager.
 *
 * @package    local_cohortbadges
 * @copyright  2026 MIA Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\local_cohortbadges\local\membership_manager::class)]
final class membership_manager_test extends \advanced_testcase {
    /**
     * The cohort name is used by default.
     *
     * @return void
     */
    public function test_format_label_defaults_to_name(): void {
        $this->resetAfterTest();
        unset_config('displayformat', 'local_cohortbadges');

        $this->assertSame('Students 2026', membership_manager::format_label('Students 2026', 'ST26'));
    }

    /**
     * The ID number format falls back to the cohort name when no ID number exists.
     *
     * @return void
     */
    public function test_format_label_idnumber_falls_back_to_name(): void {
        $this->resetAfterTest();
        set_config('displayformat', 'idnumber', 'local_cohortbadges');

        $this->assertSame('Students 2026', membership_manager::format_label('Students 2026', ''));
    }

    /**
     * The ID number format returns the ID number when it exists.
     *
     * @return void
     */
    public function test_format_label_uses_idnumber(): void {
        $this->resetAfterTest();
        set_config('displayformat', 'idnumber', 'local_cohortbadges');

        $this->assertSame('ST26', membership_manager::format_label('Students 2026', 'ST26'));
    }

    /**
     * The combined format contains both the cohort name and ID number.
     *
     * @return void
     */
    public function test_format_label_uses_name_and_idnumber(): void {
        $this->resetAfterTest();
        set_config('displayformat', 'nameandidnumber', 'local_cohortbadges');

        $this->assertSame(
            'Students 2026 (ST26)',
            membership_manager::format_label('Students 2026', 'ST26')
        );
    }

    /**
     * Membership metadata supports safe permission checks for profile quick actions.
     *
     * @return void
     */
    public function test_memberships_include_context_and_component_metadata(): void {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();
        require_once($CFG->dirroot . '/cohort/lib.php');

        $userid = $this->getDataGenerator()->create_user()->id;
        $contextid = \context_system::instance()->id;
        $cohortid = cohort_add_cohort((object) [
            'name' => 'Quick action cohort',
            'contextid' => $contextid,
        ]);
        cohort_add_member($cohortid, $userid);

        $memberships = membership_manager::get_memberships([$userid]);
        $this->assertCount(1, $memberships[$userid]);
        $this->assertSame($cohortid, $memberships[$userid][0]['id']);
        $this->assertSame($contextid, $memberships[$userid][0]['contextid']);
        $this->assertSame('', $memberships[$userid][0]['component']);
    }
    /**
     * Cohort visibility is controlled by plugin settings.
     *
     * @return void
     */
    public function test_hidden_cohorts_follow_display_setting(): void {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();
        require_once($CFG->dirroot . '/cohort/lib.php');

        $userid = $this->getDataGenerator()->create_user()->id;
        $contextid = \context_system::instance()->id;
        $visibleid = cohort_add_cohort((object) [
            'name' => 'Visible cohort',
            'contextid' => $contextid,
            'visible' => 1,
        ]);
        $hiddenid = cohort_add_cohort((object) [
            'name' => 'Hidden cohort',
            'contextid' => $contextid,
            'visible' => 0,
        ]);
        cohort_add_member($visibleid, $userid);
        cohort_add_member($hiddenid, $userid);

        set_config('showhidden', 0, 'local_cohortbadges');
        $memberships = membership_manager::get_memberships([$userid]);
        $this->assertCount(1, $memberships[$userid]);
        $this->assertSame($visibleid, $memberships[$userid][0]['id']);

        set_config('showhidden', 1, 'local_cohortbadges');
        $memberships = membership_manager::get_memberships([$userid]);
        $this->assertCount(2, $memberships[$userid]);
        $this->assertEqualsCanonicalizing(
            [$visibleid, $hiddenid],
            array_column($memberships[$userid], 'id')
        );
    }
}
