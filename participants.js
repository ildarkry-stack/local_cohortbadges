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
 * Add cohort badges to the Moodle participants table.
 *
 * @module     local_cohortbadges/participants
 * @copyright  2026 MIA Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

(function() {
    'use strict';

    const form = document.querySelector('#participantsform[data-course-id]');
    const table = document.querySelector('#participants');
    if (!form || !table) {
        return;
    }

    const courseId = Number(form.dataset.courseId || 0);
    if (!courseId) {
        return;
    }

    // Expose a tiny diagnostic marker in the browser console for support.
    window.localCohortBadgesLoaded = true;

    require(['core/ajax'], function(Ajax) {
        const cache = new Map();
        let timer = null;
        let loading = false;

        /**
         * Get the user id represented by a participant row.
         *
         * @param {HTMLElement} row Participant table row.
         * @returns {Number}
         */
        const getUserId = row => {
            const checkbox = row.querySelector('input.usercheckbox[id^="user"]');
            if (checkbox) {
                const match = checkbox.id.match(/^user(\d+)$/);
                if (match) {
                    return Number(match[1]);
                }
            }

            // Fallback for themes which alter the checkbox markup.
            const profileLink = row.querySelector(
                'a[href*="/user/view.php?id="], a[href*="/user/profile.php?id="]'
            );
            if (!profileLink) {
                return 0;
            }

            try {
                const url = new URL(profileLink.href, window.location.href);
                return Number(url.searchParams.get('id') || 0);
            } catch (error) {
                return 0;
            }
        };

        /**
         * Find the fullname cell without depending on a specific theme column class.
         *
         * @param {HTMLElement} row Participant table row.
         * @returns {HTMLElement|null}
         */
        const getNameCell = row => {
            const profileLink = row.querySelector(
                'a[href*="/user/view.php?id="], a[href*="/user/profile.php?id="]'
            );
            if (profileLink) {
                return profileLink.closest('th, td');
            }

            return row.querySelector('th.c1, td.c1, th.cell.c1, td.cell.c1');
        };

        /**
         * Render badges for one participant row.
         *
         * @param {HTMLElement} row Participant table row.
         * @param {Array} cohorts Cohort data.
         */
        const renderBadges = (row, cohorts) => {
            const existing = row.querySelector('.local-cohortbadges-list');
            if (existing) {
                existing.remove();
            }

            row.dataset.cohortbadgesProcessed = '1';
            if (!cohorts || !cohorts.length) {
                return;
            }

            const cell = getNameCell(row);
            if (!cell) {
                return;
            }

            const list = document.createElement('span');
            list.className = 'local-cohortbadges-list';
            list.setAttribute('aria-label', 'Глобальные группы');

            cohorts.forEach(cohort => {
                const badge = document.createElement('span');
                badge.className = 'badge rounded-pill text-bg-secondary local-cohortbadges-badge';
                badge.textContent = cohort.label;
                badge.title = cohort.name;
                list.appendChild(badge);
            });

            cell.appendChild(list);
        };

        /**
         * Get current participant rows indexed by user id.
         *
         * @returns {Map}
         */
        const collectRows = () => {
            const rows = new Map();
            table.querySelectorAll('tbody tr').forEach(row => {
                const userId = getUserId(row);
                if (userId) {
                    rows.set(userId, row);
                }
            });
            return rows;
        };

        /**
         * Apply any cached memberships to rows currently on screen.
         */
        const applyCache = () => {
            const rows = collectRows();
            rows.forEach((row, userId) => {
                if (cache.has(userId)) {
                    renderBadges(row, cache.get(userId));
                }
            });
        };

        /**
         * Load missing memberships from Moodle AJAX API.
         */
        const loadMissing = () => {
            if (loading) {
                return;
            }

            const rows = collectRows();
            const missing = [];
            rows.forEach((row, userId) => {
                if (!cache.has(userId)) {
                    missing.push(userId);
                }
            });

            applyCache();
            if (!missing.length) {
                return;
            }

            loading = true;
            const requestedUserIds = missing.slice(0, 500);
            const requests = Ajax.call([{
                methodname: 'local_cohortbadges_get_memberships',
                args: {
                    courseid: courseId,
                    userids: requestedUserIds,
                },
            }]);

            requests[0].then(result => {
                result.forEach(entry => {
                    cache.set(Number(entry.userid), entry.cohorts || []);
                });
                requestedUserIds.forEach(userId => {
                    if (!cache.has(userId)) {
                        cache.set(userId, []);
                    }
                });
                applyCache();
                return null;
            }).catch(error => {
                // Do not break the participants page, but leave useful diagnostics.
                // eslint-disable-next-line no-console
                console.error('local_cohortbadges: unable to load cohort memberships', error);
            }).finally(() => {
                loading = false;
                if (missing.length > 500) {
                    scheduleLoad();
                }
            });
        };

        /**
         * Debounce table updates caused by filtering and pagination.
         */
        const scheduleLoad = () => {
            window.clearTimeout(timer);
            timer = window.setTimeout(loadMissing, 100);
        };

        const observer = new MutationObserver(scheduleLoad);
        observer.observe(table.parentElement || table, {
            childList: true,
            subtree: true,
        });

        loadMissing();
    });
})();
