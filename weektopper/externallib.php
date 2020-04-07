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
class block_weektopper_external extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function get_course_quizzes_parameters() {
        return new external_function_parameters(
                array(
            'courseid' => new external_value(PARAM_INT, 'courseid'),
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
    public static function get_course_quizzes($courseid) {
        global $DB, $CFG;


        $params = self::validate_parameters(self::get_course_quizzes_parameters(), array(
                    'courseid' => $courseid,
        ));

        $quizzesarr = get_coursemodules_in_course('quiz', $courseid);
        $quizlist = array();
        foreach ($quizzesarr as $quiz) {
            $quizlist[$quiz->instance]['id'] = $quiz->instance;
            $quizlist[$quiz->instance]['name'] = $quiz->name;
            $quizlist[$quiz->instance]['cmid'] = $quiz->id;
        }
        return $quizlist;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.1
     */
    public static function get_course_quizzes_returns() {

        $data = new external_multiple_structure(
                new external_single_structure(
                [
            'id' => new external_value(PARAM_INT, 'id'),
            'cmid' => new external_value(PARAM_INT, 'cmid'),
            'name' => new external_value(PARAM_ALPHANUMEXT, 'quiz name'),
                ]
                )
        );
    }

}
