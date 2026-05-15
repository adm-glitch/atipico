<?php
namespace local_polo_enrol;
defined('MOODLE_INTERNAL') || die();

class observer {

    const COHORT_MAP = [
        'Marabá'   => 'polo_maraba',
        'Santarém' => 'polo_santarem',
        'Belém'    => 'polo_belem',
    ];

    public static function on_user_event(\core\event\base $event) {
        global $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');
        self::sync_polo((int)$event->objectid);
    }

    public static function sync_polo(int $userid) {
        global $DB;

        if ($userid <= 0) return;

        $field = $DB->get_record('user_info_field', ['shortname' => 'polo']);
        if (!$field) return;

        $data = $DB->get_record('user_info_data', [
            'userid'  => $userid,
            'fieldid' => $field->id,
        ]);

        $polo_value = $data ? trim($data->data) : '';

        $target_idnumber = null;
        foreach (self::COHORT_MAP as $option => $idnumber) {
            if (strcasecmp($polo_value, $option) === 0) {
                $target_idnumber = $idnumber;
                break;
            }
        }

        foreach (self::COHORT_MAP as $option => $idnumber) {
            $cohort = $DB->get_record('cohort', ['idnumber' => $idnumber]);
            if (!$cohort) continue;

            $in_cohort = $DB->record_exists('cohort_members', [
                'cohortid' => $cohort->id,
                'userid'   => $userid,
            ]);

            if ($idnumber === $target_idnumber) {
                if (!$in_cohort) {
                    cohort_add_member($cohort->id, $userid);
                }
            } else {
                if ($in_cohort) {
                    cohort_remove_member($cohort->id, $userid);
                }
            }
        }
    }
}
