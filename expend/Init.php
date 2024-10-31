<?php

namespace tsim\expend;

use tsim\expend\action\SaveOrderInfo;
use tsim\expend\action\User;
use tsim\expend\rest_api\OpenApi;

class Init
{

    protected static $_instance = null;

    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct()
    {
        $this->defind();
        $this->include();
        $this->initRestApi();
        $this->action();
    }

    protected function defind()
    {
    }
    public function include()
    {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $path = [
            SELLESIM_PLUGIN_PATH . 'expend/install/Table.php',

            SELLESIM_PLUGIN_PATH . 'expend/helper/DbHelper.php',
            SELLESIM_PLUGIN_PATH . 'expend/helper/Request.php',
            SELLESIM_PLUGIN_PATH . 'expend/helper/DateHelper.php',

            SELLESIM_PLUGIN_PATH . 'expend/rest_api/Base.php',
            SELLESIM_PLUGIN_PATH . 'expend/rest_api/OpenApi.php',
            SELLESIM_PLUGIN_PATH . 'expend/rest_api/ServiceApiBase.php',
            SELLESIM_PLUGIN_PATH . 'expend/rest_api/ServiceApi.php',

            SELLESIM_PLUGIN_PATH . 'expend/rest_api/open/v1/Order.php',
            SELLESIM_PLUGIN_PATH . 'expend/rest_api/open/v1/Package.php',
            SELLESIM_PLUGIN_PATH . 'expend/rest_api/open/v1/Product.php',
            SELLESIM_PLUGIN_PATH . 'expend/rest_api/open/v1/User.php',
            SELLESIM_PLUGIN_PATH . 'expend/rest_api/open/v1/Notice.php',
            SELLESIM_PLUGIN_PATH . 'expend/rest_api/open/v1/Form.php',
            SELLESIM_PLUGIN_PATH . 'expend/rest_api/open/v1/Devices.php',

            SELLESIM_PLUGIN_PATH . 'expend/rest_api/account/Info.php',

            SELLESIM_PLUGIN_PATH . 'expend/rest_api/service/Notice.php',
            SELLESIM_PLUGIN_PATH . 'expend/action/SaveOrderInfo.php',
            SELLESIM_PLUGIN_PATH . 'expend/action/User.php',
        ];
        $this->batchInclude($path);
    }
    public function action(){
        add_action('tsim_after_subscribe_esim', array(SaveOrderInfo::class, 'saveOrder'),3,2);
        add_action('tsim_after_query_data_plan_detail', array(SaveOrderInfo::class, 'saveDetail'),3,2);
        add_action('tsim_after_query_device_detail', array(SaveOrderInfo::class, 'saveDataPlanInfo'),3,2);
        add_action('woocommerce_pre_payment_complete', array(User::class, 'bindUser'));
//        add_action('wp_ajax_my_custom_action', 'handle_my_custom_action'); // 用于已登录用户

    }
    public function batchInclude($path_list)
    {
        foreach ($path_list as $path) {
            include_once $path;
            unset($path);
        }
    }
    public function initRestApi()
    {
        // 路由
        add_action('rest_api_init', array(OpenApi::class, 'api_endpoint'));
        add_action('rest_api_init', array(OpenApi::class, 'api_other'));
    }

}