<?php
/*
微信企业 支付到个人
*/
class cash_weixin
{
    /**
     *  array转xml
     */
    public function arrayToXml($arr){
        $xml = "<xml>";
        foreach ($arr as $key => $val){
            if (is_numeric($val)) {
                $xml .= "<".$key.">".$val."</".$key.">";
            }else{
                $xml .= "<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }
    //使用证书，以post方式提交xml到对应的接口url
    /**
     *   作用：使用证书，以post方式提交xml到对应的接口url
     */
    function curl_post_ssl($url, $vars, $second=30){
        $ch = curl_init();
        //超时时间
        curl_setopt($ch,CURLOPT_TIMEOUT,$second);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
        //以下两种方式需选择一种
        /******* 此处必须为文件服务器根目录绝对路径 不可使用变量代替*********/
        curl_setopt($ch,CURLOPT_SSLCERT,dirname ( __FILE__ )."/cert/weixin/apiclient_cert.pem");
        curl_setopt($ch,CURLOPT_SSLKEY,dirname ( __FILE__ )."/cert/weixin/apiclient_key.pem");
        curl_setopt($ch,CURLOPT_POST, 1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$vars);
        $data = curl_exec($ch);
        if($data){
            curl_close($ch);
            return $data;
        }else {
            $error = curl_errno($ch);
            echo "call faild, errorCode:$error\n";
            curl_close($ch);
            return false;
        }
    }
    //企业向个人付款
    //返回状态
    public function payToUser($order_sn='',$openid='',$xm='',$desc='提现成功',$amount='0'){
        //微信付款到个人的接口
        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
        $params["mch_appid"]        = 'wxad533f7c6334a27f';   //公众账号appid
        $params["mchid"]            = '1497231192';   //商户号 微信支付平台账号
        $params["nonce_str"]        = 'freely2019'.mt_rand(100,999);   //随机字符串
        $params["partner_trade_no"] = $order_sn;           //商户订单号
        $params["amount"]           = $amount*100;          //金额单位分
        $params["desc"]             = $desc;            //企业付款描述
        $params["openid"]           = $openid;          //用户openid
        $params["check_name"]       = 'NO_CHECK';       //不检验用户姓名
        $params['spbill_create_ip'] = real_ip();   //获取IP
        $appkey='rtadlllzonomk51ltikfbhrrlga25t4m';
        print_r($params);
        //print_r('<br>');
       //生成签名(签名算法后面详细介绍)
        $str = 'amount='.$params["amount"].'&check_name='.$params["check_name"].'&desc='.$params["desc"].'&mch_appid='.$params["mch_appid"].'&mchid='.$params["mchid"].'&nonce_str='.$params["nonce_str"].'&openid='.$params["openid"].'&partner_trade_no='.$params["partner_trade_no"].'&spbill_create_ip='.$params['spbill_create_ip'].'&key='.$appkey;
        //print_r($str);
        //print_r('<br>');
        //md5加密 转换成大写
        $sign = strtoupper(md5($str));
        //print_r($sign);die();
        $params["sign"] = $sign;//签名
        $xml = $this->arrayToXml($params);
        $result=$this->xmlToArray($this->curl_post_ssl($url, $xml));
        return $this->getresultData($result);
    }

    public  function getresultData($result){
        $resultData=[
            'status'=>0,
        ];
        if($result['return_code']=='SUCCESS'){
            if($result['result_code']=='SUCCESS'){
                $resultData['status']=1;//表示付款成功，
                $resultData['order_sn']=$result['partner_trade_no'];//系统内的订单号
                $resultData['online_number']=$result['payment_no'];//微信付款单的交易单号
                $resultData['time']=$result['payment_time'];//付款时间
            }else{
                $resultData['err_code']=$result['err_code'];
                $resultData['err_msg']=$result['err_code_des'];
            }
        }else{
            $resultData['err_code']=$result['return_code'];
            $resultData['err_msg']=$result['return_msg'];
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