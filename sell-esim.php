<?php

/**
 * @package Sell eSIM
 * Plugin Name: Sell eSIM
 * Description: Empower your business by seamlessly selling eSIMs with our user-friendly WordPress plugin
 * Version: 1.0.31
 * Author: tsimaboy, tsimliam
 * Requires PHP: 7.2
 */

defined('ABSPATH') || exit;

define('SELLESIM_PLUGIN', plugin_basename(__FILE__));
define('SELLESIM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SELLESIM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SELLESIM_PATH', (__FILE__));

if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

if (file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
    require_once dirname(__FILE__) . '/vendor/autoload.php';
}
require_once SELLESIM_PLUGIN_PATH . 'expend/Init.php';
$init = tsim\expend\Init::instance();
//activation
register_activation_hook(__FILE__, array('TsimAboy\\SellEsim\\SELLESIM_Init', 'activate'));
//deactivation
register_deactivation_hook(__FILE__, array('TsimAboy\\SellEsim\\SELLESIM_Init', 'deactivate'));
//call all the services's register method
TsimAboy\SellEsim\SELLESIM_Init::register_servivces();


