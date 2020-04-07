<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require('../../config.php');
require_once("locallib.php");
global $CFG, $DB, $OUTPUT, $PAGE;
require_once($CFG->dirroot . '/blocks/customreports/auto_suggestion.php');
$field = required_param('field', PARAM_RAW_TRIMMED);
//$type = required_param('fieldtype', PARAM_INT);
$search = required_param('search', PARAM_RAW_TRIMMED);
$corefield = 1;
if (field_starts_with($field, 'profile_field_')) {
    $field = str_replace("profile_field_", '', $field);
    $corefield = 0;
}
$field = strtolower($field);
$response = [];
require_login();

if ($corefield) {

//$wheresql = $DB->sql_like("LOWER($field)", ":field", false, false);
    list($wheresql, $param) = get_operator_sql('like', $field, 'field', $search);
//$params[$name] = "%$search%";
    $sql = "SELECT id ,$field as fielddata FROM {user} WHERE $wheresql LIMIT 0, 100";
} else {
    list($wheresql, $param) = get_operator_sql('like', 'uid.data', 'field', $search);
    $param['profilefield'] = $field;
    $sql = "SELECT uid.userid ,uid.data as fielddata FROM {user_info_data} uid "
            . " INNER JOIN {user_info_field} uif "
            . " WHERE LOWER(shortname) = :profilefield AND $wheresql LIMIT 0, 100";
}


$data = $DB->get_records_sql($sql, $param);
if ($data) {
    foreach ($data as $row) {
        if (!in_array($row->fielddata, $response)) {

            $response[] = $row->fielddata;
        }
    }
}
    echo json_encode($response);

    