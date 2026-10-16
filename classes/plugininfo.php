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

namespace tiny_embedpoll;

use context;
use editor_tiny\editor;
use editor_tiny\plugin;
use editor_tiny\plugin_with_buttons;
use editor_tiny\plugin_with_configuration;
use editor_tiny\plugin_with_menuitems;

/**
 * Embedded poll button for TinyMCE.
 *
 * @package    tiny_embedpoll
 * @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plugininfo extends plugin implements plugin_with_buttons, plugin_with_configuration, plugin_with_menuitems {
    /** @var string Capability to add, edit, move and delete polls. */
    protected const CAP_MANAGE = 'local/embedpoll:manage';

    /** @var string Capability to see poll results, which the editor's teachers have. */
    protected const CAP_VIEWRESULTS = 'local/embedpoll:viewresults';

    /**
     * Whether to offer the plugin in this editor.
     *
     * Where a poll can live - an activity's own editors (a label, a page, a book chapter, a lesson
     * page, a description), a section's summary on its edit page, or a course's editors on the "add
     * an activity" form, where the text is written before the activity exists - to anyone who may
     * manage polls or see their results. A non-editing teacher who may not change a poll may still
     * edit the text around it, and the plugin is what keeps its tokens whole and shows them as
     * polls. What it offers each of them is get_plugin_configuration_for_context()'s business.
     *
     * Never in any editor rendered over AJAX: every one this is meant for is on an ordinary page.
     *
     * @param context $context The editor's context.
     * @param array $options The editor options.
     * @param array $fpoptions The file picker options.
     * @param editor|null $editor The editor instance.
     * @return bool
     */
    public static function is_enabled(
        context $context,
        array $options,
        array $fpoptions,
        ?editor $editor = null
    ): bool {
        if (!get_config('local_embedpoll', 'version')) {
            return false;
        }

        if (defined('AJAX_SCRIPT') && AJAX_SCRIPT) {
            return false;
        }

        if ($context->contextlevel == CONTEXT_MODULE) {
            return self::can_use($context);
        }

        if ($context->contextlevel == CONTEXT_COURSE && (int)$context->instanceid !== (int)SITEID) {
            return (self::on_page('/course/modedit.php') || self::section_being_edited($context))
                && self::can_use($context);
        }

        return false;
    }

    /**
     * Whether the current user may manage polls here, or see their results.
     *
     * @param context $context The editor's context.
     * @return bool
     */
    protected static function can_use(context $context): bool {
        return has_any_capability([self::CAP_MANAGE, self::CAP_VIEWRESULTS], $context);
    }

    /**
     * Whether this is the page with the given path.
     *
     * @param string $path The path from the Moodle root, e.g. /course/modedit.php.
     * @return bool
     */
    protected static function on_page(string $path): bool {
        global $PAGE;

        return $PAGE->has_set_url() && str_ends_with($PAGE->url->get_path(false), $path);
    }

    /**
     * The section whose summary this course context's editor is for, or 0 if it is not one.
     *
     * A section summary's editor is in the course context, as the "add an activity" form's is, so
     * only the page tells them apart. The section id goes to the client and comes back with each
     * poll, so local_embedpoll can tell a section's poll from a draft.
     *
     * @param context $context The editor's context.
     * @return int
     */
    protected static function section_being_edited(context $context): int {
        global $DB, $PAGE;

        if ($context->contextlevel != CONTEXT_COURSE || !self::on_page('/course/editsection.php')) {
            return 0;
        }

        $sectionid = (int)$PAGE->url->param('id');
        if (!$sectionid || !$DB->record_exists('course_sections', ['id' => $sectionid, 'course' => $context->instanceid])) {
            return 0;
        }

        return $sectionid;
    }

    /**
     * The buttons this plugin provides.
     *
     * @return string[]
     */
    public static function get_available_buttons(): array {
        return ['tiny_embedpoll/tiny_embedpoll'];
    }

    /**
     * The menu items this plugin provides.
     *
     * @return string[]
     */
    public static function get_available_menuitems(): array {
        return ['tiny_embedpoll/tiny_embedpoll'];
    }

    /**
     * What the current user may do here (canmanage: add, edit, move and delete polls); the section
     * being edited, if any; and the page's theme stylesheets, for the poll preview.
     *
     * The capability goes to the client only to decide what it offers. Every web service it calls
     * checks again.
     *
     * @param context $context The editor's context.
     * @param array $options The editor options.
     * @param array $fpoptions The file picker options.
     * @param editor|null $editor The editor instance.
     * @return array
     */
    public static function get_plugin_configuration_for_context(
        context $context,
        array $options,
        array $fpoptions,
        ?editor $editor = null
    ): array {
        return [
            'canmanage' => has_capability(self::CAP_MANAGE, $context),
            'sectionid' => self::section_being_edited($context),
            'pagecss' => self::page_css(),
        ];
    }

    /**
     * The page's own theme stylesheets.
     *
     * The editor's iframe carries only the theme's editor stylesheet, which knows nothing of how a
     * poll looks; the preview links these as well, so that it looks as it will on the page. They
     * are the ones the page around the editor has already loaded.
     *
     * @return string[] URLs.
     */
    protected static function page_css(): array {
        global $PAGE;

        return array_map(fn(\moodle_url $url): string => $url->out(false), $PAGE->theme->css_urls($PAGE));
    }
}
