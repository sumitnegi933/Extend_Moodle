<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class block_customreports extends block_list {

    function init() {

        $this->title = get_string('title', 'block_customreports');
    }

    function get_content() {
        if ($this->content !== NULL) {
            return $this->content;
        }
        $context = context_system::instance();
        $this->content = new stdClass();
        if (!has_capability("block/customreports:viewreports", $context)) {
            return $this->content;
        }
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';
        $this->content->items[] = html_writer::link(new moodle_url('/blocks/customreports/addreport.php'), get_string('viewreports', "block_customreports"));
        return $this->content;
    }

    function applicable_formats() {

        return array('all' => true);
    }

    function get_all_reports() {
        
    }

    public function has_config() {
        return true;
    }

}
