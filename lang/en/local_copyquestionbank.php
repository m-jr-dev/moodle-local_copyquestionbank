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
 * English strings for the question bank copy plugin.
 *
 * @package    local_copyquestionbank
 * @copyright  2026 Marcelo M. Almeida Júnior
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['activitieswarning'] = 'Activities are excluded';
$string['activitieswarning_help'] = 'The site settings or your permissions prevent activities from being included in this backup. Question banks cannot be copied in this backup.';
$string['copyquestionbank'] = 'Copy Question Bank';
$string['copyquestionbank_help'] = 'Select the question bank activities that belong to this course in the backup schema. Moodle uses its standard backup and restore process for the selected banks, categories and questions. Banks owned by another course are not selected by this plugin. Review the schema and verify the restored questions before deleting the original course or bank.';
$string['enabled'] = 'Enable plugin';
$string['enabled_desc'] = 'When enabled, the Copy Question Bank option is shown in course backup settings.';
$string['includedquestionbanks'] = 'Included Question Banks';
$string['nobankwarning'] = 'Question bank unavailable';
$string['nobankwarning_help'] = 'No question bank owned by this course was found.';
$string['pluginname'] = 'Copy Question Bank';
$string['privacy:metadata'] = 'The plugin does not store personal data on the Moodle server. It temporarily keeps the question bank selection preference in browser session storage while navigating the backup stages.';
$string['questionbanks'] = 'Question Banks';
$string['schemawarning'] = 'Backup schema review required';
$string['schemawarning_help'] = 'Use Next to review the backup schema. Jump to final step is unavailable while this option is selected.';
