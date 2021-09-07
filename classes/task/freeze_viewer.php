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
 * Adhoc task that will freeze this viewer.
 *
 * @package     mod_syllabusviewer
 * @copyright   2021 Marty Gilbert <martygilbert@gmail>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class freeze_viewer extends \core\task\adhoc_task {


    /**
     * Run the task to load all of the syllabus files from the
     * specified category into the syllabusviewer file area.
     */
    public function execute() {
        global $DB;

        $data = (array)$this->get_custom_data();

        if (empty($this->get_custom_data()) ||
            !isset($data['viewerid']) ||
            !isset($data['cmid'])) {
            // Must have viewerid and cmid.
            return;
        }

        mtrace("About to freeze viewer...");

        $viewer = $DB->get_record('syllabusviewer', ['id' => $data['viewerid']], '*', MUST_EXIST);
        $entries = $DB->get_records('syllabusviewer_entries', ['cmid' => $data['cmid']]);

        $coursecat = \core_course_category::get($viewer->categoryid);

        // Set the cat name for this viewer
        $viewer->categoryname = $coursecat->name;
        $DB->update_record('syllabusviewer', $viewer);

        // Load all of the courses in the category, for speed.
        $courses = $coursecat->get_courses(array('recursive' => true));

        // Go through each entry, and set the teachers and course shortname
        foreach ($entries as $entry) {
           
            $entry->shortname = $courses[$entry->courseid]->shortname;    

            $coursecon = \context_course::instance($entry->courseid);
            $teachers = get_users_by_capability($coursecon, 'mod/assign:grade', 'u.id, firstname, lastname');

            if (!$teachers) {
                $entry->teachers = "No teacher listed";
            } else {
                foreach($teachers as $teacher) {
                    $entry->teachers .= $teacher->firstname.' '.$teacher->lastname.' ';
                }
            }

            $DB->update_record('syllabusviewer_entries', $entry, true);
        }

    }
}
