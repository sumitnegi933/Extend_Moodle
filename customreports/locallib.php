
<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function get_timestamp($datearray, $hour = 0, $minute = 0, $second = 0) {
    if (!$datearray) {
        return 0;
    }
    $calendartype = \core_calendar\type_factory::get_calendar_instance();
    $gregoriandate = $calendartype->convert_to_gregorian($datearray['year'], $datearray['month'], $datearray['day']);
    return make_timestamp($gregoriandate['year'], $gregoriandate['month'], $gregoriandate['day'], $hour, $minute, $second, 99, true);
}

function get_custom_profile_field_sql($fields, $operators = '', &$postfix = 1) {

    if (empty($operators)) {
        $operators = optional_param_array('filter_profile_operator', 0, PARAM_RAW_TRIMMED);
    }
    $params = array();
    $wheresqlarr = array();
//$postfix = 1;

    foreach ($fields as $field => $value) {

        if (stripos($field, 'profile_field_') !== false) {

            list($extrasql, $fielsparams) = get_operator_sql($operators[$field] ?? $operators, 'uid.data', "profilefieldvalue$postfix", $value);
            if ($extrasql) {
                $field = str_replace('profile_field_', '', $field);
                $wheresqlarr[] = " u.id IN ( SELECT uid.userid FROM {user_info_data} uid INNER JOIN {user_info_field} uif WHERE shortname = :profilefield$postfix AND $extrasql )";
                $params["profilefield$postfix"] = $field;
                $params = array_merge($params, $fielsparams);
                //$params["profilefieldvalue$postfix"] = trim(strtolower($value));
            }
        } else {

            //$value = trim(strtolower($value));

            list($extrasql, $fielsparams) = get_operator_sql($operators[$field] ?? $operators, "u.$field", "profilefieldvalue$postfix", $value);

            if ($extrasql) {
                $wheresqlarr[] = " $extrasql";
                //$params["profilefieldvalue$postfix"] = trim(strtolower($value));
                $params = array_merge($params, $fielsparams);
            }
        }
        $postfix++;
    }

    return array($wheresqlarr, $params);
}

function get_user_core_fields() {

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

function get_operator_sql($operator, $field, $name, $value) {
    global $DB;
    $sql = '';
    $params = array();
    switch ($operator) {

        case 'eq':
            $sql = $DB->sql_like("LOWER($field)", ":$name", false, false);
            $value = trim(strtolower($value));
            $params[$name] = "$value";

            break;
        case 'like':
            $sql = $DB->sql_like("LOWER($field)", ":$name", false, false);
            $value = trim(strtolower($value));
            $params[$name] = "%$value%";
            break;
        case 'startwith':

            $sql = $DB->sql_like("LOWER($field)", ":$name", false, false);
            $value = trim(strtolower($value));
            $params[$name] = "$value%";

            break;
        case 'endswith':
            $sql = $DB->sql_like("LOWER($field)", ":$name", false, false);
            $value = trim(strtolower($value));
            $params[$name] = "%$value";

            break;
        case 'inoreq':
            list($sql, $params) = $DB->get_in_or_equal($value, SQL_PARAMS_NAMED, $name);
            $sql = $field . ' ' . $sql;
            break;
        default :
            return '';
    }

    return array($sql, $params);
}

function get_user_customprofile_data($userid, $field = '') {

    global $DB;
    if (empty($userid)) {
        return false;
    }

    list($usql, $params) = $DB->get_in_or_equal($field, SQL_PARAMS_NAMED);
    $sql = 'SELECT uif.shortname,uid.data,uif.datatype FROM {user_info_field} uif '
            . ' LEFT JOIN {user_info_data} uid ON uif.id = uid.fieldid'
            . ' WHERE uid.userid = :userid AND uif.shortname ' . $usql . '';
    $params['userid'] = $userid;
    return $DB->get_record_sql($sql, $params);
}

function get_custom_fields_to_show() {

    return get_config('block_customreports', 'customfieldreportcolumn');
}

function get_country_code($country) {

    $countries = get_string_manager()->get_list_of_countries();

    $countrycode = array_search(ucwords($country), $countries);
    if ($countrycode != false) {
        return $countrycode;
    }
    return 0;
}

function field_starts_with($haystack, $needle) {
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
}

function get_country_by_code($code) {

    $countries = get_string_manager()->get_list_of_countries();


    if (array_key_exists($code, $countries)) {
        return $countries[$code];
    }
    return '';
}

function read_data_from_file($fileitemid) {
    // Let's get started!
    global $DB, $CFG, $OUTPUT;
    require_once($CFG->libdir . '/csvlib.class.php');
    //$DB->set_debug(true);
    $filter = array();
    if ($file = $DB->get_record_sql('SELECT * FROM {files} WHERE itemid =:itemid AND '
            . ' filesize <> 0 AND mimetype IS NOT NULL AND filearea = :filearea ORDER BY timemodified DESC', array('itemid' => $fileitemid, 'filearea' => 'draft'), IGNORE_MULTIPLE)) {

        $filepath = '/' . $file->contextid . '/' . $file->component . '/' . 'draft/' . $file->itemid . '/' . $file->filename;
        $filepath = rtrim($filepath, '/');
        $corefield = 0;
        $fs = get_file_storage();
        $file = $fs->get_file_by_hash(sha1($filepath));
        $content = $file->get_content();
        $importid = csv_import_reader::get_new_iid('customreportfilter');
        $csvimport = new csv_import_reader($importid, 'customreportfilter');
        $readcount = $csvimport->load_csv_content($content, 'utf-8', 'comma');
        $csvloaderror = $csvimport->get_error();
        unset($content);

        if (!is_null($csvloaderror)) {
            //print_error('csvloaderror', '', $returnurl, $csvloaderror);
            //print_string('csvloaderror');
            echo $OUTPUT->notification(get_string('csvloaderror'));
            die;
        }

        if (empty($csvloaderror)) {

            // Get header (field names).
            $header = $csvimport->get_columns();

            if (count($header) > 1) {

                echo $OUTPUT->notification(get_string('invalidcolumn', "block_customreports"));
                die;
            }
            $profilefield = $header[0];

            if (array_key_exists($profilefield, get_user_core_fields())) { // Check whether provided header is core filed or not
                $corefield = 1;
            } else {

                $userfield = str_replace('profile_field_', '', $profilefield);
                if (!$DB->get_record('user_info_field', array('shortname' => $userfield))) {
                    echo $OUTPUT->notification(get_string('invalidfield', "block_customreports"));
                    die;
                }
            }

            // print_object($header);
            $csvimport->init();
            while ($line = $csvimport->next()) {
                if (count($line) < 1) {
                    // There is no data on this line, move on.
                    continue;
                }
                if (count($line) > 1) {
                    echo $OUTPUT->notification(get_string('invalidcolumnvalue', "block_customreports"));
                    die;
                }
                if ($line) {
                    $filter[] = $line[0];
                }
            }
        }
    }
    return array($profilefield, $filter);
}
