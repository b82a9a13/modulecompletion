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

$title = 'Module Completion';
$type = '';
$enrolments = [];
$id = null;
$errorTxt = '';
if($_GET['id']){
    $id = $_GET['id'];
    if(!preg_match("/^[0-9]*$/", $id) || empty($id)){
        $errorTxt = 'Invalid course id.';
    } else {
        if($lib->check_coach_course($id)){
            $context = context_course::instance($id);
            require_capability('local/modulecompletion:teacher', $context);
            $PAGE->set_context($context);
            $PAGE->set_course($lib->get_course_record($id));
            $type = 'one';
        } else {
            $errorText = "You are not enrolled as a coach in the course provided.";
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
        $errorTxt = 'No courses available.';
    }
}

$PAGE->set_url(new moodle_url('/local/modulecompletion/teacher.php'));
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('incourse');

echo $OUTPUT->header();

if($errorTxt != ''){
    echo("<h1 class='text-error'>$errorTxt</h1>");
} else {
    if($type == 'all'){
        $_SESSION['mc_menu_type'] = 'all';
        $template = (Object)[
            'enrolments' => array_values($enrolments)
        ];
        echo $OUTPUT->render_from_template('local_modulecompletion/teacher_all_courses', $template);
        echo("<script src='./classes/js/teacher_course.js' defer></script>");
        \local_modulecompletion\event\viewed_menu::create(array('context' => \context_system::instance()))->trigger();
    } elseif($type == 'one'){
        $_SESSION['mc_menu_type'] = 'one';
        $template = (Object)[
            'coursename' => $lib->get_course_fullname($id)
        ];
        echo $OUTPUT->render_from_template('local_modulecompletion/teacher_one_course', $template);
        echo("<script src='./classes/js/teacher_course.js'></script>");
        echo("<script defer>course_clicked($id)</script>");
        \local_modulecompletion\event\viewed_menu::create(array('context' => \context_course::instance($id)))->trigger();
    }
}

echo $OUTPUT->footer();