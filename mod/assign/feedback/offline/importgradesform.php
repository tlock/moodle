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
 * This file contains the forms to create and edit an instance of this module
 *
 * @package   assignfeedback_offline
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/mod/assign/feedback/offline/importgradeslib.php');

/**
 * Import grades form
 *
 * @package   assignfeedback_offline
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignfeedback_offline_import_grades_form extends moodleform implements renderable {

    /**
     * Create this grade import form
     */
    public function definition() {
        global $CFG, $PAGE, $DB;

        $mform = $this->_form;
        $params = $this->_customdata;

        $renderer = $PAGE->get_renderer('assign');

        // Visible elements.
        $assignment = $params['assignment'];
        $csvdata = $params['csvdata'];
        $gradeimporter = $params['gradeimporter'];
        $update = false;

        $ignoremodified = $params['ignoremodified'];
        $draftid = $params['draftid'];

        if (!$gradeimporter) {
            print_error('invalidarguments');
            return;
        }

        if ($csvdata) {
            $gradeimporter->parsecsv($csvdata);
        }

        $scaleoptions = null;
        if ($assignment->get_instance()->grade < 0) {
            if ($scale = $DB->get_record('scale', array('id'=>-($assignment->get_instance()->grade)))) {
                $scaleoptions = make_menu_from_list($scale->scale);
            }
        }
        if (!$gradeimporter->init()) {
            $thisurl = new moodle_url('/mod/assign/view.php', array('action'=>'viewpluginpage',
                                                                     'pluginsubtype'=>'assignfeedback',
                                                                     'plugin'=>'offline',
                                                                     'pluginaction'=>'uploadgrades',
                                                                     'id'=>$assignment->get_course_module()->id));
            print_error('invalidgradeimport', 'assignfeedback_offline', $thisurl);
            return;
        }

        $mform->addElement('header', 'importgrades', get_string('importgrades', 'assignfeedback_offline'));

        $updates = array();
        $checkduplicateuserids = array();
        $duplicate_more_than_two = array();
        while ($record = $gradeimporter->next()) {
            $user = $record->user;
            $grade = $record->grade;
            $modified = $record->modified;
            $userdesc = fullname($user);
            if ($assignment->is_blind_marking()) {
                $userdesc = get_string('hiddenuser', 'assign') . $assignment->get_uniqueid_for_user($user->id);
            }

            $usergrade = $assignment->get_user_grade($user->id, false);
            // Note: we lose the seconds when converting to user date format - so must not count seconds in comparision.
            $skip = false;
            $feedbackskip = false;

            $stalemodificationdate = ($usergrade && $usergrade->timemodified > ($modified + 60));

            if (!empty($scaleoptions)) {
                // This is a scale - we need to convert any grades to indexes in the scale.
                $scaleindex = array_search($grade, $scaleoptions);
                if ($scaleindex !== false) {
                    $grade = $scaleindex;
                } else {
                    $grade = '';
                }
            } else {
                if(is_numeric($grade)) {
                    $grade = unformat_float($grade);
                } else {
                    // Let it out whatever it is so it can be handled in the error msgs.
                    $grade = $grade;
                }
            }

            $duplicate = false;
            if ($usergrade && $usergrade->grade == $grade) {
                // Skip - grade not modified.
                $skip = true;
            } else if (!isset($grade) || $grade === '' || $grade < 0) {
                // Skip - grade has no value.
                $skip = true;
                $feedbackskip = false;
            } else if (!$ignoremodified && $stalemodificationdate) {
                // Skip - grade has been modified.
                $skip = true;
                $feedbackskip = false;
            } else if ($assignment->grading_disabled($user->id)) {
                // Skip grade is locked.
                $skip = true;
                $feedbackskip = false;
            } else if (($assignment->get_instance()->grade > -1) &&
                      (($grade < 0) || ($grade > $assignment->get_instance()->grade))) {
                // Out of range.
                $skip = true;
                $feedbackskip = false;
            }

            if(!is_numeric($grade) && ($assignment->get_instance()->grade > -1) && !empty($grade)) {
                // Numeric value expected and text entered.
                $skip = true;
                $feedbackskip = true;
            } else if (isset($checkduplicateuserids[$user->id])){
                // Duplicate user in the worksheet.
                $skip = true;
                $feedbackskip = true;
                $duplicate = true;
                // Remove all users if they are found duplicate to avoid ambiguity.
                unset($updates['grade_' . $user->id]);
                foreach ($record->feedback as $feedback) {
                    $plugin = $feedback['plugin'];
                    unset($updates['feedback_' . $user->id . '_' . $plugin->get_type()]);
                }
            }

            if (!$skip) {
                $update = true;
                $formattedgrade = $grade;
                if (!empty($scaleoptions)) {
                    $formattedgrade = $scaleoptions[$grade];
                }

                $previousgrade = '';
                if (!isset($usergrade->grade) || empty($usergrade->grade)) {
                     $previousgrade = '0';
                } else {
                     $previousgrade = $usergrade->grade;
                }

                $updates['grade_' . $user->id] = get_string('gradeupdate', 'assignfeedback_offline',
                                            array('grade'=>format_float($formattedgrade, 2), 'student'=>$userdesc,
                                                  'previousgrade'=> format_float($previousgrade,2)));
            }

            if ($skip) {
                if ($grade < 0) {
                     $updates[] = get_string('invalidgrademsgupdate', 'assignfeedback_offline',
                                                    array('invalidgrade'=>$grade, 'student'=>$userdesc));
                } else if(!is_numeric($grade) && ($assignment->get_instance()->grade > -1) && !empty($grade)) {
                    // Numbers expected but text entered.
                     $updates[] = get_string('invalidtextgrademsgupdate', 'assignfeedback_offline',
                                                    array('invalidgrade'=> $grade , 'student'=>$userdesc));
                } else if (($assignment->get_instance()->grade > -1) && (($grade < 0) || ($grade > $assignment->get_instance()->grade))) {
                     // Error msg if out of range entered.
                     $updates[] = get_string('invalidgradeoutofrangemsgupdate', 'assignfeedback_offline',
                                                    array('invalidgrade'=> $grade , 'student'=>$userdesc,
                                                          'maxgrade'=> $assignment->get_instance()->grade));
                } else if (isset($checkduplicateuserids[$user->id])) {
                    // Check duplicate user.
                    // Remove all users if they are found duplicate to avoid ambiguity.
                    unset($updates['grade_' . $user->id]);
                    foreach ($record->feedback as $feedback) {
                    $plugin = $feedback['plugin'];
                    unset($updates['feedback_' . $user->id . '_' . $plugin->get_type()]);
                    }
                    if(!isset($duplicate_more_than_two[$user->id])) {
                        $updates[] = get_string('invalidduplicateusermsgupdate', 'assignfeedback_offline',
                                     array('student'=>$userdesc));
                    }
                    $duplicate_more_than_two[$user->id] = true;

                } else if (!$ignoremodified && $stalemodificationdate) {
                    // Grade has been modified more recently in Moodle.
                    $updates[] = get_string('invalidmodifiedgrademsgupdate', 'assignfeedback_offline',
                                                    array('invalidgrade'=> $grade , 'student'=>$userdesc));
                } else if ($usergrade && $usergrade->grade == $grade) {
                    // Skip - grade not modified because current value and the latest one are the same value.
                   $updates[] = '';
                }
           }

           if (!$duplicate && ($ignoremodified || !$stalemodificationdate)) {
                foreach ($record->feedback as $feedback) {
                    $plugin = $feedback['plugin'];
                    $field = $feedback['field'];
                    $newvalue = $feedback['value'];
                    $description = $feedback['description'];
                    $oldvalue = '';
                    if ($usergrade) {
                        $oldvalue = $plugin->get_editor_text($field, $usergrade->id);
                    }
                    if (($newvalue != $oldvalue) && !$feedbackskip) {
                        $update = true;
                        $updates['feedback_' . $user->id . '_' . $plugin->get_type()] = get_string('feedbackupdate', 'assignfeedback_offline', array('text'=>$newvalue,
                                                                                                   'field'=>$description,
                                                                                                   'student'=>$userdesc));
                    }
                }
            }

            $checkduplicateuserids[$user->id] = true;
        }
        $gradeimporter->close(false);

        $mform->addElement('html', $renderer->list_block_contents(array(), $updates));

        $mform->addElement('hidden', 'id', $assignment->get_course_module()->id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'action', 'viewpluginpage');
        $mform->setType('action', PARAM_ALPHA);
        $mform->addElement('hidden', 'confirm', 'true');
        $mform->setType('confirm', PARAM_BOOL);
        $mform->addElement('hidden', 'plugin', 'offline');
        $mform->setType('plugin', PARAM_PLUGIN);
        $mform->addElement('hidden', 'pluginsubtype', 'assignfeedback');
        $mform->setType('pluginsubtype', PARAM_PLUGIN);
        $mform->addElement('hidden', 'pluginaction', 'uploadgrades');
        $mform->setType('pluginaction', PARAM_ALPHA);
        $mform->addElement('hidden', 'importid', $gradeimporter->importid);
        $mform->setType('importid', PARAM_INT);
        $mform->addElement('hidden', 'ignoremodified', $ignoremodified);
        $mform->setType('ignoremodified', PARAM_BOOL);
        $mform->addElement('hidden', 'draftid', $draftid);
        $mform->setType('draftid', PARAM_INT);
        if ($update) {
            $this->add_action_buttons(true, get_string('confirm'));
        } else {
            $mform->addElement('cancel');
            $mform->closeHeaderBefore('cancel');
        }

    }
}

