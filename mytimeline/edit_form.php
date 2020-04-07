<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class block_mytimeline_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        $mform->addElement('text', 'config_totalitems', get_string('totalitemtoshow', 'block_mytimeline'));
        $mform->setType('totalitems', PARAM_INT);
    }

}
