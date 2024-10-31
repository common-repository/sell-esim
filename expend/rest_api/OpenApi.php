<?php

namespace tsim\expend\rest_api;

use tsim\expend\helper\DateHelper;
use tsim\expend\helper\DbHelper;
use tsim\expend\rest_api\account\Info;
use tsim\expend\rest_api\open\v1\Devices;
use tsim\expend\rest_api\open\v1\Form;
use tsim\expend\rest_api\open\v1\Order;
use tsim\expend\rest_api\open\v1\Package;
use tsim\expend\rest_api\open\v1\Product;
use tsim\expend\rest_api\open\v1\User;
use tsim\expend\rest_api\service\Notice;

class OpenApi extends Base
{

    public function __construct()
    {
        // 设置rest_api的方式
        parent::__construct();
    }

    // 设置rest_api路由
    public static function api_endpoint()
    {

        register_rest_route('sellesim/v1/package', '/package_list/', array(
            'methods' => 'get',
            'callback' => array((new Package()), 'packageList'),
            'permission_callback' => function (\WP_REST_Request $request) {
                return self::verifyAuth($request);
            },
        ));
        register_rest_route('sellesim/v1', '/package/package_detail/', array(
            'methods' => 'get',
            'callback' => array((new Package()), 'packageDetail'),
            'permission_callback' => function (\WP_REST_Request $request) {
                return self::verifyAuth($request);
            },
        ));

        register_rest_route('sellesim/v1/product', '/category/', array(
            'methods' => 'get',
            'callback' => array((new Product()), 'category'),
            'permission_callback' => function (\WP_REST_Request $request) {
                return self::verifyAuth($request, false);
            },
        ));
        register_rest_route('sellesim/v1/product', '/category/', array(
            'methods' => 'get',
            'callback' => array((new Product()), 'category'),
            'permission_callback' => function (\WP_REST_Request $request) {
                return self::verifyAuth($request, false);
            },
        ));
        register_rest_route('sellesim/v1/product', '/product_list/', array(
            'methods' => 'get',
            'callback' => array((new Product()), 'productList'),
            'permission_callback' => function (\WP_REST_Request $request) {
                return self::verifyAuth($request, false);
            },
        ));
        register_rest_route('sellesim/v1/product', '/product_detail/', array(
            'methods' => 'get',
            'callback' => array((new Product()), 'productDetail'),
            'permission_callback' => function (\WP_REST_Request $request) {
                return self::verifyAuth($request, false);
            },
        ));
        register_rest_route('sellesim/v1/product', '/banner/', array(
            'methods' => 'get',
            'callback' => array((new Product()), 'banner'),
            'permission_callback' => function (\WP_REST_Request $request) {
                return self::verifyAuth($request, false);
            },
        ));

        register_rest_route('sellesim/v1/order', '/place_order/', array('methods' => 'post',
            'callback' => array((new Order()), 'placeOrder'),
            'permission_callback' => function (\WP_REST_Request $request) {
                return self::verifyAuth($request);
            },
        ));
        register_rest_route('sellesim/v1/order', '/order_list/', array('methods' => 'get',
            'callback' => array((new Order()), 'orderList'),
            'permission_callback' => function (\WP_REST_Request $request) {
                return self::verifyAuth($request);
            },
        ));
        register_rest_route('sellesim/v1/order', '/order_detail/', array('methods' => 'get',
            'callback' => array((new Order()), 'orderDetail'),
            'permission_callback' => function (\WP_REST_Request $request) {
                return self::verifyAuth($request);
            },
        ));
        register_rest_route('sellesim/v1/order', '/query_order_status/', array('methods' => 'get',
            'callback' => array((new Order()), 'queryOrder'),
            'permission_callback' => function (\WP_REST_Request $request) {
                return self::verifyAuth($request);
            },
        ));

        register_rest_route('sellesim/v1', '/test/', array(
            'methods' => 'get',
            'callback' => array((new self), 'test'),
            'permission_callback' => function (\WP_REST_Request $request) {
//                return true;
                return self::verifyAuth($request, false);
            },
        ));
        register_rest_route('sellesim/v1/user', '/send_register_email_verify/', array(
            'methods' => 'post',
            'callback' => array((new User()), 'sendRegisterEmailVerify'),
            'permission_callback' => function (\WP_REST_Request $request) {
                return self::verifyAuth($request, false);
            },
        ));
        register_rest_route('sellesim/v1/user', '/send_change_email_verify/', array(
            'methods' => 'post',
            'callback' => array((new User()), 'sendChangeEmailVerify'),
            'permission_callback' => function (\WP_REST_Request $request) {
                return self::verifyAuth($request);
            },
        ));
        register_rest_route('sellesim/v1/user', '/change_email/', array(
            'methods' => 'post',
            'callback' => array((new User()), 'changeEmail'),
            'permission_callback' => function (\WP_REST_Request $request) {
                return self::verifyAuth($request);
            },
        ));
        register_rest_route('sellesim/v1/user', '/register/', array(
            'methods' => 'post',
            'callback' => array((new User()), 'register'),
            'permission_callback' => function (\WP_REST_Request $request) {
                return self::verifyAuth($request, false);
            },
        ));
        register_rest_route('sellesim/v1/user', '/reset_password_link_send/', array(
            'methods' => 'post',
            'callback' => array((new User()), 'sendResetPasswordLink'),
            'permission_callback' => function (\WP_REST_Request $request) {
                return self::verifyAuth($request, false);
            },
        ));
        register_rest_route('sellesim/v1/user', '/change_user_info/', array(
            'methods' => 'post',
            'callback' => array((new User()), 'changeUserInfo'),
            'permission_callback' => function (\WP_REST_Request $request) {
                return self::verifyAuth($request);
            },
        ));
        register_rest_route('sellesim/v1/user', '/delete_account/', array(
            'methods' => 'post',
            'callback' => array((new User()), 'deleteAccount'),
            'permission_callback' => function (\WP_REST_Request $request) {
                return self::verifyAuth($request);
            },
        ));
        register_rest_route('sellesim/v1/user', '/get_user_info/', array(
            'methods' => 'post',
            'callback' => array((new User()), 'getUserInfo'),
            'permission_callback' => function (\WP_REST_Request $request) {
                return self::verifyAuth($request);
            },
        ));
        register_rest_route('sellesim/v1/notice', '/notice_list/', array(
            'methods' => 'get',
            'callback' => array((new \tsim\expend\rest_api\open\v1\Notice()), 'noticeList'),
            'permission_callback' => function (\WP_REST_Request $request) {
                return self::verifyAuth($request);
            },
        ));
        register_rest_route('sellesim/v1/notice', '/check_new_notice/', array(
            'methods' => 'get',
            'callback' => array((new \tsim\expend\rest_api\open\v1\Notice()), 'checkNewNotice'),
            'permission_callback' => function (\WP_REST_Request $request) {
                return self::verifyAuth($request);
            },
        ));

        register_rest_route('sellesim/v1/form', '/submit_feedback/', array(
            'methods' => 'post',
            'callback' => array((new Form()), 'submitFeedback'),
            'permission_callback' => function (\WP_REST_Request $request) {
                return self::verifyAuth($request);
            },
        ));
        register_rest_route('sellesim/v1/device', '/support_devices/', array(
            'methods' => 'get',
            'callback' => array((new Devices()), 'supportDevices'),
            'permission_callback' => function (\WP_REST_Request $request) {
                return self::verifyAuth($request, false);
            },
        ));
        add_filter('jwt_auth_expire', function ($expire) {
            return time() + 86400;
        });
    }

    public static function api_other()
    {
        register_rest_route('sellesim/notify', '/event_notify/', array(
            'methods' => 'post',
            'callback' => array((new Notice()), 'tsimNotify'),
            'permission_callback' => function (\WP_REST_Request $request) {
                return self::verifySign($request);
            },
        ));
        register_rest_route('sellesim/get_order_info', '/infos/', array(
            'methods' => 'get',
            'callback' => array((new Notice()), 'getOrderTarget'),
            'permission_callback' => function (\WP_REST_Request $request) {
                return self::verifyAuth($request,false);
            },
        ));
        register_rest_route('sellesim/get_table_install_status', '/infos/', array(
            'methods' => 'get',
            'callback' => array((new Notice()), 'getTableInstallStatus'),
            'permission_callback' => function (\WP_REST_Request $request) {
                return self::verifyAuth($request,false);
            },
        ));
        register_rest_route('sellesim/table_install_test', '/infos/', array(
            'methods' => 'get',
            'callback' => array((new Notice()), 'tableInstallTest'),
            'permission_callback' => function (\WP_REST_Request $request) {
                return self::verifyAuth($request,false);
            },
        ));
        register_rest_route('sellesim/account', '/query_data_plan/', array(
            'methods' => 'get',
            'callback' => array((new Info()), 'queryUsage'),
            'permission_callback' => function (\WP_REST_Request $request) {
                return is_user_logged_in();
            },
        ));
    }

    public function test()
    {
        $dataplan_list = get_option('sellesim_dataplan_list');
        return $this->result($dataplan_list);

    }


}