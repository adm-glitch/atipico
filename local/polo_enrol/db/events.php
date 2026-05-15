<?php
defined('MOODLE_INTERNAL') || die();
$observers = [
    [
        'eventname' => '\core\event\\user_created',
        'callback'  => '\local_polo_enrol\observer::on_user_event',
        'priority'  => 200,
    ],
    [
        'eventname' => '\core\event\user_updated',
        'callback'  => '\local_polo_enrol\observer::on_user_event',
        'priority'  => 200,
    ],
];
