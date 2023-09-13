<?php
/**
 * @package     local_modulecompletion
 * @author      Robert Tyrone Cullen
 * @var stdClass $plugin
 */
namespace local_modulecompletion;
use stdClass;

class lib{
        
    //Get the category id for apprenticeships
    public function get_category_id(){
        global $DB;
        return $DB->get_record_sql('SELECT id FROM {course_categories} WHERE name = ?',['Apprenticeships'])->id;
    }

    //Get current userid
    public function get_userid(){
        global $USER;
        return $USER->id;
    }

    //Get full course name from course id
    public function get_course_fullname($id){
        global $DB;
        return $DB->get_record_sql('SELECT fullname FROM {course} WHERE id = ?',[$id])->fullname;
    }

    public function get_current_user_fullname(){
        global $DB;
        $record = $DB->get_record_sql('SELECT firstname, lastname FROM {user} WHERE id = ?',[$this->get_userid()]);
        return $record->firstname.' '.$record->lastname;
    }

    //Get user full name from a specific id
    public function get_user_fullname($id){
        global $DB;
        $record = $DB->get_record_sql('SELECT firstname, lastname FROM {user} WHERE id = ?',[$id]);
        return $record->firstname.' '.$record->lastname;
    }

    //Check if the current user is enrolled as a coach in a apprenticeship course
    public function check_coach(){
        global $DB;
        $records = $DB->get_records_sql('SELECT DISTINCT {enrol}.courseid as courseid, {course}.fullname as fullname FROM {user_enrolments}
            INNER JOIN {enrol} ON {enrol}.id = {user_enrolments}.enrolid
            INNER JOIN {context} ON {context}.instanceid = {enrol}.courseid
            INNER JOIN {role_assignments} ON {role_assignments}.contextid = {context}.id
            INNER JOIN {course} ON {course}.id = {enrol}.courseid
            WHERE {role_assignments}.roleid IN (3,4) AND {user_enrolments}.userid = ? AND {course}.category = ? AND {user_enrolments}.status = 0 AND {role_assignments}.userid = {user_enrolments}.userid',
        [$this->get_userid(), $this->get_category_id()]);
        $array = [];
        foreach($records as $record){
            array_push($array, [$record->courseid, $record->fullname]);
        }
        return $array;
    }
    
    //Check if the current user is enrolled in the course provided as a coach
    public function check_coach_course($id){
        global $DB;
        $record = $DB->get_record_sql('SELECT DISTINCT {enrol}.courseid as courseid FROM {user_enrolments}
            INNER JOIN {enrol} ON {enrol}.id = {user_enrolments}.enrolid
            INNER JOIN {context} ON {context}.instanceid = {enrol}.courseid
            INNER JOIN {role_assignments} ON {role_assignments}.contextid = {context}.id
            INNER JOIN {course} ON {course}.id = {enrol}.courseid
            WHERE {role_assignments}.roleid IN (3,4) AND {user_enrolments}.userid = ? AND {course}.category = ? AND {user_enrolments}.status = 0 AND {role_assignments}.userid = {user_enrolments}.userid AND {course}.id = ?',
        [$this->get_userid(), $this->get_category_id(), $id]);
        if($record->courseid != null){
            return true;
        } else {
            return false;
        }
    }
    
    //Get the record for a specific course
    public function get_course_record($id){
        global $DB;
        return $DB->get_record_sql('SELECT * FROM {course} WHERE id = ?',[$id]);
    }

    //Get the module completion progress and expected
    public function get_completion_stats($uid, $cid, $totalmonths, $startdate){
        global $DB;
        $record = $DB->get_record_sql('SELECT count(*) as total FROM {course_modules}
            INNER JOIN {course_modules_completion} ON {course_modules_completion}.coursemoduleid = {course_modules}.id
            WHERE {course_modules}.course = ? AND {course_modules}.completion != 0 AND {course_modules_completion}.userid = ? and {course_modules_completion}.completionstate = 1',
        [$cid, $uid])->total;
        $totalModules = $DB->get_record_sql('SELECT count(*) as total FROM {course_modules} WHERE course = ? and completion != 0',[$cid])->total;
        $percent = round(($record / $totalModules) * 100);
        $expected = round(((
                (($totalModules / $totalmonths) / 4) * 
                (round((date('U') - $startdate) / 604800))
                ) / $totalModules) * 100
        );
        $expected = ($expected < 0) ? 0 : $expected;
        $percent = ($percent < 0) ? 0 : $percent;
        return [$percent, $expected];
    }
    
    //Get learners for a specific course
    public function get_enrolled_learners($id){
        global $DB;
        if($this->check_coach_course($id)){
            $records = $DB->get_records_sql('SELECT {user}.firstname as firstname, {user}.lastname as lastname, {user}.id as id FROM {user_enrolments} 
                INNER JOIN {enrol} ON {enrol}.id = {user_enrolments}.enrolid
                INNER JOIN {context} ON {context}.instanceid = {enrol}.courseid
                INNER JOIN {role_assignments} ON {role_assignments}.contextid = {context}.id
                INNER JOIN {course} ON {course}.id = {enrol}.courseid 
                INNER JOIN {user} ON {user}.id = {user_enrolments}.userid
                WHERE {enrol}.courseid = ? AND {user_enrolments}.status = 0 AND {role_assignments}.roleid = 5 AND {course}.category = ? AND {user_enrolments}.userid = {role_assignments}.userid',
            [$id, $this->get_category_id()]);
            if(count($records) > 0){
                $array = [];
                foreach($records as $record){
                    //Need to add data for if a setup is created or not
                    $tmpRecord = $DB->get_record_sql('SELECT coachsign, totalmonths, startdate FROM {trainingplan_setup} WHERE userid = ? and courseid = ?',[$record->id, $id]);
                    if($tmpRecord != null){
                        if($tmpRecord->coachsign != '' && $tmpRecord->coachsign != null){
                            array_push($array, [$record->firstname.' '.$record->lastname, $id, $record->id, true, true, $this->get_completion_stats($record->id, $id, $tmpRecord->totalmonths, $tmpRecord->startdate)]);
                        } else {
                            array_push($array, [$record->firstname.' '.$record->lastname, $id, $record->id, true, false]);
                        }
                    } else {
                        array_push($array, [$record->firstname.' '.$record->lastname, $id, $record->id, false, false]);
                    }
                }
                asort($array);
                return $array;
            } else {
                return ['invalid'];
            }
        } else {
            return 'invalid';
        }
    }

    //Check if a userid is enrolled in a course as a learner
    public function check_learner_enrolment($cid, $uid){
        global $DB;
        $record = $DB->get_record_sql('SELECT {user}.firstname as firstname, {user}.lastname as lastname FROM {user_enrolments} 
            INNER JOIN {enrol} ON {enrol}.id = {user_enrolments}.enrolid
            INNER JOIN {context} ON {context}.instanceid = {enrol}.courseid
            INNER JOIN {role_assignments} ON {role_assignments}.contextid = {context}.id
            INNER JOIN {course} ON {course}.id = {enrol}.courseid 
            INNER JOIN {user} ON {user}.id = {user_enrolments}.userid
            WHERE {enrol}.courseid = ? AND {user_enrolments}.status = 0 AND {role_assignments}.roleid = 5 AND {course}.category = ? AND {user_enrolments}.userid = {role_assignments}.userid AND {user_enrolments}.userid = ?',
        [$cid, $this->get_category_id(), $uid]);
        if($record->firstname != null){
            return $record->firstname.' '.$record->lastname;
        } else {
            return false;
        }
    }

    //Get percentages for module completion page
    public function get_percentages(){
        global $DB;
        if(!isset($_SESSION['mc_records_uid']) || !isset($_SESSION['mc_records_cid'])){
            return false;
        }
        $uid = $_SESSION['mc_records_uid'];
        $cid = $_SESSION['mc_records_cid'];
        if(!$DB->record_exists('trainingplan_setup', [$DB->sql_compare_text('userid') => $uid, $DB->sql_compare_text('courseid') => $cid])){
            return false;
        } else {
            $tmpRecord = $DB->get_record_sql('SELECT coachsign, totalmonths, startdate FROM {trainingplan_setup} WHERE userid = ? and courseid = ?',[$uid, $cid]);
            return $this->get_completion_stats($uid, $cid, $tmpRecord->totalmonths, $tmpRecord->startdate);
        }
    }

    //Get individual module completion states for a specific course id and user id
    public function mod_comp_table(){
        global $DB;
        if(!isset($_SESSION['mc_records_uid']) || !isset($_SESSION['mc_records_cid'])){
            return false;
        }
        $uid = $_SESSION['mc_records_uid'];
        $cid = $_SESSION['mc_records_cid'];
        $records = $DB->get_records_sql('SELECT {course_modules}.id as cmid, {course_modules}.module as module, {modules}.name as name FROM {course_modules}
            INNER JOIN {modules} ON {modules}.id = {course_modules}.module
            WHERE {course_modules}.course = ? AND {course_modules}.completion != 0',
        [$cid]);
        $array = [];
        foreach($records as $record){
            array_push($array, [$record->cmid, $record->name]);
        }
        $info = get_fast_modinfo($cid);
        $modnames = [];
        foreach($info->cms as $inf){
            if($inf->name !== 'Announcements'){
                foreach($array as $arr){
                    if($arr[0] == $inf->id){
                        array_push($modnames, [$inf->id, $inf->name, $arr[1]]);
                    }
                }
            }
        }
        $finalArray = [];
        $moduleComps = $DB->get_records_sql('SELECT coursemoduleid FROM {course_modules_completion} WHERE userid = ? AND completionstate = 1',[$uid]);
        foreach($modnames as $modname){
            $complete = false;
            foreach($moduleComps as $moduleComp){
                if($moduleComp->coursemoduleid == $modname[0]){
                    $complete = true;
                }
            }
            if($complete){
                array_push($finalArray, [$modname[1], $modname[2], 'Complete']);
            } else {
                array_push($finalArray, [$modname[1], $modname[2], 'Incomplete']);
            }
        }
        return $finalArray;
    }

    //Get percentages for module completion page : learner
    public function get_percentages_learn(){
        global $DB;
        if(!isset($_SESSION['mc_lrecords_cid'])){
            return false;
        }
        $uid = $this->get_userid();
        $cid = $_SESSION['mc_lrecords_cid'];
        if(!$DB->record_exists('trainingplan_setup', [$DB->sql_compare_text('userid') => $uid, $DB->sql_compare_text('courseid') => $cid])){
            return false;
        } else {
            $tmpRecord = $DB->get_record_sql('SELECT coachsign, totalmonths, startdate FROM {trainingplan_setup} WHERE userid = ? and courseid = ?',[$uid, $cid]);
            return $this->get_completion_stats($uid, $cid, $tmpRecord->totalmonths, $tmpRecord->startdate);
        }
    }

    //Get individual module completion states for a specific course id and user id
    public function mod_comp_table_learn(){
        global $DB;
        if(!isset($_SESSION['mc_lrecords_cid'])){
            return false;
        }
        $uid = $this->get_userid();
        $cid = $_SESSION['mc_lrecords_cid'];
        $records = $DB->get_records_sql('SELECT {course_modules}.id as cmid, {course_modules}.module as module, {modules}.name as name FROM {course_modules}
            INNER JOIN {modules} ON {modules}.id = {course_modules}.module
            WHERE {course_modules}.course = ? AND {course_modules}.completion != 0',
        [$cid]);
        $array = [];
        foreach($records as $record){
            array_push($array, [$record->cmid, $record->name]);
        }
        $info = get_fast_modinfo($cid);
        $modnames = [];
        foreach($info->cms as $inf){
            if($inf->name !== 'Announcements'){
                foreach($array as $arr){
                    if($arr[0] == $inf->id){
                        array_push($modnames, [$inf->id, $inf->name, $arr[1]]);
                    }
                }
            }
        }
        $finalArray = [];
        $moduleComps = $DB->get_records_sql('SELECT coursemoduleid FROM {course_modules_completion} WHERE userid = ? AND completionstate = 1',[$uid]);
        foreach($modnames as $modname){
            $complete = false;
            foreach($moduleComps as $moduleComp){
                if($moduleComp->coursemoduleid == $modname[0]){
                    $complete = true;
                }
            }
            if($complete){
                array_push($finalArray, [$modname[1], $modname[2], 'Complete']);
            } else {
                array_push($finalArray, [$modname[1], $modname[2], 'Incomplete']);
            }
        }
        return $finalArray;
    }
}