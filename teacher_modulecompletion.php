<?php
/**
 * @package     local_modulecompletion
 * @author      Robert Tyrone Cullen
 * @var stdClass $plugin
 */

require_once(__DIR__.'/../../config.php');
use local_modulecompletion\lib;
require_login();
$lib = new lib;

$p = 'local_modulecompletion';
$errorTxt = '';
$e = $_GET['e'];
$uid = $_GET['uid'];
$cid = $_GET['cid'];
$fullname = '';
if($_GET['e']){
    if(($e != 'a' && $e != 'c') || empty($e)){
        $errorTxt = get_string('invalid_ecp', $p);
    } else {
        if($_GET['uid']){
            if(!preg_match("/^[0-9]*$/", $uid) || empty($uid)){
                $errorText = get_string('invalid_uip', $p);
            } else {
                if($_GET['cid']){
                    if(!preg_match("/^[0-9]*$/", $cid) || empty($cid)){
                        $errorText = get_string('invalid_cip', $p);
                    } else {
                        if($lib->check_coach_course($cid)){
                            $context = context_course::instance($cid);
                            require_capability('local/modulecompletion:teacher', $context);
                            $PAGE->set_context($context);
                            $PAGE->set_course($lib->get_course_record($cid));
                            $PAGE->set_url(new moodle_url("/local/modulecompletion/teacher_modulecompletion.php?cid=$cid&uid=$uid"));
                            $PAGE->set_title('Module Completion');
                            $PAGE->set_heading('Module Completion');
                            $PAGE->set_pagelayout('incourse');
                            $fullname = $lib->check_learner_enrolment($cid, $uid);
                            if($fullname == false){
                                $errorText = get_string('the_user_not_enrolled', $p);
                            } else {
                                $_SESSION['mc_records_uid'] = $uid;
                                $_SESSION['mc_records_cid'] = $cid;
                            }
                        } else  {
                            $errorText = get_string('you_not_coach', $p);
                        }
                    }
                } else {
                    $errorText = get_string('no_cip', $p);
                }
            }
        } else {
            $errorText = get_string('no_uip', $p);
        }
    }
}

echo $OUTPUT->header();
if($errorText != ''){
    echo("<h1 class='text-error'>$errorText</h1>");
} else {
    if($e == 'a'){
        $e = '';
    } elseif($e == 'c'){
        $e = '?id='.$cid;
    }
    $percentages = $lib->get_percentages();
    $expected = ($percentages[0] >= $percentages[1]) ? 0 : $percentages[1];
    $template = (Object)[
        'btm' => get_string('btm', $p),
        'title' => get_string('module_comp', $p),
        'progress_b' => get_string('progress_b', $p),
        'progress_str' => get_string('progress', $p),
        'expected_str' => get_string('expected', $p),
        'incomplete_str' => get_string('incomplete', $p),
        'mod_name' => get_string('module_name', $p),
        'mod_type' => get_string('module_type', $p),
        'comp_state' => get_string('comp_state', $p),
        'fullname' => $fullname,
        'coursename' => $lib->get_course_fullname($cid),
        'btm_ext' => $e,
        'progress' => $percentages[0],
        'expected' => $expected,
        'incomplete' => 100 - $percentages[0],
        'expected_txt' => $percentages[1],
        'module_array' => $lib->mod_comp_table(),
        'page_url' => './teacher.php'
    ];
    echo $OUTPUT->render_from_template('local_modulecompletion/module_completion', $template);
    \local_modulecompletion\event\viewed_modulecompletion::create(array('context' => \context_course::instance($cid), 'courseid' => $cid, 'relateduserid' => $uid, 'other' => $_GET['e']))->trigger();
}
echo $OUTPUT->footer();