<?php

/**
 * ECSHOP 程序说明
 * ===========================================================
 * * 版权所有 2017-2020 月梦网络，并保留所有权利。
 * 月梦网络: http://www.dm299.com  开发QQ:124861234  禁止倒卖 一经发现停止任何服务；
 * ----------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ==========================================================
 * $Author: derek $
 * $Id: mobile_affiliate_ck.php 17217 2011-01-19 06:29:08Z derek $
 */

define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'mobile/includes/lib_pay_fencheng.php');
admin_priv('affiliate_supplier_ck');
$timestamp = time();

$affiliate_supplier = get_affiliate_supplier();
/*------------------------------------------------------ */
//-- 分成页
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    $logdb = get_affiliate_supplier_ck();

    $smarty->assign('full_page',  1);
    $smarty->assign('ur_here', $_LANG['affiliate_supplier_ck']);
    $smarty->assign('logdb',        $logdb['logdb']);
    $smarty->assign('filter',       $logdb['filter']);
    $smarty->assign('record_count', $logdb['record_count']);
    $smarty->assign('page_count',   $logdb['page_count']);
    if (!empty($_GET['supplier_id']))
    {
        $smarty->assign('action_link',  array('text' =>'调节会员帐户', 'href'=>"supplier_account_log.php?act=add&supplier_id=$_GET[supplier_id]"));
    }
	$smarty->assign('supplier_list',get_supplier_list1());
    assign_query_info();
    $smarty->display('mobile_affiliate_supplier_ck_list.htm');
}
/*------------------------------------------------------ */
//-- 分页
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $logdb = get_affiliate_supplier_ck();
    $smarty->assign('logdb',        $logdb['logdb']);
    $smarty->assign('on', $separate_on);
    $smarty->assign('filter',       $logdb['filter']);
    $smarty->assign('record_count', $logdb['record_count']);
    $smarty->assign('page_count',   $logdb['page_count']);

    $sort_flag  = sort_flag($logdb['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('mobile_affiliate_ck_list.htm'), '', array('filter' => $logdb['filter'], 'page_count' => $logdb['page_count']));
}
elseif($_REQUEST['act'] == 'separate'){
	$id = intval($_REQUEST['oid']);
	supplier_separate($id);
	$links[] = array('text' => '操作成功', 'href' => 'mobile_affiliate_supplier_ck.php?act=list');
    sys_msg('操作成功', 0 ,$links);
}
elseif($_REQUEST['act'] == 'del'){
	$id = intval($_REQUEST['oid']);
	$sql = "UPDATE " . $GLOBALS['ecs']->table('supplier_commission_log') .
               " SET status = 3" .
               " WHERE id = '$id'";
    $db->query($sql);

	$links[] = array('text' => '取消成功', 'href' => 'mobile_affiliate_supplier_ck.php?act=list');
    sys_msg('取消成功', 0 ,$links);
}
elseif($_REQUEST['act'] == 'rollback'){
	$id = intval($_REQUEST['oid']);
	supplier_rollback($id);
	$links[] = array('text' => '操作成功', 'href' => 'mobile_affiliate_supplier_ck.php?act=list');
    sys_msg('操作成功', 0 ,$links);
}


function get_affiliate_supplier_ck()
{

    $affiliate_supplier = unserialize($GLOBALS['_CFG_MOBILE']['affiliate_supplier']);
    empty($affiliate_supplier) && $affiliate_supplier = array();

    $sqladd = '';
    
    if (isset($_REQUEST['order_sn']))
    {
        $sqladd = ' AND order_sn LIKE \'%' . trim($_REQUEST['order_sn']) . '%\'';
        $filter['order_sn'] = $_REQUEST['order_sn'];
    }
    if (isset($_GET['auid']))
    {
        $sqladd = ' AND user_id=' . $_GET['auid'];
    }
    //print_r($GLOBALS['_CFG_MOBILE']);die();
    if(isset($_REQUEST['supplier_id']))
    {
        $sqladd = ' AND supplier_id = ' . $_REQUEST['supplier_id'];
    }
   // print_r($sqladd);die();
    //按商品分成
    $sql="select count(*) from ".$GLOBALS['ecs']->table('supplier_commission_log')." where 1=1 ".$sqladd;
    $filter['record_count'] = $GLOBALS['db']->getOne($sql);
    $logdb = array();
    /* 分页大小 */
    $filter = page_and_size($filter);
    $sql="select * from ".$GLOBALS['ecs']->table('supplier_commission_log')." where 1=1 ".$sqladd." ORDER BY id DESC".
        " LIMIT ". $filter['start'] . ",$filter[page_size]";
	$query = $GLOBALS['db']->query($sql);
    while ($rt = $GLOBALS['db']->fetch_array($query))
    {
		$logdb[] = $rt;
	}
	//print_r($logdb);die();
    $arr = array('logdb' => $logdb, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}

//获取某一个订单的分成金额
function get_split_money_by_orderid1($order_id)
{
	 if($GLOBALS['_CFG_MOBILE']['distrib_type'] == 0)
	 {
		 $total_fee = " (goods_amount - discount + tax + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee) AS total_money";
		 //按订单分成
		 $sql = "SELECT " . $total_fee . " FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE order_id = '$order_id'";
		 $total_fee = $GLOBALS['db']->getOne($sql);
		 $split_money = $total_fee*($GLOBALS['_CFG_MOBILE']['distrib_percent']/100);
	 }
	 else
	 {
		//按商品分成
	 	$sql = "SELECT sum(split_money*goods_number) FROM " . $GLOBALS['ecs']->table('order_goods') . " WHERE order_id = '$order_id'";
	 	$split_money = $GLOBALS['db']->getOne($sql);
	 }
	 if($split_money > 0)
	 {
		 return $split_money;
	 }
	 else
	 {
		 return 0;
	 }
}


function get_affiliate_supplier()
{

    $sql = "select value from " . $GLOBALS['ecs']->table('ecsmart_shop_config') ."  WHERE code = 'affiliate_supplier'";
    $config = $GLOBALS['db']->getOne($sql);
    $config = unserialize($config);
    empty($config) && $config = array();
    return $config;
}





//获取供货商列表
function get_supplier_list1()
{
    $sql = 'SELECT supplier_id,supplier_name
            FROM ' . $GLOBALS['ecs']->table('supplier') . '
            WHERE status=1
            ORDER BY supplier_name ASC';
    $res = $GLOBALS['db']->getAll($sql);

    if (!is_array($res))
    {
        $res = array();
    }

    return $res;
}
?>