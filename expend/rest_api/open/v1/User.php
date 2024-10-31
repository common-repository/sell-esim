<?php

namespace tsim\expend\rest_api\open\v1;

//use Tmeister\Firebase\JWT;
use tsim\expend\helper\DbHelper;
use tsim\expend\rest_api\Base;
use tsim\expend\rest_api\OpenApi;
use TsimAboy\SellEsim\ApiClient;

class User extends Base
{

    public function register(\WP_REST_Request $request)
    {
        // 获取用户提交的注册信息
        $username = $request->get_param('username');
        $email = $request->get_param('email');
        $password = $request->get_param('password');
        $verification_code = $request->get_param('verification_code');
        if (empty($username) || empty($email) || empty($password) || empty($verification_code)) {
            return $this->resultError('Please provide username, email, and password', 400);
        }
        if (!is_email($email)) {
            return $this->resultError('Invalid email address', 400);
        }
        $email_check = $this->checkEmailCode('email_register_verify_code_' . $email, $verification_code);
        if ($email_check !== true) {
            return $email_check;
        }
        if (email_exists($email)) {
            return $this->resultError('email already exists', 400);
        }
        if (username_exists($username)) {
            return $this->resultError('Username already exists', 400);
        }
        // 创建用户账户
        $user_id = wp_create_user($username, $password, $email);
        if (is_wp_error($user_id)) {
            return $this->resultError('Failed to create user account', 500);
        }

        // 返回注册结果
        return $this->result([], 'User registered successfully');

    }

    protected function checkEmailCode($key, $verify_code, $time_out = 600)
    {
        $email_check = get_option($key);
        if (empty($email_check)) {
            return $this->resultError('The email verification code is abnormal', 400);
        }
        if (strcasecmp($email_check['verification_code'], $verify_code) !== 0) {
            return $this->resultError('Verification code error', 400);
        }
        if (($email_check['time'] + $time_out) < time()) {
            return $this->resultError('Verification code expired', 400);
        }

        return true;
    }

    public function sendRegisterEmailVerify(\WP_REST_Request $request)
    {
        $email = $request->get_param('email') ?? '';
        if (empty($email)) {
            return $this->resultError("invalid params", 400);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->resultError("Email address is not valid" . $email, 400);
        }
        if (email_exists($email)) {
            return $this->resultError('email already exists', 400);
        }
        $email_check = get_option('email_register_verify_code_' . $email);
        if (!empty($email_check)) {
            if (($email_check['time'] + 60) > time()) {
                return $this->resultError('You can only send an email once in 60 seconds', 400);
            }
        }
        $verification_code = random_int(100000, 999990);
        $subject = 'Account Verification Code';
        $message = 'Hello,Thank you for registering with us. Please use the following verification code to activate your account:Verification Code: ' . $verification_code;
        $sent = wp_mail($email, $subject, $message);
        if ($sent) {
            // 邮件发送成功
            $info = [
                'verification_code' => $verification_code,
                'time' => time(),
            ];
            update_option('email_register_verify_code_' . $email, $info);

            return $this->result(['verification_code' => md5(md5("tsim" . $verification_code))]);
        }
        return $this->resultError("fail to send", 500);
    }

    public function sendChangeEmailVerify(\WP_REST_Request $request)
    {
        $user_id = get_current_user_id();
        $email = $request->get_param('new_email') ?? '';
        if (empty($email)) {
            return $this->resultError("invalid email", 400);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->resultError("Email address is not valid" . $email, 400);
        }
        if (email_exists($email)) {
            return $this->resultError('email already exists', 400);
        }
        $key = "email_change_email_verify_code_{$user_id}_{$email}";
        $email_check = get_option($key);
        if (!empty($email_check)) {
            if (($email_check['time'] + 60) > time()) {
                return $this->resultError('You can only send an email once in 60 seconds', 400);
            }
        }
        $verification_code = random_int(100000, 999990);
        $subject = 'Email Reset Verification Code';
        $message = 'Verification Code: ' . $verification_code;
        $sent = wp_mail($email, $subject, $message);
        if ($sent) {
            // 邮件发送成功
            $info = [
                'verification_code' => $verification_code,
                'time' => time(),
            ];
            update_option($key, $info);

            return $this->result(['verification_code' => md5(md5("tsim" . $verification_code))]);
        }
        return $this->resultError("fail to send", 500);
    }


    public function changeEmail(\WP_REST_Request $request)
    {
        // 获取请求参数
        $new_email = $request->get_param('new_email') ?? '';
        $verification_code = $request->get_param('verification_code');
        $user_id = get_current_user_id();
        $key = "email_change_email_verify_code_{$user_id}_{$new_email}";
        $email_check = $this->checkEmailCode($key, $verification_code);
        if ($email_check !== true) {
            return $email_check;
        }
        // 检查邮箱地址是否有效
        if (!is_email($new_email)) {
            return $this->resultError('Invalid email address', 400);
        }
        // 检查邮箱地址是否已被其他用户使用
        $user_id_by_email = email_exists($new_email);
        $user_id = get_current_user_id();
        if ($user_id_by_email && $user_id_by_email !== $user_id) {
            return $this->resultError('Email address is already in use', 400);
        }
        // 更新用户邮箱
        $args = array(
            'user_email' => $new_email
        );
        if (!empty($args)) {
            $args['ID'] = $user_id;
            $updated = wp_update_user($args);
            if (is_wp_error($updated)) {
                return $this->resultError($updated->get_error_message(), 500);
            }
            return $this->result([], 'User profile updated successfully');
        }
        // 返回成功响应
        return $this->result([], 'fail to save', 2);
    }


    public function changeUserInfo(\WP_REST_Request $request)
    {
        $first_name = $request->get_param('first_name');
        $last_name = $request->get_param('last_name');
        $password = $request->get_param('password');
        $current_user = wp_get_current_user();
        $args = [];
        $user_id = get_current_user_id();
        // 更新密码
        if (!empty($password)) {
            wp_set_password($password, $user_id);
            return $this->result([], 'User profile updated successfully');
        }
        if (!empty($first_name) && !empty($last_name)) {
            $args['last_name'] = $last_name;
            $args['first_name'] = $first_name;
        }
        if (!empty($args)) {
            $args['ID'] = $current_user->ID;
            $updated = wp_update_user($args);
            if (is_wp_error($updated)) {
                return $this->resultError($updated->get_error_message(), 500);
            }
            return $this->result([], 'User profile updated successfully');
        }
        // 返回成功消息
        return $this->result([], 'fail to save', 2);
    }

    public function getUserInfo(\WP_REST_Request $request)
    {
        $current_user = wp_get_current_user();
        if($current_user === null){
            return $this->result([]);
        }
        $user_info = array(
            'id' => $current_user->ID,
            'username' => $current_user->user_login,
            'name' => $current_user->display_name,
            'first_name' => $current_user->user_firstname,
            'last_name' => $current_user->user_lastname,
            'email' => $current_user->user_email,
            'nickname' => $current_user->nickname,
        );
        return $this->result($user_info);
    }

    public function sendResetPasswordLink(\WP_REST_Request $request)
    {
        $email = $request->get_param('email');

        // 检查邮箱地址是否有效
        if (!is_email($email)) {
            return $this->resultError('Invalid email address', 400);
        }

        $user = get_user_by('email', $email);
        if (!$user) {
            return $this->resultError('Email address is not associated with any user', 404);
        }
        $reset_key = get_password_reset_key($user);
        $reset_password_email = new \WC_Email_Customer_Reset_Password();
        // 发送重置密码邮件
        $reset_password_email->trigger($user->user_login, $reset_key);

        return $this->result('Reset password link sent successfully');

    }

    public function deleteAccount(\WP_REST_Request $request)
    {
        // Get current user ID
        $user = wp_get_current_user();
        if ($user && in_array('customer', $user->roles)) {
            $user_id = get_current_user_id();
            // Delete user account
            if (wp_delete_user($user_id)) {
                return $this->result([], 'Account deleted successfully');
            }
        }

        return $this->resultError('Failed to delete account');
    }
}