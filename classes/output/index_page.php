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
	var $sometext = null;


	public function __construct($sometext) {
		$this->sometext = $sometext;
	}


	public function export_for_template(renderer_base $output) {
		$data = new stdClass();
		$data->sometext = $this->sometext;
		return $data;
	}
}
