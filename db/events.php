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
 * observers for mod_syllabusviewer
 *
 * @package     mod_syllabusviewer
 * @copyright   2021 Marty Gilbert <martygilbert@gmail>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

$observers = array(
    array(
        'eventname' => '\mod_syllabus\event\course_module_updated',
        'callback' => 'mod_syllabusviewer_observer::syllabus_updated',
    ),
    array(
        'eventname' => '\mod_syllabus\event\course_module_deleted',
        'callback' => 'mod_syllabusviewer_observer::syllabus_deleted',
    ),
    array(
        'eventname' => '\mod_syllabus\event\course_module_added',
        'callback' => 'mod_syllabusviewer_observer::syllabus_added',
    ),
    array(
        'eventname' => '\core\event\course_deleted',
        'callback' => 'mod_syllabusviewer_observer::course_deleted',
    ),
    array(
        'eventname' => '\core\event\course_created',
        'callback' => 'mod_syllabusviewer_observer::course_created',
    ),
);
