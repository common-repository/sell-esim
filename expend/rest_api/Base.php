<?php

namespace tsim\expend\rest_api;

class Base
{

    protected $user_id;

    public function __construct()
    {

        $user = wp_get_current_user();
        $this->user_id = $user->ID;

        // 设置rest_api的方式

    }


    public function add_cors_support()
    {
        $enable_cors = defined('JWT_AUTH_CORS_ENABLE') && JWT_AUTH_CORS_ENABLE;
        if ($enable_cors) {
            $headers = apply_filters('jwt_auth_cors_allow_headers',
                'Access-Control-Allow-Headers,Content-Type,Authorization,Tsim-Account,Tsim-Nonce,Tsim-Timestamp,Tsim-Sign');
            header(sprintf('Access-Control-Allow-Headers: %s', $headers));
        }
    }

    protected static function verifySign(\WP_REST_Request $request)
    {
        $headers = $request->get_headers();
        // 检查头部是否包含所需的签名信息
        if (!isset($headers['tsim_account'], $headers['tsim_nonce'], $headers['tsim_timestamp'], $headers['tsim_sign'])) {
            return false;
        }
        $tsim_account = $request->get_header('tsim_account');
        $tsim_nonce = $request->get_header('tsim_nonce');
        $tsim_timestamp = $request->get_header('tsim_timestamp');
        $tsim_sign = $request->get_header('tsim_sign');
        $settings = get_option('sellesim_settings');
        $account = $settings['account'] ?? '';
        $secret = $settings['secret'] ?? '';
        $token = self::get_jwt_token();
        // 构造签名内容
        $signContent = $account . $tsim_nonce . $tsim_timestamp;
        if (!empty($token)) {
            $signContent .= $token;
        }
        // 计算签名
        $expectedSignature = hash_hmac('sha256', $signContent, $secret);
        // 比较计算出的签名与传入的签名是否一致
        $check = hash_equals($expectedSignature, $tsim_sign);
        if (!$check) {
            // 验证失败时抛出 404 异常
            return new \WP_REST_Response('Invalid signature', 404);
        }
        return true;
    }

    protected static function verifyAuth(\WP_REST_Request $request, $check_login = true, $arg = [])
    {
        if ($check_login) {
            if (!is_user_logged_in()) {
                return false;
            }
            $current_user = wp_get_current_user();
            if ($current_user === null || $current_user->ID === 0) {
                return false;
            }
        }
        $headers = $request->get_headers();
        // 检查头部是否包含所需的签名信息
        if (!isset($headers['tsim_account'], $headers['tsim_nonce'], $headers['tsim_timestamp'], $headers['tsim_sign'])) {
            return false;
        }
        $tsim_account = $request->get_header('tsim_account');
        $tsim_nonce = $request->get_header('tsim_nonce');
        $tsim_timestamp = $request->get_header('tsim_timestamp');
        $tsim_sign = $request->get_header('tsim_sign');
        $settings = get_option('sellesim_settings');
        $account = $settings['account'] ?? '';
        $secret = $settings['secret'] ?? '';
        $secret = hash('sha512', $secret);
        $account = hash('sha512', $account);

        $token = self::get_jwt_token();
        if ($account != $tsim_account) {
            return false;
        }
        // 提取签名信息
        $signContent = $account . $tsim_nonce . $tsim_timestamp;
//        if (!empty($token)) {
//            $signContent .= $token;
//        }
//        if (!empty($arg)) {
//            $signContent .= implode($arg);
//        }

        // 计算签名
        $expectedSignature = hash_hmac('sha256', $signContent, $secret);

        // 比较计算出的签名与传入的签名是否一致
        $check = hash_equals($expectedSignature, $tsim_sign);
        if (!$check) {
            // 验证失败时抛出 404 异常
            return false;
        }
        return true;
    }

    protected static function get_jwt_token()
    {
        // 获取所有的请求头信息
        $headers = getallheaders();
        // 检查 Authorization 头部是否存在
        if (isset($headers['Authorization'])) {
            $authorization_header = $headers['Authorization'];
            if (strpos($authorization_header, 'Bearer') !== false) {
                // 分割字符串，获取 token 部分
                $token_parts = explode(' ', $authorization_header);
                if (count($token_parts) == 2) {
                    return $token_parts[1];
                }
            }
        }
        return '';
    }

    protected function result($data = [], $msg = 'success', $status = 1, $code = 200)
    {

        $rs = [
            'data' => $data,
            'msg' => $msg,
            'status' => $status,
        ];
        $this->add_cors_support();
        return new \WP_REST_Response($rs, $code);
    }

    protected function resultError($error, $code = 404)
    {

        $rs = [
            'error' => $error,
        ];
        $this->add_cors_support();
        return new \WP_REST_Response($rs, $code);
    }

    protected function checkProduct($product_id)
    {
        $proObj = wc_get_product($product_id);
        if (!$proObj) {
            return 'Product not found';
        }
        $sku = $proObj->get_sku();
        $dataplan_list = get_option('sellesim_dataplan_list');
        $flag = false;
        foreach ($dataplan_list as $val) {
            if ($sku == $val['channel_dataplan_id']) {
                $data_array['apn'] = $val['apn'];
                $data_array['day'] = $val['day'];
                $flag = true;
                break;
            }
        }
        if (!$flag) {
            return 'product error,not tsim product';
        }

        return true;
    }

    protected function checkOrderAllSellEsim($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }
        $dataplan_list = get_option('sellesim_dataplan_list');
        $sku_list = array_column($dataplan_list, 'channel_dataplan_id');
        $line_items = $order->get_items();
        $all_sell_esim = true;
        foreach ($line_items as $ikey => $item) {
            $product = $item->get_product();
            $sku = $product->get_sku();
            if (!$sku || !in_array($sku, $sku_list)) {
                $all_sell_esim = false;
            }
        }
        return $all_sell_esim;
    }

}