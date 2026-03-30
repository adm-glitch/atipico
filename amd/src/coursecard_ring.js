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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Theme Atipico — Course card circular progress ring injector.
 *
 * Runs on the My Courses dashboard page (block_myoverview).
 * Reads the completion percentage from each course card's progress bar markup
 * and injects a .rui-progress-ring element next to the course title.
 * A MutationObserver handles the fact that block_myoverview renders cards
 * asynchronously via JavaScript.
 *
 * @module     theme_atipico/coursecard_ring
 * @copyright  2024 Hugo Ribeiro <ribeiro.hugo@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Snap a percentage value to the nearest multiple of 5 (0–100).
 *
 * @param {number} pct
 * @returns {number}
 */
function snapToFive(pct) {
    return Math.max(0, Math.min(100, Math.round(pct / 5) * 5));
}

/**
 * Read the completion percentage from a course card element.
 * Tries several selectors used across Moodle 4.x versions.
 *
 * @param {Element} card
 * @returns {number|null} percentage 0–100, or null if not found
 */
function readProgress(card) {
    // Moodle 4.x: <div class="progress" data-value="42"> (most reliable)
    const progressEl = card.querySelector('.progress[data-value]');
    if (progressEl) {
        const val = parseFloat(progressEl.dataset.value);
        if (!isNaN(val)) {
            return val;
        }
    }

    // Fallback: <div class="progress-bar" aria-valuenow="42">
    const bar = card.querySelector('.progress-bar[aria-valuenow]');
    if (bar) {
        const val = parseFloat(bar.getAttribute('aria-valuenow'));
        if (!isNaN(val)) {
            return val;
        }
    }

    // Fallback: parse style="width: 42%"
    const barStyled = card.querySelector('.progress-bar[style]');
    if (barStyled) {
        const match = barStyled.style.width && barStyled.style.width.match(/^([\d.]+)%$/);
        if (match) {
            return parseFloat(match[1]);
        }
    }

    return null;
}

/**
 * Build a .rui-progress-ring span with the correct percentage class.
 *
 * @param {number} pct 0–100
 * @returns {HTMLElement}
 */
function buildRing(pct) {
    const snapped = snapToFive(pct);
    const ring = document.createElement('span');
    ring.className = 'rui-progress-ring rui-progress-' + snapped;
    ring.title = Math.round(pct) + '%';
    ring.setAttribute('aria-label', Math.round(pct) + '% complete');
    ring.setAttribute('role', 'img');
    return ring;
}

/**
 * Inject a progress ring into a single course card.
 * Idempotent — skips cards that already have a ring.
 *
 * @param {Element} card
 */
function decorateCard(card) {
    if (card.dataset.ruiRingAdded) {
        return;
    }

    const pct = readProgress(card);
    if (pct === null) {
        return;
    }

    // Find the title element — varies across Moodle versions.
    const title = card.querySelector('.card-title, [data-region="course-name"], .coursename');
    if (!title) {
        return;
    }

    const ring = buildRing(pct);

    // Wrap the title and ring in a flex row if not already wrapped.
    const wrapper = document.createElement('span');
    wrapper.className = 'rui-course-card__title-row';

    title.parentNode.insertBefore(wrapper, title);
    wrapper.appendChild(title);
    wrapper.appendChild(ring);

    card.dataset.ruiRingAdded = '1';
}

/**
 * Scan for all .block_myoverview course cards and decorate them.
 */
function decorateAll() {
    const cards = document.querySelectorAll(
        '.block_myoverview .card[data-course-id], .block_myoverview .card[data-courseid]'
    );
    cards.forEach(decorateCard);
}

export const init = () => {
    // Decorate any cards already in the DOM.
    decorateAll();

    // Watch for cards added asynchronously (block_myoverview renders client-side).
    const observer = new MutationObserver(() => {
        decorateAll();
    });

    const root = document.querySelector('.block_myoverview') || document.body;
    observer.observe(root, {childList: true, subtree: true});

    // Disconnect after 30 s to avoid memory leaks on long-lived pages.
    setTimeout(() => observer.disconnect(), 30000);
};
