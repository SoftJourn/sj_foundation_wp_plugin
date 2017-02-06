<?php

/*
* Plugin Name: SJ Foundation
* Description: SJ Foundation wp plugin
* Version: 0.0.1
* Author: SoftJourn
* Author URI: https://softjourn.com
*/
require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/config.php');
require_once(ABSPATH . 'wp-content/plugins/rest-api/plugin.php');

use SJFoundation\SJFoundationProjectType;

$sjProjectPlugin = new SJFoundationProjectType();
$sjProjectPlugin->init();
