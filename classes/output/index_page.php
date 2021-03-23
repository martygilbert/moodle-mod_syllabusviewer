<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * output renderer for mod_syllabusviewer
 *
 * @package     mod_syllabusviewer
 * @copyright   2021 Marty Gilbert <martygilbert@gmail>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// From https://docs.moodle.org/dev/Output_API.

namespace mod_syllabusviewer\output;

use renderable;
use renderer_base;
use templatable;
use stdClass;

class index_page implements renderable, templatable {
    /** Hold the course module id to display */
    var $cmid = null;
    
    /** Hold the sviewer id */
    var $id = null;


    public function __construct($id, $cmid) {
        $this->id = $id;
        $this->cmid = $cmid;
    }


    public function export_for_template(renderer_base $output) {
        global $DB;

        $thisviewer = $DB->get_record('syllabusviewer', array('id' => $this->id), 'categoryid', MUST_EXIST);
        $thiscat = $DB->get_field('course_categories', 'name', array('id' => $thisviewer->categoryid), MUST_EXIST);

        $entries = $DB->get_records('syllabusviewer_entries', array('cmid' => $this->cmid));

        $sql = "SELECT id,shortname FROM {course}
                 WHERE id IN (
                     SELECT DISTINCT courseid 
                       FROM {syllabusviewer_entries} 
                      WHERE cmid=:cmid)
                   ORDER BY shortname";
        //error_log(print_r($entries, true));

        $shortnames = $DB->get_records_sql($sql, array('cmid'=> $this->cmid));
        //$shortnames = $DB->get_records_select('course', 'id in(:courseids)', array('courseids' => $ids), 'shortname', 'id, shortname');
        
        //error_log(print_r($shortnames, true));

        // CS111 -
        //  -teachers
        //      --Me
        //      --Stefen
        //  --files
        //      --<link>
        //      --<link>

        $courses = array();

        $fs = get_file_storage();
        foreach ($shortnames as $sname) {

            $course = new stdClass();
            $course->shortname = $sname->shortname;


            // Load teachers.
            $coursecon = \context_course::instance($sname->id);
            $teachers = get_users_by_capability($coursecon, 'mod/assign:grade', 'u.id, firstname, lastname');

            $teacherstoadd = array();
            foreach ($teachers as $teacher) {
                $teacherstoadd[] = $teacher->firstname.' '.$teacher->lastname;
            }
            $course->teachers = $teacherstoadd;

            // What about files?
            $course->files = array();
            foreach ($entries as $idx => $entry) {
                if ($entry->courseid == $sname->id && !is_null($entry->filepath)) {
                    $files = array();
                    $file = $fs->get_file_by_hash($entry->filepath);
                    error_log(print_r($file, true));
                    $files['name'] = $file->get_filename();
                    $files['link'] = \moodle_url::make_pluginfile_url($file->get_contextid(),
                        $file->get_component(),
                        $file->get_filearea(),
                        $file->get_itemid(),
                        $file->get_filepath(),
                        $file->get_filename(),
                        false);
                    $course->files[] = $files;

                    // Don't need this one anymore.
                    unset($entries[$idx]);
                }

            }

            //error_log(print_r($course, true));

            //$course->files = $files;
            /*
            if (count($course->files) == 0) {
                error_log("Unsetting files property for $sname->shortname");
                unset($course->files);
            } else {
                error_log("count isn't 0, it's ".count($files));
            }
            */

            //unset($files);


            $courses[] = $course;
        }
        $course = new stdClass();
        $course->shortname = 'CS111.01';
        $course->teachers = array('Marty', 'Stefen');

        $course->files = array('name' => 'file1name', 'link' => 'file2link');

        $data = array(
            'catname' => $thiscat,
            'courses' => $courses,
            /*
            'courses' => array(
                'shortname' => 'CS111',
                'teachers' => array(
                    array('name' => 'Marty'),
                    array('name' => 'Stefen'),
                ),
                'files' => array(
                    array('name' => 'filelink1'),
                    array('name' => 'filelink2'),
                ),
            ),
                */
        );

        return $data;
    }
}
