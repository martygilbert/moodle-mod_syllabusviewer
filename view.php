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
 * Prints an instance of mod_syllabusviewer.
 *
 * @package     mod_syllabusviewer
 * @copyright   2021 Marty Gilbert <martygilbert@gmail>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

// Course_module ID, or
$id = optional_param('id', 0, PARAM_INT);

// ... module instance id.
$s  = optional_param('s', 0, PARAM_INT);

if ($id) {
    $cm             = get_coursemodule_from_id('syllabusviewer', $id, 0, false, MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('syllabusviewer', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($s) {
    $moduleinstance = $DB->get_record('syllabusviewer', array('id' => $n), '*', MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm             = get_coursemodule_from_instance('syllabusviewer', $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    print_error(get_string('missingidandcmid', 'mod_syllabusviewer'));
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);

$event = \mod_syllabusviewer\event\course_module_viewed::create(array(
    'objectid' => $moduleinstance->id,
    'context' => $modulecontext
));

$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('syllabusviewer', $moduleinstance);
$event->trigger();

$PAGE->set_url('/mod/syllabusviewer/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();

// MJG STARTING HERE.

error_log(print_r($cm,true));

$sventries = $DB->get_records('syllabusviewer_entries', array('cmid' => $cm->id));

echo "<table border=1 style=\"width: 100%\"><tr><th>cmid</th><th>course</th><th>filepath</th></tr>\n";
foreach ($sventries as $entry) {
    echo '<tr><td>'.$entry->cmid.'</td><td>'.$entry->courseid.'</td><td>'.$entry->filepath.'</td></tr>'."\n"; 
}
echo "</table>";

/*
$catid = 1; // This should be loaded from when the viewer was created.
$coursecat = core_course_category::get($catid);
$courses = $coursecat->get_courses(array('recursive' => true, 'idonly' => true));

$fs = get_file_storage();

$file_record = array('contextid'=>$modulecontext->id, 'component'=>'mod_syllabusviewer', 'filearea'=>'content');

foreach ($courses as $cid) {
    $course = get_course($cid);

    $thiscoursecat = \core_course_category::get($course->category);
    $children = $thiscoursecat->get_children();
    $origcatpath = $thiscoursecat->get_nested_name(false);
    $catpath = preg_replace("/[^A-Za-z0-9\/]/", '', $origcatpath);

    $syllabi = get_all_instances_in_course('syllabus', $course, null, true);

    //$newpath = $dest . '/' . $catpath . '/' . $course->shortname;

    $coursecon = context_course::instance($cid);
    $teachers = get_users_by_capability($coursecon, 'mod/assign:grade');

    $teacherdisp = "Teachers for this course:\n";
    foreach ($teachers as $teacher) {
        $teacherdisp .= $teacher->firstname .' '.$teacher->lastname.','.$teacher->email."\n";
    }

    $counter = 0;

    echo "$origcatpath<br />\n";

    foreach ($syllabi as $syllabus) {

        $modcon = context_module::instance($syllabus->coursemodule);

        $files = $fs->get_area_files($modcon->id, 'mod_syllabus', 'content', 0,
            'sortorder DESC, id ASC', false);

        //error_log(print_r($files, true));
        foreach ($files as $file) {
            // Move to this syllabusviewer's area

            $retval = $fs->create_file_from_storedfile($file_record, $file);

            error_log(print_r($retval, true));

        }

    }
    
}

*/
// MJG ENDING HERE.

echo $OUTPUT->footer();
