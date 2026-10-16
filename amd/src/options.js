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
 * Options for the embedded poll editor button.
 *
 * @module     tiny_embedpoll/options
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getPluginOptionName} from 'editor_tiny/options';
import {pluginName} from './common';

const canManageName = getPluginOptionName(pluginName, 'canmanage');
const sectionIdName = getPluginOptionName(pluginName, 'sectionid');
const pageCssName = getPluginOptionName(pluginName, 'pagecss');

/**
 * Register the options.
 *
 * @param {TinyMCE} editor
 */
export const register = (editor) => {
    editor.options.register(canManageName, {
        processor: 'boolean',
        "default": false,
    });
    editor.options.register(sectionIdName, {
        processor: 'number',
        "default": 0,
    });
    editor.options.register(pageCssName, {
        processor: 'array',
        "default": [],
    });
};

/**
 * Whether the teacher may add, edit, move and delete polls here.
 *
 * @param {TinyMCE} editor
 * @returns {boolean}
 */
export const canManage = (editor) => editor.options.get(canManageName);

/**
 * The section whose summary is being edited, or 0 for any other editor.
 *
 * @param {TinyMCE} editor
 * @returns {number}
 */
export const getSectionId = (editor) => editor.options.get(sectionIdName);

/**
 * The page's own theme stylesheets, for the poll preview.
 *
 * @param {TinyMCE} editor
 * @returns {Array}
 */
export const getPageCss = (editor) => editor.options.get(pageCssName);
