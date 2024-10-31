<?php

namespace TsimAboy\SellEsim;

use Exception;
use tsim\expend\helper\DbHelper;

class ApiClient
{
    protected $host = null;
    protected $account = null;
    protected $secret = null;
    protected $auto_send_email = null;

    protected $data = [];
    protected $headers = [];

    public function __construct()
    {
        $settings = get_option('sellesim_settings');
        $this->host = $settings['host'] ?? '';
        $this->account = $settings['account'] ?? '';
        $this->secret = $settings['secret'] ?? '';
        $this->auto_send_email = $settings['auto_send_email'] ?? '';
    }

    public function register()
    {
        add_action("update_option_sellesim_settings", array($this, 'updatePlan'));
        add_action('woocommerce_payment_complete', array($this, 'orderHandle'));
    }
    public function orderHandle($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        $dataplan_list = get_option('sellesim_dataplan_list');
        $sku_list = array_column($dataplan_list, 'channel_dataplan_id');
        $line_items = $order->get_items();
        $email = $order->get_billing_email();
        $autoComplete = true;
        foreach ($line_items as $ikey => $item) {
            $product = $item->get_product();
            /**@var $product \WC_Product  **/
            $sku = $product->get_sku();
            if ($sku && in_array($sku, $sku_list)) {
                $res = $this->subscribe_esim($sku, $item['quantity'],  $order_id, $email,$product->get_name());
                do_action('tsim_after_subscribe_esim',$res,$item);
                if ($res['topup_id'] ?? false) {
                    $detail = $this->order_detail($res['topup_id']);
//                    error_log("topup_id:{$res['topup_id']},order_id:{$order_id}zdetail rs:".var_export($detail,true));
                    update_post_meta($order_id, "sellesim-topup_id_{$ikey}", $res['topup_id']);
                    if ($detail['qrcode'] ?? false) {
                        do_action('tsim_after_query_data_plan_detail',$detail);
                        foreach ($detail['qrcode'] as $k => $v) {
                            update_post_meta($order_id, "sellesim-qrcode_{$ikey}_{$k}", $v);
                        }
                    }
                }else{
                    $autoComplete = false;
                }
            } else {
                $autoComplete = false;
            }
        }
        if ($autoComplete) {
            $order->update_status('completed');
        }
    }



    private function insert_table($table, $data_to_insert)
    {
        global $wpdb;
        // 设置你的自定义表名
        $table_name = $wpdb->prefix . $table;

        // 将数据批量插入到数据库中
        $rs = $wpdb->insert($table_name, $data_to_insert);
        // 检查插入是否成功
        if ($wpdb->last_error) {
            return false; // 插入失败
        } else {
            return $rs; // 插入成功
        }
    }

    public function updatePlan()
    {
        $client = new self();
        $dataplan_list = $client->esim_dataplan_list();
        update_option('sellesim_dataplan_list', $dataplan_list);
    }

    private function setHeaders()
    {
//        $this->headers['Content-Type'] = 'application/json;charset=utf-8';
        $this->headers['TSIM-ACCOUNT'] = $this->account;
        $this->headers['TSIM-NONCE'] = wp_rand(100000, 99999999);
        $this->headers['TSIM-TIMESTAMP'] = time();
        $signContent = $this->account . $this->headers['TSIM-NONCE'] . $this->headers['TSIM-TIMESTAMP'];
        $sign = hash_hmac('sha256', $signContent, $this->secret);
        $this->headers['TSIM-SIGN'] = $sign;
    }
    public function subscribe_esim(string $dataplan_id, int $number, string $order_id = '', string $email = '',$product_name = '')
    {
        $this->setHeaders();
        $this->data['channel_dataplan_id'] = $dataplan_id;
        $this->data['number'] = $number;
        $this->data['remark'] = '#' .$order_id;
        $this->data['custom_order_no'] = $order_id;
        $this->data['custom_product_name'] = $product_name;
        // auto send email
        if ($this->auto_send_email == 1) {
            $this->data['email'] = $email;
        }
        $args = [
            'headers' => $this->headers,
            'body' => $this->data,
            'timeout' => 60,
        ];

        $response = wp_remote_post($this->host . '/tsim/v1/esimSubscribe', $args);
        $http_body = wp_remote_retrieve_body($response);
        $http_body = json_decode($http_body, true);
        return $this->_formatResult($http_body);
    }

    // get order detail，Return encapsulated data
//    private function getOrderDetail($topup_id, $info)
//    {
//
//        $cardInfo = [
//            'info' => [
//                'product_name' => $info['wp_product_name'] ?? '',
//                'product_id' => $info['wp_product_id'] ?? '',
//                'quantity' => $info['wp_quantity'] ?? 1,
//                'sku' => $info['wp_sku'] ?? '',
//                'topup_id' => $topup_id,
//            ],
//            'list' => [] // card number and qrcode list
//        ];
//
//        $cardInfo['topup_id'] = $topup_id;
//
//        if (empty($topup_id)) {
//            return $cardInfo;
//        }
//        $detail = $this->order_detail($topup_id);
//        if (($detail)) {
//            $cardInfo['info']['create_time'] = $detail['create_time'] ?? time();
//            $cardInfo['info']['quantity'] = $detail['number'] ?? $cardInfo['info']['quantity'];
//            $cardInfo['info']['day'] = $detail['day'] ?? '';
//            $cardInfo['info']['end_time'] = $detail['expire_time'] ?? '';
//            if ($detail['qrcode'] ?? false) {
//                foreach ($detail['qrcode'] as $k => $v) {
//                    $cardInfo['list'][$k]['qrcode'] = $v;
//                }
//            }
//            if ($detail['device_ids'] ?? false) {
//                foreach ($detail['device_ids'] as $k => $v) {
//                    $cardInfo['list'][$k]['device_id'] = $v;
//                }
//            }
//            if ($detail['lpa_str'] ?? false) {
//                foreach ($detail['lpa_str'] as $k => $v) {
//                    $cardInfo['list'][$k]['lpa_str'] = $v;
//                    $lpaInfo = explode('$', $v);
//                    $cardInfo['list'][$k]['Address'] = $lpaInfo[1] ?? '';
//                    $cardInfo['list'][$k]['ActivationCode'] = $lpaInfo[2] ?? '';
//                }
//            }
//            return $cardInfo;
//        }
//        return $detail;
//    }

    public function order_detail($topup_id)
    {
        $this->setHeaders();
        $args = [
            'headers' => $this->headers,
            'body' => [
                'topup_id' => $topup_id
            ],
            'timeout' => 60,
        ];
        $response = wp_remote_post($this->host . '/tsim/v1/topupDetail', $args);
        $http_body = wp_remote_retrieve_body($response);
        $http_body = json_decode($http_body, true);
        return $this->_formatResult($http_body);
    }
    public function send_email($topup_id)
    {
        if ($this->auto_send_email != 1) {
            return 'not auto send email';
        }
        $this->setHeaders();
        $args = [
            'headers' => $this->headers,
            'body' => [
                'topup_id' => $topup_id
            ],
            'timeout' => 33,
        ];
        $response = wp_remote_post($this->host . '/tsim/v1/sendOrderEmail', $args);

        $http_body = wp_remote_retrieve_body($response);
        $http_body = json_decode($http_body, true);
        return $http_body;
    }

    // public function add_charge(string $dataplan_id, string $iccid, string $remark = '')
    // {
    //     $this->setHeaders();
    //     $this->data['channel_dataplan_id'] = $dataplan_id;
    //     $this->data['device_ids'] = $iccid;
    //     $this->data['remark'] = $remark;
    //     $args = [
    //         'headers' => $this->headers,
    //         'body' => $this->data
    //     ];
    //     $response = wp_remote_post($this->host . '/tsim/v1/topup', $args);
    //     $http_body = wp_remote_retrieve_body($response);
    //     $http_body = json_decode($http_body, true);
    //     return $this->_formatResult($http_body);
    // }

    public function esim_dataplan_list()
    {
        $this->setHeaders();
        $args = [
            'headers' => $this->headers
        ];
        $response = wp_remote_get($this->host . '/tsim/v1/esimDataplanList', $args);
        $http_body = wp_remote_retrieve_body($response);
        $http_body = json_decode($http_body, true);
        return $this->_formatResult($http_body);
    }

    private function _formatResult($result)
    {
        if (isset($result['code']) && $result['code'] == 1) {
            return $result['result'] ?? '';
        } else {
            // throw new Exception('sell-esim-plugin api error:' . $result['msg'] ?? '');
            return false;
        }
    }
}
