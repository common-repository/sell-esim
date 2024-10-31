<?php

namespace tsim\expend\rest_api\open\v1;

//use Tmeister\Firebase\JWT;
use tsim\expend\helper\DateHelper;
use tsim\expend\helper\DbHelper;
use tsim\expend\rest_api\Base;
use tsim\expend\rest_api\OpenApi;
use tsim\expend\rest_api\ServiceApi;

class Package extends Base
{


    public function packageList(\WP_REST_Request $request)
    {
        $is_current = $request->get_param('is_current') ?? '';
        $page = $request->get_param('page') ?? 1;

        $user_id = get_current_user_id();
        $obj = DbHelper::name('tsim_data_plan_items')
            ->field('i.*,o.nums,o.product_name,o.apn,o.day,o.data_allowance,o.product_id,o.can_query')
            ->alias('i')
            ->join("tsim_orders o", 'o.id=i.tsim_orders_id')
            ->limit(20, $page)
            ->order('o.id desc')
            ->where("o.user_id = %d", [$user_id]);
        if (!empty($is_current)) {
            if ($is_current == 1) {
                // 0:未使用 1:激活 2:已终止 3:已取消  4:已过期
                $obj = $obj->where('i.status in(0,1) and i.expire_time > %d', [time()]);
            } elseif ($is_current == 2) {
                $obj = $obj->where('i.status in(2,3,4) or i.expire_time < %d', [time()]);
            }

        }
//        $sql = $obj->getQuerySql();
        $list = $obj->select();
        $rs_data = [];
        $dataplan_list = get_option('sellesim_dataplan_list');
        $map_list = [];
        foreach ($dataplan_list as $val) {
            $map_list[$val['channel_dataplan_id']] = $val;
        }
        foreach ($list as $val) {
            $product = wc_get_product($val->product_id);
            $image_id = $product->get_image_id();
            $img_obj = wp_get_attachment_image_src($image_id, 'full');
            $item = [
                'id' => $val->id,
                'iccid' => $val->iccid,
                'image_url' => $img_obj[0] ?? '',
                'product_name' => $val->product_name,
                'data_usage' => $val->data_usage,
                'data_allowance' => $val->data_allowance,
                'expire_time' => $val->expire_time,
                'active_time' => $val->active_time,
                'can_query' => $val->can_query ?? 1,
                'is_daily' => false,
                'day_data_allowance' => 0,
                'status' => $val->status,
                'apn' => $val->apn,
                'day' => $val->day,
                'product_id' => $val->product_id,
            ];
            $sku = $product->get_sku();
            $sku_info = $map_list[$sku] ?? [];
            if (!empty($sku_info)) {
                $item['can_query'] = $sku_info['can_query'] ?? $item['can_query'];
                $item['is_daily'] = $sku_info['is_daily'] ?? $item['is_daily'];
                $item['day_data_allowance'] = $sku_info['day_data_allowance'] ?? $item['day_data_allowance'];
            }
            $expire_time = $val->expire_time ?? 0;

            if (!empty($expire_time) && ($expire_time) < time() && $item['status'] <= 1) {
                $item['status'] = 4;
            }
            $item['can_query'] = (int)$item['can_query'];
            $item['expire_time'] = DateHelper::timestampToLocalTime($item['expire_time']);
            $item['active_time'] = DateHelper::timestampToLocalTime($item['active_time']);
            $item['termination_time'] = DateHelper::timestampToLocalTime($item['termination_time']);
            $rs_data[] = $item;
        }
        $list = [
            'list' => $rs_data,
            'page' => $page,
        ];

        return $this->result($list);
    }


    public function packageDetail(\WP_REST_Request $request)
    {
        global $wpdb;
        $id = $request->get_param('id') ?? '';
        if (empty($id)) {
            return $this->resultError("invalid params", 400);
        }
        $user_id = get_current_user_id();
        $obj = DbHelper::name('tsim_data_plan_items')
            ->field('i.*,o.nums,o.product_name,o.sku,o.apn,o.day,o.data_allowance')
            ->alias('i')
            ->join("tsim_orders o", 'o.id=i.tsim_orders_id')
            ->where("o.user_id = %d", [$user_id])
            ->where("i.id = %d", [$id])
            ->order("i.id desc");
        $info = $obj->find();
        if (empty($info)) {
            return $this->resultError("not_found");
        }
        $params = [
            'device_id' => $info->iccid,
            'topup_id' => $info->tsim_topup_id,
        ];
        $detail_info = (new ServiceApi)->deviceDetail($params);
        do_action('tsim_after_query_device_detail', $detail_info, $info);
        $info = $obj->find();
        $now_date = DateHelper::timestampToLocalTime(time(), "Y-m-d");
        $expire_second = max(($info->expire_time - time()), 0);
        $data = [
            'id' => $info->id,
            'iccid' => $info->iccid,
            'lpa_str' => $info->lpa_str,
            'qrcode' => $info->qrcode,
            'product_name' => $info->product_name,
            'data_usage' => $info->data_usage,
            'expire_time' => $info->expire_time,
            'can_query' => $info->can_query,
            'data_allowance' => $info->data_allowance,
            'status' => $info->status,
            'is_daily' => false,
            'apn' => $info->apn,
            'day' => $info->day,
            'today_usage' => 0,
            'time' => time(),
            'expired' => $info->expire_time,
            'today_date' => $now_date,
            'expire_second' => $expire_second,
        ];
        $sku = $info->sku;
        $dataplan_list = get_option('sellesim_dataplan_list');
        foreach ($dataplan_list as $val) {
            if ($val['channel_dataplan_id'] == $sku) {
                $data['can_query'] = $val['can_query'] ?? $data['can_query'];
                $data['is_daily'] = $val['is_daily'] ?? $data['is_daily'];
                $data['day_data_allowance'] = $sku_info['day_data_allowance'] ?? $data['day_data_allowance'];
                break;
            }
        }
        if ($detail_info) {
            if (isset($detail_info['data_usage_daily']) && !empty($detail_info['data_usage_daily']) && is_array($detail_info['data_usage_daily'])) {
                foreach ($detail_info['data_usage_daily'] as $val) {
                    $usage_date = DateHelper::utf8ToGmt($val['date'], 'Y-m-d');
                    $now_date_gmt = date("Y-m-d");
                    if ($usage_date == $now_date_gmt) {
                        $data['today_usage'] = $val['total_usage'];
                        break;
                    }
                }
            }
        }
        $data['expire_time'] = DateHelper::timestampToLocalTime(($data['expire_time']));
        $data['active_time'] = DateHelper::timestampToLocalTime(($data['active_time']));
//        $data['detail_info'] = $detail_info;
        return $this->result($data);
    }


}