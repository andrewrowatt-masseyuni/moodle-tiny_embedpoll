@editor @editor_tiny @tiny @tiny_embedpoll
Feature: Adding and editing polls from the editor
  In order to gather opinions inline
  As an editing teacher
  I need to add, edit, move and delete polls from the editor, and see them as students will

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Tina      | Teacher  |
      | student1 | Sam       | Student  |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity | course | idnumber | name        |
      | book     | C1     | book1    | Course book |
    # contentformat 1 is FORMAT_HTML. The generator's default is FORMAT_MOODLE, which TinyMCE does
    # not edit - Moodle would quietly hand the chapter to another editor, with no button at all.
    And the following "mod_book > chapters" exist:
      | book        | title     | content              | contentformat |
      | Course book | Chapter 1 | <p>Chapter text.</p> | 1             |

  @javascript
  Scenario: Add a poll to a book chapter and vote in it
    Given I am on the "Course book" "book activity" page logged in as teacher1
    And I turn editing mode on
    And I follow "Edit chapter \"1. Chapter 1\""
    When I expand all toolbars for the "Content" TinyMCE editor
    And I click on the "Poll" button for the "Content" TinyMCE editor
    And I set the field "options" to multiline:
      """
      Uniquered
      Uniquegreen
      """
    And I click on "Save changes" "button" in the "Poll" "dialogue"
    Then the "Content" TinyMCE editor should preview a poll with "Uniquered"
    And the "Content" TinyMCE editor should preview a poll with "No votes yet"
    And the "Content" TinyMCE editor should not save "Uniquered"
    # Undo and redo rebuild the token, which then has no preview until the editor gives it one again.
    And I click on the "Undo" button for the "Content" TinyMCE editor
    And I click on the "Redo" button for the "Content" TinyMCE editor
    And the "Content" TinyMCE editor should preview a poll with "Uniquered"
    And I press "Save changes"
    And I should see "Chapter text."
    And I should see "Uniquered"
    And I log out
    And I am on the "Course book" "book activity" page logged in as student1
    And I should see "Select an option to vote and see the results."
    And I click on "Uniquegreen" "button"
    And "[data-action='poll-vote'][aria-pressed='true']" "css_element" should exist
    And I should see "1 vote"

  @javascript
  Scenario: A poll added while adding a text and media area belongs to it once saved
    Given I log in as "teacher1"
    And I add a "label" activity to course "Course 1" section "1"
    And I set the field "Text" to "<p>What do you think?</p>"
    When I expand all toolbars for the "Text" TinyMCE editor
    And I click on the "Poll" button for the "Text" TinyMCE editor
    And I set the field "options" to multiline:
      """
      Agree
      Disagree
      """
    And I click on "Save changes" "button" in the "Poll" "dialogue"
    And the "Text" TinyMCE editor should preview a poll with "Disagree"
    And I press "Save and return to course"
    Then I should see "What do you think?"
    And "Disagree" "button" should exist
    And I log out
    And I am on the "Course 1" "course" page logged in as student1
    And I click on "Agree" "button"
    And I should see "1 vote"

  @javascript
  Scenario: Editing a poll updates its preview and keeps the votes of options left unchanged
    Given I am on the "Course book" "book activity" page logged in as teacher1
    And I turn editing mode on
    And I follow "Edit chapter \"1. Chapter 1\""
    And I expand all toolbars for the "Content" TinyMCE editor
    And I click on the "Poll" button for the "Content" TinyMCE editor
    And I set the field "options" to multiline:
      """
      Yes
      No
      """
    And I click on "Save changes" "button" in the "Poll" "dialogue"
    And I press "Save changes"
    And I log out
    And I am on the "Course book" "book activity" page logged in as student1
    And I click on "Yes" "button"
    And I log out
    And I am on the "Course book" "book activity" page logged in as teacher1
    And I turn editing mode on
    And I follow "Edit chapter \"1. Chapter 1\""
    When I click on the poll with "Yes" in the "Content" TinyMCE editor
    Then I should see "This poll has 1 vote." in the "Poll" "dialogue"
    And I set the field "options" to multiline:
      """
      Not sure
      No
      Yes
      """
    And I click on "Save changes" "button" in the "Poll" "dialogue"
    And the "Content" TinyMCE editor should preview a poll with "Not sure"
    And the "Content" TinyMCE editor should preview a poll with "Yes 100%"
    And I press "Save changes"
    And I should see "Not sure"
    And I should see "1 vote"

  @javascript
  Scenario: Delete a poll from its form
    Given I am on the "Course book" "book activity" page logged in as teacher1
    And I turn editing mode on
    And I follow "Edit chapter \"1. Chapter 1\""
    And I expand all toolbars for the "Content" TinyMCE editor
    And I click on the "Poll" button for the "Content" TinyMCE editor
    And I set the field "options" to multiline:
      """
      Uniquered
      Uniquegreen
      """
    And I click on "Save changes" "button" in the "Poll" "dialogue"
    When I click on the poll with "Uniquered" in the "Content" TinyMCE editor
    And I click on "Delete" "button" in the "Poll" "dialogue"
    And I click on "Delete" "button" in the "Delete poll" "dialogue"
    Then the "Content" TinyMCE editor should not preview a poll with "Uniquered"
    And I press "Save changes"
    And I should see "Chapter text."
    And I should not see "Uniquered"

  @javascript
  Scenario: Move a poll past the paragraphs around it
    Given the following "local_embedpoll > polls" exist:
      | activity | embedkey         | options                |
      | book1    | 0123456789abcdef | Uniquered, Uniquegreen |
    And the following "mod_book > chapters" exist:
      | book        | title     | pagenum | content                                                                                         | contentformat |
      | Course book | Chapter 2 | 2       | <p>Before.</p><div class="embedpoll-embed" data-embedpoll="0123456789abcdef"></div><p>After.</p> | 1             |
    And I am on the "Course book" "book activity" page logged in as teacher1
    And I turn editing mode on
    When I follow "Edit chapter \"2. Chapter 2\""
    And I move the poll "up" in the "Content" TinyMCE editor
    And I press "Save changes"
    Then "Uniquered" "button" should appear before "Before." "text"
    And I follow "Edit chapter \"2. Chapter 2\""
    And I move the poll "down" in the "Content" TinyMCE editor
    And I move the poll "down" in the "Content" TinyMCE editor
    And I press "Save changes"
    And "After." "text" should appear before "Uniquered" "button"

  @javascript
  Scenario: A teacher who may not manage polls sees them in the editor but cannot change them
    Given the following "local_embedpoll > polls" exist:
      | activity | embedkey         | options                |
      | book1    | 0123456789abcdef | Uniquered, Uniquegreen |
    And the following "mod_book > chapters" exist:
      | book        | title     | pagenum | content                                                                          | contentformat |
      | Course book | Chapter 2 | 2       | <p>Second.</p><div class="embedpoll-embed" data-embedpoll="0123456789abcdef"></div> | 1             |
    And the following "permission overrides" exist:
      | capability             | permission | role           | contextlevel | reference |
      | local/embedpoll:manage | Prohibit   | editingteacher | Course       | C1        |
    And I am on the "Course book" "book activity" page logged in as teacher1
    And I turn editing mode on
    When I follow "Edit chapter \"2. Chapter 2\""
    And I expand all toolbars for the "Content" TinyMCE editor
    Then the "Content" TinyMCE editor should preview a poll with "Uniquered"
    And "button[aria-label='Poll'][aria-disabled='true']" "css_element" should exist
    And I click on the poll with "Uniquered" in the "Content" TinyMCE editor
    And "Poll" "dialogue" should not exist
    And I press "Save changes"
    And I should see "Uniquered"
