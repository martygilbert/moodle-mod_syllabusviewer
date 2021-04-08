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
require_once($CFG->dirroot.'/mod/syllabusviewer/locallib.php');

/**
 * Adhoc task that will initally load the existing syllabi for this viewer
 *
 * @package     mod_syllabusviewer
 * @copyright   2021 Marty Gilbert <martygilbert@gmail>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class load_syllabi_initial extends \core\task\adhoc_task {


    /**
     * Run the task to load all of the syllabus files from the
     * specified category into the syllabusviewer file area.
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

        // Clear any remnants in the sv_entries table. Shouldn't exist.
        $DB->delete_records('syllabusviewer_entries', array('cmid' => $data['cmid']));

        // Go through all of the courses for this instance of syllabusviewer.
        // Copy the meta data into mod_syllabusviewer_entries table.
        // Can I get by with just storing the cmid? That gives me course and syllabus id.
        // Copy the file to the mod_syllabusviewer area.

        $coursecat = \core_course_category::get($data['catid']);
        $courses = $coursecat->get_courses(array('recursive' => true, 'idonly' => true));

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
                add_syllabus_files_to_viewer($syllabus->coursemodule, $data['cmid'],
                    0, $syllabus->id, $data['contextid']);
            }
        }
    }
}
