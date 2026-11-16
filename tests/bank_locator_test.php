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

use local_copyquestionbank\local\bank_locator;

/**
 * Tests for the course question bank locator.
 *
 * @package    local_copyquestionbank
 * @copyright  2026 Marcelo M. Almeida Júnior
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_copyquestionbank\local\bank_locator
 */
final class bank_locator_test extends \advanced_testcase {
    /**
     * A course without a question bank has no related backup settings.
     */
    public function test_course_without_question_banks(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        $this->assertSame([
            'qbanks' => [],
            'sections' => [],
        ], bank_locator::get_backup_setting_names($course->id));
    }

    /**
     * Question bank and section setting names match the core backup plan convention.
     */
    public function test_course_question_bank_setting_names(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $firstqbank = $this->getDataGenerator()
            ->get_plugin_generator('mod_qbank')
            ->create_instance(['course' => $course->id]);
        $secondqbank = $this->getDataGenerator()
            ->get_plugin_generator('mod_qbank')
            ->create_instance(['course' => $course->id]);
        $firstcoursemodule = $DB->get_record('course_modules', ['id' => $firstqbank->cmid], '*', MUST_EXIST);

        $this->assertSame([
            'qbanks' => [
                'setting_activity_qbank_' . $firstqbank->cmid . '_included',
                'setting_activity_qbank_' . $secondqbank->cmid . '_included',
            ],
            'sections' => [
                'setting_section_section_' . $firstcoursemodule->section . '_included',
            ],
        ], bank_locator::get_backup_setting_names($course->id));
    }

    /**
     * Shared banks belonging to another course are outside the selected course scope.
     */
    public function test_other_course_question_banks_are_excluded(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $othercourse = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_qbank');
        $ownbank = $generator->create_instance(['course' => $course->id]);
        $generator->create_instance(['course' => $othercourse->id]);

        $settings = bank_locator::get_backup_setting_names($course->id);

        $this->assertSame([
            'setting_activity_qbank_' . $ownbank->cmid . '_included',
        ], $settings['qbanks']);
    }

    /**
     * A bank pending asynchronous deletion must not be selected for a new backup.
     */
    public function test_question_banks_pending_deletion_are_excluded(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_qbank');
        $activebank = $generator->create_instance(['course' => $course->id]);
        $deletedbank = $generator->create_instance(['course' => $course->id]);
        $DB->set_field('course_modules', 'deletioninprogress', 1, ['id' => $deletedbank->cmid]);
        $activesection = $DB->get_field('course_modules', 'section', ['id' => $activebank->cmid], MUST_EXIST);

        $this->assertSame([
            'qbanks' => [
                'setting_activity_qbank_' . $activebank->cmid . '_included',
            ],
            'sections' => [
                'setting_section_section_' . $activesection . '_included',
            ],
        ], bank_locator::get_backup_setting_names($course->id));
    }
}
