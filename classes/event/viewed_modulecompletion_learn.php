<?php
/**
 * @package     local_modulecompletion
 * @author      Robert Tyrone Cullen
 * @var stdClass $plugin
 */

namespace local_modulecompletion\event;
use core\event\base;
defined('MOODLE_INTERNAL') || die();

class viewed_modulecompletion_learn extends base {
    protected function init(){
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }
    public static function get_name(){
        return "Module completion page viewed";
    }
    public function get_description(){
        return "The user with id '".$this->userid."' viewed the module completion page for their course with id '".$this->courseid."'";
    }
    public function get_url(){
        return new \moodle_url('/local/modulecompletion/learner_modulecompletion.php?cid='.$this->courseid);
    }
    public function get_id(){
        return $this->objectid;
    }
}