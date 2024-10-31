<?php

namespace tsim\expend\helper;
class Request
{
    public static function sendGetRequest($url, $body = array(), $headers = array())
    {
        // 设置GET请求参数
        $url = add_query_arg($body, $url);

        // 设置请求头
        $args = array(
            'headers' => $headers,
        );
        // 发送GET请求
        $response = wp_remote_get($url, $args);
        // 检查是否出现错误
        if (is_wp_error($response)) {
            // 处理错误
            return false;
        }
        // 获取响应内容
        $body = wp_remote_retrieve_body($response);

        return $body;
    }

    public static function sendPostRequest($url, $body = array(), $headers = array())
    {
        // 设置POST请求参数
        $args = array(
            'body' => $body,
            'headers' => $headers,
        );

        // 发送POST请求
        $response = wp_remote_post($url, $args);

        // 检查是否出现错误
        if (is_wp_error($response)) {
            // 处理错误
            return false;
        }

        // 获取响应内容
        $body = wp_remote_retrieve_body($response);
        return $body;
    }
}