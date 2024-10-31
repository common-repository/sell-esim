<?php
namespace tsim\expend\rest_api;
class ServiceApiBase
{
    protected $host = null;
    protected $account = null;
    protected $secret = null;

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

    protected function setHeaders()
    {
        $this->header['Content-Type'] = 'application/json;charset=utf-8';
        $this->headers['TSIM-ACCOUNT'] = $this->account;
        $this->headers['TSIM-NONCE'] = wp_rand(100000, 99999999);
        $this->headers['TSIM-TIMESTAMP'] = time();
        $signContent = $this->account . $this->headers['TSIM-NONCE'] . $this->headers['TSIM-TIMESTAMP'];
        $sign = hash_hmac('sha256', $signContent, $this->secret);
        $this->headers['TSIM-SIGN'] = $sign;
    }


    protected function _formatResult($result)
    {
        if (isset($result['code']) && $result['code'] == 1) {
            return $result['result'] ?? '';
        } else {
            // throw new Exception('sell-esim-plugin api error:' . $result['msg'] ?? '');
            return false;
        }
    }
}