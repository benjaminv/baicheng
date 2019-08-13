<?php

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
include_once (ROOT_PATH . 'includes/lib_clips.php');

$supplier=$GLOBALS['db']->getRow("SELECT *,(select sum(money) from ".$GLOBALS['ecs']->table('supplier_cash_order')." where supplier_id=s.supplier_id And (status=0 Or status=1)) as nowcash_money FROM ".$GLOBALS['ecs']->table('supplier')." as s WHERE supplier_id=".$_SESSION['supplier_id']);  // 可提现金

if($_REQUEST['act'] == 'apply_money'){
   
	check_authz_json('supplier_cash_order_manage');
    $tx_money = (float)$_POST['tx_money'];

    if($tx_money<=0){

        sys_msg("要提现的金额不能小于等于0元");
    }
    if($tx_money>$supplier['commission_money']){

        sys_msg("要提现的金额不能超过可提现金额");
    }

    $pay_typ = $_POST['pay_typ']==1?1:0;
    $pay_number='';
    $pay_name='';
    if($pay_typ==1){
        $pay_number = $_POST['pay_number'];
        $pay_name = $_POST['pay_name'];
        if($pay_number==''){sys_msg("您选择了支付宝收款，需要填写收款的支付宝账号");}
        if($pay_name==''){sys_msg("您选择了支付宝收款，需要填写收款的支付宝账号姓名");}
    }else{//默认获取微信的openid值做为账号
        //判断当用户选择微信时判断是否有绑定账号
        $wxInfo=$GLOBALS['db']->getRow("SELECT * FROM ".$GLOBALS['ecs']->table('weixin_user')." WHERE ecuid=".$supplier['user_id']);
        if(!$wxInfo){sys_msg("此店铺管理员账号没有绑定微信，无法使用微信提现处理！");}
        $pay_number=$wxInfo['fake_id'];
        $pay_name='';
    }
    $order_sn=get_cash_order_sns();
     $sql = "INSERT INTO " . $ecs->table('supplier_cash_order') . " (order_sn,supplier_id, money,pay_typ,pay_number,pay_name, status,add_time) " .
            "VALUES ('$order_sn','$supplier[supplier_id]', '$tx_money','".$pay_typ."','".$pay_number."','".$pay_name."', '0','".gmtime()."')";
     $result=$db->query($sql);
     if($result===false){
         sys_msg("提现处理失败，刷新后再试");
     }
     $db->query("UPDATE ".$ecs->table('supplier')." SET commission_money=commission_money-".$tx_money." WHERE supplier_id=".$supplier['supplier_id']);
    /* 清除缓存 */
    clear_cache_files();
    /* 提示信息 */
    $links[] = array('href' => 'money_apply.php', 'text' => '返回提现列表');
    sys_msg('佣金提现申请成功，等待审核处理', 0, $links);
}


$smarty->assign('supplier', $supplier);
$smarty->display('money_apply.htm');





function get_cash_order_sns()
{
    /* 选择一个随机的方案 */
    mt_srand((double) microtime() * 1000000);
    return date('YmdHi') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
}