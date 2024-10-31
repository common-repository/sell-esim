<?php
namespace tsim\expend\rest_api;
use tsim\expend\helper\Request;

class ServiceApi extends ServiceApiBase
{
    public function deviceDetail($params)
    {
        $this->setHeaders();
        $url = $this->host."/tsim/v1/deviceDetail";
        $this->data = [
            'device_id' => $params['device_id'],
            'topup_id' => $params['topup_id'],
        ];
        $rs = Request::sendPostRequest($url,$this->data,$this->headers);
        $http_body = json_decode($rs, true);
        return $this->_formatResult($http_body);
    }


}