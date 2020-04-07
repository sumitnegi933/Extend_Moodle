<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require('../../../config.php');
require_once("../locallib.php");
global $CFG, $DB, $OUTPUT, $PAGE;
require_once($CFG->libdir . '/completionlib.php');

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
$excel = ($format == 'excelcsv');
$csv = ($format == 'csv' || $excel);

$fileitemid = optional_param('filterfile', 0, PARAM_INT);
// Load CSV library
$wheresqlarr = array();
if ($csv) {
    require_once("{$CFG->libdir}/csvlib.class.php");
}

$context = context_system::instance();
$PAGE->set_context($context);

require_capability("block/customreports:viewreports", $context);
//$PAGE->set_url(new moodle_url('/blocks/customreports/courselist.php'));
$params = array();
$extrasql = '';
if ($fileitemid) {
    list($field, $data) = read_data_from_file($fileitemid);
    list($customwheresqlarr, $customparams) = get_custom_profile_field_sql(array($field => $data), 'inoreq');
    $params += $customparams;
    foreach ($customwheresqlarr as $userfieldwhere) {
        $wheresqlarr[] = $userfieldwhere;
    }
} else if ($filter->userprofile) {

    list($customwheresqlarr, $customparams) = get_custom_profile_field_sql($filter->userprofile);

    $params += $customparams;
    foreach ($customwheresqlarr as $userfieldwhere) {
        $wheresqlarr[] = $userfieldwhere;
    }
}

if ($filter->datefrom) {
    $wheresqlarr[] = "cmc.timemodified >= :datefrom";
    $params['datefrom'] = $filter->datefrom;
}
if ($filter->dateto) {
    $wheresqlarr[] = "cmc.timemodified <= :dateto";
    $params['dateto'] = $filter->dateto;
}
if ($filter->category) {

    $wheresqlarr[] = "cc.id = :category";
    $params['category'] = $filter->category;
}

if ($filter->course) {

    $wheresqlarr[] = "c.id = :course";
    $params['course'] = $filter->course;
}
$filteractivites = array();
if ($filter->activity && !empty($filter->activity[0])) {
    foreach ($filter->activity as $instancedata) {
        list($mod, $instance) = explode('-', $instancedata);

        $data = $DB->get_record_sql("SELECT cm.id as coursemodule
                               FROM {course_modules} cm
                                    INNER JOIN {" . $mod . "} s ON s.id = cm.instance
                                    INNER JOIN {modules} m ON m.id = cm.module
                                    WHERE s.id = ? AND
                                     m.name = ?
                                     ", array($instance, $mod));
        if ($data) {
            $filteractivites[] = $data->coursemodule;
        }
    }
}

if ($filteractivites) {
    /*
      list($cmsql,$cparams) = get_operator_sql('inoreq','cm.id','filteractivites',$filteractivites);
      $wheresqlarr[] = $cmsql;
      $params = array_merge($params,$cparams); */
}

if (count($wheresqlarr) > 1) {
    $wheresql = implode(' AND ', $wheresqlarr);
} else {
    $wheresql = implode(' ', $wheresqlarr);
}
$course = $DB->get_record('course', array('id' => $filter->course));

$completion = new completion_info($course);
$activities = $completion->get_activities();

/* if ($filteractivites) {
  foreach ($activities as $activiy) {
  if (!in_array($activiy->id, $filteractivites)) {
  unset($activities[$activity->id]);
  }
  }
  } */
list($enrolledsql, $extraparams) = get_enrolled_sql(
        context_course::instance($filter->course), 'moodle/course:isincompletionreports', 0, true);

$params = array_merge($params, $extraparams);
$sql = "SELECT u.id as userid, "
        . " GROUP_CONCAT("
        . " JSON_OBJECT('coursemoduleid',cmc.coursemoduleid,'userid',cmc.userid,"
        . " 'completionstate',cmc.completionstate,'overrideby',cmc.overrideby,'timemodified',cmc.timemodified) "
        . " SEPARATOR ';') as activitycompletion, "
        . " CONCAT(u.firstname, ' ', u.lastname) as studentname,u.email,c.fullname as coursename,cc.name as category FROM {course_modules_completion} cmc "
        . " INNER JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid "
        . " INNER JOIN {user} u ON u.id = cmc.userid "
        . " INNER JOIN {course} c ON c.id = cm.course "
        . " INNER JOIN {course_categories} cc ON cc.id = c.category "
        . " WHERE $wheresql AND cmc.userid IN ($enrolledsql) GROUP BY cmc.userid";

$countsql = "SELECT COUNT(DISTINCT u.id)  FROM {course_modules_completion} cmc "
        . " INNER JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid "
        . " INNER JOIN {user} u ON u.id = cmc.userid "
        . " INNER JOIN {course} c ON c.id = cm.course "
        . " INNER JOIN {course_categories} cc ON cc.id = c.category "
        . " WHERE $wheresql AND cmc.userid IN ($enrolledsql)";

$progress = array();


$totalcount = $DB->count_records_sql($countsql, $params);
if ($totalcount) {
    $progress = $DB->get_records_sql($sql, $params, $perpage * $page, $perpage);
}
if ($csv) {

    $shortname = format_string('Completion', true, array('context' => $context));
    $shortname = preg_replace('/[^a-z0-9-]/', '_', core_text::strtolower(strip_tags($shortname)));
    $export = new csv_export_writer();
    $export->set_filename('User-' . $shortname);
}
if ($totalcount && $activities) {
    $table = new html_table();
    $table->head = array();
    $headings = array();
    if ($customfields = get_custom_fields_to_show()) {
        $customfields = explode(',', $customfields);
        foreach ($customfields as $customfield) {
            $field = $DB->get_record('user_info_field', array('shortname' => $customfield));
            if ($field) {
                $headings [] = $field->name;
            }
        }
    }
    $headings [] = get_string('name');
    $headings [] = get_string('category');
    $headings [] = get_string('course');

    foreach ($activities as $activity) {
        if ($filteractivites) {
            if (!in_array($activity->id, $filteractivites)) {
                continue;
            }
        }
        $datepassed = $activity->completionexpected && $activity->completionexpected <= time();
        $datepassedclass = $datepassed ? 'completion-expired' : '';

        if ($activity->completionexpected) {
            $datetext = userdate($activity->completionexpected, get_string('strftimedate', 'langconfig'));
        } else {
            $datetext = '';
        }

        // Some names (labels) come URL-encoded and can be very long, so shorten them
        $activitydisplayname = $displayname = format_string($activity->name, true, array('context' => $activity->context));
        if (!$csv) {
            $activitydisplayname = html_writer::span(shorten_text($displayname), 'activity_name', array('title' => s($displayname)));
        }

        $headings[] = $activitydisplayname;
        /* if ($activity->completionexpected) {
          print '<div class="completion-expected"><span>' . $datetext . '</span></div>';
          } */

        $formattedactivities[$activity->id] = (object) array(
                    'datepassedclass' => $datepassedclass,
                    'displayname' => $displayname,
        );
    }
    if ($csv) {
        $export->add_data($headings);
    } else {
        $table->head = $headings;
    }


    foreach ($progress as $user) {
        // Progress for each activity
        $tablerow = array();
        if ($customfields = get_custom_fields_to_show()) {
            $customfields = explode(',', $customfields);
            foreach ($customfields as $customfield) {
                $userdata = get_user_customprofile_data($user->userid, $customfield);
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

        $tablerow[] = $user->studentname;
        $tablerow[] = $user->category;
        $tablerow[] = $user->coursename;
        $useractivityprogress = array();
        $uactivities = explode(';', $user->activitycompletion);

        foreach ($uactivities as $uactivity) {
            $uactivity = json_decode($uactivity);
            if ($uactivity) {
                $useractivityprogress[$user->userid]->progress[$uactivity->coursemoduleid] = $uactivity;
            }
        }


        /* foreach ($uactivities as $r) {
          $temp = explode('-', $r);
          $useractivityprogress[$temp[0]] = $temp[1];
          } */

        foreach ($activities as $activity) {


            if ($filteractivites) {
                if (!in_array($activity->id, $filteractivites)) {
                    continue;
                }
            }

            // Get progress information and state
            if (array_key_exists($activity->id, $useractivityprogress[$user->userid]->progress)) {
                $thisprogress = $useractivityprogress[$user->userid]->progress[$activity->id];
                $state = $thisprogress->completionstate;
                $overrideby = $thisprogress->overrideby;
                $date = userdate($thisprogress->timemodified);
            } else {
                $state = COMPLETION_INCOMPLETE;
                $overrideby = 0;
                $date = '';
            }
            // Work out how it corresponds to an icon
            switch ($state) {
                case COMPLETION_INCOMPLETE :
                    $completiontype = 'n' . ($overrideby ? '-override' : '');
                    break;
                case COMPLETION_COMPLETE :
                    $completiontype = 'y' . ($overrideby ? '-override' : '');
                    break;
                case COMPLETION_COMPLETE_PASS :
                    $completiontype = 'pass';
                    break;
                case COMPLETION_COMPLETE_FAIL :
                    $completiontype = 'fail';
                    break;
            }
            $completiontrackingstring = $activity->completion == COMPLETION_TRACKING_AUTOMATIC ? 'auto' : 'manual';
            $completionicon = 'completion-' . $completiontrackingstring . '-' . $completiontype;

            if ($overrideby) {
                $overridebyuser = \core_user::get_user($overrideby, '*', MUST_EXIST);
                $describe = get_string('completion-' . $completiontype, 'completion', fullname($overridebyuser));
            } else {
                $describe = get_string('completion-' . $completiontype, 'completion');
            }
            $a = new StdClass;
            $a->state = $describe;
            $a->date = $date;
            $a->user = $user->studentname;
            $a->activity = $formattedactivities[$activity->id]->displayname;
            $fulldescribe = get_string('progress-title', 'completion', $a);

            if ($csv) {
                $tablerow[] = $describe . ',' . $date;
            } else {
                $celltext = $OUTPUT->pix_icon('i/' . $completionicon, s($fulldescribe));

                $tablerow[] = $celltext;
            }
        }
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

        echo $OUTPUT->paging_bar($totalcount, $page, $perpage, new moodle_url('/blocks/customreports/addreport.php'));
    }
} else {
    echo $OUTPUT->notification(get_string('nodata', "block_customreports"));
}

