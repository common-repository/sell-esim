<?php
// NOTIFY
namespace tsim\expend\rest_api\service;


use tsim\expend\helper\DateHelper;
use tsim\expend\helper\DbHelper;
use tsim\expend\install\Table;
use tsim\expend\rest_api\Base;
use TsimAboy\SellEsim\ApiClient;

class Notice extends Base
{


    public function tsimNotify(\WP_REST_Request $request)
    {
        $params = $request->get_params();
        $topup_id = $params['topup_id'] ?? '';
        $device_id = $params['device_id'] ?? '';
        $event = $params['event'] ?? '';
        $time = $params['time'] ?? time();
        if (empty($topup_id) || empty($event)) {
            return $this->resultError('params error', 401);
        }
        $order_info = DbHelper::name('tsim_orders')
            ->where('tsim_topup_id = %s', [$topup_id])->find();
        if (empty($order_info)) {
            return $this->resultError('order not fund', 401);
        }
        // 1、激活；2、终止；3、取消；4、激活码通知；5、订单处理完成通知
        if ($event == 5) {
            $detail = (new ApiClient())->order_detail($topup_id);

            $send = (new ApiClient())->send_email($topup_id);

            do_action('tsim_after_query_data_plan_detail', $detail);
            $order_id = $order_info->order_id;
            $order = wc_get_order($order_id);
            $all_sell_esim = $this->checkOrderAllSellEsim($order_id);
            if ($order && $all_sell_esim) {
                $order->update_status('completed');
            }
        }
        $item = DbHelper::name('tsim_data_plan_items')->limit(1)->where("tsim_topup_id = %s and iccid = %s", [$topup_id, $device_id])->find();
        if (!empty($item)) {
            $event_map = [1 => 'active', 2 => 'termination', 3 => "cancel"];
            if ($event == 1) {
                $info = [
                    'status' => 1,
                    'active_time' => $time,
                ];
            }
            if ($event == 2) {
                $info = [
                    'status' => 2,
                    'termination_time' => $time,
                ];
            }
            if ($event == 3) {
                $info = [
                    'status' => 3,
                    'termination_time' => $time,
                ];
            }
            if (!empty($info)) {
                $notice = [
                    'tsim_orders_id' => $order_info->id,
                    'data_plan_items_id' => $item->id,
                    'event' => $event_map[$event] ?? '',
                    'event_time' => $time,
                    'user_id' => $order_info->user_id,
                ];
                $rs = DbHelper::name('tsim_data_plan_items')->limit(1)->updateData($info, "id = {$item->id}");
                $exit = DbHelper::name('tsim_order_notice')->where("data_plan_items_id = %d and event = %s", [$notice['data_plan_items_id'], $notice['event']])->find();
                if (empty($exit)) {
                    DbHelper::name('tsim_order_notice')->insertdata($notice);
                }
            }
        }
        return $this->result([]);
    }

    public function getOrderTarget(\WP_REST_Request $request)
    {
        $params = $request->get_params();
        $topup_id = $params['topup_id'] ?? '';
        $device_id = $params['device_id'] ?? '';
        $event = $params['event'] ?? '';
        $order_id = $params['order_id'] ?? '';
        $time = $params['time'] ?? time();
        $rs = [
            'all_sell_esim' => 'not order id',
            'order_info' => 'not tsim_topup_id',
            'data_plan_items' => 'not tsim_topup_id',
            'wp_order_tsim_info' => 'not order id',
            'wp_order_tsim_item_info' => 'not order id',
            'wp_order_info' => 'not order id',
        ];
        if (!empty($order_id)) {
            $order = wc_get_order($order_id);
            if ($order) {
                $rs['wp_order_info'] = $order->get_data();
                $rs['wp_order_tsim_info'] = DbHelper::name('tsim_orders')
                    ->where('order_id = %d', [$order_id])->select();
                $rs['wp_order_tsim_item_info'] = DbHelper::name('tsim_data_plan_items')->where("order_id = %d", [$order_id])->select();
            }
            $all_sell_esim = $this->checkOrderAllSellEsim($order_id);
            $rs['all_sell_esim'] = $all_sell_esim;
        }
        if (!empty($topup_id)) {
            $order_info = DbHelper::name('tsim_orders')
                ->where('tsim_topup_id = %s', [$topup_id])->find();
            $rs['order_info'] = $order_info;
            $items = DbHelper::name('tsim_data_plan_items')->where("tsim_topup_id = %s", [$topup_id])->select();
            $rs['data_plan_items'] = $items;
        }
        return $this->result($rs);
    }

    public function getTableInstallStatus(\WP_REST_Request $request)
    {
        global $wpdb;

        // 返回这几张表的安装情况
        $table_list = [
            'tsim_orders' => $wpdb->prefix.'tsim_orders',
            'tsim_data_plan_items' => $wpdb->prefix.'tsim_data_plan_items',
            'tsim_order_notice' => $wpdb->prefix.'tsim_order_notice',
            'tsim_feedback_list' => $wpdb->prefix.'tsim_feedback_list',
        ];
        $rs = [];
        foreach ($table_list as $key => $table_name) {
            if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
                $rs[$key] = 'installed';
            } else {
                $rs[$key] = 'not installed';
            }
        }

        return $this->result($rs);
    }
    public function tableInstallTest(\WP_REST_Request $request)
    {
        (new Table())->init();
    }
}