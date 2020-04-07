<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * External assign API
 *
 * @package    mod_assign
 * @since      Moodle 2.4
 * @copyright  2012 Paul Charsley
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/user/externallib.php");
require_once("$CFG->dirroot/mod/assign/locallib.php");

/**
 * Assign functions
 * @copyright 2012 Paul Charsley
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_customreports_external extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function get_courses_parameters() {
        return new external_function_parameters(
                array(
            'categoryid' => new external_value(PARAM_INT, 'categoryid'),
                )
        );
    }

    /**
     * Get the user participating in the given assignment. An error with code 'usernotincourse'
     * is thrown is the user isn't a participant of the given assignment.
     *
     * @param int $assignid the assign instance id
     * @param int $userid the user id
     * @param bool $embeduser return user details (only applicable if not blind marking)
     * @return array of warnings and status result
     * @since Moodle 3.1
     * @throws moodle_exception
     */
    public static function get_courses($category) {
        global $DB, $CFG;


        $params = self::validate_parameters(self::get_courses_parameters(), array(
                    'categoryid' => $category,
        ));
        if (!$category) {
            return array(array('id' => 0, 'fullname' => get_string('all')));
        }
        $category = $DB->get_record('course_categories', array('id' => $category), '*', MUST_EXIST);
        $courses = get_courses($category->id);
        foreach ($courses as $c) {
            if (!$c->visible) {
                unset($courses[$c->id]);
            }
        }
        return $courses;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.1
     */
    public static function get_courses_returns() {

        $data = new external_multiple_structure(
                new external_single_structure(
                [
            'id' => new external_value(PARAM_INT, 'id'),
            'fullname' => new external_value(PARAM_INT, 'cmid'),
            'shortname' => new external_value(PARAM_ALPHANUMEXT, 'course name'),
                ]
                )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function get_activities_parameters() {
        return new external_function_parameters(
                array(
            'courseid' => new external_value(PARAM_INT, 'courseid'),
            'report' => new external_value(PARAM_ALPHA, 'reporttype'),
                )
        );
    }

    /**
     * Get the user participating in the given assignment. An error with code 'usernotincourse'
     * is thrown is the user isn't a participant of the given assignment.
     *
     * @param int $assignid the assign instance id
     * @param int $userid the user id
     * @param bool $embeduser return user details (only applicable if not blind marking)
     * @return array of warnings and status result
     * @since Moodle 3.1
     * @throws moodle_exception
     */
    public static function get_activities($course, $report) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/grade/querylib.php');
        $params = self::validate_parameters(self::get_activities_parameters(), array(
                    'courseid' => $course,
                    'report' => $report
        ));
        if (!$course) {
            return array(array('id' => 0, 'name' => get_string('all')));
        }
        $course = $DB->get_record('course', array('id' => $course), '*', MUST_EXIST);

        //$courses = get_course_mods($courseid);
        //$activities = get_array_of_activities($course->id);
        // print_object($activities);
        //echo "==============";
        $activities = array();
        if ($report === 'completion') {
            require_once($CFG->libdir . '/completionlib.php');
            $completion = new completion_info($course);
            $cmactivities = $completion->get_activities();

            foreach ($cmactivities as $activity) {
                $tempobj = new stdClass();
                $tempobj->instance = $activity->instance;
                $tempobj->modname = $activity->modname;
                $tempobj->name = $activity->name;
                $tempobj->course = $activity->course;
                $tempobj->id = $activity->id;
                $activities [$activity->id] = $tempobj;
            }
        } else {
            $activities = grade_get_gradable_activities($course->id);
        }

        return $activities;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.1
     */
    public static function get_activities_returns() {

        $data = new external_multiple_structure(
                new external_single_structure(
                [
            'instance' => new external_value(PARAM_INT, 'instance'),
            'name' => new external_value(PARAM_ALPHANUMEXT, 'name'),
            'modname' => new external_value(PARAM_ALPHANUMEXT, 'modname'),
                //'shortname' => new external_value(PARAM_ALPHANUMEXT, 'course name'),
                ]
                )
        );
    }

}
