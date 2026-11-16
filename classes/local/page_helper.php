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

namespace local_copyquestionbank\local;

use core\url;

/**
 * Identifies the interactive whole-course backup page.
 *
 * @package    local_copyquestionbank
 * @copyright  2026 Marcelo M. Almeida Júnior
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class page_helper {
    /**
     * Checks the Moodle-relative route and excludes activity and section backups.
     *
     * @param \moodle_page $page Current page.
     * @return bool Whether this is a whole-course backup page.
     */
    public static function is_course_backup_page(\moodle_page $page): bool {
        return $page->url->compare(new url('/backup/backup.php'), URL_MATCH_BASE)
            && $page->url->get_param('cm') === null
            && $page->url->get_param('section') === null;
    }
}
