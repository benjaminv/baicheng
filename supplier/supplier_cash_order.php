<?php

/**
 * 管理中心 佣金提现管理
 * $Author: Freely
 * 
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
//require_once(ROOT_PATH . 'includes/lib_rebate.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
//require(ROOT_PATH . 'languages/' .$_CFG['lang']. '/admin/supplier.php');
$supplier=$GLOBALS['db']->getRow("SELECT *,(select sum(money) from ".$GLOBALS['ecs']->table('supplier_cash_order')." where supplier_id=s.supplier_id And (status=0 Or status=1)) as nowcash_money FROM ".$GLOBALS['ecs']->table('supplier')." as s WHERE supplier_id=".$_SESSION['supplier_id']);
//echo $supplier['nowcash_money'];
//$supplier['commission_money']-=$supplier['nowcash_money'];
$smarty->assign('lang', $_LANG);
$smarty->assign('supplier',$supplier);

/*------------------------------------------------------ */
//-- 佣金提现列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
     /* 检查权限 */
     admin_priv('supplier_cash_order_manage');

	$ur_here_lang = '店铺佣金提现列表';
    $smarty->assign('ur_here', $ur_here_lang); // 当前导航
    $smarty->assign('action_link',  array('href' => 'supplier_cash_order.php?act=add', 'text' => '申请提现'));
	$result = supplier_cash_order_list();
	$smarty->assign('cash_order_list',    $result['result']);
    $smarty->assign('filter',       $result['filter']);
    $smarty->assign('record_count', $result['record_count']);
    $smarty->assign('page_count',   $result['page_count']);
	$smarty->assign('full_page',        1); // 翻页参数
	assign_query_info();
    $smarty->display('supplier_cash_order_list.htm');
}

/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    check_authz_json('supplier_cash_order_manage');

    $result = supplier_cash_order_list();
	$smarty->assign('cash_order_list',    $result['result']);
	$smarty->assign('filter',       $result['filter']);
	$smarty->assign('record_count', $result['record_count']);
	$smarty->assign('page_count',   $result['page_count']);

	/* 排序标记 */
	$sort_flag  = sort_flag($result['filter']);
	$smarty->assign($sort_flag['tag'], $sort_flag['img']);

	make_json_result($smarty->fetch('supplier_cash_order_list.htm'), '',
		array('filter' => $result['filter'], 'page_count' => $result['page_count']));
}
//提现申请
elseif($_REQUEST['act']=='add'){
    check_authz_json('supplier_cash_order_manage');
    $smarty->display('supplier_cash_order_info.htm');
}
elseif($_REQUEST['act']=='save'){
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
    $order_sn=get_cash_order_sn();
     $sql = "INSERT INTO " . $ecs->table('supplier_cash_order') . " (order_sn,supplier_id, money,pay_typ,pay_number,pay_name, status,add_time) " .
            "VALUES ('$order_sn','$supplier[supplier_id]', '$tx_money','".$pay_typ."','".$pay_number."','".$pay_name."', '0','".gmtime()."')";
     $result=$db->query($sql);
     if($result===false){
         sys_msg("提现处理失败，刷新后再试");
     }
     $db->query("UPDATE ".$ecs->table('supplier')." SET commission_money=commission_money-".$tx_money.",withdrawals_money=withdrawals_money+".$tx_money." WHERE supplier_id=".$supplier['supplier_id']);
    /* 清除缓存 */
    clear_cache_files();
    /* 提示信息 */
    $links[] = array('href' => 'supplier_cash_order.php?act=list', 'text' => '返回提现列表');
    sys_msg('佣金提现申请成功，等待审核处理', 0, $links);
}
elseif($_REQUEST['act']=='remove_order'){
    $oid = intval($_REQUEST['id']);
    /* 检查权限 */
    check_authz_json('supplier_cash_order_manage');
    /* 检查订单是否允许删除操作 */
    $order=$GLOBALS['db']->getRow("SELECT * FROM ".$GLOBALS['ecs']->table('supplier_cash_order')." WHERE id=".$oid);
    if(empty($order)){sys_msg('此提现订单不存在，无法处理！');}
    if($order['status']==1){sys_msg('此提现订单已审核通过，将不能删除了！');}
    $GLOBALS['db']->query("DELETE FROM ".$GLOBALS['ecs']->table('supplier_cash_order'). " WHERE id = '$oid'");
    //给增加回去
    //$GLOBALS['db']->query("UPDATE ".$GLOBALS['ecs']->table('supplier')." SET commission_money=commission_money+".$order['money']." WHERE supplier_id=".$order['supplier_id']);
    if ($GLOBALS['db'] ->errno() == 0)
    {
        $url = 'supplier_cash_order.php?act=query&' . str_replace('act=remove_order', '', $_SERVER['QUERY_STRING']);
        ecs_header("Location: $url\n");
        exit;
    }
    else
    {
        make_json_error($GLOBALS['db']->errorMsg());
    }
}

//入驻商佣金提现列表
function supplier_cash_order_list()
{
	$result = get_filter();
	if ($result === false)
    {
		$filter['sort_by'] = empty($_REQUEST['sort_by']) ? ' add_time' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? ' ASC' : trim($_REQUEST['sort_order']);
		$where = (isset($_SESSION['supplier_id']) && intval($_SESSION['supplier_id'])>0) ? 'WHERE supplier_id='.intval($_SESSION['supplier_id']) : 'WHERE 1';
        /* 分页大小 */
        $filter['page'] = empty($_REQUEST['page']) || (intval($_REQUEST['page']) <= 0) ? 1 : intval($_REQUEST['page']);

        if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0)
        {
            $filter['page_size'] = intval($_REQUEST['page_size']);
        }
        elseif (isset($_COOKIE['ECSCP']['page_size']) && intval($_COOKIE['ECSCP']['page_size']) > 0)
        {
            $filter['page_size'] = intval($_COOKIE['ECSCP']['page_size']);
        }
        else
        {
            $filter['page_size'] = 15;
        }
        /* 记录总数 */
        $sql="select count(*) from ".$GLOBALS['ecs']->table('supplier_cash_order').' '.$where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);
        $filter['page_count']     = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;
        /* 查询 */
        $sql="select * from ".$GLOBALS['ecs']->table('supplier_cash_order').' '.$where." ORDER BY id DESC";
        $sql .= " LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ", " . $filter['page_size'] . " ";
        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }
    $statuslist=[0=>'未处理',1=>'正在付款中',2=>'付款成功',3=>'取消'];
	$list=array();
	$res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchRow($res))
	{
        $row['pay_time'] = local_date('Y-m-d H:i:s', $row['pay_time']);
		$row['add_time'] = local_date('Y-m-d H:i:s', $row['add_time']);
		$row['status_txt']=isset($statuslist[$row['status']])?$statuslist[$row['status']]:'';
		$list[]=$row;
	}
    $arr = array('result' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}



/**************************************生成提现单号******************************/
function get_cash_order_sn()
{
    /* 选择一个随机的方案 */
    mt_srand((double) microtime() * 1000000);
    return date('YmdHi') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
}
?>