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
require_once(ROOT_PATH . 'includes/lib_pay_fencheng.php');
admin_priv('push_team_commission');
$timestamp = time();

$affiliate_supplier = get_affiliate_supplier();
/*------------------------------------------------------ */
//-- 分成页
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    $logdb = get_push_team_commission();

    $smarty->assign('full_page',  1);
    $smarty->assign('ur_here', $_LANG['affiliate_ck']);
    $smarty->assign('logdb',        $logdb['logdb']);
    $smarty->assign('filter',       $logdb['filter']);
    $smarty->assign('record_count', $logdb['record_count']);
    $smarty->assign('page_count',   $logdb['page_count']);
    $smarty->assign('action_link',  array('text' => '调整地推团队佣金', 'href'=>"push_team_account_log.php?act=add"));
	$smarty->assign('supplier_list',get_supplier_list1());
    assign_query_info();
    $smarty->display('mobile_push_team_commission.htm');
}
/*------------------------------------------------------ */
//-- 分页
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $logdb = get_push_team_commission();
    $smarty->assign('logdb',        $logdb['logdb']);
    $smarty->assign('on', $separate_on);
    $smarty->assign('filter',       $logdb['filter']);
    $smarty->assign('record_count', $logdb['record_count']);
    $smarty->assign('page_count',   $logdb['page_count']);

    $sort_flag  = sort_flag($logdb['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('mobile_push_team_commission.htm'), '', array('filter' => $logdb['filter'], 'page_count' => $logdb['page_count']));
}
function get_push_team_commission()
{
    $affiliate_supplier = unserialize($GLOBALS['_CFG_MOBILE']['affiliate_supplier']);
    empty($affiliate_supplier) && $affiliate_supplier = array();
    $sqladd = '';
    if (isset($_REQUEST['status']))
    {
        $sqladd = ' AND is_separate = ' . (int)$_REQUEST['status'];
        $filter['status'] = (int)$_REQUEST['status'];
    }
    if (isset($_REQUEST['order_sn']))
    {
        $sqladd = ' AND order_sn LIKE \'%' . trim($_REQUEST['order_sn']) . '%\'';
        $filter['order_sn'] = $_REQUEST['order_sn'];
    }

    //按商品分成
    $sql="select count(*) from ".$GLOBALS['ecs']->table('push_team_commission')." where 1=1 ".$sqladd;
    $filter['record_count'] = $GLOBALS['db']->getOne($sql);
    $logdb = array();
    /* 分页大小 */
    $filter = page_and_size($filter);
    $sql="select * from ".$GLOBALS['ecs']->table('push_team_commission')." where 1=1 ".$sqladd." ORDER BY id DESC".
        " LIMIT ". $filter['start'] . ",$filter[page_size]";
	$query = $GLOBALS['db']->query($sql);
    while ($rt = $GLOBALS['db']->fetch_array($query))
    {
        $rt['time']=date('Y-m-d H:i:s',$rt['createtime']);
		$logdb[] = $rt;
	}
	//print_r($logdb);die();
    $arr = array('logdb' => $logdb, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}


function get_affiliate_supplier()
{
    $sql = "select value from " . $GLOBALS['ecs']->table('ecsmart_shop_config') ."  WHERE code = 'affiliate_supplier'";
    $config = $GLOBALS['db']->getOne($sql);
    $config = unserialize($config);
    empty($config) && $config = array();
    return $config;
}

//获取供货商名称
function get_supplier1($supplier_id)
{
	$sql = "SELECT supplier_name FROM " . $GLOBALS['ecs']->table('supplier') . " WHERE supplier_id = '$supplier_id'";
	return $GLOBALS['db']->getOne($sql);
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