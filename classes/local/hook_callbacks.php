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

use core\hook\output\before_standard_footer_html_generation;

/**
 * Output hook callbacks.
 *
 * @package    local_cohortbadges
 * @copyright  2026 MIA Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class hook_callbacks {
    /**
     * Load the participants page enhancer in the footer.
     *
     * Loading the script here guarantees that the participants table has already
     * been rendered before the enhancement starts.
     *
     * @param before_standard_footer_html_generation $hook Hook instance.
     * @return void
     */
    public static function before_standard_footer_html_generation(
        before_standard_footer_html_generation $hook
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

        $url = new \moodle_url('/local/cohortbadges/participants.js', [
            'v' => '2026091703',
        ]);
        $hook->add_html(\html_writer::tag('script', '', [
            'src' => $url->out(false),
        ]));
    }
}
