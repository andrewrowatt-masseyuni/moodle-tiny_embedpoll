<?php
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
 * Steps for the embedded poll editor button.
 *
 * @package    tiny_embedpoll
 * @category   test
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use Behat\Mink\Exception\ExpectationException;
use Facebook\WebDriver\WebDriverBy;

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.
require_once(__DIR__ . '/../../../../tests/behat/editor_tiny_helpers.php');
require_once(__DIR__ . '/../../../../../../behat/behat_base.php');

/**
 * Steps for the embedded poll editor button.
 *
 * The preview lives in each token's shadow root, which Mink's XPath cannot see into, so these read
 * it through the editor instead.
 *
 * @package    tiny_embedpoll
 * @category   test
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_tiny_embedpoll extends behat_base {
    use editor_tiny_helpers;

    /**
     * Check that the editor previews a poll containing some text.
     *
     * @Then /^the "(?P<editor_string>(?:[^"]|\\")*)" TinyMCE editor should preview a poll with "(?P<text_string>(?:[^"]|\\")*)"$/
     * @param string $locator The editor.
     * @param string $text The text.
     */
    public function the_editor_should_preview_a_poll_with(string $locator, string $text): void {
        $this->check_previews($locator, $text, true);
    }

    /**
     * Check that the editor previews no poll containing some text.
     *
     * @Then /^the "(?P<editor_string>[^"]*)" TinyMCE editor should not preview a poll with "(?P<text_string>[^"]*)"$/
     * @param string $locator The editor.
     * @param string $text The text.
     */
    public function the_editor_should_not_preview_a_poll_with(string $locator, string $text): void {
        $this->check_previews($locator, $text, false);
    }

    /**
     * Check whether the editor previews a poll containing some text.
     *
     * @param string $locator The editor.
     * @param string $text The text.
     * @param bool $expected Whether it should.
     */
    protected function check_previews(string $locator, string $text, bool $expected): void {
        $previews = $this->get_previews($locator);
        if (str_contains($previews, $text) === $expected) {
            return;
        }

        throw new ExpectationException(
            $expected
                ? "The \"{$locator}\" editor previews no poll containing \"{$text}\". It previews: {$previews}"
                : "The \"{$locator}\" editor previews a poll containing \"{$text}\": {$previews}",
            $this->getSession()
        );
    }

    /**
     * The text of every preview in an editor, in order, one per line.
     *
     * @param string $locator The editor.
     * @return string
     */
    protected function get_previews(string $locator): string {
        $this->require_tiny_tags();

        $editorid = $this->get_textarea_for_locator($locator)->getAttribute('id');

        return (string)$this->evaluate_javascript_for_editor($editorid, <<<EOF
            resolve(Array.from(instance.getBody().querySelectorAll('div[data-embedpoll]'))
                .map((token) => token.shadowRoot?.querySelector('.tiny-embedpoll-preview'))
                .filter((content) => content)
                .map((content) => content.textContent.replace(/\\s+/g, ' ').trim())
                .join('\\n'));
            EOF);
    }

    /**
     * Click on the poll in the editor that previews some text, as a teacher would to edit it.
     *
     * The preview is in the token's shadow root, so the token itself is clicked, which is where a
     * click on the preview lands.
     *
     * @When /^I click on the poll with "(?P<text_string>(?:[^"]|\\")*)" in the "(?P<editor_string>(?:[^"]|\\")*)" TinyMCE editor$/
     * @param string $text Text in the poll's preview.
     * @param string $locator The editor.
     */
    public function i_click_on_the_poll(string $text, string $locator): void {
        $this->require_tiny_tags();

        $editorid = $this->get_textarea_for_locator($locator)->getAttribute('id');
        $needle = json_encode($text);
        $clicked = $this->evaluate_javascript_for_editor($editorid, <<<EOF
            const token = Array.from(instance.getBody().querySelectorAll('div[data-embedpoll]'))
                .find((token) => token.shadowRoot?.querySelector('.tiny-embedpoll-preview')
                    ?.textContent.includes({$needle}));
            token?.click();
            resolve(Boolean(token));
            EOF);

        if (!$clicked) {
            throw new ExpectationException(
                "The \"{$locator}\" editor previews no poll containing \"{$text}\".",
                $this->getSession()
            );
        }
    }

    /**
     * Click the first poll's move up or move down button.
     *
     * The buttons are in the token's shadow root, and only appear on hover. Moving past nothing does
     * nothing, as it does in the editor.
     *
     * @When /^I move the poll "(?P<direction_string>up|down)" in the "(?P<editor_string>(?:[^"]|\\")*)" TinyMCE editor$/
     * @param string $direction up or down.
     * @param string $locator The editor.
     */
    public function i_move_the_poll(string $direction, string $locator): void {
        $this->click_poll_button($locator, "[data-direction=\"{$direction}\"]", "move {$direction}");
    }

    /**
     * Click the first poll's insert space above or insert space after button.
     *
     * Like the move buttons, these are in the token's shadow root. They leave the cursor in the new
     * paragraph, so the next step can type into it (I type "…" in the "…" TinyMCE editor).
     *
     * @When /^I insert space "(?P<where_string>above|after)" the poll in the "(?P<editor_string>(?:[^"]|\\")*)" TinyMCE editor$/
     * @param string $where above or after.
     * @param string $locator The editor.
     */
    public function i_insert_space_by_the_poll(string $where, string $locator): void {
        $this->click_poll_button($locator, "[data-insert=\"{$where}\"]", "insert space {$where}");
    }

    /**
     * Type into an editor, wherever its cursor is.
     *
     * Core's "I type" sends keys to the page. After the editor has taken focus from script, as the
     * insert space buttons give it, Chrome delivers those keys to the editor without inserting any
     * text. Keys sent to the element with focus in the editor's own frame are inserted.
     *
     * @When /^I type "(?P<text_string>(?:[^"]|\\")*)" in the "(?P<editor_string>(?:[^"]|\\")*)" TinyMCE editor$/
     * @param string $text The text.
     * @param string $locator The editor.
     */
    public function i_type_in_the_editor(string $text, string $locator): void {
        $this->require_tiny_tags();

        $webdriver = $this->getSession()->getDriver()->getWebDriver();
        $this->execute('behat_general::switch_to_iframe', [$this->get_editor_iframe_name($locator)]);
        try {
            $webdriver->switchTo()->activeElement()->sendKeys($text);
        } finally {
            $this->execute('behat_general::switch_to_the_main_frame');
        }
    }

    /**
     * Click one of the buttons in the first poll's shadow root, as a teacher would.
     *
     * The pointer goes over the poll, which shows the buttons, and then clicks one. Until the poll
     * is hovered, the buttons take no clicks.
     *
     * @param string $locator The editor.
     * @param string $attribute An attribute selector for the button.
     * @param string $name What the button does, for the error.
     */
    protected function click_poll_button(string $locator, string $attribute, string $name): void {
        $this->require_tiny_tags();

        $webdriver = $this->getSession()->getDriver()->getWebDriver();
        $this->execute('behat_general::switch_to_iframe', [$this->get_editor_iframe_name($locator)]);
        try {
            $token = $webdriver->findElements(WebDriverBy::cssSelector('div[data-embedpoll]'))[0] ?? null;
            $button = $token?->getShadowRoot()
                ->findElements(WebDriverBy::cssSelector(".tiny-embedpoll-move button{$attribute}"))[0] ?? null;
            if (!$button) {
                throw new ExpectationException(
                    "The \"{$locator}\" editor has no poll with a {$name} button.",
                    $this->getSession()
                );
            }

            // The pointer only goes where it can be seen. This scrolls the page, too, not just the editor.
            $webdriver->executeScript('arguments[0].scrollIntoView({block: "center"});', [$token]);
            $webdriver->action()->moveToElement($token)->moveToElement($button)->click()->perform();
        } finally {
            $this->execute('behat_general::switch_to_the_main_frame');
        }
    }

    /**
     * Check that what the editor would save does not contain some text.
     *
     * @Then /^the "(?P<editor_string>(?:[^"]|\\")*)" TinyMCE editor should not save "(?P<text_string>(?:[^"]|\\")*)"$/
     * @param string $locator The editor.
     * @param string $text The text.
     */
    public function the_editor_should_not_save(string $locator, string $text): void {
        $this->require_tiny_tags();

        $editorid = $this->get_textarea_for_locator($locator)->getAttribute('id');
        $content = (string)$this->evaluate_javascript_for_editor($editorid, 'resolve(instance.getContent());');

        if (str_contains($content, $text)) {
            throw new ExpectationException(
                "The \"{$locator}\" editor would save \"{$text}\": {$content}",
                $this->getSession()
            );
        }
    }
}
