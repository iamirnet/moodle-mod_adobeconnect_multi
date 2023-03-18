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

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/lib/accesslib.php');

require_login(SITEID, false);

global $USER, $CFG, $DB, $OUTPUT;

$checkifempty = true; // Check for uninitialized variable

$url = new moodle_url('/mod/adobeconnect/conntest.php');
$PAGE->set_url($url);

$admins = explode(',', $CFG->siteadmins);

if (false === array_search($USER->id, $admins)) {
    print_error('error1', 'adobeconnect', $CFG->wwwroot);
}

$ac = new stdClass();

$server = $_REQUEST['server'] == 1 || !$_REQUEST['server'] ? "" : "s_{$_REQUEST['server']}_";

$param = array('name' => "adobeconnect_{$server}admin_login");
$ac->login      = $DB->get_field('config', 'value', $param);

$param = array('name' => "adobeconnect_{$server}host");
$ac->host       = $DB->get_field('config', 'value', $param);

$param = array('name' => "adobeconnect_{$server}port");
$ac->port       = $DB->get_field('config', 'value', $param);

$param = array('name' => "adobeconnect_{$server}admin_password");
$ac->pass       = $DB->get_field('config', 'value', $param);

$param = array('name' => "adobeconnect_{$server}admin_httpauth");
$ac->httpauth   = $DB->get_field('config', 'value', $param);

$param = array('name' => "adobeconnect_{$server}email_login");
$ac->emaillogin = $DB->get_field('config', 'value', $param);

$param = array('name' => "adobeconnect_{$server}https");
$ac->https = $DB->get_field('config', 'value', $param);

foreach ($ac as $propertyname => $propertyvalue) {

    // Check if the property is equal to email login or https check boxes
    // These are the only values allowed to be empty
    $isnotemaillogin   = strcmp($propertyname, 'emaillogin');
    $isnothttps        = strcmp($propertyname, 'https');

    $checkifempty = $isnotemaillogin && $isnothttps;

    // If this property is empty
    if ($checkifempty and empty($propertyvalue)) {
        print_error('error2', 'adobeconnect', '', $propertyname);
        die();
    }

}

$strtitle = get_string('connectiontesttitle', 'adobeconnect');

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$PAGE->set_title($strtitle);
//$PAGE->set_heading($strtitle);

echo $OUTPUT->header();
echo $OUTPUT->box_start('center');

$param = new stdClass();
$param->url = 'http://docs.moodle.org/en/Remote_learner_adobe_connect_pro';
print_string('conntestintro', 'adobeconnect', $param);

if (!empty($ac->https)) {
    $https = true;
} else {
    $https = false;
}

adobe_connection_test($ac->host, $ac->port, $ac->login,
                      $ac->pass, $ac->httpauth,
                      $ac->emaillogin, $ac->https);

echo '<center>'. "\n";
echo '<input type="button" onclick="self.close();" value="' . get_string('closewindow') . '" />';
echo '</center>';

echo $OUTPUT->box_end();