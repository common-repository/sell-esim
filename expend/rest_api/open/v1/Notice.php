<?php

namespace tsim\expend\rest_api\open\v1;

//use Tmeister\Firebase\JWT;
use tsim\expend\helper\DateHelper;
use tsim\expend\helper\DbHelper;
use tsim\expend\rest_api\Base;
use tsim\expend\rest_api\OpenApi;

class Notice extends Base
{
    const last_time_key = "last_api_notice_list_call_time";

    public function checkNewNotice()
    {
        $last_time_key = self::last_time_key;
        $user_id = get_current_user_id();
        $read_time = get_user_meta($user_id, $last_time_key, true);
        $exit = DbHelper::name('tsim_order_notice')
            ->where("user_id = %d", [$user_id])
            ->where("event_time > %d", [$read_time])
            ->find();
        $rs = [
            'has_new' => 0
        ];
        if (!empty($exit)) {
            $rs['has_new'] = 1;
        }
        return $this->result($rs);
    }

    public function noticeList(\WP_REST_Request $request)
    {
        $user_id = get_current_user_id();
        $page = $request->get_param('page') ?? 1; // 默认为第一页
        $list = DbHelper::name('tsim_order_notice')
            ->alias('t')
            ->field('t.*,o.product_name')
            ->join('tsim_orders o', 'o.id = t.tsim_orders_id')
            ->where("t.user_id = %d", [$user_id])
            ->order('t.id desc')
            ->limit(100, $page);
//        $sql = $list->getQuerySql();
        $list = $list->select();
        $list_arr = [];
        $last_time_key = self::last_time_key;
        $read_time = get_user_meta($user_id, $last_time_key, true);
        foreach ($list as $val) {
            // 获取产品对象
            $item = [
                'id' => $val->id,
                'tsim_orders_id' => $val->tsim_orders_id,
                'data_plan_items_id' => $val->data_plan_items_id,
                'product_name' => $val->product_name,
                'event' => $val->event,
                'is_read' => 0,
                'timezone_string' => get_option('timezone_string'),
                'event_time' => DateHelper::timestampToLocalTime($val->event_time),
                'type' => 'data_plan_event'
            ];
            if (!empty($read_time) && ($val->event_time) <= $read_time) {
                $item['is_read'] = 1;
            }
            $list_arr[] = $item;
        }
        update_user_meta($user_id, $last_time_key, time());

        // 返回订单列表数据了
        $data = [
            'list' => $list_arr,
            'page' => $page,
        ];

        return $this->result($data);
    }
}