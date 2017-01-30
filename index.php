<?php
/*
* Plugin Name: SJ Foundation
* Description: SJ Foundation wp plugin
* Version: 0.0.1
* Author: SoftJourn
* Author URI: https://softjourn.com
*/

define( 'PROJECT_PLUGIN_FILE',  __FILE__ );
define( 'PROJECT_BASENAME', plugin_basename( PROJECT_PLUGIN_FILE ) );
define( 'PROJECT_PATH', plugin_dir_path( __FILE__ ) );
define( 'PROJECT_URL', plugin_dir_url( __FILE__ ) );
define( 'PROJECT_ADMIN', PROJECT_PATH . 'admin' . DIRECTORY_SEPARATOR );
define( 'PROJECT_ADMIN_TEMPLATE_PATH', PROJECT_ADMIN . 'templates' . DIRECTORY_SEPARATOR );
define( 'PROJECT_ASSETS_DIR', PROJECT_ADMIN . 'assets' . DIRECTORY_SEPARATOR );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
require_once(ABSPATH . 'wp-content/plugins/rest-api/plugin.php');

require PROJECT_PATH . 'WP_REST_Project_Controller.php';
require PROJECT_ADMIN . 'SJProjectsApi.php';
require PROJECT_ADMIN. 'ErisContractAPI.php';
require PROJECT_PATH . 'login/SJAuth.php';
require PROJECT_PATH . 'login/SJLogin.php';
require PROJECT_PATH . 'SJ_Foundation_Class.php';
require_once ( PROJECT_ADMIN . 'project_metabox.php' );

$sjLogin = new SJLogin();
$sjLogin->sj_authenticate();

$sjProjectPlugin = new SJ_Foundation_Class();
$sjProjectPlugin->init();