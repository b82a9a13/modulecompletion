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
$cid = $_GET['cid'];
$fullname = '';
if(isset($_GET{'cid'})){
    if(!preg_match("/^[0-9]*$/", $cid) || empty($cid)){
        $errorTxt = get_string('invalid_cip', $p);
    } else {
        $context = context_course::instance($cid);
        require_capability('local/modulecompletion:student', $context);
        $PAGE->set_context($context);
        $PAGE->set_course($lib->get_course_record($cid));
        $PAGE->set_url(new moodle_url("/local/modulecompletion/learner_modulecompletion.php?cid=$cid"));
        $PAGE->set_title('Module Completion');
        $PAGE->set_heading('Module Completion');
        $PAGE->set_pagelayout('incourse');
        $fullname = $lib->get_current_user_fullname();
        $_SESSION['mc_lrecords_cid'] = $cid;
    }
} else {
    $errorTxt = get_string('no_cip', $p);
}

echo $OUTPUT->header();
if($errorTxt != ''){
    echo("<h1 class='text-error'>$errorTxt</h1>");
} else {
    $percentages = $lib->get_percentages_learn();
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
        'page_url' => './../../my/index.php',
        'fullname' => $fullname,
        'coursename' => $lib->get_course_fullname($cid),
        'progress' => $percentages[0],
        'expected' => $expected,
        'incomplete' => 100 - $percentages[0],
        'expected_txt' => $percentages[1],
        'module_array' => $lib->mod_comp_table_learn()
    ];
    echo $OUTPUT->render_from_template('local_modulecompletion/module_completion', $template);
    \local_modulecompletion\event\viewed_modulecompletion_learn::create(array('context' => \context_course::instance($cid), 'courseid' => $cid))->trigger();
}
echo $OUTPUT->footer();