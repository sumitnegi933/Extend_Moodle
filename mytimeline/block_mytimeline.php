<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class block_mytimeline extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_mytimeline');
    }

    public function get_content() {

        global $DB, $CFG;
        if ($this->content !== NULL) {
            return $this->content;
        }

        if (!isloggedin() or isguestuser()) {
            return '';      // Never useful unless you are logged in as real users
        }

        return $this->print_timeline_activities();
    }

    public function print_timeline_activities() {
        global $USER, $DB, $CFG, $OUTPUT;
        require_once($CFG->dirroot . '/question/editlib.php');
        $itemtoshow = $this->config->totalitems ?? 10;
        $this->content = new stdClass();
        /*$sql = "SELECT * FROM {logstore_standard_log} "
                . " INNER JOIN (SELECT @a:= 0) AS a "
                . " WHERE ";
        $sql .= " action = ?  AND target = ? AND userid = ? ";
        $sql .= 'order by timecreated DESC';*/
        $this->content->text = '';
        $sql = "SELECT cmc.id,cmc.coursemoduleid, c.fullname as coursename, cmc.timemodified as completedtime FROM {course_modules_completion} cmc "
                . "INNER JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid "
                . "INNER JOIN {user} u ON u.id = cmc.userid "
                . "INNER JOIN {course} c ON c.id = cm.course "
                . "WHERE  u.id = :userid AND cmc.completionstate = :completed ORDER BY cmc.timemodified DESC";
        //$lastasset = $DB->get_records_sql($sql, array('viewed', 'course_module', $USER->id), 0, 10);
        $recentcompletedactivities = $DB->get_records_sql($sql, array('userid' => $USER->id, 'completed' => 1), 0, $itemtoshow);

        $activitieslist = array();
        $i = 0;
        $allactivitieslist = array();

        /* foreach ($lastasset as $key => $record) {
          if (!isset($allactivitieslist[$record->objecttable . '-' . $record->contextinstanceid])) {
          $allactivitieslist[$record->objecttable . '-' . $record->contextinstanceid] = 1;
          } else {
          unset($lastasset[$key]);
          }
          } */
        /* foreach ($lastasset as $record) {

          $url = $CFG->wwwroot . '/mod/' . $record->objecttable . '/view.php?id=' . $record->contextinstanceid;

          list($activityinfo, $cminfo) = get_module_from_cmid($record->contextinstanceid);
          $course = $DB->get_record('course', array('id' => $record->courseid));

          if ($activityinfo) {
          $icon = "<img src=\"" . $OUTPUT->image_url('icon', $record->objecttable) . "\" alt=\"\" />";
          //$strmodulename = $icon.' '.get_string('modulename', $record->objecttable);
          $activitieslist[$i]['icon'] = $icon;
          $activitieslist[$i]['course'] = $course->fullname;
          $activitieslist[$i]['url'] = $url;
          $activitieslist[$i]['name'] = $activityinfo->name;
          $activitieslist[$i]['time'] = userdate($record->timecreated);
          $i++;
          }
          } */

        foreach ($recentcompletedactivities as $activity) {

            list($activityinfo, $cminfo) = get_module_from_cmid($activity->coursemoduleid);
            $url = $CFG->wwwroot . '/mod/' . $cminfo->modname . '/view.php?id=' . $cminfo->id;
            if ($activityinfo) {
                $icon = "<img src=\"" . $OUTPUT->image_url('icon', $cminfo->modname) . "\" alt=\"\" />";
                $activitieslist[$i]['icon'] = $icon;
                $activitieslist[$i]['course'] = $activity->coursename;
                $activitieslist[$i]['url'] = $url;
                $activitieslist[$i]['name'] = $activityinfo->name;
                $activitieslist[$i]['time'] = userdate($activity->completedtime);
                $i++;
            }
        }


        if ($activitieslist) {
            $this->content->text = $OUTPUT->render_from_template('block_mytimeline/mytimeline', array('activities' => $activitieslist));
        }
        $this->content->footer = '';
        return $this->content;
    }

    function instance_allow_config() {
        return true;
    }

    function instance_config_save($data, $nolongerused = false) {
        global $DB;
        $DB->update_record('block_instances', ['id' => $this->instance->id,
            'configdata' => base64_encode(serialize($data)), 'timemodified' => time()]);
    }

    /**
     * Replace the instance's configuration data with those currently in $this->config;
     */
    function instance_config_commit($nolongerused = false) {
        global $DB;
        $this->instance_config_save($this->config);
    }

}
