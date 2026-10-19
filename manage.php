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
 * Cohort membership controls reached from the standard user profile.
 *
 * @package    local_cohortbadges
 * @copyright  2026 MIA Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_cohortbadges\local\cohort_management;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/user/lib.php');

$userid = required_param('userid', PARAM_INT);
$search = optional_param('search', '', PARAM_RAW_TRIMMED);
$page = max(0, optional_param('page', 0, PARAM_INT));
$action = optional_param('action', '', PARAM_ALPHA);
$cohortid = optional_param('cohortid', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

require_login();

$user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
$usercontext = context_user::instance($userid, MUST_EXIST);
if (!user_can_view_profile($user, null, $usercontext)) {
    throw new moodle_exception('nopermissions', 'error');
}
if (!cohort_management::enabled()) {
    throw new moodle_exception('managementdisabled', 'local_cohortbadges');
}

$contextids = cohort_management::assignable_contextids();
if (!$contextids) {
    require_capability('moodle/cohort:assign', context_system::instance());
    throw new moodle_exception('nopermissions', 'error');
}

$manageurl = new moodle_url('/local/cohortbadges/manage.php', ['userid' => $userid]);
$profileurl = new moodle_url('/user/profile.php', ['id' => $userid]);

$PAGE->set_url($manageurl);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('managecohorts', 'local_cohortbadges'));
$PAGE->set_heading(get_string('profileheading', 'local_cohortbadges'));
$PAGE->navbar->add(get_string('managecohorts', 'local_cohortbadges'));

if ($action !== '') {
    if ($action !== 'add' && $action !== 'remove') {
        throw new invalid_parameter_exception('Invalid cohort action.');
    }

    $cohort = cohort_management::require_editable_cohort($cohortid);
    if ($action === 'remove' && !$confirm) {
        $message = get_string('confirmremove', 'local_cohortbadges', (object) [
            'user' => fullname($user),
            'cohort' => format_string($cohort->name),
        ]);
        $confirmurl = new moodle_url('/local/cohortbadges/manage.php', [
            'userid' => $userid,
            'cohortid' => $cohortid,
            'action' => 'remove',
            'confirm' => 1,
        ]);
        echo $OUTPUT->header();
        echo $OUTPUT->confirm(
            $message,
            new single_button($confirmurl, get_string('remove'), 'post'),
            $manageurl
        );
        echo $OUTPUT->footer();
        exit;
    }

    if (!data_submitted()) {
        throw new moodle_exception('invalidrequest', 'error');
    }
    require_sesskey();
    cohort_management::change_membership($userid, $cohortid, $action);
    redirect($manageurl, get_string('membershipupdated', 'local_cohortbadges'));
}

$search = core_text::substr($search, 0, 100);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('managecohorts', 'local_cohortbadges'));
echo html_writer::tag('p', s(fullname($user)));
echo html_writer::link($profileurl, get_string('backtoprofile', 'local_cohortbadges'));

echo $OUTPUT->heading(get_string('currentmemberships', 'local_cohortbadges'), 3);
$memberships = cohort_management::editable_memberships($userid, $contextids);
if (!$memberships) {
    echo $OUTPUT->notification(get_string('noeditablememberships', 'local_cohortbadges'), 'info');
} else {
    $table = new html_table();
    $table->head = [
        get_string('name'),
        get_string('idnumber'),
        get_string('actions'),
    ];
    foreach ($memberships as $cohort) {
        $context = context::instance_by_id((int) $cohort->contextid);
        $removeurl = new moodle_url('/local/cohortbadges/manage.php', [
            'userid' => $userid,
            'cohortid' => $cohort->id,
            'action' => 'remove',
        ]);
        $table->data[] = [
            format_string($cohort->name, true, ['context' => $context]),
            s($cohort->idnumber),
            $OUTPUT->single_button($removeurl, get_string('remove'), 'get'),
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->heading(get_string('addtocohort', 'local_cohortbadges'), 3);
echo html_writer::start_tag('form', [
    'method' => 'get',
    'action' => $manageurl->out(false),
    'class' => 'local-cohortbadges-search',
]);
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'userid',
    'value' => $userid,
]);
echo html_writer::tag('label', get_string('searchcohorts', 'local_cohortbadges'), [
    'for' => 'local-cohortbadges-query',
]);
echo html_writer::empty_tag('input', [
    'type' => 'search',
    'id' => 'local-cohortbadges-query',
    'name' => 'search',
    'value' => $search,
    'maxlength' => '100',
    'class' => 'form-control',
]);
echo html_writer::tag('button', get_string('search'), [
    'type' => 'submit',
    'class' => 'btn btn-secondary',
]);
echo html_writer::end_tag('form');

if ($search !== '') {
    $result = cohort_management::search_available($userid, $contextids, $search, $page);
    if (!$result['cohorts']) {
        echo $OUTPUT->notification(get_string('noavailablecohorts', 'local_cohortbadges'), 'info');
    } else {
        $table = new html_table();
        $table->head = [
            get_string('name'),
            get_string('idnumber'),
            get_string('actions'),
        ];
        foreach ($result['cohorts'] as $cohort) {
            $context = context::instance_by_id((int) $cohort->contextid);
            $addurl = new moodle_url('/local/cohortbadges/manage.php', [
                'userid' => $userid,
                'cohortid' => $cohort->id,
                'action' => 'add',
            ]);
            $table->data[] = [
                format_string($cohort->name, true, ['context' => $context]),
                s($cohort->idnumber),
                $OUTPUT->single_button($addurl, get_string('add'), 'post'),
            ];
        }
        echo html_writer::table($table);
    }
    $pageurl = new moodle_url('/local/cohortbadges/manage.php', [
        'userid' => $userid,
        'search' => $search,
    ]);
    echo $OUTPUT->paging_bar($result['total'], $page, 20, $pageurl);
}
echo $OUTPUT->footer();
