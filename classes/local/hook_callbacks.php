<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_cohortbadges\local;

use core\hook\output\before_standard_head_html_generation;

/**
 * Output hook callbacks.
 *
 * @package    local_cohortbadges
 * @copyright  2026 MIA Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class hook_callbacks {
    /**
     * Load the participants page enhancer when appropriate.
     *
     * @param before_standard_head_html_generation $hook Hook instance.
     * @return void
     */
    public static function before_standard_head_html_generation(
        before_standard_head_html_generation $hook
    ): void {
        global $PAGE;

        if (during_initial_install()) {
            return;
        }

        $enabled = get_config('local_cohortbadges', 'enabled');
        if ($enabled !== false && !(bool) $enabled) {
            return;
        }

        if ($PAGE->pagetype !== 'course-view-participants' || empty($PAGE->course->id)) {
            return;
        }

        $context = \context_course::instance((int) $PAGE->course->id, IGNORE_MISSING);
        if (!$context || !has_capability('local/cohortbadges:view', $context)) {
            return;
        }

        $PAGE->requires->js_call_amd(
            'local_cohortbadges/participants',
            'init',
            [(int) $PAGE->course->id, get_string('cohortsaria', 'local_cohortbadges')]
        );
    }
}
