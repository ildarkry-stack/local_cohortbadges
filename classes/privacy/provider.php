<?php
// This file is part of Moodle - http://moodle.org/

namespace local_cohortbadges\privacy;

use core_privacy\local\metadata\null_provider;

/**
 * Privacy provider for Cohort badges.
 *
 * @package    local_cohortbadges
 * @copyright  2026 MIA Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class provider implements null_provider {
    /**
     * Explain why no personal data is stored by this plugin.
     *
     * @return string
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
