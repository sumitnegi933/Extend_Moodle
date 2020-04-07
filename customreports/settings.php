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
 *
 * @package   block_weekquiz
 * @copyright 2018 Sumit Negi
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/blocks/customreports/locallib.php');

if ($ADMIN->fulltree) {
    global $DB;
    $userprofilefields = get_user_core_fields();

    $fields = $DB->get_records('user_info_field', null, 'id ASC');
    if ($fields) {
        foreach ($fields as $field) {
            $userprofilefields['profile_field_' . $field->shortname] = $field->name;
        }
    }


    $settings->add(new admin_setting_configmulticheckbox('block_customreports/profilefields', get_string('profilefields', 'block_customreports'), get_string('profilefields_desc', 'block_customreports'), array(), $userprofilefields));
    $userprofilefields = array();
    if ($fields) {
        foreach ($fields as $field) {
            $userprofilefields[$field->shortname] = $field->name;
        }
         $settings->add(new admin_setting_configmulticheckbox('block_customreports/customfieldreportcolumn', get_string('showprofilefieldsinreport', 'block_customreports'), get_string('customfieldreportcolumn_desc', 'block_customreports'), array(), $userprofilefields));
    }
   
}