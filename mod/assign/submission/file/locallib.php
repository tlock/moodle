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
 * This file contains the definition for the library class for file submission plugin
 *
 * This class provides all the functionality for the new assign module.
 *
 * @package assignsubmission_file
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir.'/eventslib.php');

defined('MOODLE_INTERNAL') || die();

// File areas for file submission assignment.
define('ASSIGNSUBMISSION_FILE_MAXFILES', 20);
define('ASSIGNSUBMISSION_FILE_MAXSUMMARYFILES', 5);
define('ASSIGNSUBMISSION_FILE_FILEAREA', 'submission_files');

/**
 * Library class for file submission plugin extending submission plugin base class
 *
 * @package   assignsubmission_file
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_submission_file extends assign_submission_plugin {

    /**
     * Get the name of the file submission plugin
     * @return string
     */
    public function get_name() {
        return get_string('file', 'assignsubmission_file');
    }

    /**
     * Get file submission information from the database
     *
     * @param int $submissionid
     * @return mixed
     */
    private function get_file_submission($submissionid) {
        global $DB;
        return $DB->get_record('assignsubmission_file', array('submission'=>$submissionid));
    }

    /**
     * Get the default setting for file submission plugin
     *
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform) {
        global $CFG, $COURSE;

        $worddocs = $this->get_config('worddocs');
        $pdfdocs = $this->get_config('pdfdocs');
        $imagedocs = $this->get_config('imagedocs');
        $videodocs = $this->get_config('videodocs');
        $audiodocs = $this->get_config('audiodocs');
        $otherdocs = $this->get_config('otherdocs');
        $otherdocstext = $this->get_config('otherdocstext');
        $restrictfiletypes = $this->get_config('restrictfiletypes');
        $defaultmaxfilesubmissions = $this->get_config('maxfilesubmissions');
        $defaultmaxsubmissionsizebytes = $this->get_config('maxsubmissionsizebytes');

        $settings = array();
        $options = array();
        for ($i = 1; $i <= ASSIGNSUBMISSION_FILE_MAXFILES; $i++) {
            $options[$i] = $i;
        }

        $name = get_string('maxfilessubmission', 'assignsubmission_file');
        $mform->addElement('select', 'assignsubmission_file_maxfiles', $name, $options);
        $mform->addHelpButton('assignsubmission_file_maxfiles',
                              'maxfilessubmission',
                              'assignsubmission_file');
        $mform->setDefault('assignsubmission_file_maxfiles', $defaultmaxfilesubmissions);
        $mform->disabledIf('assignsubmission_file_maxfiles', 'assignsubmission_file_enabled', 'notchecked');

        $choices = get_max_upload_sizes($CFG->maxbytes,
                                        $COURSE->maxbytes,
                                        get_config('assignsubmission_file', 'maxbytes'));

        $settings[] = array('type' => 'select',
                            'name' => 'maxsubmissionsizebytes',
                            'description' => get_string('maximumsubmissionsize', 'assignsubmission_file'),
                            'options'=> $choices,
                            'default'=> $defaultmaxsubmissionsizebytes);

        $name = get_string('maximumsubmissionsize', 'assignsubmission_file');
        $mform->addElement('select', 'assignsubmission_file_maxsizebytes', $name, $choices);
        $mform->addHelpButton('assignsubmission_file_maxsizebytes',
                              'maximumsubmissionsize',
                              'assignsubmission_file');
        $mform->setDefault('assignsubmission_file_maxsizebytes', $defaultmaxsubmissionsizebytes);
        $mform->disabledIf('assignsubmission_file_maxsizebytes',
                           'assignsubmission_file_enabled',
                           'notchecked');

        require_once("HTML/QuickForm/element.php");
        if (class_exists('HTML_QuickForm')) {
            HTML_QuickForm::registerRule('othertextboxemptycheck', 'function', 'othertextbox_validation', 'assign_submission_file');
        }

        // File types restriction setting.
        $mform->addElement('selectyesno', 'assignsubmission_file_restrictfiletypes', get_string('restrictfiletypes', 'assignsubmission_file'));
        $mform->addHelpButton('assignsubmission_file_restrictfiletypes', 'restrictfiletypes', 'assignsubmission_file');
        $mform->setDefault('assignsubmission_file_restrictfiletypes',  $restrictfiletypes);
        $mform->disabledIf('assignsubmission_file_restrictfiletypes', 'assignsubmission_file_enabled', 'eq', 0);

        // File type checkboxes.

        // Word docs.
        $mform->addElement('advcheckbox', 'assignsubmission_file_worddocs', '', get_string('worddocs', 'assignsubmission_file'));
        $mform->setDefault('assignsubmission_file_worddocs', $worddocs);
        $mform->disabledIf('assignsubmission_file_worddocs', 'assignsubmission_file_enabled', 'eq', 0);
        $mform->disabledIf('assignsubmission_file_worddocs', 'assignsubmission_file_restrictfiletypes', 'eq', 0);

        // PDF docs.
        $mform->addElement('advcheckbox', 'assignsubmission_file_pdfdocs', '', get_string('pdfdocs', 'assignsubmission_file'));
        $mform->setDefault('assignsubmission_file_pdfdocs', $pdfdocs);
        $mform->disabledIf('assignsubmission_file_pdfdocs', 'assignsubmission_file_enabled', 'eq', 0);
        $mform->disabledIf('assignsubmission_file_pdfdocs', 'assignsubmission_file_restrictfiletypes', 'eq', 0);

        // Image docs.
        $mform->addElement('advcheckbox', 'assignsubmission_file_imagedocs', '', get_string('imagedocs', 'assignsubmission_file'));
        $mform->setDefault('assignsubmission_file_imagedocs', $imagedocs);
        $mform->disabledIf('assignsubmission_file_imagedocs', 'assignsubmission_file_enabled', 'eq', 0);
        $mform->disabledIf('assignsubmission_file_imagedocs', 'assignsubmission_file_restrictfiletypes', 'eq', 0);

        // Video docs.
        $mform->addElement('advcheckbox', 'assignsubmission_file_videodocs', '', get_string('videodocs', 'assignsubmission_file'));
        $mform->setDefault('assignsubmission_file_videodocs', $videodocs);
        $mform->disabledIf('assignsubmission_file_videodocs', 'assignsubmission_file_enabled', 'eq', 0);
        $mform->disabledIf('assignsubmission_file_videodocs', 'assignsubmission_file_restrictfiletypes', 'eq', 0);

        // Audio docs.
        $mform->addElement('advcheckbox', 'assignsubmission_file_audiodocs', '', get_string('audiodocs', 'assignsubmission_file'));
        $mform->setDefault('assignsubmission_file_audiodocs', $audiodocs);
        $mform->disabledIf('assignsubmission_file_audiodocs', 'assignsubmission_file_enabled', 'eq', 0);
        $mform->disabledIf('assignsubmission_file_audiodocs', 'assignsubmission_file_restrictfiletypes', 'eq', 0);

        // Other docs.
        $mform->addElement('advcheckbox', 'assignsubmission_file_otherdocs', '', get_string('otherdocs', 'assignsubmission_file'));
        $mform->setDefault('assignsubmission_file_otherdocs', $otherdocs);
        $mform->disabledIf('assignsubmission_file_otherdocs', 'assignsubmission_file_enabled', 'eq', 0);
        $mform->disabledIf('assignsubmission_file_otherdocs', 'assignsubmission_file_restrictfiletypes', 'eq', 0);

        // Other docs text
        $mform->addElement('text', 'assignsubmission_file_otherdocstext','','placeholder= "*.xlsx, *.pptx"');
        $mform->setType('assignsubmission_file_otherdocstext', PARAM_TEXT);
        $mform->setDefault('assignsubmission_file_otherdocstext', $otherdocstext);
        $mform->addRule('assignsubmission_file_otherdocstext', get_string('incorrectformatothertext', 'assignsubmission_file'), 'othertextboxemptycheck', null, 'client');
        $mform->disabledIf('assignsubmission_file_otherdocstext', 'assignsubmission_file_otherdocs', 'eq', 0);
        $mform->disabledIf('assignsubmission_file_otherdocstext', 'assignsubmission_file_enabled', 'eq', 0);
        $mform->disabledIf('assignsubmission_file_otherdocstext', 'assignsubmission_file_restrictfiletypes', 'eq', 0);

    }

    /**
     * Registered callback for the addRule function to validate the other textbox validation
     * @param $elementValue  value entered by the user
     * @return boolean
     */
    public static function othertextbox_validation($elementValue) {
        // Must match this patttern : *.etc, *.test
        if (preg_match('/^\*\.[a-zA-Z0-9]+(,\s*\*\.[a-zA-Z0-9]+)*$/i', $elementValue)) {
            return true;
        }

        return false;
    }

    /**
     * Save the settings for file submission plugin
     *
     * @param stdClass $data
     * @return bool
     */
    public function save_settings(stdClass $data) {
        $this->set_config('maxfilesubmissions', $data->assignsubmission_file_maxfiles);
        $this->set_config('maxsubmissionsizebytes', $data->assignsubmission_file_maxsizebytes);
        $this->set_config('restrictfiletypes', $data->assignsubmission_file_restrictfiletypes);
        $this->set_config('worddocs', $data->assignsubmission_file_worddocs);
        $this->set_config('pdfdocs', $data->assignsubmission_file_pdfdocs);
        $this->set_config('imagedocs', $data->assignsubmission_file_imagedocs);
        $this->set_config('videodocs', $data->assignsubmission_file_videodocs);
        $this->set_config('audiodocs', $data->assignsubmission_file_audiodocs);
        $this->set_config('otherdocs', $data->assignsubmission_file_otherdocs);
        if (isset($data->assignsubmission_file_otherdocstext)) {
            $otherdocstext = str_replace('"',"'",$data->assignsubmission_file_otherdocstext);
            $this->set_config('otherdocstext', $otherdocstext);
        }
        return true;
    }

    /**
     * File format options
     *
     * @return array
     */
    private function get_file_options() {
        $restrictfiletypes = $this->get_config('restrictfiletypes');
        $worddocs = $this->get_config('worddocs');
        $pdfdocs = $this->get_config('pdfdocs');
        $imagedocs = $this->get_config('imagedocs');
        $videodocs = $this->get_config('videodocs');
        $audiodocs = $this->get_config('audiodocs');
        $otherdocs = $this->get_config('otherdocs');

        // check mimetypes
        if ($restrictfiletypes) {
            $worddocs_types = array();
            $pdfdocs_types = array();
            $imagedocs_types = array();
            $videodocs_types = array();
            $audiodocs_types = array();
            $otherdocs_types = array();
            if ($worddocs && empty($otherdocs)) {
                // Word (*.doc, *.docx, *.rtf).
                $worddocs_types = file_get_typegroup('type', array('application/msword',
                                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/rtf'));
            }
            if ($pdfdocs && empty($otherdocs)) {
                // PDF (*.pdf).
                $pdfdocs_types = file_get_typegroup('type', array('application/pdf'));
            }
            if ($imagedocs && empty($otherdocs)) {
                // Image (*.gif, *.jpg, *.jpeg, *.png), *.svg, *.tiff).
               $imagedocs_types = file_get_typegroup('type', array('image/gif', 'image/jpeg', 'image/png', 'image/svg+xml', 'image/tiff'));
            }
            if ($videodocs && empty($otherdocs)) {
                // Video (*.mp4, *.flv, *.mov, *.avi).
                $videodocs_types = file_get_typegroup('type', array('video/mp4', 'video/quicktime', 'video/x-ms-wm'));
            }
            if ($audiodocs && empty($otherdocs)) {
                // Audio (*.mp3, *.ogg, *.wav, *.aac, *.wma).
                $audiodocs_types = file_get_typegroup('type', array('audio/mp3', 'audio/ogg', 'audio/wav', 'audio/aac', 'audio/wma'));
            }
            if ($otherdocs) {
                // Other file types.
                // The mimetype of 'other' files just can not be verified here as we just dont know what they are and they might not
                // Be in the list (get_mimetypes_array) so if this other checkbox is ticked then we are going to accept
                // All types of files and the validation of the uploaded file(s) extensions will be done when the assignment is
                // Submmited not in the file picker any longer
                $otherdocs_types =  array('*');

            }
            $accepted_types = array_merge($worddocs_types, $pdfdocs_types, $imagedocs_types,
                                          $videodocs_types, $audiodocs_types, $otherdocs_types);
        } else {
            $accepted_types = '*';
        }

        $fileoptions = array('subdirs'=>1,
                                'maxbytes'=>$this->get_config('maxsubmissionsizebytes'),
                                'maxfiles'=>$this->get_config('maxfilesubmissions'),
                                'accepted_types'=> $accepted_types,
                                'return_types'=>FILE_INTERNAL);
        return $fileoptions;
    }

    /**
     * Add elements to submission form
     *
     * @param mixed $submission stdClass|null
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return bool
     */
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data) {

        if ($this->get_config('maxfilesubmissions') <= 0) {
            return false;
        }

        $fileoptions = $this->get_file_options();
        $submissionid = $submission ? $submission->id : 0;

        $data = file_prepare_standard_filemanager($data,
                                                  'files',
                                                  $fileoptions,
                                                  $this->assignment->get_context(),
                                                  'assignsubmission_file',
                                                  ASSIGNSUBMISSION_FILE_FILEAREA,
                                                  $submissionid);
        $mform->addElement('filemanager', 'files_filemanager', $this->get_name(), null, $fileoptions);

        return true;
    }

    /**
     * Count the number of files
     *
     * @param int $submissionid
     * @param string $area
     * @return int
     */
    private function count_files($submissionid, $area) {

        $fs = get_file_storage();
        $files = $fs->get_area_files($this->assignment->get_context()->id,
                                     'assignsubmission_file',
                                     $area,
                                     $submissionid,
                                     'id',
                                     false);

        return count($files);
    }

    /**
     * Save the files and trigger plagiarism plugin, if enabled,
     * to scan the uploaded files via events trigger
     *
     * @param stdClass $submission
     * @param stdClass $data
     * @return bool
     */
    public function save(stdClass $submission, stdClass $data) {
        global $USER, $DB;

        $fileoptions = $this->get_file_options();

        $data = file_postupdate_standard_filemanager($data,
                                                     'files',
                                                     $fileoptions,
                                                     $this->assignment->get_context(),
                                                     'assignsubmission_file',
                                                     ASSIGNSUBMISSION_FILE_FILEAREA,
                                                     $submission->id);

        $filesubmission = $this->get_file_submission($submission->id);

        // Plagiarism code event trigger when files are uploaded.

        $fs = get_file_storage();
        $files = $fs->get_area_files($this->assignment->get_context()->id,
                                     'assignsubmission_file',
                                     ASSIGNSUBMISSION_FILE_FILEAREA,
                                     $submission->id,
                                     'id',
                                     false);

        $count = $this->count_files($submission->id, ASSIGNSUBMISSION_FILE_FILEAREA);

        $params = array(
            'context' => context_module::instance($this->assignment->get_course_module()->id),
            'courseid' => $this->assignment->get_course()->id,
            'objectid' => $submission->id,
            'other' => array(
                'content' => '',
                'pathnamehashes' => array_keys($files)
            )
        );
        $event = \assignsubmission_file\event\assessable_uploaded::create($params);
        $event->set_legacy_files($files);
        $event->trigger();

        $groupname = null;
        $groupid = 0;
        // Get the group name as other fields are not transcribed in the logs and this information is important.
        if (empty($submission->userid) && !empty($submission->groupid)) {
            $groupname = $DB->get_field('groups', 'name', array('id' => $submission->groupid), '*', MUST_EXIST);
            $groupid = $submission->groupid;
        } else {
            $params['relateduserid'] = $submission->userid;
        }

        // Unset the objectid and other field from params for use in submission events.
        unset($params['objectid']);
        unset($params['other']);
        $params['other'] = array(
            'submissionid' => $submission->id,
            'submissionattempt' => $submission->attemptnumber,
            'submissionstatus' => $submission->status,
            'filesubmissioncount' => $count,
            'groupid' => $groupid,
            'groupname' => $groupname
        );

        if ($filesubmission) {
            $filesubmission->numfiles = $this->count_files($submission->id,
                                                           ASSIGNSUBMISSION_FILE_FILEAREA);
            $updatestatus = $DB->update_record('assignsubmission_file', $filesubmission);
            $params['objectid'] = $filesubmission->id;

            $event = \assignsubmission_file\event\submission_updated::create($params);
            $event->trigger();
            return $updatestatus;
        } else {
            $filesubmission = new stdClass();
            $filesubmission->numfiles = $this->count_files($submission->id,
                                                           ASSIGNSUBMISSION_FILE_FILEAREA);
            $filesubmission->submission = $submission->id;
            $filesubmission->assignment = $this->assignment->get_instance()->id;
            $filesubmission->id = $DB->insert_record('assignsubmission_file', $filesubmission);
            $params['objectid'] = $filesubmission->id;

            $event = \assignsubmission_file\event\submission_created::create($params);
            $event->trigger();
            return $filesubmission->id > 0;
        }
    }

    /**
     * Produce a list of files suitable for export that represent this feedback or submission
     *
     * @param stdClass $submission The submission
     * @param stdClass $user The user record - unused
     * @return array - return an array of files indexed by filename
     */
    public function get_files(stdClass $submission, stdClass $user) {
        $result = array();
        $fs = get_file_storage();

        $files = $fs->get_area_files($this->assignment->get_context()->id,
                                     'assignsubmission_file',
                                     ASSIGNSUBMISSION_FILE_FILEAREA,
                                     $submission->id,
                                     'timemodified',
                                     false);

        foreach ($files as $file) {
            $result[$file->get_filename()] = $file;
        }
        return $result;
    }

    /**
     * are all uploaded files with acceptable file extensions?
     * @param stdClass $submissionorgrade assign_submission or assign_grade
     *                 For submission plugins this is the submission data
     * @return array of invalid file types
     */
    public function invalid_files(stdClass $submission) {

        if(!$this->get_config('restrictfiletypes')) {
            return array();
        }

        $worddocs = $this->get_config('worddocs');
        $pdfdocs = $this->get_config('pdfdocs');
        $imagedocs = $this->get_config('imagedocs');
        $videodocs = $this->get_config('videodocs');
        $audiodocs = $this->get_config('audiodocs');
        $otherdocs = $this->get_config('otherdocs');
        $otherdocstext = $this->get_config('otherdocstext');
        $worddocs_types = array();
        $pdfdocs_types = array();
        $imagedocs_types = array();
        $videodocs_types = array();
        $audiodocs_types = array();
        $otherdocs_types = array();
        $arraydiffer = array();
        if ($otherdocs) {
            if ($worddocs) {
                // Word (*.doc, *.docx, *.rtf).
                $worddocs_types = array('doc', 'docx', 'rtf');
            }
            if ($pdfdocs) {
                // PDF (*.pdf).
                $pdfdocs_types = array('pdf');
            }
            if ($imagedocs) {
                // Image (*.gif, *.jpg, *.jpeg, *.png), *.svg, *.tiff).
               $imagedocs_types = array('gif', 'jpg', 'jpeg', 'png', 'svg', 'tiff');
            }
            if ($videodocs) {
                // Video (*.mp4, *.flv, *.mov, *.avi).
                $videodocs_types = array('mp4', 'flv', 'mov', 'avi');
            }
            if ($audiodocs) {
                // Audio (*.mp3, *.ogg, *.wav, *.aac, *.wma).
                $audiodocs_types = array('mp3', 'ogg', 'wav', 'aac', 'wma');
            }
            $cleaneddocs_types = array();
            $nowhitespace = str_replace(' ','',$otherdocstext);
            $filetypes = explode('*.', $nowhitespace);
            foreach ($filetypes as $key => $filetype) {
                $cleaneddocs_types[$key] = str_replace(',','', $filetype);
            }
            array_shift($cleaneddocs_types); // Skipping 0 index as it is always empty value.
            $otherdocs_types = $cleaneddocs_types;
            $accepted_types = array_merge($worddocs_types, $pdfdocs_types, $imagedocs_types,
                                          $videodocs_types, $audiodocs_types, $otherdocs_types);

            $result = array();
            $fs = get_file_storage();

            $files = $fs->get_area_files($this->assignment->get_context()->id, 'assignsubmission_file', ASSIGNSUBMISSION_FILE_FILEAREA, $submission->id, "timemodified", false);
            foreach ($files as $file) {
                 if (!$file->is_directory()) {
                     $uploadedfileextensions = explode('.', $file->get_filename());
                     array_push($result, end($uploadedfileextensions));
                 }
            }

            $arraydiffer = array_diff($result, $accepted_types);
      }

      return $arraydiffer;

    }

    /**
     * Display the list of files  in the submission status table
     *
     * @param stdClass $submission
     * @param bool $showviewlink Set this to true if the list of files is long
     * @return string
     */
    public function view_summary(stdClass $submission, & $showviewlink) {
        $count = $this->count_files($submission->id, ASSIGNSUBMISSION_FILE_FILEAREA);

        // Show we show a link to view all files for this plugin?
        $showviewlink = $count > ASSIGNSUBMISSION_FILE_MAXSUMMARYFILES;
        if ($count <= ASSIGNSUBMISSION_FILE_MAXSUMMARYFILES) {
            return $this->assignment->render_area_files('assignsubmission_file',
                                                        ASSIGNSUBMISSION_FILE_FILEAREA,
                                                        $submission->id);
        } else {
            return get_string('countfiles', 'assignsubmission_file', $count);
        }
    }

    /**
     * No full submission view - the summary contains the list of files and that is the whole submission
     *
     * @param stdClass $submission
     * @return string
     */
    public function view(stdClass $submission) {
        return $this->assignment->render_area_files('assignsubmission_file',
                                                    ASSIGNSUBMISSION_FILE_FILEAREA,
                                                    $submission->id);
    }



    /**
     * Return true if this plugin can upgrade an old Moodle 2.2 assignment of this type
     * and version.
     *
     * @param string $type
     * @param int $version
     * @return bool True if upgrade is possible
     */
    public function can_upgrade($type, $version) {

        $uploadsingletype ='uploadsingle';
        $uploadtype ='upload';

        if (($type == $uploadsingletype || $type == $uploadtype) && $version >= 2011112900) {
            return true;
        }
        return false;
    }


    /**
     * Upgrade the settings from the old assignment
     * to the new plugin based one
     *
     * @param context $oldcontext - the old assignment context
     * @param stdClass $oldassignment - the old assignment data record
     * @param string $log record log events here
     * @return bool Was it a success? (false will trigger rollback)
     */
    public function upgrade_settings(context $oldcontext, stdClass $oldassignment, & $log) {
        global $DB;

        if ($oldassignment->assignmenttype == 'uploadsingle') {
            $this->set_config('maxfilesubmissions', 1);
            $this->set_config('maxsubmissionsizebytes', $oldassignment->maxbytes);
            return true;
        } else if ($oldassignment->assignmenttype == 'upload') {
            $this->set_config('maxfilesubmissions', $oldassignment->var1);
            $this->set_config('maxsubmissionsizebytes', $oldassignment->maxbytes);

            // Advanced file upload uses a different setting to do the same thing.
            $DB->set_field('assign',
                           'submissiondrafts',
                           $oldassignment->var4,
                           array('id'=>$this->assignment->get_instance()->id));

            // Convert advanced file upload "hide description before due date" setting.
            $alwaysshow = 0;
            if (!$oldassignment->var3) {
                $alwaysshow = 1;
            }
            $DB->set_field('assign',
                           'alwaysshowdescription',
                           $alwaysshow,
                           array('id'=>$this->assignment->get_instance()->id));
            return true;
        }
    }

    /**
     * Upgrade the submission from the old assignment to the new one
     *
     * @param context $oldcontext The context of the old assignment
     * @param stdClass $oldassignment The data record for the old oldassignment
     * @param stdClass $oldsubmission The data record for the old submission
     * @param stdClass $submission The data record for the new submission
     * @param string $log Record upgrade messages in the log
     * @return bool true or false - false will trigger a rollback
     */
    public function upgrade(context $oldcontext,
                            stdClass $oldassignment,
                            stdClass $oldsubmission,
                            stdClass $submission,
                            & $log) {
        global $DB;

        $filesubmission = new stdClass();

        $filesubmission->numfiles = $oldsubmission->numfiles;
        $filesubmission->submission = $submission->id;
        $filesubmission->assignment = $this->assignment->get_instance()->id;

        if (!$DB->insert_record('assignsubmission_file', $filesubmission) > 0) {
            $log .= get_string('couldnotconvertsubmission', 'mod_assign', $submission->userid);
            return false;
        }

        // Now copy the area files.
        $this->assignment->copy_area_files_for_upgrade($oldcontext->id,
                                                        'mod_assignment',
                                                        'submission',
                                                        $oldsubmission->id,
                                                        $this->assignment->get_context()->id,
                                                        'assignsubmission_file',
                                                        ASSIGNSUBMISSION_FILE_FILEAREA,
                                                        $submission->id);

        return true;
    }

    /**
     * The assignment has been deleted - cleanup
     *
     * @return bool
     */
    public function delete_instance() {
        global $DB;
        // Will throw exception on failure.
        $DB->delete_records('assignsubmission_file',
                            array('assignment'=>$this->assignment->get_instance()->id));

        return true;
    }

    /**
     * Formatting for log info
     *
     * @param stdClass $submission The submission
     * @return string
     */
    public function format_for_log(stdClass $submission) {
        // Format the info for each submission plugin (will be added to log).
        $filecount = $this->count_files($submission->id, ASSIGNSUBMISSION_FILE_FILEAREA);

        return get_string('numfilesforlog', 'assignsubmission_file', $filecount);
    }

    /**
     * Return true if there are no submission files
     * @param stdClass $submission
     */
    public function is_empty(stdClass $submission) {
        return $this->count_files($submission->id, ASSIGNSUBMISSION_FILE_FILEAREA) == 0;
    }

    /**
     * Get file areas returns a list of areas this plugin stores files
     * @return array - An array of fileareas (keys) and descriptions (values)
     */
    public function get_file_areas() {
        return array(ASSIGNSUBMISSION_FILE_FILEAREA=>$this->get_name());
    }

    /**
     * Copy the student's submission from a previous submission. Used when a student opts to base their resubmission
     * on the last submission.
     * @param stdClass $sourcesubmission
     * @param stdClass $destsubmission
     */
    public function copy_submission(stdClass $sourcesubmission, stdClass $destsubmission) {
        global $DB;

        // Copy the files across.
        $contextid = $this->assignment->get_context()->id;
        $fs = get_file_storage();
        $files = $fs->get_area_files($contextid,
                                     'assignsubmission_file',
                                     ASSIGNSUBMISSION_FILE_FILEAREA,
                                     $sourcesubmission->id,
                                     'id',
                                     false);
        foreach ($files as $file) {
            $fieldupdates = array('itemid' => $destsubmission->id);
            $fs->create_file_from_storedfile($fieldupdates, $file);
        }

        // Copy the assignsubmission_file record.
        if ($filesubmission = $this->get_file_submission($sourcesubmission->id)) {
            unset($filesubmission->id);
            $filesubmission->submission = $destsubmission->id;
            $DB->insert_record('assignsubmission_file', $filesubmission);
        }
        return true;
    }

    /**
     * Return a description of external params suitable for uploading a file submission from a webservice.
     *
     * @return external_description|null
     */
    public function get_external_parameters() {
        return array(
            'files_filemanager' => new external_value(
                PARAM_INT,
                'The id of a draft area containing files for this submission.'
            )
        );
    }
}
