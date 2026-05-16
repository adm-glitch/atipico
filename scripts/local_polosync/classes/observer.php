<?php
namespace local_polosync;

defined('MOODLE_INTERNAL') || die();

class observer {

    const POLO_IDNUMBERS = [
        'polo_maraba',
        'polo_santarem',
        'polo_belem',
        'polo_interno',
    ];

    public static function user_saved(\core\event\base $event): void {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');

        $userid = (int) $event->objectid;

        $fieldid = $DB->get_field('user_info_field', 'id', ['shortname' => 'polo']);
        if (!$fieldid) {
            return;
        }

        $rec  = $DB->get_record('user_info_data', ['userid' => $userid, 'fieldid' => $fieldid]);
        $polo = $rec ? trim($rec->data) : '';

        [$insql, $params] = $DB->get_in_or_equal(self::POLO_IDNUMBERS, SQL_PARAMS_NAMED, 'idn');
        $params['ctx'] = \context_system::instance()->id;
        $cohorts = $DB->get_records_select(
            'cohort',
            "idnumber $insql AND contextid = :ctx",
            $params,
            '',
            'id,name,idnumber'
        );

        $target = null;
        foreach ($cohorts as $c) {
            if (trim($c->name) === $polo) {
                $target = $c;
                break;
            }
        }

        foreach ($cohorts as $c) {
            $ismember = cohort_is_member($c->id, $userid);
            if ($target && $c->id === $target->id) {
                if (!$ismember) {
                    cohort_add_member($c->id, $userid);
                }
            } else {
                if ($ismember) {
                    cohort_remove_member($c->id, $userid);
                }
            }
        }
    }
}
