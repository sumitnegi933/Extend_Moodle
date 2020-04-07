<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/coursecatlib.php');
global $CFG;

class report_filter_form extends moodleform {

    function definition() {
        $mform = & $this->_form;
        //$mform->addElement('text', 'filtername', get_string('name'));
        //$mform->setType('filtername', PARAM_ALPHANUM);
        $radioarray = array();
        //$mform->addElement('header', 'moodle', get_string("report"));
        $radioarray[] = $mform->createElement('radio', 'reporttype', '', get_string('grades_report', 'block_customreports'), 'grade');
        //$radioarray[] = $mform->createElement('radio', 'reporttype', '', get_string('completion', 'block_customreports'), 'completion');
        $radioarray[] = $mform->createElement('radio', 'reporttype', '', get_string('attempts_report', 'block_customreports'), 'attempt');
        $radioarray[] = $mform->createElement('radio', 'reporttype', '', get_string('users_report', 'block_customreports'), 'user');
        $radioarray[] = $mform->createElement('radio', 'reporttype', '', get_string('monitoring_report', 'block_customreports'), 'monitoring');
        $radioarray[] = $mform->createElement('radio', 'reporttype', '', get_string('completion_report', 'block_customreports'), 'completion');

        $mform->setDefault('reporttype', 'grade');
        $mform->addGroup($radioarray, 'reporttypearr', get_string("reporttype", "block_customreports"), array(' '), false);
        // $mform->addElement('header', 'moodle', get_string("filters"));

        $dates = array();
        $dates[] = &$mform->createElement('date_selector', 'datefrom', '', true);
        $dates[] = &$mform->createElement('date_selector', 'dateto', get_string('to') . '&nbsp;&nbsp;', true);
        $dates[] = &$mform->createElement('checkbox', 'datefilter', get_string('enabledate', 'block_customreports'));
        //$mform->disabledIf('datefrom', 'datefilter', 'notchecked');
        //$mform->disabledIf('dateto', 'datefilter', 'notchecked');
        $mform->addGroup($dates, 'daterange', get_string('daterange', 'block_customreports'), '&nbsp;&nbsp;', false);
        $mform->disabledIf('daterange', 'datefilter', 'notchecked');
        $mform->addElement('checkbox', 'usefilefilter', get_string('uploadfilterfile', 'block_customreports'));
       

        $samplefile = [];
        $samplefile[] = $mform->createElement('html', '<a href="sample.csv">' . get_string('downloadtemplate', 'block_customreports') . '</a>');
        $mform->addGroup($samplefile, 'downloadsample');
        $mform->hideIf('downloadsample', 'usefilefilter', 'notchecked');
        $mform->addElement('filepicker', 'filterfile', get_string('uploadfilelabel', 'block_customreports'), null, array('maxbytes' => 1024 * 300, 'accepted_types' => '.csv'));
        $mform->hideIf('filterfile', 'usefilefilter', 'notchecked');
        $userporfile = array();
        $userporfile[] = $mform->createElement('select', 'userprofile', '', self::get_user_profile_field());
        $userporfile[] = $mform->createElement('html', '<div id="profilefield_filters"></div>');
        $mform->addGroup($userporfile, 'userprofilearr', get_string('userprofile', 'block_customreports'), array(' '), false);
        $mform->hideIf('userprofilearr', 'usefilefilter', 'checked');
        $mform->disabledIf('userprofilearr', 'usefilefilter', 'checked');
        $categorylist = array(0 => get_string('select'));
        //$categorylist = coursecat::make_categories_list('block/customreports:viewreports');
        $categorylist += coursecat::make_categories_list('block/customreports:viewreports');
        $mform->addElement('select', 'category', get_string('category', 'block_customreports'), $categorylist);
        $mform->addElement('select', 'course', get_string('course', 'block_customreports'), array(0 => get_string('all')), array('disabled' => 'disabled'));
        $select = $mform->addElement('select', 'activity', get_string('activity', 'block_customreports'), array(0 => get_string('all')), array('disabled' => 'disabled'));
        $select->setMultiple(true);
        //$radioarray[] = $mform->createElement('radio', 'reporttype', '', get_string('notattempt', 'block_customreports'), 'notattempt');
        //$radioarray[] = $mform->createElement('radio', 'reporttype', '', get_string('user'), 'user');
        $action = array();
        //$this->add_action_buttons(false, get_string('search'));
        //$this->add_action_buttons(false, get_string('search1'));
        $mform->addElement('html', '<div class="hiddenelement"></div>');
        $action[] = $mform->createElement('button', 'submitbutton', get_string('view'));
        $action[] = $mform->createElement('button', 'exportcsv', get_string('export', 'block_customreports'));
        $extrahtml = '<div class="form-group row  fitem">';
        $extrahtml .= '<div class="col-md-3">';
        $extrahtml .= '</div>';
        $extrahtml .= '<div class="col-md-9">';
        $extrahtml .= '<button class="btn btn-primary" name="submitbutton" id="id_submitbutton" type="button">
                ' . get_string('view') . '
            </button>';
        $extrahtml .= ' ';
        $extrahtml .= '<button class="btn btn-success" name="exportcsv" id="id_exportcsv" type="button">
                ' . get_string('export', 'block_customreports') . '
            </button>';
        $extrahtml .= '</div>';
        $extrahtml .= '</div>';
        $mform->addElement('html', $extrahtml);
        //$action[] = $mform->createElement('button', 'exportexcelcsv', get_string('excelcsvdownload','completion'));
        //$mform->addGroup($action, 'actiontype', '', array(' '), false);
    }

    protected function get_user_profile_field() {
        global $DB;

        $configfields = get_config('block_customreports', 'profilefields');
        $fieldtoshow = array();
        if ($configfields) {
            $fieldtoshow = explode(',', $configfields);
        }
        $fields = $DB->get_records('user_info_field', null, 'id ASC');

        $userfield = array('' => get_string('all'));
        $userfield += self::get_user_core_fields();

        foreach ($userfield as $key => $field) {
            if ($key != '') {
                if (!in_array($key, $fieldtoshow)) {

                    unset($userfield[$key]);
                }
            }
        }

        foreach ($fields as $field) {
            if (in_array('profile_field_' . $field->shortname, $fieldtoshow)) {
                $userfield['profile_field_' . $field->shortname] = $field->name;
            }
        }


        return $userfield;
    }

    protected function get_user_core_fields($returnfield = '') {

        $corefields = array(
            'firstname' => get_string('firstname'),
            'lastname' => get_string('lastname'),
            'institution' => get_string('institution'),
            'department' => get_string('department'),
            'city' => get_string('city'),
            'country' => get_string('country'),
            'email' => get_string('email'),
        );
        return $corefields;
    }

}
