<?php

/*
* Plugin Name: SJ Foundation
* Description: SJ Foundation wp plugin
* Version: 0.0.1
* Author: SoftJourn
* Author URI: https://softjourn.com
*/
require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/src/Admin/ProjectMetaboxErrors.php');
require_once(__DIR__ . '/config.php');
require_once(ABSPATH . 'wp-content/plugins/rest-api/plugin.php');

function hide_update_notice_to_all_but_admin_users()
{
    if (!current_user_can('update_core')) {
        remove_action( 'admin_notices', 'update_nag', 3 );
    }
}
add_action( 'admin_head', 'hide_update_notice_to_all_but_admin_users', 1 );


//wp_register_script( 'sj_project_metabox_script', __DIR__ . '/js/sj_project_metabox.js', false, '1.0.0' );
wp_register_script( 'sj_project_metabox_script', plugins_url('/sj_foundation_plugin/js/sj_project_metabox.js'), false);

use SJFoundation\SJFoundationProjectType;

$sjProjectPlugin = new SJFoundationProjectType();
$sjProjectPlugin->init();
