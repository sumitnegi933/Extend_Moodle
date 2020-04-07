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

require_once($CFG->dirroot.'/blocks/weekquiz/classes/block_weekquiz_admin_setting_upload.php');
if ($ADMIN->fulltree) {
    $settings->add(new block_weekquiz_manage_quiz('block_weekquiz/manageweekquiz', get_string('manageweekquiz', 'block_weekquiz'), get_string('manageweekquiz', 'block_weekquiz'), ''));
    //$settings->add('abc');
    $settings->add(new admin_setting_configstoredfile('block_weekquiz/image', get_string('uploadimage', 'block_weekquiz'), get_string('uploadimagedesc', 'block_weekquiz'), 'weekquizimage',0,array('maxfiles' => 1, 'accepted_types' => array('.jpg', '.png'))));
    $settings->add(new admin_setting_configtextarea('block_weekquiz/desc', get_string('blocksettingdesc', 'block_weekquiz'), get_string('blocksettingdetaildesc', 'block_weekquiz'), ''));
    
    
}