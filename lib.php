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
    //error_log(print_r($moduleinstance, true));
    //error_log("ID is $id");
    $loadsyllabi = new \mod_syllabusviewer\task\load_syllabi_initial();
    $loadsyllabi->set_custom_data(array(
       'contextid'  => $modcon->id,
       'catid'      => $moduleinstance->categoryid,
       'cmid'       => $moduleinstance->coursemodule,
    ));

    //error_log ("About to trigger task load_syllabi_initial");
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

    $DB->delete_records('syllabusviewer', array('id' => $id));

    // TODO Delete the entries (and files?) in syllabusviewer_entries

    return true;
}
