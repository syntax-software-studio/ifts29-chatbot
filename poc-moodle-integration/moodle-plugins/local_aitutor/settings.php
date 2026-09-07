<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_aitutor', get_string('pluginname', 'local_aitutor'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configtextarea(
        'local_aitutor/enabledcourses',
        get_string('enabledcourses', 'local_aitutor'),
        get_string('enabledcourses_desc', 'local_aitutor'),
        '',
        PARAM_RAW
    ));
}
