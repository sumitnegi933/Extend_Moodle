<?php

require('../../../config.php');
require_once("../locallib.php");
global $CFG, $DB, $OUTPUT, $PAGE;

require_once($CFG->dirroot . '/blocks/customreports/classes/report/filter.class.php');
require_login();
$filter = new stdClass();
$filter->datefrom = optional_param_array('datefrom', 0, PARAM_INT);
$filter->dateto = optional_param_array('dateto', 0, PARAM_INT);
$filter->userprofile = optional_param_array('filter_profile', 0, PARAM_RAW_TRIMMED);
$filter->category = required_param('category', PARAM_INT);
$filter->course = optional_param('course', 0, PARAM_INT);
$filter->activity = optional_param_array('activity', 0, PARAM_RAW_TRIMMED);

$filter->datefrom = get_timestamp($filter->datefrom);
$filter->dateto = get_timestamp($filter->dateto, 23, 59, 59);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 50, PARAM_INT);
$format = optional_param('format', '', PARAM_ALPHA);
$fileitemid = optional_param('filterfile', 0, PARAM_INT);
$excel = ($format == 'excelcsv');
$csv = ($format == 'csv' || $excel);


// Load CSV library
if ($csv) {
    require_once("{$CFG->libdir}/csvlib.class.php");
}


$context = context_system::instance();
$PAGE->set_context($context);
require_capability("block/customreports:viewreports", $context);
//$PAGE->set_url(new moodle_url('/blocks/customreports/courselist.php'));

$params = array('deleted' => 0, 'visible' => 1, 'itemtype' => 'mod');
$wheresqlarr = array();

$wheresqlarr[] = "u.deleted = :deleted AND c.visible = :visible AND gi.itemtype = :itemtype";
if ($filter->datefrom) {
    $wheresqlarr[] = "gg.timemodified >= :datefrom";
    $params['datefrom'] = $filter->datefrom;
}
if ($filter->dateto) {
    $wheresqlarr[] = "gg.timemodified <= :dateto";
    $params['dateto'] = $filter->dateto;
}
$extrasql = '';
if ($fileitemid) {
    list($field, $data) = read_data_from_file($fileitemid);

   // list($sql, $params) = $DB->get_in_or_equal($data, SQL_PARAMS_NAMED, $field);

    list($customwheresqlarr, $customparams) = get_custom_profile_field_sql(array($field => $data), 'inoreq');
    $params += $customparams;
    foreach ($customwheresqlarr as $userfieldwhere) {
        $wheresqlarr[] = $userfieldwhere;
    }
 
} else if ($filter->userprofile) {

    // $i = 1;
    //$hascustomfields = 0;
    /* foreach ($filter->userprofile as $field => $value) {
      if (stripos($field, 'profile_field_') !== false) {
      $hascustomfields = 1;
      $field = str_replace('profile_field_', '', $field);
      $wheresqlarr[] = " u.id IN ( SELECT uid.userid FROM {user_info_data} uid INNER JOIN {user_info_field} uif WHERE shortname = :profilefield$i AND LOWER(uid.data) = :profilefieldvalue$i )";
      $params["profilefield$i"] = $field;
      $params["profilefieldvalue$i"] = strtolower($value);
      //$wheresqlarr[] = "";

      } else {
      $wheresqlarr[] = "LOWER(u.$field) = :profilefieldvalue$i";
      $params["profilefieldvalue$i"] = strtolower($value);
      }

      $i++;
      }
     */
    list($customwheresqlarr, $customparams) = get_custom_profile_field_sql($filter->userprofile);

    $params += $customparams;
    foreach ($customwheresqlarr as $userfieldwhere) {
        $wheresqlarr[] = $userfieldwhere;
    }
}

if ($filter->category) {

    $wheresqlarr[] = "cc.id = :category";
    $params['category'] = $filter->category;
}

if ($filter->course) {

    $wheresqlarr[] = "c.id = :course";
    $params['course'] = $filter->course;
}

if ($filter->activity && !empty($filter->activity[0])) {

    $mods = array();
    $instances = array();

    foreach ($filter->activity as $instancedata) {
        list($mod, $instance) = explode('-', $instancedata);
        if (!in_array($mod, $mods)) {
            $mods[] = $mod;
        }
        $instances[] = $instance;
    }
    $wheresqlarr[] = "gi.iteminstance IN (:activties)";
    $params['activties'] = implode(',', $instances);
    $wheresqlarr[] = "gi.itemmodule IN (:activity_types)";
    $params['activity_types'] = implode(',', $mods);
}

if (count($wheresqlarr) > 1) {
    $wheresql = implode(' AND ', $wheresqlarr);
} else {
    $wheresql = implode(' ', $wheresqlarr);
}
if (!$wheresqlarr) {
    $wheresql = '1=1';
}
//get_module_types_names();

$sql = "SELECT DISTINCT (concat(u.id,'-',gi.id))  , concat(u.firstname,' ',u.lastname) "
        . " as userfullname, gg.*, gi.iteminstance,gi.itemmodule,gi.itemname as activityname, "
        . " cc.name as categoryname,c.fullname as coursename,c.id as courseid FROM {grade_grades} gg "
        . " INNER JOIN {grade_items} gi ON gi.id = gg.itemid"
        . " INNER JOIN {course} c ON c.id = gi.courseid"
        . " INNER JOIN {course_categories} cc ON cc.id = c.category"
        . " INNER JOIN {user} u ON u.id = gg.userid"
        . $extrasql
        . " WHERE $wheresql"
        . " ORDER BY gg.timemodified DESC, u.id ASC";

$countsql = "SELECT COUNT(DISTINCT concat(u.id,'-',gi.id)) as totalrecord"
        . " FROM {grade_grades} gg"
        . " INNER JOIN {grade_items} gi ON gi.id = gg.itemid"
        . " INNER JOIN {course} c ON c.id = gi.courseid"
        . " INNER JOIN {course_categories} cc ON cc.id = c.category"
        . " INNER JOIN {user} u ON u.id = gg.userid"
        . $extrasql
        . " WHERE $wheresql";

if ($csv) {

    $shortname = format_string('grade', true, array('context' => $context));
    $shortname = preg_replace('/[^a-z0-9-]/', '_', core_text::strtolower(strip_tags($shortname)));
    $export = new csv_export_writer();
    $export->set_filename('User-' . $shortname);
}
//$DB->set_debug(true);

if ($csv) {
    $data = $DB->get_records_sql($sql, $params);
} else {
    $data = $DB->get_records_sql($sql, $params, $perpage * $page, $perpage);
}
//$data = $DB->get_records_sql($sql, $params);
if (!$csv) {
    $count = $DB->count_records_sql($countsql, $params);
}


if ($data) {
    $table = new html_table();
    $table->head = array();
    $csvheading = array();
    //$table->head = array(get_string('name'), get_string('category'), get_string('course'), get_string('activity'), get_string('grade'), get_string('maxgrade', 'block_customreports'));

    if ($customfields = get_custom_fields_to_show()) {
        $customfields = explode(',', $customfields);
        foreach ($customfields as $customfield) {
            $field = $DB->get_record('user_info_field', array('shortname' => $customfield));
            if ($field) {
                if ($csv) {
                    $csvheading [] = $field->name;
                } else {
                    $table->head[] = $field->name;
                }
            }
        }
    }
    if ($csv) {
        $csvheading [] = get_string('name');
        $csvheading [] = get_string('category');
        $csvheading [] = get_string('course');
        $csvheading [] = get_string('activity');
        $csvheading [] = get_string('grade');
        $csvheading [] = get_string('maxgrade', 'block_customreports');
    } else {
        $table->head[] = get_string('name');
        $table->head[] = get_string('category');
        $table->head[] = get_string('course');
        $table->head[] = get_string('activity');
        $table->head[] = get_string('grade');
        $table->head[] = get_string('maxgrade', 'block_customreports');
    }
    if ($csv) {
        $export->add_data($csvheading);
    }
    foreach ($data as $row) {

        $tablerow = array();
        if ($customfields = get_custom_fields_to_show()) {
            $customfields = explode(',', $customfields);
            foreach ($customfields as $customfield) {
                $userdata = get_user_customprofile_data($row->userid, $customfield);
                if ($userdata) {
                    if ($userdata->datatype == 'datetime') {
                        $tablerow[] = userdate($userdata->data);
                    } else {
                        $tablerow[] = $userdata->data;
                    }
                } else {
                    $tablerow[] = '-';
                }
            }
        }
        $tablerow[] = $row->userfullname;
        $tablerow[] = $row->categoryname;
        $tablerow[] = $row->coursename;
        $tablerow[] = $row->activityname;

        $tablerow[] = (!is_null($row->finalgrade)) ? round($row->finalgrade, 2) : get_string('notgraded', 'block_customreports');
        $tablerow[] = round($row->rawgrademax, 2);
        if ($csv) {
            $export->add_data($tablerow);
        } else {
            $table->data[] = $tablerow;
        }
    }

    if ($csv) {
        $export->download_file();
        die;
    }

    if (!empty($table)) {
        echo html_writer::start_tag('div', array('class' => 'no-overflow'));
        echo html_writer::table($table);
        echo html_writer::end_tag('div');

        echo $OUTPUT->paging_bar($count, $page, $perpage, new moodle_url('/blocks/customreports/addreport.php'));
    }
} else {

    echo $OUTPUT->notification(get_string('nodata', "block_customreports"));
}
