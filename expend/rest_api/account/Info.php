<?php

namespace tsim\expend\rest_api\account;

//use Tmeister\Firebase\JWT;
use tsim\expend\helper\DbHelper;
use tsim\expend\rest_api\Base;
use tsim\expend\rest_api\OpenApi;
use tsim\expend\rest_api\service\Notice;
use tsim\expend\rest_api\ServiceApi;

class Info extends Base
{
    public function queryUsage(\WP_REST_Request $request)
    {

        $order_id = $request->get_param('order_id');
        if (empty($order_id)) {
            return $this->resultError('Missing parameter');
        }
        if (!is_ajax()) {
            return $this->resultError('error request');
        }
        $user_id = get_current_user_id();
        $transient_name = 'tsim_order_query_device_detail_' . $order_id;
        $last_submission_time = get_transient($transient_name);

        $obj = DbHelper::name('tsim_data_plan_items')
            ->field('i.*,o.nums,o.product_name,o.apn,o.day,o.data_allowance,o.product_id,o.can_query')
            ->alias('i')
            ->join("tsim_orders o", 'o.id=i.tsim_orders_id')
            ->where('i.order_id =  %d and o.user_id = %d', [$order_id, $user_id]);

        $list = $obj->select();
        if (empty($list)) {
            return $this->resultError('data not fund');
        }
        $is_query = 0;
        $syn_time = 600;
        if ((time() - $last_submission_time) > $syn_time) {
            foreach ($list as $val) {
                $params = [
                    'device_id' => $val->iccid,
                    'topup_id' => $val->tsim_order_id,
                ];
                $detail_info = (new ServiceApi())->deviceDetail($params);
                do_action('tsim_after_query_device_detail', $detail_info,$val);
            }
            set_transient($transient_name, time(), $syn_time);
            $list = $obj->select();
            $is_query = 1;
        }
        $data_list = [];
        $status_map = [0=>'inactive',1 => 'active', 2 => 'termination', 3 => "cancel", 4 => "expired"];
        foreach ($list as $val) {
            $item = [
                'iccid' => $val->iccid,
                'lpa_str' => $val->lpa_str,
                'qrcode' => $val->qrcode,
                'data_usage' => $val->data_usage,
                'status' => $status_map[$val->status] ?? '',
                'status_code' => $val->status,
            ];
            if ($list->status == 1 && time() > $val->expire_time) {
                $item['status'] = 'expired';
            }
            $data_list[] = $item;
        }
        $data  = [
            'list'=>$data_list,
            'is_query'=>$is_query,
        ];
        return $this->result($data);
    }
}