<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

if (!defined('SHOW')) {
    define('SHOW', 1);
}
if (!defined('HIDE')) {
    define('HIDE', 0);
}
define('TOTAL_TOPPERS', 10);

function hide_or_show_weektopper($weektopper, $action = '') {


    global $DB;

    $quiz = $DB->get_record('block_weektopper', array('id' => $weektopper), '*', MUST_EXIST);
    switch ($action) {
        case SHOW:
            $quiz->display = SHOW;
            break;
        case HIDE:
            $quiz->display = HIDE;
            break;
        default:
            return;
            break;
    }

    return $DB->update_record('block_weektopper', $quiz);
}

function delete_weektopper($weektopper) {
    global $DB;

    return $DB->delete_records('block_weektopper', array('id' => $weektopper));
}

function get_toppers_list() {

    global $DB, $OUTPUT;
    $topperslist = [];
    $quizzes = $DB->get_records('block_weektopper', array('display' => SHOW));
    if ($quizzes) {
        $i = 0;
        foreach ($quizzes as $q) {
            $toppers = get_quiz_topper($q->quiz);

            if ($toppers) {
                $quiz = $DB->get_record('quiz', array('id' => $q->quiz), '*', MUST_EXIST);
                $topperslist['slide-' . $i]['quiz']['name'] = $quiz->name;
                if ($i) {
                    $topperslist['slide-' . $i]['slidestatus'] = '';
                } else {
                    $topperslist['slide-' . $i]['slidestatus'] = 'active';
                }
                foreach ($toppers as $user) {
                    $temp = [];

                    $temp['fullname'] = fullname($user);
                    $temp['grade'] = round($user->usergrade, 2);
                    $temp['userpicture'] = $OUTPUT->user_picture($user, array('courseid' => SITEID, 'size' => 64,'link'=>false));
                    $topperslist['slide-' . $i]['quiz']['users'][] = new ArrayIterator($temp);
                }
            }
            $i++;
        }
    }

    return new ArrayIterator($topperslist);
}

function get_quiz_topper($quizid) {

    global $DB;

    $sql = "SELECT u.*,gg.rawgrade as usergrade FROM {grade_grades} gg "
            . " INNER JOIN {grade_items} gi ON gi.id = gg.itemid"
            . " INNER JOIN {quiz} q ON (q.course = gi.courseid AND q.id = gi.iteminstance)"
            . " INNER JOIN {user} u ON u.id = gg.userid"
            . " WHERE gi.itemmodule = :quiz AND "
            . " gi.iteminstance = :quizid AND gi.itemtype = :itemtype "
            . " AND gg.rawgrade IS NOT NULL AND gg.rawgrade != 0"
            . " ORDER BY gg.rawgrade DESC, gg.timemodified DESC";
    $params = array('quiz' => 'quiz', 'quizid' => $quizid, 'itemtype' => 'mod');
    return $DB->get_records_sql($sql, $params, 0, TOTAL_TOPPERS);
}
