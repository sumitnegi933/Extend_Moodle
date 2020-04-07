<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Handles uploading files
 *
 * @package    block_weekquiz
 * @copyright  Sumit Negi
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}
require_once($CFG->libdir . '/formslib.php');

class manage_weekquiz_form extends moodleform {

    function definition() {

        global $CFG;
        $courseid = '';
        $html = '';
        $mform = & $this->_form;
        $quizlists = new stdClass();
        // print_object($this->_customdata);
        if (isset($this->_customdata['id'])) {
            $id = $this->_customdata['id'];
            $mform->addElement('hidden', 'id', $id);
            $mform->setType('id', PARAM_INT);
            if (isset($this->_customdata['courseid'])) {
                $courseid = $this->_customdata['courseid'];

                $quizlists = get_coursemodules_in_course('quiz', $courseid);
                // print_object($quizlists);
            }
            $quizid = '';
            if (isset($this->_customdata['quizid'])) {
                $quizid = $this->_customdata['quizid'];
            }

            if ($quizlists) {
                $html = '<ul class="list-group">';
                foreach ($quizlists as $quiz) {
                    $checked = '';
                    if ($quizid == $quiz->instance) {
                        $checked = 'checked="checked"';
                    }
                    $html .= '<li class="list-group-item">';
                    $html .= '<span><input type="radio" ' . $checked . ' name="quizitem" class="quizitem" value=' . $quiz->instance . '>&nbsp;</span><span>' . $quiz->name . '</span>';
                    $html .= '</li>';
                }
                $html .= '</ul>';
            }
        }
        //$mform->addElement('hidden','id',);
        $mform->addElement('date_selector', 'available_from', get_string('from'));
        $mform->addElement('date_selector', 'available_to', get_string('to'));
        $courses = get_courses('all', "c.sortorder ASC", "c.id,c.fullname,c.category");
        $courselist = ['' => get_string('select')];
        foreach ($courses as $course) {
            if (!$course->category) {
                continue;
            }
            $courselist[$course->id] = $course->fullname;
        }
        $options = array('noselectionstring' => get_string('allareas', 'search'),
        );

        $auto = $mform->addElement('autocomplete', 'courselist', get_string('course'), $courselist, $options);
        $courselist = $mform->getElementValue('courselist');
        if ($courseid) {
            $auto->setValue($courseid);
        }

        $postdata = $this->_get_post_params();

        if (!empty($postdata['courselist'])) {
            $quizlists = get_coursemodules_in_course('quiz', $postdata['courselist']);
            if ($quizlists) {
                $html = '<ul class="list-group">';
                foreach ($quizlists as $quiz) {
                    $checked = '';
                    if ($postdata['quizitem'] == $quiz->instance) {
                        $checked = 'checked="checked"';
                    }
                    $html .= '<li class="list-group-item">';
                    $html .= '<span><input type="radio" ' . $checked . ' name="quizitem" class="quizitem" value=' . $quiz->instance . '>&nbsp;</span><span>' . $quiz->name . '</span>';
                    $html .= '</li>';
                }
                $html .= '</ul>';
            }
        }
        $mform->addElement('html', '<div class="form-group row  fitem ">'
                . '<div class="col-md-3">'
                . '</div>'
                . '<div class="col-md-9"><div id="quizcontainer"> '
                . $html
                . '</div>'
                . '</div>'
                . '</div>');

        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        global $DB;
        $quizitem = optional_param('quizitem', '', PARAM_INT);
        global $DB, $CFG;
        $errors = parent::validation($data, $files);
        $current_available_from = $data['available_from'];
        $current_available_to = $data['available_to'];
        $sql = "SELECT * FROM {block_weekquiz_availability} "
                . " WHERE (available_from <= :currentavailabefrom AND available_to >= :currentavailabefrom1 ) "
                . " OR (available_from <= :currentavailabeto AND available_to >= :currentavailabeto1 )";
        if ($DB->get_records_sql($sql, array('currentavailabefrom' => $current_available_from,
                    'currentavailabeto' => $current_available_to,
                    'currentavailabefrom1' => $current_available_from,
                    'currentavailabeto1' => $current_available_to))) {
            $errors['available_from'] = get_string('dataalreaythere', 'block_weekquiz');
        }
        if ($data['available_from'] >= $data['available_to']) {
            $errors['available_from'] = get_string('dateerror', 'block_weekquiz');
        }
        if (empty($data['courselist'])) {
            $errors['courselist'] = get_string('selectcourseerror', 'block_weekquiz');
        }

        if (empty($quizitem)) {
            $errors['courselist'] = get_string('selectquizerror', 'block_weekquiz');
        }


        return $errors;
    }

}
