# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Where this plugin sits

This directory is the `tiny_embedpoll` plugin and **its own git repository** (remote
`git@github.com:andrewrowatt-masseyuni/moodle-tiny_embedpoll.git`, branch `main`). It is one of
three plugins that make up embedded polls:

| Plugin | Path | Role |
| --- | --- | --- |
| `local_embedpoll` | `local/embedpoll` | Storage, votes, the poll form, rendering, web services, backup. |
| `filter_embedpoll` | `filter/embedpoll` | Swaps each token in formatted text for the rendered poll. |
| `tiny_embedpoll` (this one) | `lib/editor/tiny/plugins/embedpoll` | The TinyMCE button that adds, edits, moves, deletes and previews polls. |

All three are checked out inside a Moodle 4.5 core checkout (branch `MOODLE_405_STABLE`) at
`/home/arowatt/moodle405_filter_embedpoll`. Commit from inside this directory; never commit into the
outer core repo.

The shared design lives in `local_embedpoll`, not here:

* `local/embedpoll/README.md` is the design record for all three plugins. *The preview in the
  editor*, *Drafts* and *Polls in section summaries* are the sections about this plugin. Read the
  relevant one before changing behaviour, and update it in the same change.
* `local/embedpoll/CLAUDE.md` has the cross-plugin map and the full list of traps. Read it before
  any change that touches the form, the web services or the token format.

This plugin owns no tables, forms, web services or templates. The form is
`local_embedpoll\form\embed_form`. The web service is `local_embedpoll_get_previews`, and its preview
HTML comes from `local_embedpoll`'s own template.

## Commands

In the container, cwd `/var/www/html` (`docker exec moodle405_filter_embedpoll-webserver-1 ...`):

```bash
vendor/bin/phpunit --testsuite tiny_embedpoll_testsuite
vendor/bin/phpunit --filter test_section_summary_editor lib/editor/tiny/plugins/embedpoll/tests/plugininfo_test.php   # one test
vendor/bin/phpunit privacy/tests/privacy/provider_test.php
php admin/tool/behat/cli/run.php --tags=@tiny_embedpoll --format progress   # with -u www-data
php admin/tool/phpunit/cli/init.php && php admin/tool/behat/cli/init.php     # after version.php changes
php admin/cli/upgrade.php --non-interactive && php admin/cli/purge_caches.php
php local/moodlecheck/cli/moodlecheck.php -p=lib/editor/tiny/plugins/embedpoll -f=text   # PHPDoc
```

For the one feature file, or screenshots, run Behat directly with `-u www-data`:
`vendor/bin/behat --config /var/www/behatdata/behatrun/behat/behat.yml lib/editor/tiny/plugins/embedpoll/tests/behat/editor.feature`,
adding `--format=moodle_screenshot`. `admin/tool/behat/cli/run.php` mangles the JSON in
`--format-settings`.

On the host, from the Moodle root:
`../moodle-plugin-ci/bin/moodle-plugin-ci phpcs --max-warnings 0 ./lib/editor/tiny/plugins/embedpoll`
(also `phplint`, `phpmd`, `phpcbf`). On the host, **from this directory**: `grunt
--max-lint-warnings=0 amd` and `grunt --max-lint-warnings=0 gherkinlint`. `amd/build` is committed,
so rebuild after every `amd/src` change and purge caches before testing in a browser or Behat.
`.vscode/tasks.json` holds all of these as tasks, including "Run grunt AMD & Purge cache".

CI (`.github/workflows/moodle-ci.yml`) also runs `validate`, `savepoints`, `phpdoc --max-warnings 0`
and `phpunit --fail-on-warning`. It fetches both `local_embedpoll` (the dependency) and
`filter_embedpoll` (which the Behat features need to show polls on the page) from `main`. So a
change here that needs a change there passes CI only once that change has been pushed.

## How the editor works

**Server side:** `classes/plugininfo.php` is all of the PHP.

* `is_enabled()` decides which editors get the plugin.
* `get_plugin_configuration_for_context()` passes three options to the client:
  * `canmanage`;
  * `sectionid`, which is non-zero only on `/course/editsection.php`;
  * `pagecss`, the page's theme stylesheet URLs.

`amd/src/options.js` registers the same three. A new option needs both.

**Client side:** `plugin.js` wires up three setups. Each module's `getSetup()` awaits its strings
first, because `PluginManager.add` cannot take a promise.

* `commands.js`: one toggle button (in the `content` toolbar group) and one **Insert** menu item.
  Both run the same action: edit the token at the cursor, or insert a new one. Both are disabled
  without `canmanage`, and pressed while the cursor is on a token.
* `tokens.js`: what makes a token behave as one unit, for everyone the plugin is enabled for.
  * On `SetContent`, it sets `contenteditable=false` on each token.
  * A serializer attribute filter empties every token on the way out, and drops its
    `contenteditable`.
  * A click on a token opens the form, for `canmanage` only.
  * It starts `previews.watch()` on `init`.
* `ui.js`: opens `core_form/modalform` with `{contextid, sectionid, key?}`.
  * On submit, it writes the returned key into the text. That is either a new
    `<div class="embedpoll-embed" data-embedpoll="KEY"></div>`, or a re-keyed existing token.
  * For an existing token, it adds a **Delete** button to the modal footer. Delete removes the
    token in one undo step.
* `previews.js`: gives each token an open shadow root that holds:
  * the page's stylesheets;
  * the preview HTML;
  * the "Click to edit" hint;
  * the move and insert space buttons from `move.js`.

  A `MutationObserver` on the body re-attaches the shadow root whenever TinyMCE rebuilds a token.
  Keys are batched into a single `local_embedpoll_get_previews` call (`setTimeout(0)`). A
  per-editor batch counter stops a stale answer from overwriting a fresher one after an edit.
* `move.js`: swaps the token with its previous or next element sibling, skipping
  `data-mce-bogus` scaffolding, in one undo step. Its insert space buttons put an empty
  `forced_root_block` before or after the token, also in one undo step, and the cursor in it.

This plugin has no `styles.css`. All of its CSS lives in template strings in `tokens.js` (the
editor's document), `previews.js` and `move.js` (the shadow root).

## Traps

* **Nothing may ever be put inside a token.** The filter's `token::PATTERN` stops at the first
  `</div>`, so anything inside is saved into the host text and shows under the rendered poll.
  Previews, hints and buttons all go in the shadow root. The serializer filter in `tokens.js` is the
  second line of defence, so keep both.
* **XPath cannot see into the shadow root.** Behat must use the steps in
  `tests/behat/behat_tiny_embedpoll.php`, which read it through `evaluate_javascript_for_editor`.
  `the "…" TinyMCE editor should not save "…"` is what pins that the preview stays out of the text.
  The move and insert space steps click their buttons with the pointer, over the poll first,
  because the buttons take no clicks until it is hovered.
* **To type after an insert space button, use `I type "…" in the "…" TinyMCE editor`.** Core's
  `I type "…"` sends keys to the page, and once the editor has taken focus from script Chrome
  delivers them without inserting any text. This plugin's step sends them to the focused element
  in the editor's own frame.
* **The plugin is enabled for `viewresults` as well as `manage`.** A non-editing teacher still edits
  the text around polls, and without this plugin's `tokens.js` they would type into the tokens or
  break them. Gate *what is offered* on `canmanage`, not *whether the plugin loads*.
* **`canmanage` decides only what the client offers.** `embed_form` and
  `local_embedpoll_get_previews` check capabilities again on the server.
* **A course context gets the button on only two pages**: `/course/modedit.php` (drafts) and
  `/course/editsection.php` (section polls), and never under `AJAX_SCRIPT`. A course context is
  ambiguous without the page URL. Adding a host page also needs `local_embedpoll\api::embed_target()`
  to accept it. `plugininfo_test` pins the current set. It sets `$PAGE->set_url()` and replaces
  `$PAGE` with `new \moodle_page()` between cases.
* **Keep the `get_config('local_embedpoll', 'version')` check** at the top of `is_enabled()`.
* **The server may hand back a different key when an existing poll is saved.** A token pasted in
  from another activity or section gets a poll of its own. `placeToken()` re-keys the token and
  calls `refresh()`. Do not assume the key is unchanged.
* **TinyMCE rebuilds token elements freely** (undo, paste, drag and drop), sometimes without firing
  an event. Do not hold on to a token element across an async step. `removeToken()` falls back to
  finding the token by key when the original is no longer `isConnected`.
* **Only keys that match `keyPattern` (`/^[0-9a-f]{16}$/`) are sent to the server or inserted.**
  `placeToken()` throws on anything else.
* **Each line of the poll form has its own "Delete option N" button**, which `"Delete" "button"`
  also matches, so a step that deletes a poll from its form clicks Delete in `.modal-footer`. The
  lines are `local_embedpoll`'s: set one by its placeholder, `I set the field "Option 1" to "…"`.
* **Wrap every new async path in `core/pending`**, as `send()` and `addDelete()` do, or Behat races
  it.
* **`@font-face` is ignored inside a shadow root.** `fontFaces()` copies the page's Font Awesome
  faces onto the editor's document so that option icons render. Keep it if the preview styling
  changes.
* **Behat book chapters need `contentformat` 1 (`FORMAT_HTML`).** The generator's default is
  `FORMAT_MOODLE`, which TinyMCE does not edit, so Moodle silently uses another editor with no
  button. The button can sit in the toolbar's overflow, so steps that click it first run `I expand
  all toolbars for the "…" TinyMCE editor`, as `editor.feature` does.
* **Polls that already exist come from `local_embedpoll`'s generator**: `the following
  "local_embedpoll > polls" exist` (activity idnumber, `embedkey`, comma-separated options). Put
  the matching token straight into the chapter's `content`.
* **Keep `$plugin->dependencies` on `local_embedpoll` (2026101700) in step** with what this plugin
  calls. `$plugin->supported` is `[405, 405]`.
* Lang keys are kept in alphabetical order.
