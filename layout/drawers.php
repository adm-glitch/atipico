<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * A drawer based layout for the stream theme.
 *
 * @package   theme_atipico
 * @copyright Based on 2021 Bas Brands
 * @copyright 2022 Hugo Ribeiro ribeiro.hugo@gmail.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/behat/lib.php');
require_once($CFG->dirroot . '/course/lib.php');

// Add block button in editing mode.
$addblockbutton = $OUTPUT->addblockbutton();

if (isloggedin()) {
    $courseindexopen = (get_user_preferences('drawer-open-index', true) == true);
    $blockdraweropen = (get_user_preferences('drawer-open-block') == true);
} else {
    $courseindexopen = false;
    $blockdraweropen = false;
}

if (defined('BEHAT_SITE_RUNNING') && get_user_preferences('behat_keep_drawer_closed') != 1) {
    $blockdraweropen = true;
}

$extraclasses = ['uses-drawers'];
if ($courseindexopen) {
    $extraclasses[] = 'drawer-open-index';
}

$blockshtml = $OUTPUT->blocks('side-pre');
$hasblocks = (strpos($blockshtml, 'data-block=') !== false || !empty($addblockbutton));
if (!$hasblocks) {
    $blockdraweropen = false;
}
$courseindex = core_course_drawer();
if (!$courseindex) {
    $courseindexopen = false;
}

$bodyattributes = $OUTPUT->body_attributes($extraclasses);
$forceblockdraweropen = $OUTPUT->firstview_fakeblocks();

$secondarynavigation = false;
$overflow = '';
if ($PAGE->has_secondary_navigation()) {
    $tablistnav = $PAGE->has_tablist_secondary_navigation();
    $moremenu = new \core\navigation\output\more_menu($PAGE->secondarynav, 'nav-tabs', true, $tablistnav);
    $secondarynavigation = $moremenu->export_for_template($OUTPUT);
    $overflowdata = $PAGE->secondarynav->get_overflow_menu_data();
    if (!is_null($overflowdata)) {
        $overflow = $overflowdata->export_for_template($OUTPUT);
    }
}

$primary = new core\navigation\output\primary($PAGE);
$renderer = $PAGE->get_renderer('core');
$primarymenu = $primary->export_for_template($renderer);
$buildregionmainsettings = !$PAGE->include_region_main_settings_in_header_actions() && !$PAGE->has_secondary_navigation();
// If the settings menu will be included in the header then don't add it here.
$regionmainsettingsmenu = $buildregionmainsettings ? $OUTPUT->region_main_settings_menu() : false;

$header = $PAGE->activityheader;
$headercontent = $header->export_for_template($renderer);

$theme = theme_config::load('atipico');
$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'output' => $OUTPUT,
    'sidepreblocks' => $blockshtml,
    'hasblocks' => $hasblocks,
    'bodyattributes' => $bodyattributes,
    'courseindexopen' => $courseindexopen,
    'blockdraweropen' => $blockdraweropen,
    'courseindex' => $courseindex,
    'primarymoremenu' => $primarymenu['moremenu'],
    'secondarymoremenu' => $secondarynavigation ?: false,
    'mobileprimarynav' => $primarymenu['mobileprimarynav'],
    'usermenu' => $primarymenu['user'],
    'langmenu' => $primarymenu['lang'],
    'forceblockdraweropen' => $forceblockdraweropen,
    'regionmainsettingsmenu' => $regionmainsettingsmenu,
    'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu),
    'overflow' => $overflow,
    'headercontent' => $headercontent,
    'addblockbutton' => $addblockbutton,
];

// Quick access icon menu — shown to logged-in, non-guest users only.
if (isloggedin() && !isguestuser()) {
    global $USER;
    $rui_isediting = $PAGE->user_is_editing();
    $rui_editurl   = clone $PAGE->url;
    $rui_editurl->param('edit', $rui_isediting ? 'off' : 'on');
    $rui_editurl->param('sesskey', sesskey());
    $templatecontext['rui_icon_menu'] = [
        'mycourses_url'    => (new moodle_url('/my/courses.php'))->out(false),
        'messages_url'     => (new moodle_url('/message/index.php'))->out(false),
        'notifications_url'=> (new moodle_url('/message/output/popup/notifications.php'))->out(false),
        'profile_url'      => (new moodle_url('/user/profile.php', ['id' => $USER->id]))->out(false),
        'settings_url'     => (new moodle_url('/user/preferences.php'))->out(false),
        'editmode_url'     => $rui_editurl->out(false),
        'isediting'        => (bool)$rui_isediting,
        'caneditpage'      => (bool)$PAGE->user_can_edit_blocks(),
        'editmode_tooltip' => $rui_isediting
            ? get_string('turneditingoff', 'core')
            : get_string('turneditingon', 'core'),
    ];
}

// Include the content for the footer.
require_once(__DIR__ . '/includes/footer.php');

// Include the content for scrollspy feature.
require_once(__DIR__ . '/includes/scrollspy.php');

// Loads backtotop button. hribeiro dec2022.
$backtotopbutton = $theme->settings->backtotopbutton;
if ($backtotopbutton) {
    $PAGE->requires->js_call_amd('theme_atipico/backtotop', 'init');
}

// Inject circular progress rings into block_myoverview course cards.
// Scoped to dashboard/my-courses pages to avoid unnecessary global overhead.
if (strpos($PAGE->pagetype, 'my-') === 0) {
    $PAGE->requires->js_call_amd('theme_atipico/coursecard_ring', 'init');
}

// Check for the option to print a course index heading.
$courseindexheading = $theme->settings->courseindexheading;
if ($courseindexheading != 0) {
    $templatecontext['courseindexheading'] = format_string($this->page->course->$courseindexheading);
} else {
    $templatecontext['courseindexheading'] = null;
}

echo $OUTPUT->render_from_template('theme_atipico/drawers', $templatecontext);
