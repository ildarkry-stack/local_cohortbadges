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
 * This file is loaded through page requirements and uses Moodle's RequireJS
 * loader to obtain core/ajax. Keeping it outside amd/src means there are no
 * generated AMD build artefacts to commit or become stale.
 *
 * @module     local_cohortbadges/participants
 * @copyright  2026 MIA Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

window.localCohortBadgesInit = function(courseId, ariaLabel) {
    require(['core/ajax'], function(Ajax) {
        const cache = new Map();
        let observer = null;
        let timer = null;
        const resolvedCourseId = Number(courseId);
        const resolvedAriaLabel = ariaLabel || 'Cohorts';

        const getUserId = checkbox => {
            const match = checkbox.id.match(/^user(\d+)$/);
            return match ? Number(match[1]) : 0;
        };

        const getNameCell = row => row.querySelector('th.cell.c1, td.cell.c1')
            || row.querySelector('th.cell, td.cell');

        const renderBadges = (checkbox, cohorts) => {
            const row = checkbox.closest('tr');
            if (!row || row.dataset.cohortbadgesProcessed === '1') {
                return;
            }

            if (!cohorts.length) {
                row.dataset.cohortbadgesProcessed = '1';
                return;
            }

            const cell = getNameCell(row);
            if (!cell) {
                return;
            }

            const list = document.createElement('span');
            list.className = 'local-cohortbadges-list';
            list.setAttribute('aria-label', resolvedAriaLabel);

            cohorts.forEach(cohort => {
                const badge = document.createElement('span');
                badge.className = 'badge rounded-pill text-bg-secondary local-cohortbadges-badge';
                badge.textContent = cohort.label;
                badge.title = cohort.name;
                list.appendChild(badge);
            });

            cell.appendChild(list);
            row.dataset.cohortbadgesProcessed = '1';
        };

        const applyFromCache = () => {
            document.querySelectorAll('#participants input.usercheckbox[id^="user"]').forEach(checkbox => {
                const userId = getUserId(checkbox);
                if (userId && cache.has(userId)) {
                    renderBadges(checkbox, cache.get(userId));
                }
            });
        };

        const scheduleLoad = () => {
            window.clearTimeout(timer);
            timer = window.setTimeout(loadMissing, 80);
        };

        const loadMissing = () => {
            const missing = [];
            document.querySelectorAll('#participants input.usercheckbox[id^="user"]').forEach(checkbox => {
                const userId = getUserId(checkbox);
                if (userId && !cache.has(userId) && !missing.includes(userId)) {
                    missing.push(userId);
                }
            });

            applyFromCache();
            if (!missing.length) {
                return;
            }

            const requestedUserIds = missing.slice(0, 500);
            const requests = Ajax.call([{
                methodname: 'local_cohortbadges_get_memberships',
                args: {
                    courseid: resolvedCourseId,
                    userids: requestedUserIds,
                },
            }]);

            requests[0].then(result => {
                result.forEach(entry => cache.set(Number(entry.userid), entry.cohorts || []));
                requestedUserIds.forEach(userId => {
                    if (!cache.has(userId)) {
                        cache.set(userId, []);
                    }
                });
                applyFromCache();
                if (missing.length > 500) {
                    scheduleLoad();
                }
                return null;
            }).catch(() => {
                // Keep the standard participants table usable if this optional enhancement cannot load.
            });
        };

        const startObserver = () => {
            const target = document.querySelector('.userlist')
                || document.querySelector('#participants')
                || document.body;
            observer = new MutationObserver(scheduleLoad);
            observer.observe(target, {childList: true, subtree: true});
        };

        const run = () => {
            loadMissing();
            if (!observer) {
                startObserver();
            }
        };

        if (!resolvedCourseId) {
            return;
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', run, {once: true});
        } else {
            run();
        }
    });
};
