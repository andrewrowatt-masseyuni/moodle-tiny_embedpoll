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
 * Adding and editing polls from the editor.
 *
 * Both open local_embedpoll's own form in a modal. The server hands back a key, and all this module
 * ever puts in the text is the token for that key.
 *
 * Editing a poll also offers to delete it - after asking, because its results go with it.
 *
 * @module     tiny_embedpoll/ui
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalForm from 'core_form/modalform';
import Notification from 'core/notification';
import Pending from 'core/pending';
import {getString, getStrings} from 'core/str';
import {getContextId} from 'editor_tiny/options';
import {component, keyPattern, tokenClass, tokenSelector} from './common';
import {getSectionId} from './options';
import {refresh} from './previews';

/**
 * The token under the cursor, if there is one.
 *
 * @param {TinyMCE} editor
 * @returns {HTMLElement|null}
 */
export const tokenAtCursor = (editor) => {
    const node = editor.selection.getNode();
    return node && node.closest ? node.closest(tokenSelector) : null;
};

/**
 * Put a token into the text, or re-key the one being edited.
 *
 * @param {TinyMCE} editor
 * @param {string} key The poll's key.
 * @param {HTMLElement|null} existing The token being edited, if any.
 */
const placeToken = (editor, key, existing = null) => {
    if (!keyPattern.test(key)) {
        throw new Error('Unexpected poll key');
    }

    if (existing) {
        // The server may have handed back a different key - a token pasted in from another
        // activity or section gets a poll of its own - so the token follows whatever came back.
        editor.dom.setAttrib(existing, 'data-embedpoll', key);
        editor.undoManager.add();
        editor.nodeChanged();
        // The poll has just changed, whether or not its key has.
        refresh(editor, key);
        return;
    }

    editor.insertContent(`<div class="${tokenClass}" data-embedpoll="${key}"></div>`);
};

/**
 * Take a token out of the text, in one undo step.
 *
 * Only the token goes, as when it is deleted by hand, so nothing is lost until the text is saved,
 * and undo brings it straight back until then.
 *
 * @param {TinyMCE} editor
 * @param {HTMLElement} token The token as it was when the form was opened.
 */
const removeToken = (editor, token) => {
    const key = token.dataset.embedpoll || '';
    // TinyMCE rebuilds tokens freely - undo, say - so the one clicked may since have been replaced.
    const target = token.isConnected ? token : Array.from(editor.getBody().querySelectorAll(tokenSelector))
        .find((candidate) => candidate.dataset.embedpoll === key);
    if (!target) {
        return;
    }

    editor.undoManager.transact(() => editor.dom.remove(target));
    editor.nodeChanged();
};

/**
 * Add Delete to the footer of the form for a poll already in the text.
 *
 * @param {TinyMCE} editor
 * @param {ModalForm} modalForm
 * @param {HTMLElement} token The token being edited.
 */
const addDelete = async(editor, modalForm, token) => {
    const pending = new Pending('tiny_embedpoll/ui:adddelete');
    let strings;
    try {
        strings = await getStrings([
            {key: 'delete', component},
            {key: 'deletetitle', component},
            {key: 'deleteconfirm', component},
        ]);
    } catch (error) {
        // The form still saves and cancels without it.
        Notification.exception(error);
        pending.resolve();
        return;
    }
    const [deleteText, deleteTitle, deleteQuestion] = strings;

    const modal = modalForm.modal;
    const remove = document.createElement('button');
    remove.type = 'button';
    remove.className = 'btn btn-outline-danger';
    remove.dataset.action = 'tiny-embedpoll-delete';
    remove.textContent = deleteText;

    const actions = document.createElement('div');
    actions.className = 'mr-auto';
    actions.append(remove);
    modal.getFooter()[0].prepend(actions);

    remove.addEventListener('click', async() => {
        try {
            await Notification.deleteCancelPromise(deleteTitle, deleteQuestion, deleteText, {triggerElement: remove});
        } catch (cancelled) {
            return;
        }

        removeToken(editor, token);
        modal.hide();
        editor.focus();
    });

    pending.resolve();
};

/**
 * Open the poll form in a modal.
 *
 * @param {TinyMCE} editor
 * @param {object} args Extra form arguments: key, for a poll already in the text.
 * @param {HTMLElement|null} existing The token being edited, if any.
 */
const openForm = async(editor, args, existing = null) => {
    const modalForm = new ModalForm({
        formClass: 'local_embedpoll\\form\\embed_form',
        args: {contextid: getContextId(editor), sectionid: getSectionId(editor), ...args},
        modalConfig: {
            title: await getString('modaltitle', component),
        },
    });

    modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, (event) => {
        try {
            placeToken(editor, event.detail.key, existing);
        } catch (error) {
            Notification.exception(error);
        }
    });

    if (existing) {
        modalForm.addEventListener(modalForm.events.LOADED, () => addDelete(editor, modalForm, existing));
    }

    modalForm.show();
};

/**
 * Insert a new poll.
 *
 * @param {TinyMCE} editor
 */
export const insertPoll = (editor) => openForm(editor, {});

/**
 * Edit a poll already in the text.
 *
 * @param {TinyMCE} editor
 * @param {HTMLElement} token The token.
 */
export const editPoll = (editor, token) => openForm(editor, {key: token.dataset.embedpoll || ''}, token);
