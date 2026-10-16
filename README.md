# Embedded poll button for TinyMCE (tiny_embedpoll)

Part of **embedded polls**. The design for all three plugins is documented in
`local/embedpoll/README.md`.

This plugin adds a **Poll** button to the TinyMCE toolbar, and to the **Insert** menu.

* **To add a poll:** with the cursor where the poll should go, click the button. Write the options
  one per line, then save.
* **To edit a poll:** click it, or put the cursor on it and click the button. The button shows as
  pressed while the cursor is on a poll. The form's footer also has **Delete**, which asks first and
  then removes the poll from the text in one undo step.
* **To move a poll:** hover over it and use the up and down arrows at its bottom right.

The form is `local_embedpoll\form\embed_form`, opened with `core_form/modalform`. The server returns
a key, and the token for that key is all this plugin ever puts in the text.

In the editor, each token shows its poll exactly as the page will, results included. It is rendered
by the `local_embedpoll_get_previews` web service, and styled with the page's own theme stylesheets.
It cannot be voted in.

The preview goes in a shadow root attached to the token, never inside it, so it is never part of the
saved text. A serializer filter also saves every token empty, whatever is in it.

**Who gets the plugin**

* It is offered to people who may manage polls or see their results.
* A teacher without `local/embedpoll:manage` sees the polls but cannot change them. Their button is
  disabled.
* It is offered in these editors only:
  * an activity's own editors;
  * a section's *Edit section* page, where the section id is passed to the editor so the poll
    belongs to that section;
  * the *add an activity* form, where polls are drafts until the activity is saved.

Requires `local_embedpoll`. Moodle 4.5 only.

## License

2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>

This program is free software: you can redistribute it and/or modify it under the terms of the GNU
General Public License as published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
General Public License for more details.

You should have received a copy of the GNU General Public License along with this program. If not,
see <https://www.gnu.org/licenses/>.
