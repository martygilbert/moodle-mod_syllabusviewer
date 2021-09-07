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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * output renderer for mod_syllabusviewer
 *
 * @package     mod_syllabusviewer
 * @copyright   2021 Marty Gilbert <martygilbert@gmail>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// From https://docs.moodle.org/dev/Output_API.

namespace mod_syllabusviewer\output;
defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;
use stdClass;

class frozen_index_page implements renderable, templatable {
    /* Hold the course module id to display */
    private $cmid = null;

    /* Hold the sviewer id */
    private $id = null;

    public function __construct($id, $cmid) {
        $this->id = $id;
        $this->cmid = $cmid;
    }


    public function export_for_template(renderer_base $output) {
        global $DB;

        $thisviewer = $DB->get_record('syllabusviewer', ['id' => $this->id], 'categoryid, categoryname', MUST_EXIST);

        // Need shortname, teachers
        $sql = "  SELECT id, courseid, shortname, teachers
                    FROM {syllabusviewer_entries}
                   WHERE cmid=:cmid 
                GROUP BY shortname, teachers
                ORDER BY shortname";

        $shortnames = $DB->get_records_sql($sql, array('cmid' => $this->cmid));

        $courses = array();

        $fs = get_file_storage();
        foreach ($shortnames as $sname) {

            $course = new stdClass();

            $course->shortname = $sname->shortname;
            $course->teachers = $sname->teachers;

            $entries = $DB->get_records('syllabusviewer_entries', ['cmid' => $this->cmid, 'courseid' => $sname->courseid]);
            $course->files = array();

            // Add the file(s).
            foreach ($entries as $idx => $entry) {
                if (!is_null($entry->pathnamehash)) {
                    $files = array();
                    $file = $fs->get_file_by_hash($entry->pathnamehash);

                    $files['name'] = $file->get_filename();
                    $files['link'] = \moodle_url::make_pluginfile_url($file->get_contextid(),
                        $file->get_component(),
                        $file->get_filearea(),
                        $file->get_itemid(),
                        $file->get_filepath(),
                        $file->get_filename(),
                        false);
                    $course->files[] = $files;

                    //error_log(print_r($files, true));

                    unset($entries[$idx]);
                }
            }
            $courses[] = $course;
        }

        $data = array(
            'catname' => $thisviewer->categoryname,
            'courses' => $courses,
        );

        return $data;
    }
}
