<?php

namespace tsim\expend\rest_api\open\v1;

//use Tmeister\Firebase\JWT;
use tsim\expend\helper\DateHelper;
use tsim\expend\helper\DbHelper;
use tsim\expend\rest_api\Base;
use tsim\expend\rest_api\OpenApi;

class Order extends Base
{

    public function placeOrder(\WP_REST_Request $request)
    {
        $user_id = $this->user_id;
        $product_id = $request->get_param('product_id');
        $quantity = $request->get_param('quantity');
        if (empty($quantity)) {
            return $this->resultError('Please fill in the quantity', 401);
        }
        if (empty($product_id)) {
            return $this->resultError('Please fill in the product', 401);
        }
        if (!is_user_logged_in() || get_current_user_id() !== $user_id) {
            return $this->resultError('User authentication failed', 401);
        }
        $product = wc_get_product($product_id);
        $check_product = $this->checkProduct($product_id);
        if ($check_product !== true) {
            return $this->resultError($check_product);
        }
        // 检查产品库存是否充足
        if (!$product->is_in_stock()) {
            return $this->resultError('Product out of stock', 400);
        }


        // 创建新订单
        $order = wc_create_order();
        $order->add_product($product, $quantity); // 添加1个产品到订单中

//         设置订单的用户 ID
//        $order->set_customer_id($user_id);
//        $order->set_customer_id('');
        // 获取当前用户的默认账单地址
        $user_email = get_user_meta($user_id, 'billing_email', true);
        // 设置订单的联系邮箱
        $order->set_billing_email($user_email);
        // 计算订单总额
        $order->calculate_totals();
        $order_id = $order->get_id();
        // 获取订单支付链接
        $payment_link = $order->get_checkout_payment_url();
        // 记录订单的用户
        update_post_meta($order_id, "tsim_order_api_user", $user_id);

        // 返回订单 ID 或其他订单信息
        return $this->result(array('payment_link' => $payment_link, 'order_id' => $order_id));
    }
    public function orderList(\WP_REST_Request $request)
    {
        // 获取请求参数
        $page = $request->get_param('page') ?? 1; // 默认为第一页
        $user_id = get_current_user_id();
        // 构建查询参数
        $list = DbHelper::name('tsim_orders')
            ->alias('o')
            ->field('o.*,wo.status as order_status,wo.date_created_gmt')
            ->join('wc_orders wo', 'wo.id = o.order_id')
            ->where("o.user_id = %d", [$user_id])
            ->limit(20, $page)
            ->order("o.id desc")
            ->select();
        $list_arr = [];

        foreach ($list as $val) {
            // 获取产品对象
            $product = wc_get_product($val->product_id);
            $item = [
                'id' => $val->id,
                'order_id' => $val->order_id,
                'sku' => $val->sku,
                'product_id' => $val->product_id,
                'product_name' => $val->product_name,
                'quantity' => $val->nums,
                'order_status' => $val->order_status,
                'total_amount' => round($val->total_amount, 2),
                'img' => '',
            ];
            if ($product) {
                $image_id = $product->get_image_id();
                $img_obj = wp_get_attachment_image_src($image_id, 'full');
                $item['img'] = $img_obj[0] ?? '';
            }
            $list_arr[] = $item;
        }
        // 返回订单列表数据了
        $data = [
            'list' => $list_arr,
            'page' => $page,
        ];
        return $this->result($data);
    }

    public function orderDetail(\WP_REST_Request $request)
    {

        $user_id = get_current_user_id();
        $id = $request->get_param('id') ?? '';
        if (empty($id)) {
            return $this->resultError('Please fill in the id', 401);
        }
        $info = DbHelper::name('tsim_orders')
            ->alias('o')
            ->field('o.*,wo.status as order_status,wo.date_created_gmt')
            ->join('wc_orders wo', 'wo.id = o.order_id')
            ->where('o.id = %d and o.user_id=%d', [$id, $user_id])
            ->find();
        if (empty($info)) {
            return $this->resultError('data not fund', 401);
        }
        $item = [
            'id' => $info->id,
            'order_id' => $info->order_id,
            'product_id' => $info->product_id,
            'product_name' => $info->product_name,
            'quantity' => $info->nums,
            'order_status' => $info->order_status,
            'create_time' => DateHelper::gmtToLocalDate($info->date_created_gmt),
            'total_amount' => round($info->total_amount, 2),
            'img' => '',
        ];
        $product = wc_get_product($info->product_id);
        if ($product) {
            $image_id = $product->get_image_id();
            $img_obj = wp_get_attachment_image_src($image_id, 'full');
            $item['img'] = $img_obj[0] ?? '';
        }
        $data = [
            'info' => $item,
        ];
        return $this->result($data);
    }

    public function queryOrder(\WP_REST_Request $request)
    {
        $order_id = $request->get_param('order_id') ?? '';
        if (empty($order_id)) {
            return $this->resultError('Please fill in the order id', 401);
        }
        $user_id = get_current_user_id();

        $order = wc_get_order($order_id);
        if(empty($order)){
            return $this->resultError('order not fund', 401);
        }
        $user_id_order = $order->get_user_id();
        $user_id_order2 = get_post_meta($order_id, "tsim_order_api_user")[0] ?? '';
        if (strcasecmp($user_id, $user_id_order) !== 0 && strcasecmp($user_id, $user_id_order2) !==0 ) {
            return $this->resultError('user order not fund', 401);
        }
        $line_items = $order->get_items();
        $product_id = '';
        foreach ($line_items as $item) {
            $product_id = $item->get_product_id();
        }
        $paid = $order->is_paid();
        $info = [
            'paid' => $paid,
            'order_status' => $order->get_status(),
            'product_id' => $product_id
        ];
        return $this->result($info);
    }


//    function get_payment_gateways_list()
//    {
//        // 获取支付网关列表
//        $payment_gateways = WC()->payment_gateways()->get_available_payment_gateways();
//
//        $gateway_names = array();
//
//        foreach ($payment_gateways as $gateway) {
//            $gateway_names[] = $gateway->get_title(); // 或者使用 get_id() 方法获取支付网关的 ID
//        }
//
//        return $gateway_names;
//    }
}