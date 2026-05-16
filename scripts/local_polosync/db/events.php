<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\core\event\user_updated',
        'callback'  => '\local_polosync\observer::user_saved',
    ],
    [
        'eventname' => '\core\event\user_created',
        'callback'  => '\local_polosync\observer::user_saved',
    ],
];
