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
 * @package   block_weektopper
 * @copyright 2018 Sumit Negi
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/blocks/weektopper/classes/block_weektopper_admin_setting_upload.php');
if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext('block_weektopper/titleweektopper',get_string('toppertitle','block_weektopper'),'',get_string('topper','block_weektopper')));
    $settings->add(new block_weektopper_manage_quiz('block_weektopper/manageweektopper', get_string('manageweektopper', 'block_weektopper'), get_string('manageweektopper', 'block_weektopper'), ''));
    //$settings->add('abc');
    //$settings->add(new admin_setting_configstoredfile('block_weektopper/image', get_string('uploadimage', 'block_weektopper'), get_string('uploadimagedesc', 'block_weektopper'), 'weektopperimage',0,array('maxfiles' => 1, 'accepted_types' => array('.jpg', '.png'))));
    //$settings->add(new admin_setting_configtextarea('block_weektopper/desc', get_string('blocksettingdesc', 'block_weektopper'), get_string('blocksettingdetaildesc', 'block_weektopper'), ''));
    
    
}