<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require('../../config.php');
require('lib.php');
require_once($CFG->dirroot . '/blocks/weektopper/manage_weektopper_form.php');
$id = optional_param('id', -1, PARAM_INT);
$sort = optional_param('sort', 'timemodified', PARAM_ALPHANUM);

$dir = optional_param('dir', 'DESC', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 3, PARAM_INT);
$quizitem = optional_param('quizitem', '', PARAM_INT);
$delete = optional_param('delete', 0, PARAM_INT);
$confirm = optional_param('confirm', '', PARAM_ALPHANUM);   //md5 confirmation hash
$hidequiz = optional_param('hide', 0, PARAM_INT);
$showquiz = optional_param('show', 0, PARAM_INT);
global $DB, $CFG;
require_login();
$weektopperdata = '';
if ($id > 0) {
    $weektopperdata = $DB->get_record('block_weektopper', array('id' => $id), '*', MUST_EXIST);
}
$context = context_system::instance();
require_capability('moodle/site:config', $context);
$strmanageweektopper = get_string('manageweektopper', 'block_weektopper');

$PAGE->set_url(new moodle_url('/admin/settings.php', array('section' => 'blocksettingweektopper')));
$PAGE->set_pagetype('admin-setting-blocksettingweektopper');
$PAGE->set_pagelayout('admin');
$PAGE->set_context($context);
$PAGE->set_title($strmanageweektopper);
$PAGE->set_heading($SITE->fullname);
$PAGE->navbar->add($strmanageweektopper);
//$PAGE->navbar->add(get_string('manageweektopper','block_weektopper'),new moodle_url('#'));
$PAGE->requires->js_call_amd('block_weektopper/weektopper', 'setup');
$formdata = array();

// Hide/show week quiz

if ($hidequiz and confirm_sesskey()) {
    require_capability('block/weektopper:mangeweektopper', $context);

    if ($weekq = $DB->get_record('block_weektopper', array('id' => $hidequiz))) {
        hide_or_show_weektopper($weekq->id, HIDE);
    }
    redirect(new moodle_url('/blocks/weektopper/manage_weektopper.php'));
} else if ($showquiz and confirm_sesskey()) {
    require_capability('block/weektopper:mangeweektopper', $context);

    if ($weekq = $DB->get_record('block_weektopper', array('id' => $showquiz))) {
        hide_or_show_weektopper($weekq->id, SHOW);
    }
    redirect(new moodle_url('/blocks/weektopper/manage_weektopper.php'));
} else if ($delete and confirm_sesskey()) {              // Delete a selected user, after confirmation
    require_capability('block/weektopper:mangeweektopper', $context);

    $weektopper = $DB->get_record('block_weektopper', array('id' => $delete), '*', MUST_EXIST);
    $quizinfo = $DB->get_record('quiz', array('id' => $weektopper->quiz), '*', MUST_EXIST);
    if ($confirm != md5($delete)) {
        echo $OUTPUT->header();

        $fullname = $quizinfo->name;
        echo $OUTPUT->heading(get_string('deleteweektopper', 'block_weektopper'));

        $optionsyes = array('delete' => $delete, 'confirm' => md5($delete), 'sesskey' => sesskey());
        $deleteurl = new moodle_url('/blocks/weektopper/manage_weektopper.php', $optionsyes);
        $deletebutton = new single_button($deleteurl, get_string('delete'), 'post');
        echo $fullname;
        echo $OUTPUT->confirm(get_string('deletecheckfull', 'block_weektopper', "'$fullname'"), $deletebutton, new moodle_url('/blocks/weektopper/manage_weektopper.php'));
        echo $OUTPUT->footer();
        die;
    } else if (data_submitted()) {

        if (delete_weektopper($weektopper->id)) {
            \core\session\manager::gc(); // Remove stale sessions.
            redirect(new moodle_url('/blocks/weektopper/manage_weektopper.php'));
        } else {
            \core\session\manager::gc(); // Remove stale sessions.
            echo $OUTPUT->header();
            echo $OUTPUT->notification(new moodle_url('/blocks/weektopper/manage_weektopper.php'), get_string('deletednot', '', $quizinfo->name));
        }
    }
}
if ($weektopperdata) {
    $quiz = $DB->get_record('quiz', array('id' => $weektopperdata->quiz), '*', MUST_EXIST);
    $formdata = array('id' => $weektopperdata->id, 'quizid' => $weektopperdata->quiz, 'courseid' => $quiz->course, 'courselist' => $quiz->course);
}

$form = new manage_weektopper_form('', $formdata);

if ($weektopperdata) {
    $form->set_data($weektopperdata);
}



if ($form->is_cancelled()) {
    redirect(new moodle_url('/admin/settings.php?section=blocksettingweektopper'));
} else if ($data = $form->get_data()) {

    $quizinfo = $DB->get_record('quiz', array('course' => $data->courselist, 'id' => $quizitem));

    if ($quizitem && (int) $quizinfo->id === $quizitem) {
        // Insert new record
        $weektopper = new stdClass();
        $weektopper->quiz = $quizinfo->id;
       /* $weektopper->available_from = $data->available_from;
        $weektopper->available_to = $data->available_to + (DAYSECS - 1);*/
        $weektopper->timemodified = time();
        if (empty($data->id)) {
            $weektopper->timecreated = time();

            $DB->insert_record('block_weektopper', $weektopper);
            redirect(new moodle_url('/blocks/weektopper/manage_weektopper.php'));
        } else {
            // Update record
            $oldweektopper = $DB->get_record('block_weektopper', array('id' => $data->id));
            $weektopper->id = $oldweektopper->id;
            $DB->update_record('block_weektopper', $weektopper);
            redirect(new moodle_url('/blocks/weektopper/manage_weektopper.php'));
        }
    }
}
echo $OUTPUT->header();
if ($weektopperdata) {

    echo $OUTPUT->single_button(new moodle_url('/blocks/weektopper/manage_weektopper.php'), get_string('addnew', 'block_weektopper'));
}
echo $form->display();
if (!$weektopperdata) {
    $totalrecords = $DB->count_records_sql("SELECT COUNT(wa.quiz) as totalrecord "
            . " FROM {block_weektopper} wa "
            . " INNER JOIN {quiz} q ON q.id = wa.quiz");
    if ($totalrecords < 1) {
        echo $OUTPUT->notification(get_string("nodata", "block_weektopper"));
    }
    if ($sort == 'from') {
        $dbsort = 'wa.available_from';
    } else if ($sort == 'to') {
        $dbsort = 'wa.available_to';
    } else if ($sort == 'name') {
        $dbsort = 'q.name';
    } else if ($sort == 'quizstatus') {
        $dbsort = 'cm.visible';
    } else {

        $dbsort = 'wa.timemodified';
    }
    //$DB->set_debug(true);
    $data = $DB->get_records_sql("SELECT wa.*, q.name ,cm.visible as quizstatus,cm.id as quizcmid"
            . " FROM {block_weektopper} wa "
            . " INNER JOIN {quiz} q ON q.id = wa.quiz "
            . " INNER JOIN {course_modules} cm ON cm.instance = q.id"
            . " INNER JOIN {modules} m ON m.id = cm.module "
            . " WHERE m.name = ?"
            . " ORDER BY $dbsort $dir", array('quiz'), $perpage * $page, $perpage);
    if ($data) {
        $table = new html_table();
        $table->head;
        $table->head = array(get_string('name'), 'Action');
        $columns = array('name', 'quizstatus');
        foreach ($columns as $column) {
            // $string[$column] = get_user_field_name($column);
            if ($sort != $column) {
                $columnicon = "";
                if ($column == "timemodified") {
                    $columndir = "DESC";
                } else {
                    $columndir = "ASC";
                }
            } else {
                $columndir = $dir == "ASC" ? "DESC" : "ASC";
                if ($column == "lastaccess") {
                    $columnicon = ($dir == "ASC") ? "sort_desc" : "sort_asc";
                } else {
                    $columnicon = ($dir == "ASC") ? "sort_asc" : "sort_desc";
                }
                $columnicon = $OUTPUT->pix_icon('t/' . $columnicon, get_string(strtolower($columndir)), 'core', ['class' => 'iconsort']);
            }

            if ($column == 'quizstatus') {
                $str = get_string('quizstatus', 'block_weektopper');
            } else {
                $str = get_string($column);
            }
            $$column = "<a href=\"manage_weektopper.php?sort=$column&amp;dir=$columndir\">" . $str . "</a>$columnicon";
        }
        $table->id = "weektoppertable";
        $table->head = array($name,$quizstatus, get_string('action'));

        foreach ($data as $weektopper) {
            $row = array();
            $row[] = html_writer::link(new moodle_url('/mod/quiz/view.php',array('id'=>$weektopper->quizcmid)),$weektopper->name);
            //$row[] = userdate($weektopper->available_from, get_string('strftimedate'));
            //$row[] = userdate($weektopper->available_to, get_string('strftimedate'));

            $quizstatus = 'NA';
            switch ($weektopper->display) {
                case 1:
                    $quizstatus = get_string('visible', 'block_weektopper');
                    break;
                case 0:
                    $quizstatus = get_string('hidden', 'block_weektopper');
                    break;
            }
            $row[] = $quizstatus;
            $buttons = array();
            if (has_capability('block/weektopper:mangeweektopper', context_system::instance())) {
                if ($weektopper->display) {
                    $url = new moodle_url('/blocks/weektopper/manage_weektopper.php', array('hide' => $weektopper->id, 'sesskey' => sesskey()));
                    $buttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/hide', get_string('hide')));
                } else {
                    $url = new moodle_url('/blocks/weektopper/manage_weektopper.php', array('show' => $weektopper->id, 'sesskey' => sesskey()));
                    $buttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/show', get_string('show')));
                }
                $buttons[] = html_writer::link(new moodle_url('/blocks/weektopper/manage_weektopper.php', array('id' => $weektopper->id)), $OUTPUT->pix_icon('t/edit', 'edit'));
                $url = new moodle_url('/blocks/weektopper/manage_weektopper.php', array('delete' => $weektopper->id, 'sesskey' => sesskey()));
                $buttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/delete', 'delete'));
            }
            $row[] = implode('|', $buttons);
            if (!$weektopper->display) {

                foreach ($row as $k => $v) {
                    $row[$k] = html_writer::tag('span', $v, array('class' => 'dimmed_text'));
                }
            }
            $table->data[] = $row;
        }
    }


    if (!empty($table)) {
        echo html_writer::start_tag('div', array('class' => 'no-overflow'));
        echo html_writer::table($table);
        echo html_writer::end_tag('div');
        echo $OUTPUT->paging_bar($totalrecords, $page, $perpage, new moodle_url('/blocks/weektopper/manage_weektopper.php'));
    }
} else {
    echo $OUTPUT->single_button(new moodle_url('/blocks/weektopper/manage_weektopper.php'), get_string('addnew', 'block_weektopper'));
}
echo $OUTPUT->footer();
