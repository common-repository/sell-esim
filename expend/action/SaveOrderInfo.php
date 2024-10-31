<?php

namespace tsim\expend\action;

use tsim\expend\helper\DbHelper;
use tsim\expend\helper\Request;
use tsim\expend\helper\DateHelper;

class SaveOrderInfo
{
    public static function saveOrder($res, \WC_Order_Item $item)
    {
        if (!($res['topup_id'] ?? false)) {
            return false;
        }
        $order = wc_get_order($item->get_order_id());
        $product = $item->get_product();
        $total = $item->get_subtotal();
        /**
         * @var $product \WC_Product
         */
        $sku = $product->get_sku();
        $now = time();
        $data_array = array(
            'order_id' => $order->get_id(),
            'tsim_topup_id' => $res['topup_id'],
            'sku' => $sku,
            'product_id' => $item->get_product_id(),
            'total_amount' => $total,
            'product_name' => $product->get_name(),
            'user_id' => $order->get_user_id(),
            'nums' => $item->get_quantity(),
            'create_time' => $now,
        );
        $oinfo = DbHelper::name('tsim_orders')->where('tsim_topup_id = %s and order_id=%d', [$res['topup_id'], $order->get_id()])->find();
        if (empty($oinfo)) {
            $dataplan_list = get_option('sellesim_dataplan_list');
            foreach ($dataplan_list as $val) {
                if ($sku == $val['channel_dataplan_id']) {
                    $data_array['apn'] = $val['apn'];
                    $data_array['day'] = $val['day'];
                    $data_array['data_allowance'] = $val['data_allowance'];
                    $data_array['can_query'] = $val['can_query'];
                }
            }
            DbHelper::name('tsim_orders')->insertData($data_array);
            $oinfo = DbHelper::name('tsim_orders')->where('tsim_topup_id = %s and order_id=%d', [$res['topup_id'], $order->get_id()])->find();
        }
        $tsim_orders_id = $oinfo->id;
        $item_list = DbHelper::name('tsim_data_plan_items')->where("tsim_orders_id = {$tsim_orders_id}")->select();
        if (count($item_list) >= $oinfo->nums) {
            return true;
        }
        $start = count($item_list);
        for ($i = $start; $i < $data_array['nums']; $i++) {
            $info = [
                'order_id' => $order->get_id(),
                'tsim_topup_id' => $res['topup_id'],
                'tsim_orders_id' => $oinfo->id,
                'create_time' => $now,
                'sort' => $i,
            ];
            $rs = DbHelper::name('tsim_data_plan_items')->insertData($info);
        }
        return true;
    }


    public static function saveDetail($detail)
    {
        if ($detail['qrcode'] ?? false) {
            foreach ($detail['qrcode'] as $key => $val) {
                $info = [
                    'iccid' => array_shift($detail['device_ids']),
                    'lpa_str' => array_shift($detail['lpa_str']),
                    'qrcode' => array_shift($detail['qrcode']),
                    'expire_time' => isset($detail['expire_time']) ? DateHelper::datetimeToTimestampSH($detail['expire_time']) : '',
                ];
                $rs = DbHelper::name('tsim_data_plan_items')->limit(1)->updateData($info, "tsim_topup_id = {$detail['topup_id']} and sort = {$key} ");
            }
        }

    }

    public static function saveDataPlanInfo($detail_info, $info)
    {
        if ($detail_info) {
            $active_time = (isset($detail_info['active_time']) && !empty($detail_info['active_time'])) ? DateHelper::datetimeToTimestampSH($detail_info['active_time']) : 0;
            $expire_time = (isset($detail_info['expire_time']) && !empty($detail_info['expire_time'])) ? DateHelper::datetimeToTimestampSH($detail_info['expire_time']) : 0;
            $up = [];
            if (is_numeric($detail_info['data_usage'])) {
                $up['data_usage'] = $detail_info['data_usage'];
            }
            if (!empty($active_time)) {
                if ($info->status == 0) {
                    $up['active_time'] = $active_time;
                    $up['status'] = 1;
                }
            }
            if ($expire_time !== $info->expire_time) {
                $up['expire_time'] = $expire_time;
            }
            if (!empty($expire_time) && ($expire_time) < time() && $info->status <= 1) {
                $up['status'] = 4;
            }
            if (!empty($up)) {
                $rs = DbHelper::name('tsim_data_plan_items')->updateArrData($up, ['id' => $info->id]);
            }
        }
    }

    public static function bindUser($order_id)
    {

        $user_id = get_post_meta($order_id, "tsim_order_api_user")[0] ?? '';
        if (!empty($user_id)) {
            $order = wc_get_order($order_id);
            $order_user_id = $order->get_user_id();
            if (empty($order_user_id)) {
                $order->set_customer_id((int)$user_id);
                $order->save();
            }
        }
    }
}