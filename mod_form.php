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
 * The main mod_syllabusviewer configuration form.
 *
 * @package     mod_syllabusviewer
 * @copyright   2021 Marty Gilbert <martygilbert@gmail>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form.
 *
 * @package    mod_syllabusviewer
 * @copyright  2021 Marty Gilbert <martygilbert@gmail>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_syllabusviewer_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('syllabusviewername', 'mod_syllabusviewer'), array('size' => '64'));

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'syllabusviewername', 'mod_syllabusviewer');

        // Adding the standard "intro" and "introformat" fields.
        if ($CFG->branch >= 29) {
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor();
        }

        // Adding the standard "name" field.
        // Get the categories for a dropdown.
        global $DB;
        $categories = $DB->get_records('course_categories', null, '', 'id');
        //error_log(print_r($categories, true));

        $options = array();
        $options[0] = 'Entire site';
        foreach ($categories as $cat) {
            $category = core_course_category::get($cat->id);
            
            $options[$cat->id] = $category->get_nested_name(false);
        }

        $mform->addElement('select', 'categoryid', get_string('categoryid_desc', 'mod_syllabusviewer'), $options);

        //$mform->addElement('text', 'categoryid', get_string('categoryid_desc', 'mod_syllabusviewer'), array('size' => '64'));
        //$mform->setType('categoryid', PARAM_INT);
        //$mform->addRule('categoryid', get_string('mustbenumber', 'syllabusviewer'), 'numeric');

        // Adding the rest of mod_syllabusviewer settings, spreading all them into this fieldset
        // ... or adding more fieldsets ('header' elements) if needed for better logic.
        //$mform->addElement('static', 'label1', 'syllabusviewersettings', get_string('syllabusviewersettings', 'mod_syllabusviewer'));
        //$mform->addElement('header', 'syllabusviewerfieldset', get_string('syllabusviewerfieldset', 'mod_syllabusviewer'));

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }
}
