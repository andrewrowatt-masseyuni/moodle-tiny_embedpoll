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
 * Common values for the embedded poll editor button.
 *
 * @module     tiny_embedpoll/common
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export default {
    pluginName: 'tiny_embedpoll/plugin',
    component: 'tiny_embedpoll',
    buttonName: 'tiny_embedpoll',
    icon: 'tiny_embedpoll',
    // The token, as local_embedpoll\token writes it. Only the attribute matters for matching.
    tokenSelector: 'div[data-embedpoll]',
    tokenClass: 'embedpoll-embed',
    // What a key looks like. Anything else is neither sent to the server nor inserted.
    keyPattern: /^[0-9a-f]{16}$/,
};
