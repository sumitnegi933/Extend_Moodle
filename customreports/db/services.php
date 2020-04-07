<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
$functions = array(
    'block_customreports_get_courses_list' => array(
        'classname' => 'block_customreports_external',
        'methodname' => 'get_courses',
        'description' => 'Get all courses of category',
        'classpath' => 'blocks/customreports/externallib.php',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'block/customreports:viewreports',
    ),
    'block_customreports_get_activities_list' => array(
        'classname' => 'block_customreports_external',
        'methodname' => 'get_activities',
        'description' => 'Get all activities of course',
        'classpath' => 'blocks/customreports/externallib.php',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'block/customreports:viewreports',
    ),
);
