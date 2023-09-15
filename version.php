<?php
// This file is part of the modulecompletion plugin
/**
 * @package     local_modulecompletion
 * @author      Robert Tyrone Cullen
 * @var stdClass $plugin
 */
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_modulecompletion';
$plugin->version = 9;
$plugin->requires = 2016052314;
$plugin->dependencies = [
    'local_trainingplan' => 23
];
