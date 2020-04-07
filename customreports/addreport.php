<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require('../../config.php');
global $CFG, $DB, $OUTPUT, $PAGE;
require_once($CFG->dirroot . '/blocks/customreports/classes/report/report.class.php');
require_login();
$context = context_system::instance();
$PAGE->set_context($context);
require_capability("block/customreports:viewreports", $context);
$PAGE->set_url(new moodle_url('/blocks/customreports/addreport.php'));
$PAGE->set_title(get_string('filterreport', 'block_customreports'));
$PAGE->set_heading(get_string('filterreport', 'block_customreports'));
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add(get_string('filterreport', 'block_customreports'));
$PAGE->requires->js_call_amd('block_customreports/customreports', 'setup');
echo $OUTPUT->header();
echo html_writer::div('', 'customreportvalidationerror alert alert-danger', array('style' => 'display:none;'));
$userreport = new user_report();
$userreport->get_filters();
$filter = new stdClass();
$filter->datefrom = optional_param('datefrom', 0, PARAM_INT);
$filter->dateto = optional_param('dateto', 0, PARAM_INT);
$filter->userprofile = optional_param('userprofile', 0, PARAM_RAW_TRIMMED);
$filter->category = optional_param('category', 0, PARAM_INT);
$filter->course = optional_param('course', 0, PARAM_INT);
$filter->activity = optional_param('activity', 0, PARAM_SEQUENCE);
echo html_writer::div(html_writer::img($OUTPUT->image_url('i/loading'), '', array('class' => 'spinner')), 'spinnercontainer', array('style' => 'display:none;'));
echo html_writer::div('', 'reportContainer');
echo $OUTPUT->footer();
