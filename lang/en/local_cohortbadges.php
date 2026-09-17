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
 * English strings for Cohort badges.
 *
 * @package    local_cohortbadges
 * @copyright  2026 MIA Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['cohortbadges:view'] = 'View user cohort membership information';
$string['displayformat'] = 'Badge text';
$string['displayformat_desc'] = 'Choose what is displayed for each cohort membership.';
$string['enabled'] = 'Enable cohort membership display';
$string['enabled_desc'] = 'Show cohort membership information in the user profile.';
$string['format_idnumber'] = 'Cohort ID number';
$string['format_name'] = 'Cohort name';
$string['format_nameandidnumber'] = 'Name and ID number';
$string['nameandidnumberformat'] = '{$a->name} ({$a->idnumber})';
$string['pluginname'] = 'Cohort memberships';
$string['privacy:metadata'] = 'The Cohort badges plugin does not store personal data. '
    . 'It only displays existing Moodle cohort membership information.';
$string['profileheading'] = 'Global groups';
$string['showhidden'] = 'Show hidden cohorts';
$string['showhidden_desc'] = 'If enabled, hidden cohorts are also displayed to users who have permission '
    . 'to view this information.';
$string['showinprofile'] = 'Show in user profile';
$string['showinprofile_desc'] = 'Show cohort memberships in the standard Moodle user profile.';
