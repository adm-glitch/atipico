<?php
/**
 * Add ALL available Moodle webservice functions to the replit_agent service.
 * Run from Moodle root: php add_ws_functions.php
 */
define('CLI_SCRIPT', true);
require('/home/user/htdocs/srv1526987.hstgr.cloud/config.php');

$srv = $DB->get_record('external_services', ['shortname' => 'replit_agent'], MUST_EXIST);
echo "Service: {$srv->name} (id={$srv->id})\n\n";

// Get every function registered in Moodle
$all_functions = $DB->get_records('external_functions', [], 'name ASC', 'name');

$added   = 0;
$skipped = 0;

foreach ($all_functions as $fn) {
    if (!$DB->record_exists('external_services_functions', [
            'externalserviceid' => $srv->id,
            'functionname'      => $fn->name])) {
        $DB->insert_record('external_services_functions', (object)[
            'externalserviceid' => $srv->id,
            'functionname'      => $fn->name,
            'timemodified'      => 0,
        ]);
        echo "  + {$fn->name}\n";
        $added++;
    } else {
        $skipped++;
    }
}

echo "\nAdded: {$added}  |  Already existed: {$skipped}\n";
echo "Total functions in service: " . ($added + $skipped) . "\n";
