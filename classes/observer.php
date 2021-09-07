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
 * The mod_syllabusviewer observer
 *
 * @package    mod_syllabusviewer
 * @copyright  2021 Marty Gilbert <martygilbert@gmail>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/syllabusviewer/locallib.php');

class mod_syllabusviewer_observer {
    public static function course_created(\core\event\course_created $event) {
        // What happens when a new course is created?
        // First, if there is no SV, return.
        // Then, if the course doesn't exist, return
        // Then checked to see if this course is in a category that is being
        // tracked by a SV. If so, add a NULL entry in sv_entries. 
        // If not, ignore it.
        global $DB;

        $viewers = $DB->get_records('syllabusviewer');
        if (!$viewers) {
            return;
        }

        $course = $DB->get_record('course', array('id' => $event->courseid), 'id,category', MUST_EXIST);
        if (!$course) {
            return;
        }

        $cat = \core_course_category::get($course->category);
        $parents = $cat->get_parents();

        $toinsert = new stdClass();
        $toinsert->courseid = $course->id;
        foreach ($viewers as $viewer) {
            // Does this viewer track this category?
            if ($viewer->categoryid == 0 ||
                $viewer->categoryid == $course->category ||
                in_array($viewer->categoryid, $parents)) {

                $cm = $DB->get_record('course_modules', array('instance' => $viewer->id), 'id', MUST_EXIST);
                if (is_frozen($cm->id)) {
                    error_log("not adding the blank entry for this new course because the viewer is 'frozen'");
                    continue;
                }

                $toinsert->cmid = $cm->id;

                $DB->insert_record('syllabusviewer_entries', $toinsert);
            }
        }
    }

    public static function course_deleted(\core\event\course_deleted $event) {
        // If a course is a deleted, it first deletes all of the modules in the course
        // (which would trigger event syllabus_updated()). That method ensures that a NULL
        // entry is in the sv_entries table if a file is removed, to show that the course
        // has no syllabus. Now that the course is being deleted, we need to remove all of
        // those NULL entries, too, as the course is no longer tracked.
        global $DB;


        // This is more complicated now with the 'frozen' flag. We must get all of the
        // entires with this courseid, and delete them one-by-one **if** the frozen flag
        // isn't set for this viewer cmid.

        $entries = $DB->get_records('syllabusviewer_entries', ['courseid' => $event->courseid]);

        foreach ($entries as $entry) {
            if (!is_frozen($entry->cmid)) {
                $DB->delete_records('syllabusviewer_entries', ['courseid' => $event->courseid, 'cmid' => $entry->cmid]);
            } else {
                error_log("Not deleting the records for this course b/c the viewer is frozen");
            }
        }
    }

    public static function syllabus_updated(\mod_syllabus\event\course_module_updated $event) {
    }

    public static function syllabus_added(\mod_syllabus\event\course_module_added $event) {
        global $DB;

        $data = $event->get_data();

        $syllabusid = $data['other']['syllabusid'];
        $syllabuscmid = $data['other']['cmid'];

        add_syllabus_entry($syllabuscmid, 6);
    }

    public static function syllabus_deleted(\mod_syllabus\event\course_module_deleted $event) {
        global $DB;

        $data = $event->get_data();

        $syllabusid = $data['other']['syllabusid'];

        delete_syllabus($syllabusid);
    }
}
