<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require('../../config.php');
$counrties = get_string_manager()->get_list_of_countries();
$countries_list = [];
require_login();
$context = context_system::instance();
require_capability("block/customreports:viewreports", $context);
foreach ($counrties as $code => $country) {
    $temp = array();
    $temp ['code'] = $code;
    $temp ['country'] = $country;
    $countries_list[] = $temp;
}
echo json_encode($countries_list);
