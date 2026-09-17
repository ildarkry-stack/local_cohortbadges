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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Settings for Cohort badges.
 *
 * @package    local_cohortbadges
 * @copyright  2026 MIA Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_cohortbadges', get_string('pluginname', 'local_cohortbadges'));

    $settings->add(new admin_setting_configcheckbox(
        'local_cohortbadges/enabled',
        get_string('enabled', 'local_cohortbadges'),
        get_string('enabled_desc', 'local_cohortbadges'),
        1
    ));

    $settings->add(new admin_setting_configselect(
        'local_cohortbadges/displayformat',
        get_string('displayformat', 'local_cohortbadges'),
        get_string('displayformat_desc', 'local_cohortbadges'),
        'name',
        [
            'name' => get_string('format_name', 'local_cohortbadges'),
            'idnumber' => get_string('format_idnumber', 'local_cohortbadges'),
            'nameandidnumber' => get_string('format_nameandidnumber', 'local_cohortbadges'),
        ]
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_cohortbadges/showhidden',
        get_string('showhidden', 'local_cohortbadges'),
        get_string('showhidden_desc', 'local_cohortbadges'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_cohortbadges/showinprofile',
        get_string('showinprofile', 'local_cohortbadges'),
        get_string('showinprofile_desc', 'local_cohortbadges'),
        1
    ));

    $ADMIN->add('localplugins', $settings);
}
