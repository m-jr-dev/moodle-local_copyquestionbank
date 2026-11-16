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

namespace local_copyquestionbank;

use core\hook\output\before_standard_head_html_generation;
use core\output\help_icon;
use local_copyquestionbank\local\bank_locator;
use local_copyquestionbank\local\page_helper;

/**
 * Registers the interactive course backup enhancement.
 *
 * @package    local_copyquestionbank
 * @copyright  2026 Marcelo M. Almeida Júnior
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class hook_callbacks {
    /**
     * Loads the backup integration on the interactive course backup page.
     *
     * @param before_standard_head_html_generation $hook Output hook.
     */
    public static function before_standard_head_html_generation(
        before_standard_head_html_generation $hook,
    ): void {
        global $PAGE;

        if (during_initial_install() || !page_helper::is_course_backup_page($PAGE)) {
            return;
        }

        $enabled = get_config('local_copyquestionbank', 'enabled');
        if ($enabled !== false && (int) $enabled === 0) {
            return;
        }

        $courseid = (int) $PAGE->url->get_param('id');
        if ($courseid <= 0 || !isloggedin() || isguestuser()) {
            return;
        }

        $context = \context_course::instance($courseid, IGNORE_MISSING);
        if (!$context || !has_capability('moodle/backup:backupcourse', $context)) {
            return;
        }

        if (!\core_component::get_plugin_directory('mod', 'qbank')) {
            return;
        }

        $renderer = $hook->renderer;
        if (!($renderer instanceof \core_renderer)) {
            return;
        }

        $settings = bank_locator::get_backup_setting_names($courseid);
        $requestmarker = optional_param('local_copyquestionbank_present', 0, PARAM_BOOL);
        $requestpreference = -1;
        if ($requestmarker) {
            $requestpreference = optional_param('local_copyquestionbank_copy', 0, PARAM_BOOL) ? 1 : 0;
        }

        $config = [
            'activitiesWarningIcon' => self::get_warning_help_icon($renderer, 'activitieswarning'),
            'bankGroupLabel' => get_string('questionbanks', 'local_copyquestionbank'),
            'courseId' => $courseid,
            'helpIcon' => $renderer->help_icon('copyquestionbank', 'local_copyquestionbank'),
            'includedBankGroupLabel' => get_string('includedquestionbanks', 'local_copyquestionbank'),
            'label' => get_string('copyquestionbank', 'local_copyquestionbank'),
            'qbankSettings' => $settings['qbanks'],
            'requestPreference' => $requestpreference,
            'schemaApplied' => $requestmarker && optional_param('local_copyquestionbank_applied', false, PARAM_BOOL),
            'schemaWarningIcon' => self::get_warning_help_icon($renderer, 'schemawarning'),
            'sectionSettings' => $settings['sections'],
            'userInfoLabel' => get_string('userinfo', 'backup'),
            'warningIcon' => self::get_warning_help_icon($renderer, 'nobankwarning'),
        ];

        $PAGE->requires->js_call_amd('local_copyquestionbank/course_backup_ui', 'init', [$config]);
    }

    /**
     * Returns a standard help control using the warning glyph.
     *
     * @param \core_renderer $renderer Page renderer.
     * @param string $identifier Language string identifier.
     * @return string Warning help icon HTML.
     */
    private static function get_warning_help_icon(\core_renderer $renderer, string $identifier): string {
        $helpicon = new help_icon($identifier, 'local_copyquestionbank');

        return $renderer->render_from_template(
            'local_copyquestionbank/warning_help_icon',
            $helpicon->export_for_template($renderer),
        );
    }
}
