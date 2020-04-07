<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/adminlib.php');
class block_weekquiz_manage_quiz extends admin_setting_configtext {
    public function __construct($name, $visiblename, $description, $defaultsetting) {
        parent::__construct($name, $visiblename, $description, $defaultsetting, PARAM_RAW, 50);
    }

    function output_html($data, $query='') {
        // Create a dummy var for this field.
        $this->config_write($this->name, '');

        return format_admin_setting($this, $this->visiblename,
            html_writer::link(new moodle_url('/blocks/weekquiz/manage_weekquiz.php'), get_string('manageweekquiz','block_weekquiz')),
            $this->description, true, '', null, $query);
    }
}