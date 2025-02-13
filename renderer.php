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
class mod_adobeconnect_renderer extends plugin_renderer_base
{

    /**
     * Returns HTML to display the meeting details
     * @param object $meetingdetail
     * @param int $cmid
     * @param int $groupid
     * @return string
     */
    public function display_meeting_detail($meetingdetail, $cmid, $groupid = 0)
    {
        global $CFG;

		$context = context_module::instance($cmid);
        $target = new moodle_url('/mod/adobeconnect/view.php');

        $attributes = array('method' => 'POST', 'target' => $target);

        $html = html_writer::start_tag('form', $attributes);

        // Display the main field set
        $param = array('class' => 'aconfldset');
        $html .= html_writer::start_tag('div', $param);

        // Display the meeting name field and value
        $param = array('class' => 'aconmeetinforow');
        $html .= html_writer::start_tag('div', $param);

        // Print meeting name label
        $param = array('class' => 'aconlabeltitle', 'id' => 'aconmeetnametitle');
        $html .= html_writer::start_tag('div', array('class' => 'aconlabeltitle', 'id' => 'aconmeetnametitle'));
        $param = array('for' => 'lblmeetingnametitle');
        $html .= html_writer::tag('label', get_string('meetingname', 'adobeconnect'), $param);
        $html .= html_writer::end_tag('div');

        // Print meeting name value
        $param = array('class' => 'aconlabeltext', 'id' => 'aconmeetnametxt');
        $html .= html_writer::start_tag('div', $param);
        $param = array('for' => 'lblmeetingname');
        $html .= html_writer::tag('label', format_string($meetingdetail->name), $param);
        $html .= html_writer::end_tag('div');

        $html .= html_writer::end_tag('div');

        // Display the meeting url and port if the user has the capabilities
        if ($meetingdetail->url) {

            $param = array('class' => 'aconmeetinforow');
            $html .= html_writer::start_tag('div', $param);

            // Print meeting URL label
            $param = array('class' => 'aconlabeltitle', 'id' => 'aconmeeturltitle');
            $html .= html_writer::start_tag('div', $param);
            $param = array('for' => 'lblmeetingurltitle');
            $html .= html_writer::tag('label', get_string('meeturl', 'adobeconnect'), $param);
            $html .= html_writer::end_tag('div');

            // Print meeting URL value
            $param = array('class' => 'aconlabeltext', 'id' => 'aconmeeturltext');
            $html .= html_writer::start_tag('div', $param);
            $param = array('for' => 'lblmeetingurl');
            $html .= html_writer::tag('label', $meetingdetail->url, $param);
            $html .= html_writer::end_tag('div');

            $html .= html_writer::end_tag('div');

        }

        if ($meetingdetail->servermeetinginfo) {
            $param = array('class' => 'aconmeetinforow');
            $html .= html_writer::start_tag('div', $param);

            // Print meeting URL label
            $param = array('class' => 'aconlabeltitle', 'id' => 'aconmeeturlinfo');
            $html .= html_writer::start_tag('div', $param);
            $param = array('for' => 'lblmeetingurlinfo');
            $html .= html_writer::tag('label', get_string('meetinfo', 'adobeconnect'), $param);
            $html .= html_writer::end_tag('div');

            // Print meeting URL value
            $param = array('class' => 'aconlabeltext', 'id' => 'aconmeeturlinfotext');
            $html .= html_writer::start_tag('div', $param);
            $param = array('target' => '_blank');
//            $html .= html_writer::tag('label', $meetingdetail->url, $param);
            $html .= html_writer::link($meetingdetail->servermeetinginfo, get_string('meetinfotxt', 'adobeconnect'), $param);
            $html .= html_writer::end_tag('div');

            $html .= html_writer::end_tag('div');

        }



        // Print meeting summary label and value
        $param = array('class' => 'aconmeetinforow');
        $html .= html_writer::start_tag('div', $param);

        // Print meeting summary label
        $param = array('class' => 'aconlabeltitle', 'id' => 'aconmeetsummarytitle');
        $html .= html_writer::start_tag('div', $param);
        $param = array('for' => 'lblmeetingsummarytitle');
        $html .= html_writer::tag('label', get_string('meetingintro', 'adobeconnect'), $param);
        $html .= html_writer::end_tag('div');

        // Print meeting summary value
        $param = array('class' => 'aconlabeltext', 'id' => 'aconmeetsummarytxt');
        $html .= html_writer::start_tag('div', $param);
        $param = array('for' => 'lblmeetingsummary');
        $html .= html_writer::tag('label', format_module_intro('adobeconnect', $meetingdetail, $cmid), $param);
        $html .= html_writer::end_tag('div');

        $html .= html_writer::end_tag('div');

        // Print hidden elements
        $param = array('type' => 'hidden', 'name' => 'id', 'value' => $cmid);
        $html .= html_writer::empty_tag('input', $param);
        $param = array('type' => 'hidden', 'name' => 'group', 'value' => $groupid);
        $html .= html_writer::empty_tag('input', $param);
        $param = array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey());
        $html .= html_writer::empty_tag('input', $param);

        // Print buttons
        $param = array('class' => 'aconbtnrow');
        $html .= html_writer::start_tag('div', $param);

        $param = array('class' => 'aconbtnjoin');
        $html .= html_writer::start_tag('div', $param);

        $param = array('id' => $cmid, 'sesskey' => sesskey(), 'groupid' => $groupid);
        $target = new moodle_url('/mod/adobeconnect/join.php', $param);

        $param = array('type' => 'button',
            'value' => get_string('joinmeeting', 'adobeconnect'),
            'name' => 'btnname',
            'onclick' => 'window.open(\'' . $target->out(false) . '\', \'btnname\',
                                                 \'menubar=0,location=0,scrollbars=0,resizable=0,width=900,height=900\', 0);',
        'class' => 'moodlebuttons' );


        $html .= html_writer::empty_tag('input', $param);
        $html .= html_writer::end_tag('div');
if(has_capability('mod/adobeconnect:view_rollcall', $context)){
        $param = array('class' => 'aconbtnroles');
        $html .= html_writer::start_tag('div', $param);
        $param = array('type' => 'submit',
            'value' => get_string('selectparticipants', 'adobeconnect'),'class' => 'moodlebuttons',
            'name' => 'participants',
        );
        $html .= html_writer::empty_tag('input', $param);
        $html .= html_writer::end_tag('div');
		}
		if(has_capability('mod/adobeconnect:view_rollcall', $context)){
			$param = array('class' => 'aconbtnrollcall');
			$html .= html_writer::start_tag('div', $param);
			$param = array('id' => $cmid, 'sesskey' => sesskey());
			$target = new moodle_url('/mod/adobeconnect/rollcall.php', $param);
			$param = array('type' => 'button',
				'value' => get_string('rollcall', 'adobeconnect'),
				'name' => 'rollcall',
				'onclick' => 'window.open(\'' . $target->out(false) . '\', \'btnname\',
													 \'menubar=0,location=0,scrollbars=0,resizable=0,width=900,height=900\', 0);','class' => 'moodlebuttons'
			);
			$html .= html_writer::empty_tag('input', $param);
			$html .= html_writer::end_tag('div');
		}
        $html .= html_writer::end_tag('div');


        $html .= html_writer::end_tag('div');
        $html .= html_writer::end_tag('form');


        return $html;
    }

    /** This function outpus HTML markup with links to Connect meeting recordings.
     * If a valid groupid is passed it will only display recordings that
     * are a part of the group
     *
     * @param array - 2d array of recorded meeting and meeting details
     * @param int - course module id
     * @param int - group id
     * @param int - source sco id, used to filter meetings
     *
     * @return string - HTML markup, links to recorded meetings
     */
    function display_meeting_recording($recordings, $cmid, $groupid, $sourcescoid)
    {
        global $CFG, $USER;

        $html = '';
        $protocol = 'http://';
        $port = ''; // Include the port number only if it is a port other than 80

        if (!empty($CFG->adobeconnect_port) and (80 != $CFG->adobeconnect_port)) {
            $port = ':' . $CFG->adobeconnect_port;
        }

        if (isset($CFG->adobeconnect_https) and (!empty($CFG->adobeconnect_https))) {
            $protocol = 'https://';
        }

        // Display the meeting name field and value
        $param = array('id' => 'aconfldset2', 'class' => 'aconfldset');
        $html .= html_writer::start_tag('div', $param);
        $html .= html_writer::tag('h3', get_string('recordinghdr', 'adobeconnect'), $param);

        $table = new html_table();
        $table->attributes['class'] = 'generaltable mod_index';
        $c = 0;
        foreach ($recordings as $key => $recordinggrp) {
            if (!empty($recordinggrp)) {
                $table->head = array(
                    '#',
                    get_string('name'),
                    get_string('start', 'adobeconnect'),
                    get_string('end', 'adobeconnect'),
                    get_string('duration', 'adobeconnect'),
                );
                foreach ($recordinggrp as $recording_scoid => $recording) {
                    if  (empty($recording->duration)){
                        continue;
                    }
                    $c++;

                    $html .= html_writer::start_tag('tr');
                    $url = 'joinrecording.php?id=' . $cmid . '&recording=' . $recording_scoid .
                        '&groupid=' . $groupid . '&sesskey=' . $USER->sesskey;
                    $param = array('target' => '_blank');
                    $name = html_entity_decode($recording->name);
                    $link = html_writer::link($url, format_string($name), $param);
                    $table->data[] = array($c,
                        $link,
                        userdate(strtotime($recording->startdate)),
                        userdate(strtotime($recording->enddate)),
                        gmdate("H:i:s", $recording->duration ),
                        strtotime($recording->startdate)
                    );
                }
                usort($table->data,function ($a, $b)
                {
                    return strcmp($b[5], $a[5]);
                });
                foreach ($table->data as $index => $datum) {
                    unset($table->data[$index][5]);
                }
            }
        }

        $html .= html_writer::table($table);

        $html .= html_writer::end_tag('div');

        return $html;
        //$html .= html_writer::link($url, get_string('removemychoice','choice'));
    }

    function display_no_groups_message()
    {
        $html = html_writer::tag('p', get_string('usergrouprequired', 'adobeconnect'));
        return $html;
    }
}