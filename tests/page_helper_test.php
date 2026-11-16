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

use core\url;
use local_copyquestionbank\local\page_helper;

/**
 * Tests for the interactive course backup page scope.
 *
 * @package    local_copyquestionbank
 * @copyright  2026 Marcelo M. Almeida Júnior
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_copyquestionbank\local\page_helper
 */
final class page_helper_test extends \advanced_testcase {
    /**
     * Backup-stage parameters do not change the whole-course page scope.
     */
    public function test_course_backup_page_is_accepted(): void {
        $page = new \moodle_page();
        $page->set_url(new url('/backup/backup.php', ['id' => 2, 'stage' => 2, 'backup' => 'abc123']));

        $this->assertTrue(page_helper::is_course_backup_page($page));
    }

    /**
     * A Moodle installation below the domain root keeps the same backup scope.
     */
    public function test_course_backup_page_in_subdirectory_is_accepted(): void {
        global $CFG;

        $this->resetAfterTest();
        $CFG->wwwroot .= '/learning';

        $page = new \moodle_page();
        $page->set_url(new url('/backup/backup.php', ['id' => 2]));

        $this->assertTrue(page_helper::is_course_backup_page($page));
    }

    /**
     * Activity and section backups must retain Moodle's own settings.
     */
    public function test_activity_and_section_backups_are_excluded(): void {
        foreach (['cm', 'section'] as $parameter) {
            $page = new \moodle_page();
            $page->set_url(new url('/backup/backup.php', ['id' => 2, $parameter => 10]));

            $this->assertFalse(page_helper::is_course_backup_page($page));
        }
    }

    /**
     * Restoration and course-import workflows are outside this integration.
     */
    public function test_other_backup_workflows_are_excluded(): void {
        foreach (['/backup/restore.php', '/backup/import.php'] as $path) {
            $page = new \moodle_page();
            $page->set_url(new url($path, ['id' => 2]));

            $this->assertFalse(page_helper::is_course_backup_page($page));
        }
    }
}
