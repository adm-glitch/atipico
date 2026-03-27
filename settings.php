<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     theme_atipico
 * @category    admin
 * @copyright   2022 Hugo Ribeiro <ribeiro.hugo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings = new theme_boost_admin_settingspage_tabs('themesettingstream',
    get_string('configtitle', 'theme_atipico'), 'theme/atipico:changesettings');
    $page = new admin_settingpage ('theme_atipico_general', get_string('generalsettings', 'theme_atipico'));

    $page->add(new admin_setting_heading('theme_atipico/colours', get_string('colours', 'theme_atipico'), ''));
    // Variable $primary.
    $name = 'theme_atipico/primarycolour';
    $title = get_string('primarycolour', 'theme_atipico');
    $description = get_string('primarycolour_desc', 'theme_atipico');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#daaa00');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Variable $secondary.
    $name = 'theme_atipico/secondarycolour';
    $title = get_string('secondarycolour', 'theme_atipico');
    $description = get_string('secondarycolour_desc', 'theme_atipico');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '#298976');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $page->add(new admin_setting_heading('theme_atipico/fonts', get_string('fonts', 'theme_atipico'), ''));

    // External Fonts source.
    $name = 'theme_atipico/externalfonts';
    $title = get_string('externalfonts', 'theme_atipico');
    $description = get_string('externalfonts_desc', 'theme_atipico');
    $setting = new admin_setting_configcheckbox($name, $title, $description, '1');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Fonts settings.
    $name = 'theme_atipico/bunnyfonts';
    $title = get_string('bunnyfonts', 'theme_atipico');
    $description = get_string('bunnyfonts_desc', 'theme_atipico');
    $choices = [
        'IBM Plex Sans' => 'IBM Plex Sans ★',
        'Inter' => 'Inter',
        'Plus Jakarta Sans' => 'Plus Jakarta Sans',
        'DM Sans' => 'DM Sans',
        'Space Grotesk' => 'Space Grotesk',
        'Geist' => 'Geist',
        'Nunito' => 'Nunito',
        'Poppins' => 'Poppins',
        'Montserrat' => 'Montserrat',
        'Roboto' => 'Roboto',
        'roboto-condensed' => 'Roboto Condensed',
        'Lato' => 'Lato',
        'Oswald' => 'Oswald',
        'Abel' => 'Abel',
        'Mukta' => 'Mukta',
    ];
    $setting = new admin_setting_configselect($name, $title, $description, 'IBM Plex Sans', $choices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $page->add(new admin_setting_heading('theme_atipico/customstylessettings',
    get_string('customstylessettings', 'theme_atipico'), ''));

    // Favicon.
    $name = 'theme_atipico/favicon';
    $title = get_string('favicon', 'theme_atipico');
    $description = get_string('favicon_desc', 'theme_atipico');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'favicon', 0,
    ['maxfiles' => 1, 'accepted_types' => ['.ico', '.png' ]]);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Login Page Image.
    $name = 'theme_atipico/loginimg';
    $title = get_string('loginimg', 'theme_atipico');
    $description = get_string('loginimg_desc', 'theme_atipico');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'loginimg', 0,
    ['maxfiles' => 1, 'accepted_types' => 'web_image']);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Full-width setting.
    $name = 'theme_atipico/fullwidthpage';
    $title = get_string('fullwidthpage', 'theme_atipico');
    $description = get_string('fullwidthpage_desc', 'theme_atipico');
    $setting = new admin_setting_configcheckbox($name, $title, $description, '1');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Raw SCSS to include before the content. As per Boost.
    $setting = new admin_setting_scsscode('theme_atipico/scsspre',
    get_string('rawscsspre', 'theme_boost'), get_string('rawscsspre_desc', 'theme_boost'), '', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Raw SCSS to include after the content. As per Boost.
    $setting = new admin_setting_scsscode('theme_atipico/scss', get_string('rawscss', 'theme_boost'),
    get_string('rawscss_desc', 'theme_boost'), '', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Show back to top icon.
    $name = 'theme_atipico/backtotopbutton';
    $title = get_string('backtotopbutton', 'theme_atipico');
    $description = get_string('backtotopbutton_desc', 'theme_atipico');
    $setting = new admin_setting_configcheckbox($name, $title, $description, '1');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Hide primary navigation nodes.
    $choices = [
        'home' => get_string('home'),
        'myhome' => get_string('myhome'),
        'courses' => get_string('mycourses'),
    ];
      $name = 'theme_atipico/hideprimarynodes';
      $title = get_string('hideprimarynodes', 'theme_atipico');
      $description = get_string('hideprimarynodes_desc', 'theme_atipico');
      $setting = new admin_setting_configmulticheckbox($name, $title, $description, null, $choices);
      $setting->set_updatedcallback('theme_reset_all_caches');
      $page->add($setting);

    $settings->add($page);

    require_once('settings/frontpagesettings.php');
    require_once('settings/coursesettings.php');
    require_once('settings/footersettings.php');
}

