<?php
include($_SERVER['DOCUMENT_ROOT'] ."/vender/alipay/AopSdk.php");
include($_SERVER['DOCUMENT_ROOT']."/data/config.php");
require_once $_SERVER['DOCUMENT_ROOT'].'/vender/alipay/pagepay/service/AlipayTradeService.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/vender/alipay/pagepay/buildermodel/AlipayTradeRefundContentBuilder.php';
function do_alipay_refund($order_id,$order_sn,$refund_amount){
    $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('payment')." WHERE pay_code = 'alipay'";
    $payment = $GLOBALS['db']->getRow($sql);
    $payment = unserialize($payment['pay_config']);
    $payment_config = array();
    foreach($payment as $k => $v){
        $payment_config[$v['name']] = $v['value'];
    }
    $config = [
        'app_id' => ALIPAY_APPID,
        'merchant_private_key' => ALIPAY_PRIVATE_KEY,
        'alipay_public_key' => ALIPAY_PUBLIC_KEY,
        'charset' => "UTF-8",
        'sign_type'=>"RSA2",
        'gatewayUrl' => "https://openapi.alipay.com/gateway.do",
    ];
    $aop = new AlipayTradeService($config);
    $RequestBuilder=new AlipayTradeRefundContentBuilder();
    $RequestBuilder->setOutTradeNo($order_sn);
    $RequestBuilder->setRefundAmount($refund_amount);
    $RequestBuilder->setRefundReason("正常退款");
    $response = $aop->Refund($RequestBuilder);
    alipay_refund_log('alipay refund return:',$response);
    alipay_refund_log('+++++++:','end+++++++');
    if($response->code == "10000"){
        return true;
    }else{
        return false;
    }
}


function alipay_refund_log($key,$content){
//    echo ROOT_PATH.'logs/alipay/pt_refund.log';//die;
    $date = date('Y-m-d');
    $fileName = ROOT_PATH.'logs/alipay/pt_refund_'.$date.'.log';
    if(!create_file($fileName)) {
        file_put_contents($fileName, '----'.date('Y-m-d H:i:s').'-----' . $key . ':' . var_export($content, true) . '=================>' . PHP_EOL, FILE_APPEND);
    }
}

function create_file($filename){
    if(file_exists($filename)){
        return false;
    }
    // 检测目录是否存在，不存在则创建
    if(!file_exists(dirname($filename))){
        mkdir(dirname($filename),0777,true);   //true是指是否创建多级目录
    }
    // if(touch($filename)){
    // 	return true;
    // }
    // return false;
    if(file_put_contents($filename,'')!==false){   // ''是指创建的文件中的内容是空的
        return true;
    }
    return false;
}
