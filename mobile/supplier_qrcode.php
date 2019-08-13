<?php
//通过本页面进入的都需判断是否登录By Freely
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init_supplier.php');
$u_supplier_id=$_GET['suppId'];
if($u_supplier_id<=0){
	ecs_header("Location: index.php");
    exit;
}
$user_id = isset($_SESSION['user_id'])?$_SESSION['user_id']:0;
set_affiliate();//保存信息

$parent_id=$_GET['u'];
if($parent_id!=0){
    list($u_supplier_id,$parent_id,$is_old)=get_affiliate_supplier_id($parent_id);
}else{
    $parent_id=0;
    $affiliate_supplier = unserialize($GLOBALS['_CFG']['affiliate_supplier']);
    $u_supplier_id=$affiliate_supplier['config']['default_reg_supplier_id'];
}
$gotoUrl = "supplier.php?u=".$_GET['u']."&suppId=".$u_supplier_id;//页面跳转
if($user_id==0){//判断当是通过扫码进入的，那判断是否登录，如果未登录跳转到登录页面去
    //获取推广地址里带的推广人ID，获取推广人相关信息组合成推广地址发送--By Freely
    //$up_uid = get_affiliate();
	
    //$portal_id=$GLOBALS['db']->getOne("select portal_id from ".$GLOBALS['ecs']->table('users')." where user_id=".$_GET['u']);
    //$gotoUrl=OSS_LOGIN."/?uid=".$portal_id."&dest=".$gotoUrl;
	//跳转到微信登录
	setcookie('callback','http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'],time()+3600);
	$gotoUrl ="weixin_login.php?user_id=".$_GET['u']."&suppId=".$u_supplier_id."&erweima_type=1&callback=".$gotoUrl;

}
header("location: ".$gotoUrl."");exit;
?>