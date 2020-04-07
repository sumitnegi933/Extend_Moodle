<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require('../../config.php');
require('lib.php');
require_once($CFG->dirroot . '/blocks/weekquiz/manage_weekquiz_form.php');
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
$weekquizdata = '';
if ($id > 0) {
    $weekquizdata = $DB->get_record('block_weekquiz_availability', array('id' => $id), '*', MUST_EXIST);
}
$context = context_system::instance();
require_capability('moodle/site:config', $context);
$strmanageweekquiz = get_string('manageweekquiz', 'block_weekquiz');

$PAGE->set_url(new moodle_url('/admin/settings.php', array('section' => 'blocksettingweekquiz')));
$PAGE->set_pagetype('admin-setting-blocksettingweekquiz');
$PAGE->set_pagelayout('admin');
$PAGE->set_context($context);
$PAGE->set_title($strmanageweekquiz);
$PAGE->set_heading($SITE->fullname);
$PAGE->navbar->add($strmanageweekquiz);
//$PAGE->navbar->add(get_string('manageweekquiz','block_weekquiz'),new moodle_url('#'));
$PAGE->requires->js_call_amd('block_weekquiz/weekquiz', 'setup');
$formdata = array();

// Hide/show week quiz

if ($hidequiz and confirm_sesskey()) {
    require_capability('block/weekquiz:mangeweekquiz', $context);

    if ($weekq = $DB->get_record('block_weekquiz_availability', array('id' => $hidequiz))) {
        hide_or_show_weekquiz($weekq->id, HIDE);
    }
    redirect(new moodle_url('/blocks/weekquiz/manage_weekquiz.php'));
} else if ($showquiz and confirm_sesskey()) {
    require_capability('block/weekquiz:mangeweekquiz', $context);

    if ($weekq = $DB->get_record('block_weekquiz_availability', array('id' => $showquiz))) {
        hide_or_show_weekquiz($weekq->id, SHOW);
    }
    redirect(new moodle_url('/blocks/weekquiz/manage_weekquiz.php'));
} else if ($delete and confirm_sesskey()) {              // Delete a selected user, after confirmation
    require_capability('block/weekquiz:mangeweekquiz', $context);

    $weekquiz = $DB->get_record('block_weekquiz_availability', array('id' => $delete), '*', MUST_EXIST);
    $quizinfo = $DB->get_record('quiz', array('id' => $weekquiz->quiz), '*', MUST_EXIST);
    if ($confirm != md5($delete)) {
        echo $OUTPUT->header();

        $fullname = $quizinfo->name;
        echo $OUTPUT->heading(get_string('deleteweekquiz', 'block_weekquiz'));

        $optionsyes = array('delete' => $delete, 'confirm' => md5($delete), 'sesskey' => sesskey());
        $deleteurl = new moodle_url('/blocks/weekquiz/manage_weekquiz.php', $optionsyes);
        $deletebutton = new single_button($deleteurl, get_string('delete'), 'post');
        echo $fullname;
        echo $OUTPUT->confirm(get_string('deletecheckfull', 'block_weekquiz', "'$fullname'"), $deletebutton, new moodle_url('/blocks/weekquiz/manage_weekquiz.php'));
        echo $OUTPUT->footer();
        die;
    } else if (data_submitted()) {

        if (delete_weekquiz($weekquiz->id)) {
            \core\session\manager::gc(); // Remove stale sessions.
            redirect(new moodle_url('/blocks/weekquiz/manage_weekquiz.php'));
        } else {
            \core\session\manager::gc(); // Remove stale sessions.
            echo $OUTPUT->header();
            echo $OUTPUT->notification(new moodle_url('/blocks/weekquiz/manage_weekquiz.php'), get_string('deletednot', '', $quizinfo->name));
        }
    }
}
if ($weekquizdata) {
    $quiz = $DB->get_record('quiz', array('id' => $weekquizdata->quiz), '*', MUST_EXIST);
    $formdata = array('id' => $weekquizdata->id, 'quizid' => $weekquizdata->quiz, 'courseid' => $quiz->course, 'courselist' => $quiz->course);
}

$form = new manage_weekquiz_form('', $formdata);

if ($weekquizdata) {
    $form->set_data($weekquizdata);
}



if ($form->is_cancelled()) {
    redirect(new moodle_url('/admin/settings.php?section=blocksettingweekquiz'));
} else if ($data = $form->get_data()) {

    $quizinfo = $DB->get_record('quiz', array('course' => $data->courselist, 'id' => $quizitem));

    if ($quizitem && (int) $quizinfo->id === $quizitem) {
        // Insert new record
        $weekquiz = new stdClass();
        $weekquiz->quiz = $quizinfo->id;
        $weekquiz->available_from = $data->available_from;
        $weekquiz->available_to = $data->available_to + (DAYSECS - 1);
        $weekquiz->timemodified = time();
        if (empty($data->id)) {
            $weekquiz->timecreated = time();
            $DB->insert_record('block_weekquiz_availability', $weekquiz);
            redirect(new moodle_url('/blocks/weekquiz/manage_weekquiz.php'));
        } else {
            // Update record
            $oldweekquiz = $DB->get_record('block_weekquiz_availability', array('id' => $data->id));
            $weekquiz->id = $oldweekquiz->id;
            $DB->update_record('block_weekquiz_availability', $weekquiz);
            redirect(new moodle_url('/blocks/weekquiz/manage_weekquiz.php'));
        }
    }
}
echo $OUTPUT->header();
if ($weekquizdata) {

    echo $OUTPUT->single_button(new moodle_url('/blocks/weekquiz/manage_weekquiz.php'), get_string('addnew', 'block_weekquiz'));
}
echo $form->display();
if (!$weekquizdata) {
    $totalrecords = $DB->count_records_sql("SELECT COUNT(wa.quiz) as totalrecord "
            . " FROM {block_weekquiz_availability} wa "
            . " INNER JOIN {quiz} q ON q.id = wa.quiz");
    if ($totalrecords < 1) {
        echo $OUTPUT->notification(get_string("nodata", "block_weekquiz"));
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
            . " FROM {block_weekquiz_availability} wa "
            . " INNER JOIN {quiz} q ON q.id = wa.quiz "
            . " INNER JOIN {course_modules} cm ON cm.instance = q.id"
            . " INNER JOIN {modules} m ON m.id = cm.module "
            . " WHERE m.name = ?"
            . " ORDER BY $dbsort $dir", array('quiz'), $perpage * $page, $perpage);
    if ($data) {
        $table = new html_table();
        $table->head;
        $table->head = array(get_string('name'), get_string('from'), get_string('to'), 'Action');
        $columns = array('name', 'from', 'to', 'quizstatus');
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
                $str = get_string('quizstatus', 'block_weekquiz');
            } else {
                $str = get_string($column);
            }
            $$column = "<a href=\"manage_weekquiz.php?sort=$column&amp;dir=$columndir\">" . $str . "</a>$columnicon";
        }
        $table->id = "weekquiztable";
        $table->head = array($name, $from, $to, $quizstatus, get_string('action'));

        foreach ($data as $weekquiz) {
            $row = array();
            $row[] = html_writer::link(new moodle_url('/mod/quiz/view.php',array('id'=>$weekquiz->quizcmid)),$weekquiz->name);
            $row[] = userdate($weekquiz->available_from, get_string('strftimedate'));
            $row[] = userdate($weekquiz->available_to, get_string('strftimedate'));

            $quizstatus = 'NA';
            switch ($weekquiz->quizstatus) {
                case 1:
                    $quizstatus = get_string('visible', 'block_weekquiz');
                    break;
                case 0:
                    $quizstatus = get_string('hidden', 'block_weekquiz');
                    break;
            }
            $row[] = $quizstatus;
            $buttons = array();
            if (has_capability('block/weekquiz:mangeweekquiz', context_system::instance())) {
                if ($weekquiz->display) {
                    $url = new moodle_url('/blocks/weekquiz/manage_weekquiz.php', array('hide' => $weekquiz->id, 'sesskey' => sesskey()));
                    $buttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/hide', get_string('hide')));
                } else {
                    $url = new moodle_url('/blocks/weekquiz/manage_weekquiz.php', array('show' => $weekquiz->id, 'sesskey' => sesskey()));
                    $buttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/show', get_string('show')));
                }
                $buttons[] = html_writer::link(new moodle_url('/blocks/weekquiz/manage_weekquiz.php', array('id' => $weekquiz->id)), $OUTPUT->pix_icon('t/edit', 'edit'));
                $url = new moodle_url('/blocks/weekquiz/manage_weekquiz.php', array('delete' => $weekquiz->id, 'sesskey' => sesskey()));
                $buttons[] = html_writer::link($url, $OUTPUT->pix_icon('t/delete', 'delete'));
            }
            $row[] = implode('|', $buttons);
            if (!$weekquiz->display) {

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
        echo $OUTPUT->paging_bar($totalrecords, $page, $perpage, new moodle_url('/blocks/weekquiz/manage_weekquiz.php'));
    }
} else {
    echo $OUTPUT->single_button(new moodle_url('/blocks/weekquiz/manage_weekquiz.php'), get_string('addnew', 'block_weekquiz'));
}
echo $OUTPUT->footer();
