<?php
/****************************************************************************/
//商城对接oddo接口。实现会员，订单，商品的同步。
//本文件为服务端
/****************************************************************************/
/**
** @desc 封装 curl 的调用接口，post的请求方式
**/
error_reporting(0);
$url = "https://ym.linkall.sh.cn/api/order.php";
$action = "deposit";
$hash_code="31693422540744c0a6b6da635b7a5a93";
$key='*5t8bw5HQKoba!3O$inTstcIhdGLIzMV';
$auth = md5($key.$hash_code);

//配货中数据 更新发货单接口数据
$data=array(
	"action"=>$action,
	"auth"=>$auth,
	"data"=>array(
		"delivery_sn"=>'1544598870',
		"invoice_no"=>"yhy".date("YmdHis"),
		"shipping_id"=>9,
		"shipping_name"=>"货运发货",
	)
);

//用户商家预存款更新接口
//$data=array(
	//"action"=>$action,
	//"auth"=>$auth,
	//"data"=>array(
		//"user_id"=>99,
		//"company_id"=>3,
		//"money"=>100,
		//"originid"=>1,
		//"admin"=>"erp管理员xxx",
	//)
//);

echo "<pre>";print_r(json_encode($data));exit;

http_request($url,$data);
function http_request($url, $data = null){
		$headers = array("Content-type: application/json;charset=utf-8","Accept: application/json","Cache-Control: no-cache","Pragma: no-cache");
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60); //设置超时
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
		$rtn = curl_exec($ch);//CURLOPT_RETURNTRANSFER 不设置  curl_exec返回TRUE 设置  curl_exec返回json(此处) 失败都返回FALSE
		echo "<pre>";print_r($rtn);
  		curl_close($ch);
		return $rtn;
	}


