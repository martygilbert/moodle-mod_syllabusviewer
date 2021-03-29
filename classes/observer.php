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

    public static function syllabus_updated(\mod_syllabus\event\course_module_updated $event) {
        error_log(print_r($event, true));
        error_log("IN THE OBSERVER!!! SV - UPDATE - variety");
    }

    public static function syllabus_deleted(\mod_syllabus\event\course_module_deleted $event) {
        global $DB;

        $data = $event->get_data();

        $syllabusid = $data['other']['syllabusid'];
        
        delete_syllabus($syllabusid);
    }
}
