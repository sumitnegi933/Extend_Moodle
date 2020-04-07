<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class course_quizzes{
    
    public function get_all_quizzes($courseid){
        global $DB;
        
        if(empty($courseid)){
            return '';
        }
        $course = $DB->get_record('course',array('id'=>$courseid),'*',MUST_EXIST);
        print_object(get_coursemodules_in_course('quiz', $course->id));
    }
    
}