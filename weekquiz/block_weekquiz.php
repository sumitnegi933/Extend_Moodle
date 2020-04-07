<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


class block_weekquiz extends block_base {

    function init() {
        $this->title = get_string('pluginname', 'block_weekquiz');
        $this->config = new stdClass();
    }

    function applicable_formats() {

        return array('all' => true);
    }

    public function has_config() {
        return true;
    }

    function get_content() {
        global $CFG, $OUTPUT;
        require_once($CFG->dirroot.'/blocks/weekquiz/lib.php');
        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass();
        $imageurl = '';
        if(get_config('block_weekquiz','image')){
        $imageurl = get_block_weekquiz_image();
        }
        $data = array();
        if($imageurl){
            $data['imageurl'] = $imageurl;
        }
        $desc = get_config('block_weekquiz', 'desc');
        if ($desc) {
            $data['description'] = $desc;
        }
        //echo block_weekquiz_pluginfile(context_system::instance(), 'weekquizimage', array($imagefile), false);
        //die;
        // $this->content->text .= "<div>" . $desc . "</div>";
        $url = $this->get_quiz_url();
        if ($url) {
            $data['quizurl'] = $url;
        }
        $this->content->text = $OUTPUT->render_from_template('block_weekquiz/weekquiz', $data);
        /* if ($url) {
          $this->content->footer = '<a class="btn btn-success" href=' . $url . '> Take a quiz</a>';
          } */
        return $this->content;
    }

    function hide_header() {
        return false;
    }

  

    function get_quiz_url() {

        global $DB;
        // $currenttime = time();
        $sql = "SELECT * FROM {block_weekquiz_availability} "
                . " WHERE available_from <= :currenttime1 AND available_to >= :currenttime2 AND display = :display";
        $quiz = $DB->get_record_sql($sql, array('currenttime1' => time(), 'currenttime2' => time(), 'display' => SHOW));

        if ($quiz) {
            list($course, $cm) = get_course_and_cm_from_instance($quiz->quiz, 'quiz');
            if ($cm->uservisible) {
                return new moodle_url('/mod/quiz/view.php', array('id' => $cm->id));
            }
        }
        return '';
    }

}
