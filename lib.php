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
 * Library of interface functions and constants.
 *
 * @package     mod_syllabusviewer
 * @copyright   2021 Marty Gilbert <martygilbert@gmail>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function syllabusviewer_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_syllabusviewer into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_syllabusviewer_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function syllabusviewer_add_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timecreated = time();

    $id = $DB->insert_record('syllabusviewer', $moduleinstance);

    // Trigger the load_syllabi_initial task.
    $modcon = context_module::instance($moduleinstance->coursemodule);
    $loadsyllabi = new \mod_syllabusviewer\task\load_syllabi_initial();
    $loadsyllabi->set_custom_data(array(
       'contextid'  => $modcon->id,
       'catid'      => $moduleinstance->categoryid,
       'cmid'       => $moduleinstance->coursemodule,
    ));

    \core\task\manager::queue_adhoc_task($loadsyllabi);

    return $id;
}

/**
 * Updates an instance of the mod_syllabusviewer in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_syllabusviewer_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function syllabusviewer_update_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;

    return $DB->update_record('syllabusviewer', $moduleinstance);
}

/**
 * Removes an instance of the mod_syllabusviewer from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function syllabusviewer_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('syllabusviewer', array('id' => $id));
    if (!$exists) {
        return false;
    }

    // Files are deleted automagically from mdl_files (should probably verify this: Issue #4).
    list($course, $cm) = get_course_and_cm_from_instance($id, 'syllabusviewer');

    $DB->delete_records('syllabusviewer_entries', array('cmid' => $cm->id));
    $DB->delete_records('syllabusviewer', array('id' => $id));
    return true;
}
/**
 * Serve the files from the syllabusviewer file areas
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if the file not found, just send the file otherwise and do not return anything
 */
function syllabusviewer_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options) {

    // Adapted from https://docs.moodle.org/dev/File_API#Serving_files_to_users
    // Check the contextlevel is as expected - if your plugin is a block, this becomes CONTEXT_BLOCK, etc.
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    // Make sure the filearea is one of those used by the plugin.
    if ($filearea !== 'content') {
        return false;
    }

    // Make sure the user is logged in and has access to the module
    // (plugins that are not course modules should leave out the 'cm' part).
    require_login($course, true, $cm);

    // Check the relevant capabilities - these may vary depending on the filearea being accessed.
    if (!has_capability('mod/syllabusviewer:view', $context)) {
        return false;
    }

    // Leave this line out if you set the itemid to null in make_pluginfile_url (set $itemid to 0 instead).
    $itemid = array_shift($args); // The first item in the $args array.

    // Use the itemid to retrieve any relevant data records and perform any security checks to see if the
    // user really does have access to the file in question.

    // Extract the filename / filepath from the $args array.
    $filename = array_pop($args); // The last item in the $args array.
    if (!$args) {
        $filepath = '/'; // If $args is empty => the path is '/'.
    } else {
        $filepath = '/'.implode('/', $args).'/'; // If $args contains elements of the filepath.
    }

    // Retrieve the file from the Files API.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_syllabusviewer', $filearea, $itemid, $filepath, $filename);
    error_log(print_r($file, true));
    if (!$file) {
        return false; // The file does not exist.
    }

    // We can now send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering.
    send_stored_file($file, 86400, 0, $forcedownload, $options);
}
