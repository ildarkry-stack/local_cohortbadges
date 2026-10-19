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
    $viewer = $iscurrentuser ? 'self' : 'other';
    \local_cohortbadges\local\profile_display::add_to_tree($tree, $user, $course, $viewer);
}
