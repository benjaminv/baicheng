<?php
require_once(dirname(__FILE__) ."/alipay/AopSdk.php");
/*
支付宝企业 支付到个人
*/
class cash_alipay
{
    //企业向个人付款
    //返回状态
    public function payToUser($order_sn='',$payee_account='',$payee_real_name='',$desc='佣金提现',$amount='0'){
        $aop = new AopClient ();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = '2018011901970468';
        $aop->rsaPrivateKey = 'MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDZmvFlRb8fW606aCz+UhiSphsHs4mEZXp/sLtJZC10NZ8UjDGWSzIJxS6GUHTBiwpXOm/6NMjvkxvbUEQSH5mclsR3OvHjDlzKiPUDG8/Ype0hScMJYcX2B197M4RA/Q3JYQ+KVN9h0tNqr4z2ngLHxGjn+te90sQw4T90go1X+2AkWitlOoazVxyvp0McxOBPRFf4hSC9S+NhsDy2Rc3Hr1IfMtch+OnalflXr8sWEV19fWkKs5XJ9+KlcOPMLGiFSFEMq8lov0frqICamX7Wt3nn+xSLvzKHtpMorHmNA5t96oWZ1vCgMyseBGL0hb/WjaXZkc0ETzAYag6f95MDAgMBAAECggEAU2gwXsD9IOfi3iBQHqsZABzq/2ixrS24Znk3UEo1ofVrpFSYLSNlaplJ2/G6zvScYhLkGONioXGhm86ISOoT1xFy/MB7NqyqpHcacraWVFRFMB01xMLVPhhVYMO+TaqxPh8V9c/ST4yfvKTNQzoNlsSR8VkUmI3Q5WtxBxeDVdUvdN2iWQQEjf8o6e2j/JPPpIlgmq5rTWG0+u8QX998X4E7o6Vt9VnjJI3HcwqJE46zQLfuoC07Fhf2JQG91kaPtlhPnoNg+0qpVNuiFbV+Nfm/pkH67Vovu3z2Dp+aAfa7NuhfJ29Dwnoh08tX2wtSrD4vyPPWn6i+edDNO+IWAQKBgQDs631nYy6c1dHtfkZmVpnuIhl1BaC1NWhxHSpMIiiWB7Hg1zEqV/0IbW7r2hroq+DvHiVsUzexNgpehb6dQhrzNVZHFeUMf3G3crHo1IMX+CcwxD3P8bAujCCeJIyDI4nqkKrNZ1MdExFw81BT6GId7LHiMvAxoLlcQeHKE6sIOQKBgQDrIT+q/hF7dHk18txRa0Puqsr01/9tNEVBtfhKbxn+sK/gb8gZcDjwxQfzESDTwlLuDV/70Z2H7WpLFatl7X0D2x1Z9KBC9UAlgwWvusJjKk3dMhJfL9sxavzUnNoWB3hqWNSHcfXaOAmNBWReOmpCcqegW5aF/iuIer0GyHldGwKBgQDT3hesEC8MA86SspzkQcewBAB9/MVlp1g551n+8YEYAdOZfPczpbHbCnnqIoZz0dj6HRxcTeL875XAR5xZZ1dQbT81nKfTUFjyM3hT/U8qbTkmzCd2wOzMA3Xb1lVtpKdeA3cq7p6N3pJ3Tq9kCelMV3IQFXtk9hUtIqF3I7WMSQKBgHFWN4BOs1KU1BBjHjvIvpf+j5Hxw9d5yKBh/Gq0nw0bUcuXVhac93VnI+vQJ8iq9Jp2q/uQEKUClafXrCSXkxkWt1EzD0T3PpJWU5lfJm/yZlHm3uAvCzMI5RH/AUh5FVv9sYQQNHeZZ1EodjbNZYbeCVrMiwPPfmBs+UyZuZZdAoGAUAmtiTO5Ru160GFVz4pkItTIzJUSMaw4+PMieMqkrARF75yLrbGa8pptVjBGvj7Ku8HxbTN+W1LNrV72cUlH9Tog11okvleY9BPoyzpZ6NoblgG4MSuuMtBVpCdeXzzqCMEnkGTqOrPRQjj9CzTL0zPBcH7eCjbv11jhMN55sJQ=';
        $aop->alipayrsaPublicKey='MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAgxL0ej+IZNA+Q8WfBXsgJCJ38S2HWctZucpxPwK7HcYwayplDbYYatCMp9wGEwJFhyE7Kp7Hy1Ez52NQbxeygZQKnHmjOKkBBIT+mGlb3otNMUqmqsiM3uTvLyo+SvgDtBfW7Cd4QYCwH3dpwv8aqrXLG40UxkIfJCh7MEVXD1P38uplgkhaJeOz4kgvh7pe5M940irj3wCk1758ebCe3FDxBGl7Zt2Qx8h/PCK6YyaPt0O4YO4dugC2cTgOYx6kQGu4JrN+M9GF49PHNHmfGyH5pClxCsD6EEF+L5HVIpxTDPxvCP3Io98FW3KMwq9bppEfSfkeq3FFmlNENpdVUwIDAQAB';
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        //$aop->postCharset='GBK';
        $aop->format='json';
        $request = new AlipayFundTransToaccountTransferRequest();
        $request->setBizContent("{" .
            "\"out_biz_no\":\"".$order_sn."\"," .
            "\"payee_type\":\"ALIPAY_LOGONID\"," .
            "\"payee_account\":\"".$payee_account."\"," .
            "\"amount\":\"".$amount."\"," .
            //"\"payer_show_name\":\"".$desc."\"," .
            "\"payee_real_name\":\"".$payee_real_name."\"," .
            "\"remark\":\"".$desc."\"" .
            "  }");
        //print_r('$result='.$request);die();
        $result = $aop->execute( $request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $rData=$result->$responseNode;
        $rData=json_decode(json_encode($rData),true);
        //print_r($rData);
        return $this->getresultData($rData);
    }

    public function getresultData($result){
        $resultData=[
            'status'=>0,
        ];
        if($result['code']=='10000'){
            $resultData['status']=1;//表示付款成功，
            $resultData['order_sn']=$result['out_biz_no'];//系统内的订单号
            $resultData['online_number']=$result['order_id'];//微信付款单的交易单号
            $resultData['time']=$result['pay_date'];//付款时间
        }else{
            $resultData['err_code']=$result['sub_code'];
            $resultData['err_msg']=$result['sub_msg'];
        }
        return $resultData;
    }
    /**
     * 	作用：将xml转为array
     */
    public function xmlToArray($xml)
    {
        //将XML转为array
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $array_data;
    }

}