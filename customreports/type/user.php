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

$params = array();
$uparams = array();
$wheresqlarr = array();

$wheresqlarr[] = "u.deleted = 0 AND c.visible = 1 ";

$extrasql = '';

if ($filter->category) {

    $wheresqlarr[] = "cc.id = $filter->category";
    // $params['category'] = $filter->category;
}

if ($filter->course) {

    $wheresqlarr[] = "c.id = $filter->course";
    //$params['course'] = $filter->course;
}
$sql = array();
if (empty($filter->activity[0]) && $filter->course) {
    $filter->activity = array();
    define('GRADE_TYPE_NONE', 0);
    require_once($CFG->dirroot . '/course/lib.php');
    require_once($CFG->dirroot . '/grade/querylib.php');
    foreach (grade_get_gradable_activities($filter->course) as $instance) {

        $filter->activity[] = $instance->modname . '-' . $instance->instance;
    }
}
if ($filter->activity && !empty($filter->activity[0])) {

    $mods = array();
    $instances = array();
    $i = 1;
    $postfix = 1;
    foreach ($filter->activity as $instancedata) {
        list($mod, $instance) = explode('-', $instancedata);


        if ($mod == 'quiz') {

            $mysqlvariableprefix = "Q_$i";
            $wherequizsqlarr = array();
            if ($fileitemid) {
                list($field, $data) = read_data_from_file($fileitemid);

                // list($sql, $params) = $DB->get_in_or_equal($data, SQL_PARAMS_NAMED, $field);

                list($customwheresqlarr, $customparams) = get_custom_profile_field_sql(array($field => $data), 'inoreq', $postfix);

                $params = array_merge($params, $customparams);
                foreach ($customwheresqlarr as $userfieldwhere) {
                    $wherequizsqlarr[] = $userfieldwhere;
                }
            } else if ($filter->userprofile) {


                list($customwheresqlarr, $customparams) = get_custom_profile_field_sql($filter->userprofile, '', $postfix);

                $params = array_merge($params, $customparams);
                foreach ($customwheresqlarr as $userfieldwhere) {
                    $wherequizsqlarr[] = $userfieldwhere;
                }
            }
            if ($filter->datefrom) {
                $wheresqlarr["quiz_" . $i . "_datefrom"] = " qa.timestart >= :quiz_" . $i . "_datefrom";
                $params["quiz_" . $i . "_datefrom"] = $filter->datefrom;
            }
            if ($filter->dateto) {
                $wheresqlarr["quiz_" . $i . "_dateto"] = " qa.timemodified <= :quiz_" . $i . "_dateto";
                $params["quiz_" . $i . "_dateto"] = $filter->dateto;
            }
            $params["quiz$i"] = $instance;
            $wheresqlarr["quiz_$i"] = " q.id = :quiz$i";
            $wheresqlarr["quiz_" . $i . "_mode"] = " qa.preview = :quiz_" . $i . "_mode";
            $params["quiz_" . $i . "_mode"] = 0;
            if (count($wheresqlarr) > 1) {
                $wheresql = implode(' AND ', $wheresqlarr);
            } else {
                $wheresql = implode(' ', $wheresqlarr);
            }
            if ($wherequizsqlarr) {
                $wheresql .= ' AND ' . implode(' AND ', $wherequizsqlarr);
            }
            unset($wheresqlarr["quiz_" . $i . "_datefrom"]);
            unset($wheresqlarr["quiz_" . $i . "_dateto"]);
            unset($wheresqlarr["quiz_" . $i . "_mode"]);
            unset($wheresqlarr["quiz_$i"]);
            //$quizsql = 'SELECT COUNT(DISTINCT(' . $DB->sql_concat('q.id', '\'#\'', 'q.id', '\'#\'', 'COALESCE(qa.attempt, 0)') . ')) AS uniqueid, ';
            $quizsql = "SELECT temp.*, "
                    . " (CASE "
                    . " WHEN gg.finalgrade IS NULL "
                    . " THEN 0 "
                    . " ELSE gg.finalgrade "
                    . " END) as usergrade,"
                    . " (CASE "
                    . " WHEN gg.rawgrademax IS NULL "
                    . " THEN 0 "
                    . " ELSE gg.rawgrademax "
                    . " END) as maxgrade,"
                    . " (CASE "
                    . " WHEN gg.timemodified IS NULL "
                    . " THEN 0 "
                    . " ELSE gg.timemodified "
                    . " END) as timemodified "
                    . " FROM ( SELECT concat('$mysqlvariableprefix',@a:=@a+1) as serial_number, ";
            $sql[] = $quizsql . "COUNT(qa.attempt) as totalattempt,u.*, "
                    . " u.id as studentid,cc.name as category,c.fullname as coursename,q.name as activity "
                    . " FROM {quiz_attempts} as qa INNER JOIN {quiz} q ON q.id = qa.quiz"
                    . " INNER JOIN {course} c ON c.id = q.course"
                    . " INNER JOIN {course_categories} cc ON cc.id = c.category"
                    . " INNER JOIN {user} u ON u.id = qa.userid "
                    . " INNER JOIN (SELECT @a:= 0) AS a WHERE $wheresql GROUP BY u.id ) as temp"
                    . " INNER JOIN {grade_grades} gg ON gg.userid = temp.studentid"
                    . " INNER JOIN {grade_items} gi ON gi.id = gg.itemid "
                    . " WHERE gi.itemtype = 'mod' AND gi.itemmodule = 'quiz' AND gi.iteminstance = '$instance' ";
        } else if ($mod == 'scorm') {
            $mysqlvariableprefix = "S_$i";
            $scormwherecustomfiled = array();
            if ($fileitemid) {
                list($field, $data) = read_data_from_file($fileitemid);

                // list($sql, $params) = $DB->get_in_or_equal($data, SQL_PARAMS_NAMED, $field);

                list($customwheresqlarr, $customparams) = get_custom_profile_field_sql(array($field => $data), 'inoreq', $postfix);

                $params = array_merge($params, $customparams);
                foreach ($customwheresqlarr as $userfieldwhere) {
                    $scormwherecustomfiled[] = $userfieldwhere;
                }
            } else if ($filter->userprofile) {

                list($customwheresqlarr, $customparams) = get_custom_profile_field_sql($filter->userprofile, '', $postfix);

                $params = array_merge($params, $customparams);
                foreach ($customwheresqlarr as $userfieldwhere) {
                    $scormwherecustomfiled[] = $userfieldwhere;
                }
            }
            //get_module_from_cmid($cmid);
            $scormdata = $DB->get_record_sql("SELECT cm.*
                               FROM {course_modules} cm
                                    INNER JOIN {scorm} s ON s.id = cm.instance
                                    INNER JOIN {modules} m ON m.id = cm.module
                                    WHERE s.id = ? AND
                                     m.name = ?
                                     ", array($instance, 'scorm'));

            if ($scormdata) {
                $contextmodule = \context_module::instance($scormdata->id);
                if (!$students = get_users_by_capability($contextmodule, 'mod/scorm:savetrack', 'u.id', '', '', '', '', '', false)) {
                    echo $OUTPUT->notification(get_string('nostudentsyet'));
                    $allowedlist = '';
                } else {
                    $allowedlist = array_keys($students);
                }
                $scormwheresqlarr = array();
                if ($filter->datefrom) {
                    $scormwheresqlarr[] = " st.timemodified >= :scorm_" . $i . "_datefrom";
                    $params["scorm_" . $i . "_datefrom"] = $filter->datefrom;
                }
                if ($filter->dateto) {
                    $scormwheresqlarr[] = " st.timemodified <= :scorm_" . $i . "_dateto";
                    $params["scorm_" . $i . "_dateto"] = $filter->dateto;
                }
                $params["scorm$i"] = $instance;
                $scormwheresqlarr[] = " s.id = :scorm$i";
                if (count($wheresqlarr) > 1) {
                    $wheresql = implode(' AND ', $wheresqlarr);
                } else {
                    $wheresql = implode(' ', $wheresqlarr);
                }
                if ($allowedlist) {
                    $prefix = "scorm_$scormdata->id" . "_";
                    list($usql, $uparams) = $DB->get_in_or_equal($allowedlist, SQL_PARAMS_NAMED, $prefix);
                    $params = array_merge($params, $uparams);
                }
                if ($scormwheresqlarr) {
                    $wheresql .= ' AND ' . implode(' AND ', $scormwheresqlarr);
                }
                if (isset($usql) && $usql) {
                    $wheresql .= ' AND u.id ' . $usql;
                }

                if ($scormwherecustomfiled) {
                    $wheresql .= ' AND ' . implode(' AND ', $scormwherecustomfiled);
                }
                $scormsql = " SELECT temp.*, gg.finalgrade as usergrade,gg.rawgrademax as maxgrade, ( CASE WHEN gg.timemodified IS NULL THEN 0 ELSE gg.timemodified END ) as gg.timemodified  FROM ( SELECT concat('$mysqlvariableprefix',@a:=@a+1) as serial_number, ";
                $scormsql .= ' COUNT(DISTINCT(' . $DB->sql_concat('u.id', '\'#\'', 'st.attempt') . ')) AS totalattempt, ';
                $sql[] = $scormsql . " u.id as studentid, cc.name as category, c.fullname as coursename, s.name as activity, u.* "
                        . " FROM {scorm_scoes_track} as st INNER JOIN {scorm} s ON s.id = st.scormid"
                        . " INNER JOIN {course} c ON c.id = s.course"
                        . " INNER JOIN {course_categories} cc ON cc.id = c.category"
                        . " INNER JOIN {user} u ON u.id = st.userid "
                        . " INNER JOIN (SELECT @a:= 0) AS a WHERE $wheresql GROUP BY u.id ) as temp"
                        . " INNER JOIN {grade_grades} gg ON gg.userid = temp.studentid"
                        . " INNER JOIN {grade_items} gi ON gi.id = gg.itemid "
                        . " WHERE gi.itemtype = 'mod' AND gi.itemmodule = 'scorm' AND gi.iteminstance = '$instance' ";
            }
            //$params['activties'] = implode(',', $instances);
            //$wheresqlarr[] = "";
            //$params['activity_types'] = implode(',', $mods);
        } else if ($mod == 'assign') {
            $mysqlvariableprefix = "A_$i";
            $whereassignsqlarr = array();
            if ($fileitemid) {
                list($field, $data) = read_data_from_file($fileitemid);

                // list($sql, $params) = $DB->get_in_or_equal($data, SQL_PARAMS_NAMED, $field);

                list($customwheresqlarr, $customparams) = get_custom_profile_field_sql(array($field => $data), 'inoreq', $postfix);

                $params = array_merge($params, $customparams);
                foreach ($customwheresqlarr as $userfieldwhere) {
                    $whereassignsqlarr[] = $userfieldwhere;
                }
            } else if ($filter->userprofile) {

                list($customwheresqlarr, $customparams) = get_custom_profile_field_sql($filter->userprofile, '', $postfix);

                $params = array_merge($params, $customparams);
                foreach ($customwheresqlarr as $userfieldwhere) {
                    $whereassignsqlarr[] = $userfieldwhere;
                }
            }
            if ($filter->datefrom) {
                $wheresqlarr["assign_" . $i . "_datefrom"] = " asn.timemodified >= :assign_" . $i . "_datefrom";
                $params["assign_" . $i . "_datefrom"] = $filter->datefrom;
            }
            if ($filter->dateto) {
                $wheresqlarr["assign_" . $i . "_dateto"] = " asn.timemodified <= :assign_" . $i . "_dateto";
                $params["assign_" . $i . "_dateto"] = $filter->dateto;
            }
            $params["assign$i"] = $instance;
            $wheresqlarr["assign_$i"] = " a.id = :assign$i";
            $wheresqlarr["assign_" . $i . "_mode"] = " asn.status= :assign_" . $i . "_mode";
            $params["assign_" . $i . "_mode"] = 'submitted';
            if (count($wheresqlarr) > 1) {
                $wheresql = implode(' AND ', $wheresqlarr);
            } else {
                $wheresql = implode(' ', $wheresqlarr);
            }
            if ($whereassignsqlarr) {
                $wheresql .= ' AND ' . implode(' AND ', $whereassignsqlarr);
            }
            unset($wheresqlarr["assign_" . $i . "_datefrom"]);
            unset($wheresqlarr["assign_" . $i . "_dateto"]);
            unset($wheresqlarr["assign_" . $i . "_mode"]);
            unset($wheresqlarr["assign_$i"]);
            //$quizsql = 'SELECT COUNT(DISTINCT(' . $DB->sql_concat('u.id', '\'#\'', 'a.id', '\'#\'', 'COALESCE(asn.id, 0)') . ')) AS uniqueid, ';
            // $quizsql = 1;
            $sql[] = "  SELECT temp.*, gg.finalgrade as usergrade,gg.rawgrademax as maxgrade,gg.timemodified FROM ( SELECT concat('$mysqlvariableprefix',@a:=@a+1) as serial_number, "
                    . " COUNT(asn.userid) as totalattempt,"
                    . " u.id as studentid, cc.name as category,c.fullname as coursename,a.name as activity, u.* "
                    . " FROM {assign_submission} as asn INNER JOIN {assign} a ON a.id = asn.assignment"
                    . " INNER JOIN {course} c ON c.id = a.course"
                    . " INNER JOIN {course_categories} cc ON cc.id = c.category"
                    . " INNER JOIN {user} u ON u.id = asn.userid "
                    . " INNER JOIN {grade_grades} gg ON gg.userid = u.id "
                    . " INNER JOIN {grade_items} gi ON gi.id = gg.itemid AND gi.itemtype = 'mod' AND itemmodule='assign'"
                    . " INNER JOIN (SELECT @a:= 0) AS a WHERE $wheresql GROUP BY u.id ) as temp"
                    . " INNER JOIN {grade_grades} gg ON gg.userid = temp.studentid"
                    . " INNER JOIN {grade_items} gi ON gi.id = gg.itemid "
                    . " WHERE gi.itemtype = 'mod' AND gi.itemmodule = 'assign' AND gi.iteminstance = '$instance' ";
        }
        $i++;
    }



    if (count($sql) > 1) {
        $finalsql = implode(' UNION ALL ', $sql);
    } else {
        $finalsql = implode(' ', $sql);
    }
    // $finalsql.= ' ORDER BY usergrade DESC';
    $data = '';
    $count = 0;

    if ($csv) {

        $shortname = format_string('grade_and_attempts', true, array('context' => $context));
        $shortname = preg_replace('/[^a-z0-9-]/', '_', core_text::strtolower(strip_tags($shortname)));

        $export = new csv_export_writer();

        $export->set_filename('User-' . $shortname);
    }

    if (count($sql) > 0) {


        $data = $DB->get_records_sql($finalsql, $params);
    } else {
        $data = $DB->get_records_sql($finalsql, $params, $perpage * $page, $perpage);
        if ($csv) {
            
        }
        if (!$csv) {
            $count = count($DB->get_records_sql($finalsql, $params));
        }
    }

    if ($data) {

        $table = new html_table();
        $table->head = array();
        $csvheading = array();
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
        /* $table->head[] = array(
          get_string('name'),
          get_string('category'), get_string('course'),
          get_string('activity'),
          get_string('attempt', 'block_customreports'),
          get_string('grade'),
          get_string('maxgrade', 'block_customreports')); */
        if ($csv) {
            $csvheading[] = get_string('name');
            $csvheading[] = get_string('category');
            $csvheading[] = get_string('course');
            $csvheading[] = get_string('activity');
            $csvheading[] = get_string('attempt', 'block_customreports');
            $csvheading[] = get_string('grade');
            $csvheading[] = get_string('maxgrade', 'block_customreports');
        } else {
            $table->head[] = get_string('name');
            $table->head[] = get_string('category');
            $table->head[] = get_string('course');
            $table->head[] = get_string('activity');
            $table->head[] = get_string('attempt', 'block_customreports');
            $table->head[] = get_string('grade');
            $table->head[] = get_string('maxgrade', 'block_customreports');
        }
        if ($csv) {
            $export->add_data($csvheading);
        }

        foreach ($data as $row) {

            $tablerow = array();

            /* $userdata = get_user_customprofile_data($row->studentid, 'Designation');
              if ($userdata) {

              $tablerow[] = $userdata->data;
              } else {
              $tablerow[] = '-';
              } */
            if ($customfields = get_custom_fields_to_show()) {
                $customfields = explode(',', $customfields);
                foreach ($customfields as $customfield) {
                    $userdata = get_user_customprofile_data($row->studentid, $customfield);
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
            $tablerow[] = $row->firstname . ' ' . $row->lastname;
            $tablerow[] = $row->category;
            $tablerow[] = $row->coursename;
            $tablerow[] = $row->activity;
            $tablerow[] = $row->totalattempt;
            $tablerow[] = round($row->usergrade, 2);
            $tablerow[] = round($row->maxgrade, 2);

            if ($csv) {
                $export->add_data($tablerow);
            } else {
                $table->data[] = $tablerow;
            }
            $i++;
        }
        if ($csv) {
            $export->download_file();
            //die;
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
} else {

    echo $OUTPUT->notification(get_string('nodata', "block_customreports"));
}
