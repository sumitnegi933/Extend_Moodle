<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class block_weektopper extends block_base {

    function init() {
        $title = get_config('block_weektopper', 'titleweektopper');

        $this->title = $title;
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
        require_once($CFG->dirroot . '/blocks/weektopper/lib.php');
        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $data['toppers'] = get_toppers_list();
        $str = explode(' ',trim($this->title));
        $firstword = $str[0];
        unset($str[0]);
        $title = '';
        if(!empty($str)){
        $title = implode(' ',$str);
        }
        $this->content->text .= html_writer::tag('h3', '<span class="firstChartitle">'.$firstword.'</span> '.$title);
        $this->content->text .= $OUTPUT->render_from_template('block_weektopper/weektopper', $data);

        /* if ($url) {
          $data['quizurl'] = $url;
          }
          $this->content->text = $OUTPUT->render_from_template('block_weektopper/weektopper', $data); */
        /* if ($url) {
          $this->content->footer = '<a class="btn btn-success" href=' . $url . '> Take a quiz</a>';
          } */
        return $this->content;
    }

    function hide_header() {
        return true;
    }

}
