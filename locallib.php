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

defined('MOODLE_INTERNAL') || die;
require_once("$CFG->libdir/filelib.php");

/**
 * Local library functions
 *
 * @package     mod_syllabusviewer
 * @copyright   2021 Marty Gilbert <martygilbert@gmail>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Delete a syllabus from syllabus_entries and any related files from sv  If there are no
 * other syllabus entries with this courseid, then make one with NULL values.
 * @param syllbusid int id of syllabus to delete
 */
function delete_syllabus($syllabusid) {
    global $DB;

    // Get the entry(ies).
    $entries = $DB->get_records('syllabusviewer_entries', array('syllabusid' => $syllabusid));
    if (!$entries) {
        return;
    }

    // Delete the syllabus entries - they're gone.
    $DB->delete_records('syllabusviewer_entries', array('syllabusid' => $syllabusid));

    // Delete the associated files, if any.
    foreach ($entries as $thisentry) {
        if (!is_null($thisentry->pathnamehash)) {

            // Count with this pathnamehash should == 0 to proceed with file deletion.
            $num = $DB->count_records('syllabusviewer_entries', array('pathnamehash' => $thisentry->pathnamehash));
            if ($num > 0) {
                continue;
            }

            $fs = get_file_storage();
            $file = $fs->get_file_by_hash($thisentry->pathnamehash);
            if ($file) {
                if (!$file->delete()) {
                    mtrace ("Error deleting file with pathnamehash $thisentry->pathnamehash");
                }
            }
        }
    }

    $thisentry = array_shift($entries);

    // How many are left in this course?
    $numleft = $DB->count_records('syllabusviewer_entries', array('courseid' => $thisentry->courseid));

    // If none are left, add a NULL entry to say that this course doesn't have a syllabus.
    if ($numleft == 0) {
        unset($thisentry->id);
        unset($thisentry->syllabusid);
        unset($thisentry->pathnamehash);
        unset($thisentry->timemodified);

        $DB->insert_record('syllabusviewer_entries', $thisentry);
    }
}
