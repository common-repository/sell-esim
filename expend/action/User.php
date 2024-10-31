<?php
namespace tsim\expend\action;

class User
{
    public  static function bindUser($order_id)
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