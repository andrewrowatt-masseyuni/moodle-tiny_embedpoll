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
 * The poll button and menu item.
 *
 * Both do the same: edit the poll under the cursor if there is one, or insert a new one. The
 * button shows as pressed while the cursor is on a poll, as the link button does on a link. For a
 * teacher who may not manage polls both are disabled; the plugin is still there for them, keeping
 * polls whole and previewing them.
 *
 * @module     tiny_embedpoll/commands
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getString} from 'core/str';
import {getButtonImage} from 'editor_tiny/utils';
import {buttonName, component, icon} from './common';
import {canManage} from './options';
import {editPoll, insertPoll, tokenAtCursor} from './ui';

export const getSetup = async() => {
    const [buttonText, buttonImage] = await Promise.all([
        getString('buttontitle', component),
        getButtonImage('icon', component),
    ]);

    return (editor) => {
        /**
         * Edit the poll under the cursor, or insert a new one.
         */
        const action = () => {
            const token = tokenAtCursor(editor);
            if (token) {
                editPoll(editor, token);
            } else {
                insertPoll(editor);
            }
        };

        /**
         * Keep the button's state in step with the cursor.
         *
         * @param {object} api The button's, or the menu item's.
         * @returns {Function} What undoes it.
         */
        const onSetup = (api) => {
            const update = () => {
                api.setEnabled(canManage(editor));
                api.setActive?.(Boolean(tokenAtCursor(editor)));
            };
            editor.on('NodeChange', update);
            update();
            return () => editor.off('NodeChange', update);
        };

        editor.ui.registry.addIcon(icon, buttonImage.html);

        editor.ui.registry.addToggleButton(buttonName, {
            icon,
            tooltip: buttonText,
            onAction: action,
            onSetup,
        });

        editor.ui.registry.addMenuItem(buttonName, {
            icon,
            text: buttonText,
            onAction: action,
            onSetup,
        });
    };
};
