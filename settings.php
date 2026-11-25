<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_autoteacher',
        get_string('pluginname', 'local_autoteacher')
    );
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configtext(
        'local_autoteacher/apiendpoint',
        get_string('apiendpoint', 'local_autoteacher'),
        get_string('apiendpoint_desc', 'local_autoteacher'),
        'https://api.openai.com/v1/chat/completions',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'local_autoteacher/apikey',
        get_string('apikey', 'local_autoteacher'),
        get_string('apikey_desc', 'local_autoteacher'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'local_autoteacher/model',
        get_string('model', 'local_autoteacher'),
        get_string('model_desc', 'local_autoteacher'),
        'gpt-4o-mini',
        PARAM_TEXT
    ));
}
