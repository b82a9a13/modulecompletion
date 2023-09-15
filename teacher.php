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
$type = '';
$enrolments = [];
$id = null;
$errorTxt = '';
if(isset($_GET['id'])){
    $id = $_GET['id'];
    if(!preg_match("/^[0-9]*$/", $id) || empty($id)){
        $errorTxt = get_string('invalid_ci', $p);
    } else {
        if($lib->check_coach_course($id)){
            $context = context_course::instance($id);
            require_capability('local/modulecompletion:teacher', $context);
            $PAGE->set_context($context);
            $PAGE->set_course($lib->get_course_record($id));
            $type = 'one';
        } else {
            $errorTxt = get_string('you_not_coach', $p);
        }
    }
} else {
    $enrolments = $lib->check_coach();
    if(count($enrolments) > 0){
        $context = context_course::instance($enrolments[0][0]);
        require_capability('local/modulecompletion:teacher', $context);
        $PAGE->set_context($context);
        $type = 'all';
    } else {
        $errorTxt = get_string('no_ca', $p);
    }
}

$title = get_string('module_comp', $p);
$PAGE->set_url(new moodle_url('/local/modulecompletion/teacher.php'));
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('incourse');

echo $OUTPUT->header();

if($errorTxt != ''){
    echo("<h1 class='text-error'>$errorTxt</h1>");
} else {
    $titletemp = (Object)['title', get_string('module_comp', $p)];
    if($type == 'all'){
        $_SESSION['mc_menu_type'] = 'all';
        $template = (Object)[
            'enrolments' => array_values($enrolments)
        ];
        $template = (object)array_merge((array)$template, (array)$titletemp);
        echo $OUTPUT->render_from_template('local_modulecompletion/teacher_all_courses', $template);
        echo("<script src='./amd/min/teacher_course.min.js' defer></script>");
        \local_modulecompletion\event\viewed_menu::create(array('context' => \context_system::instance()))->trigger();
    } elseif($type == 'one'){
        $_SESSION['mc_menu_type'] = 'one';
        $template = (Object)[
            'coursename' => $lib->get_course_fullname($id)
        ];
        $template = (object)array_merge((array)$template, (array)$titletemp);
        echo $OUTPUT->render_from_template('local_modulecompletion/teacher_one_course', $template);
        echo("<script src='./amd/min/teacher_course.min.js'></script>");
        echo("<script defer>course_clicked($id)</script>");
        \local_modulecompletion\event\viewed_menu::create(array('context' => \context_course::instance($id)))->trigger();
    }
}

echo $OUTPUT->footer();