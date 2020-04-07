<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
$functions = array(
    'block_weekquiz_get_course_quizzes' => array(
        'classname' => 'block_weekquiz_external',
        'methodname' => 'get_course_quizzes',
        'description' => 'Get all the quizzes of a specific courses',
        'classpath' => 'blocks/weekquiz/externallib.php',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'moodle/course:manageactivities',
    ),
);
