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
 * @package    mod_adobeconnect
 * @author     Akinsaya Delamarre (adelamarre@remote-learner.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2015 Remote Learner.net Inc http://www.remote-learner.net
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/connect_class.php');
require_once(dirname(__FILE__).'/connect_class_dom.php');

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$a  = optional_param('a', 0, PARAM_INT);  // adobeconnect instance ID
$groupid = optional_param('group', 0, PARAM_INT);

global $CFG, $USER, $DB, $PAGE, $OUTPUT, $SESSION, $COURSE;

if ($id) {
    if (! $cm = get_coursemodule_from_id('adobeconnect', $id)) {
        print_error('Course Module ID was incorrect');
    }

    $cond = array('id' => $cm->course);
    if (! $course = $DB->get_record('course', $cond)) {
        print_error('Course is misconfigured');
    }

    $cond = array('id' => $cm->instance);
    if (! $adobeconnect = $DB->get_record('adobeconnect', $cond)) {
        print_error('Course module is incorrect');
    }

} else if ($a) {

    $cond = array('id' => $a);
    if (! $adobeconnect = $DB->get_record('adobeconnect', $cond)) {
        print_error('Course module is incorrect');
    }

    $cond = array('id' => $adobeconnect->course);
    if (! $course = $DB->get_record('course', $cond)) {
        print_error('Course is misconfigured');
    }
    if (! $cm = get_coursemodule_from_instance('adobeconnect', $adobeconnect->id, $course->id)) {
        print_error('Course Module ID was incorrect');
    }

} else {
    print_error('You must specify a course_module ID or an instance ID');
}
$COURSE = $course;
require_login($course, true, $cm);

$context = context_module::instance($cm->id);

// Check for submitted data
if (($formdata = data_submitted($CFG->wwwroot . '/mod/adobeconnect/view.php')) && confirm_sesskey()) {

    // Edit participants
    if (isset($formdata->participants)) {

        $cond = array('shortname' => 'adobeconnectpresenter');
        $roleid = $DB->get_field('role', 'id', $cond);

        if (!empty($roleid)) {
            redirect("participants.php?id=$id&contextid={$context->id}&roleid=$roleid&groupid={$formdata->group}", '', 0);
        } else {
            $message = get_string('nopresenterrole', 'adobeconnect');
            $OUTPUT->notification($message);
        }
    }
}


// Check if the user's email is the Connect Pro user's login
$usrobj = new stdClass();
$usrobj = clone($USER);

$usrobj->username = set_username($usrobj->username, $usrobj->email);

/// Print the page header
$url = new moodle_url('/mod/adobeconnect/view.php', array('id' => $cm->id));

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(format_string($adobeconnect->name));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();

$stradobeconnects = get_string('modulenameplural', 'adobeconnect');
$stradobeconnect  = get_string('modulename', 'adobeconnect');

$params = array('instanceid' => $cm->instance);
$sql = "SELECT meetingscoid ".
       "FROM {adobeconnect_meeting_groups} amg ".
       "WHERE amg.instanceid = :instanceid ";

$meetscoids = $DB->get_records_sql($sql, $params);
$recording = array();
$meetingserver = isset($_REQUEST['meetingserver']) && $_REQUEST['meetingserver'] && is_numeric($_REQUEST['meetingserver']) ? $_REQUEST['meetingserver'] : ($DB->get_field('adobeconnect', 'meetingserver', ['course' => $COURSE->id]) ?: 1);
if (!empty($meetscoids)) {
    $recscoids = array();
    if ($instanceid = $DB->get_field('adobeconnect_meeting_groups', 'instanceid', ['meetingscoid' => array_values($meetscoids)[0]->meetingscoid])) {
        //$DB->set_field('adobeconnect', 'templatescoid', array_values($meetscoids)[0]->meetingscoid, ['id' => $instanceid]);
        $meetingserver = isset($_REQUEST['meetingserver']) && $_REQUEST['meetingserver'] && is_numeric($_REQUEST['meetingserver']) ? $_REQUEST['meetingserver'] : ($DB->get_field('adobeconnect', 'meetingserver', ['id' => $instanceid]) ?: 1);
    }
    $aconnect = aconnect_login($meetingserver);

    // Get the forced recordings folder sco-id
    // Get recordings that are based off of the meeting
    $fldid = aconnect_get_folder($aconnect, 'forced-archives');
    foreach($meetscoids as $scoid) {

        $data = aconnect_get_recordings($aconnect, $fldid, $scoid->meetingscoid);

        if (!empty($data)) {
          // Store recordings in an array to be moved to the Adobe shared folder later on
          $recscoids = array_merge($recscoids, array_keys($data));

        }

    }

    // Move the meetings to the shared content folder
    if (!empty($recscoids)) {
        $recscoids = array_flip($recscoids);

        if (aconnect_move_to_shared($aconnect, $recscoids)) {
            // do nothing
        }
    }

    //Get the shared content folder sco-id
    // Create a list of recordings moved to the shared content folder
    $fldid = aconnect_get_folder($aconnect, 'content');
    foreach($meetscoids as $scoid) {

        // May need this later on
        $data = aconnect_get_recordings($aconnect, $fldid, $scoid->meetingscoid);

        if (!empty($data)) {
            $recording[] = $data;
        }

        $data2 = aconnect_get_recordings($aconnect, $scoid->meetingscoid, $scoid->meetingscoid);

        if (!empty($data2)) {
             $recording[] = $data2;
        }

    }


    // Clean up any duplciated meeting recordings.  Duplicated meeting recordings happen when the
    // recording settings on ACP server change between "publishing recording links in meeting folders" and
    // not "publishing recording links in meeting folders"
    $names = array();
    foreach ($recording as $key => $recordingarray) {

        foreach ($recordingarray as $key2 => $record) {


            if (!empty($names)) {

                if (!array_search($record->name, $names)) {

                    $names[] = $record->name;
                } else {

                    unset($recording[$key][$key2]);
                }
            } else {

                $names[] = $record->name;
            }
        }
    }

    unset($names);


    // Check if the user exists and if not create the new user
    if (!($usrprincipal = aconnect_user_exists($aconnect, $usrobj))) {
        if (!($usrprincipal = aconnect_create_user($aconnect, $usrobj))) {
            // DEBUG
            debugging("error creating user", DEBUG_DEVELOPER);

//            print_object("error creating user");
//            print_object($aconnect->_xmlresponse);
            $validuser = false;
        }
    }

    // Check the user's capability and assign them view permissions to the recordings folder
    // if it's a public meeting give them permissions regardless
    if ($cm->groupmode) {


        if (has_capability('mod/adobeconnect:meetingpresenter', $context, $usrobj->id) or
            has_capability('mod/adobeconnect:meetingparticipant', $context, $usrobj->id)) {
            if (aconnect_assign_user_perm($aconnect, $usrprincipal, $fldid, ADOBE_VIEW_ROLE)) {
                //DEBUG
                // echo 'true';
            } else {
                //DEBUG
                debugging("error assign user recording folder permissions", DEBUG_DEVELOPER);
//                print_object('error assign user recording folder permissions');
//                print_object($aconnect->_xmlrequest);
//                print_object($aconnect->_xmlresponse);
            }
        }
    } else {
        aconnect_assign_user_perm($aconnect, $usrprincipal, $fldid, ADOBE_VIEW_ROLE);
    }

    aconnect_logout($aconnect);
}

// Log in the current user
$login = $usrobj->username;
$password  = $usrobj->username;
$https = false;
$meetingserver = $adobeconnect->meetingserver ?: 1;
$meetingserver_prefix = $meetingserver == 1 ? "" : "s_{$meetingserver}_";
$configac = (object) [
    "adobeconnect_https" => $CFG->{"adobeconnect_{$meetingserver_prefix}https"},
    "adobeconnect_host" => $CFG->{"adobeconnect_{$meetingserver_prefix}host"},
    "adobeconnect_port" => $CFG->{"adobeconnect_{$meetingserver_prefix}port"},
    "adobeconnect_meethost" => $CFG->{"adobeconnect_{$meetingserver_prefix}meethost"},
];
if (isset($configac->adobeconnect_https) and (!empty($configac->adobeconnect_https))) {
    $https = true;
}

$aconnect = new connect_class_dom($configac->adobeconnect_host, $configac->adobeconnect_port,
                                  '', '', '', $https);

$aconnect->request_http_header_login(1, $login);
$adobesession = $aconnect->get_cookie();

// The batch of code below handles the display of Moodle groups
if ($cm->groupmode) {

    $querystring = array('id' => $cm->id);
    $url = new moodle_url('/mod/adobeconnect/view.php', $querystring);

    // Retrieve a list of groups that the current user can see/manage
    $user_groups = groups_get_activity_allowed_groups($cm, $USER->id);

    if ($user_groups) {

        // Print groups selector drop down
        groups_print_activity_menu($cm, $url, false, true);


        // Retrieve the currently active group for the user's session
        $groupid = groups_get_activity_group($cm);

        /* Depending on the series of events groups_get_activity_group will
         * return a groupid value of  0 even if the user belongs to a group.
         * If the groupid is set to 0 then use the first group that the user
         * belongs to.
         */
        $aag = has_capability('moodle/site:accessallgroups', $context);

        if (0 == $groupid) {
            $groups = groups_get_user_groups($cm->course, $USER->id);
            $groups = current($groups);

            if (!empty($groups)) {

                $groupid = key($SESSION->activegroup[$cm->course]);
            } elseif ($aag) {
                /* If the user does not explicitely belong to any group
                 * check their capabilities to see if they have access
                 * to manage all groups; and if so display the first course
                 * group by default
                 */
                $groupid = key($user_groups);
            }
        }
    }
}


$aconnect = aconnect_login($meetingserver);

// Get the Meeting details
$cond = array('instanceid' => $adobeconnect->id, 'groupid' => $groupid);
$scoid = $DB->get_field('adobeconnect_meeting_groups', 'meetingscoid', $cond);

$meetfldscoid = aconnect_get_folder($aconnect, 'meetings');


$filter = array('filter-sco-id' => $scoid);

if (($meeting = aconnect_meeting_exists($aconnect, $meetfldscoid, $filter))) {
    $meeting = current($meeting);
} else {
    /* First check if the module instance has a user associated with it
       if so, then check the user's adobe connect folder for existince of the meeting */
    if (!empty($adobeconnect->userid)) {
        $username     = get_connect_username($adobeconnect->userid);
        $meetfldscoid = aconnect_get_user_folder_sco_id($aconnect, $username);
        $meeting      = aconnect_meeting_exists($aconnect, $meetfldscoid, $filter);

        if (!empty($meeting)) {
            $meeting = current($meeting);
        }
    }

    // If meeting does not exist then display an error message
    if (empty($meeting)) {
        /*adobeconnect_add_instance($aconnect);
        var_dump(55);
        die();*/
        $message = get_string('nomeeting', 'adobeconnect');
        echo $OUTPUT->notification($message);
        aconnect_logout($aconnect);
        die();
    }
}

aconnect_logout($aconnect);

$sesskey = !empty($usrobj->sesskey) ? $usrobj->sesskey : '';

$renderer = $PAGE->get_renderer('mod_adobeconnect');

$meetingdetail = new stdClass();
$meetingdetail->name = html_entity_decode($meeting->name);

// Determine if the Meeting URL is to appear
if (has_capability('mod/adobeconnect:meetingpresenter', $context) or
    has_capability('mod/adobeconnect:meetinghost', $context)) {

    // Include the port number only if it is a port other than 80
    $port = '';

    if (!empty($configac->adobeconnect_port) and (80 != $configac->adobeconnect_port)) {
        $port = ':' . $configac->adobeconnect_port;
    }

    $protocol = 'http://';

    if ($https) {
        $protocol = 'https://';
    }

    $url = $protocol . $configac->adobeconnect_meethost . $port
           . $meeting->url;

    $meetingdetail->url = $url;


    $url = $protocol.$configac->adobeconnect_meethost.$port.'/admin/meeting/sco/info?principal-id='.
           $usrprincipal.'&amp;sco-id='.$scoid.'&amp;session='.$adobesession;

    // Get the server meeting details link
    $meetingdetail->servermeetinginfo = $url;

} else {
    $meetingdetail->url = '';
    $meetingdetail->servermeetinginfo = '';
}

// Determine if the user has the permissions to assign perticipants
$meetingdetail->participants = false;

if (has_capability('mod/adobeconnect:meetingpresenter', $context, $usrobj->id) or
    has_capability('mod/adobeconnect:meetinghost', $context, $usrobj->id)){

    $meetingdetail->participants = true;
}

//  CONTRIB-2929 - remove date format and let Moodle decide the format
// Get the meeting start time
$time = userdate($adobeconnect->starttime);
$meetingdetail->starttime = $time;

// Get the meeting end time
$time = userdate($adobeconnect->endtime);
$meetingdetail->endtime = $time;

// Get the meeting intro text
$meetingdetail->intro = $adobeconnect->intro;
$meetingdetail->introformat = $adobeconnect->introformat;

echo $OUTPUT->box_start('generalbox', 'meetingsummary');

// If groups mode is enabled for the activity and the user belongs to a group
if (NOGROUPS != $cm->groupmode && 0 != $groupid) {

    echo $renderer->display_meeting_detail($meetingdetail, $id, $groupid);
} else if (NOGROUPS == $cm->groupmode) {

    // If groups mode is disabled
    echo $renderer->display_meeting_detail($meetingdetail, $id, $groupid);
} else {

    // If groups mode is enabled but the user is not in a group
    echo $renderer->display_no_groups_message();
}

echo $OUTPUT->box_end();

echo '<br />';

$showrecordings = false;
// Check if meeting is private, if so check the user's capability.  If public show recorded meetings
if (!$adobeconnect->meetingpublic) {

    // Check capabilities
    if (has_capability('mod/adobeconnect:meetingpresenter', $context, $usrobj->id) or
        has_capability('mod/adobeconnect:meetingparticipant', $context, $usrobj->id)) {
        $showrecordings = true;
    }
} else {

    // Check group mode and group membership
    $showrecordings = true;
}

// Lastly check group mode and group membership
if (NOGROUPS != $cm->groupmode && 0 != $groupid) {
    $showrecordings = $showrecordings && true;
} elseif (NOGROUPS == $cm->groupmode) {
    $showrecording = $showrecordings && true;
} else {
    $showrecording = $showrecordings && false;
}

$recordings = $recording;

if ($showrecordings and !empty($recordings)) {
    echo $OUTPUT->box_start('generalbox', 'meetingsummary');

    // Echo the rendered HTML to the page
    echo $renderer->display_meeting_recording($recordings, $cm->id, $groupid, $scoid);

    echo $OUTPUT->box_end();
}

// Trigger an event for joining a meeting.
$params = array(
    'relateduserid' => $USER->id,
    'courseid' => $course->id,
    'context' => context_module::instance($cm->id)
);

$event = \mod_adobeconnect\event\adobeconnect_view::create($params);
$event->trigger();

/// Finish the page
echo $OUTPUT->footer();
