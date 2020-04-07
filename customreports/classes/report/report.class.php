<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


require_once('filter.class.php');
class user_report {
   
    public function get_filters(){
        $obj = new report_filter_form(null, null, 'post', '', array('class'=>'customreportsform'));
        $obj->display();
    }
    
    
    
}