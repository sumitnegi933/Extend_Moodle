<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

define('SHOW', 1);
define('HIDE', 0);

function get_block_weekquiz_image() {
    global $CFG;

    $context = context_system::instance();

    // If there is no picture, do nothing.
    /* if (!$group->picture) {
      return;
      }

      // If picture is hidden, only show to those with course:managegroups.
      if ($group->hidepicture and ! has_capability('moodle/course:managegroups', $context)) {
      return;
      } */

    $imagefile = get_config('block_weekquiz', 'image');

    $grouppictureurl = moodle_url::make_pluginfile_url($context->id, 'block_weekquiz', 'weekquizimage', 0, '/', $imagefile);
    // $grouppictureurl->param('rev', $group->picture);
    return $grouppictureurl;
}

function block_weekquiz_pluginfile($course, $instance, $context, $filearea, $args, $forcedownload) {
    global $CFG, $DB, $USER;

    require_once($CFG->libdir . '/filelib.php');
    require_login();
    if($filearea != 'weekquizimage'){
        return;
    }
    $syscontext = context_system::instance();
    $component = 'block_weekquiz';

    $revision = array_shift($args);
    if ($revision < 0) {
        $lifetime = 0;
    } else {
        $lifetime = 60 * 60 * 24 * 60;
        // By default, block files must be cache-able by both browsers and proxies.
        if (!array_key_exists('cacheability', $options)) {
            $options['cacheability'] = 'public';
        }
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);

    $fullpath = "/{$syscontext->id}/{$component}/{$filearea}/0/{$relativepath}";
    $fullpath = rtrim($fullpath, '/');
    if ($file = $fs->get_file_by_hash(sha1($fullpath))) {
        send_stored_file($file, $lifetime, 0, $forcedownload, $options);
        return true;
    } else {
        send_file_not_found();
    }
   // send_file($filecontents, $filename, 0, 0, true, true, 'application/pdf');
}

function hide_or_show_weekquiz($weekquiz, $action = '') {

    global $DB;

    $weequiz = $DB->get_record('block_weekquiz_availability', array('id' => $weekquiz), '*', MUST_EXIST);
    switch ($action) {
        case SHOW:
            $weequiz->display = SHOW;
            break;
        case HIDE:
            $weequiz->display = HIDE;
            break;
        default:
            return;
            break;
    }

    return $DB->update_record('block_weekquiz_availability', $weequiz);
}

function delete_weekquiz($weekquiz) {
    global $DB;

    return $DB->delete_records('block_weekquiz_availability', array('id' => $weekquiz));
}
