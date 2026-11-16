# Copy Question Bank (`local_copyquestionbank`)

Copy Question Bank adds a **Copy Question Bank** option to Moodle's interactive
course backup. It selects question bank activities owned by the course and groups
their native backup controls together so each bank can be reviewed separately.

## Requirements

- Moodle 5.1 or later.
- The standard Question bank activity (`mod_qbank`).
- Permission to back up the course.
- JavaScript enabled in the browser.

## Installation

1. Install the ZIP through **Site administration > Plugins > Install plugins**.
   For a manual installation, place `copyquestionbank` in
   `public/local/copyquestionbank` within the Moodle source tree.
2. Open **Site administration > Notifications** and complete the installation.
3. Open **Site administration > Plugins > Local plugins > Copy Question Bank**
   to enable or disable the integration. It is enabled by default.

When updating an existing installation, replace the plugin files and run the
upgrade through **Notifications**. Purge Moodle caches if an old interface
remains visible.

## Use

1. Open a course and start a full course backup.
2. Leave **Copy Question Bank** selected in **Backup settings**. The option is
   selected by default when the course contains a question bank.
3. Choose **Next**. **Jump to final step** is unavailable while the option is
   selected because the question bank settings must be reviewed in the schema.
4. Review the **Question Banks** group above the course sections. The plugin
   initially selects the course's question banks and the sections required to
   include them. Clear any bank that should be excluded.
5. Review the selected banks in **Included Question Banks** on the confirmation
   page, then complete the backup.
6. Restore the backup using Moodle's standard restore process.

If the course has no question bank, the option is disabled and its help icon
explains why. English and Brazilian Portuguese interface strings are included.

## Scope

The plugin prepares Moodle's existing backup settings. Question bank activities,
questions, categories and activity references are exported and restored by
Moodle and the installed question types. The plugin does not copy question
records directly or replace Moodle's restore rules.

Only question banks belonging to the course being backed up are selected.
Banks shared from another course are outside this scope. Banks being deleted
are ignored. Review the restored course and its question references before
removing any source content.

The integration applies to interactive full-course backups. Activity-only,
section-only, automated and command-line backups use the standard Moodle flow.
The plugin uses Moodle's existing course-backup permission and adds no separate
capability.

## Privacy

The plugin creates no database tables and stores no question content or personal
data. Its global enabled setting is stored in Moodle's plugin configuration.
The browser retains backup form preferences in session storage while the user
moves between stages. These preferences are scoped to the current backup and
are not sent to an external service.

The plugin implements `\core_privacy\local\metadata\null_provider`.

## Development

The JavaScript source is in `amd/src/course_backup_ui.js`. Its compiled AMD file
and source map are in `amd/build/`. Rebuild these files with Moodle's Grunt tasks
when changing the JavaScript source.

The GitHub Actions workflow in `.github/workflows/ci.yml` is configured for
Moodle 5.1 with PHP 8.2, 8.3 and 8.4, using PostgreSQL 15 and MariaDB 10.11.
It runs PHP syntax and coding checks, plugin validation, Mustache and JavaScript
checks, and PHPUnit tests.

Before releasing a version, also check the interactive backup and restore flow
with multiple banks, courses without question banks, individual exclusions and
navigation back to the schema. Repeat the check on a Moodle installation served
from a subdirectory. The restore check should include quizzes that use the
selected banks.

## License

Copyright 2026 Marcelo M. Almeida Júnior.

GNU GPL v3 or later. See `LICENSE`.
