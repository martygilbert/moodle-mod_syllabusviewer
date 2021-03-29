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
 * Adhoc task that will initally load the existing syllabi for this viewer
 *
 * @package    mod_syllabusviewer
 * @copyright  2021 Marty Gilbert <martygilbert@gmail>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace mod_syllabusviewer\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Adhoc task that will initally load the existing syllabi for this viewer
 *
 * @package     mod_syllabusviewer
 * @copyright   2021 Marty Gilbert <martygilbert@gmail>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class load_syllabi_initial extends \core\task\adhoc_task {


    /**
     * Run the task to populate word and character counts on existing forum posts.
     * If the maximum number of records are updated, the task re-queues itself,
     * as there may be more records to process.
     */
    public function execute() {
        global $DB;

        $data = (array)$this->get_custom_data();

        if (empty($this->get_custom_data()) ||
            !isset($data['catid']) ||
            !isset($data['cmid']) ||
            !isset($data['contextid'])) {

            // Must have catid and contextid.
            return;
        }

        // Go through all of the courses for this instance of syllabusviewer.
        // Copy the meta data into mod_syllabusviewer_entries table.
        // Can I get by with just storing the cmid? That gives me course and syllabus id.
        // Copy the file to the mod_syllabusviewer area.

        $coursecat = \core_course_category::get($data['catid']);
        $courses = $coursecat->get_courses(array('recursive' => true, 'idonly' => true));

        $fs = get_file_storage();
        $filerec = array('contextid' => $data['contextid'], 'component' => 'mod_syllabusviewer', 'fileare' => 'content');

        $toinsert = new \stdClass();
        $toinsert->cmid = $data['cmid'];

        foreach ($courses as $cid) {
            $toinsert->courseid = $cid;

            $course = get_course($cid);
            $syllabi = get_all_instances_in_course('syllabus', $course, null, true);

            if (count($syllabi) == 0) {
                // No Syllabus Resource. We should add an entry that has no file information.
                $DB->insert_record('syllabusviewer_entries', $toinsert);
                continue;
            }

            foreach ($syllabi as $syllabus) {
                $toinsert->syllabusid = $syllabus->id;

                $modcon = \context_module::instance($syllabus->coursemodule);

                $files = $fs->get_area_files($modcon->id, 'mod_syllabus', 'content', 0,
                    'sortorder DESC, id ASC', false);

                foreach ($files as $file) {

                    // Only call create_file_from_storedfile if it doesn't exist.
                    if (!$fs->file_exists($data['contextid'], 'mod_syllabusviewer',
                        'content', 0, '/', $file->get_filename())) {
                        $newfile = $fs->create_file_from_storedfile($filerec, $file);
                        $toinsert->filepath = $newfile->get_pathnamehash();
                        $toinsert->timemodified = $newfile->get_timemodified();
                    } else {
                        $newfile = $fs->get_file($data['contextid'], 'mod_syllabusviewer',
                            'content', 0, '/', $file->get_filename());
                        $toinsert->filepath = $newfile->get_pathnamehash();
                        $toinsert->timemodified = $newfile->get_timemodified();
                    }
                    $DB->insert_record('syllabusviewer_entries', $toinsert);
                }

                unset($toinsert->filepath);
                unset($toisnert->timemodified);
                unset($toinsert->syllabusid);
                unset($toinsert->timemodified);
                unset($toinsert->filepath);
            }
        }
    }
}
