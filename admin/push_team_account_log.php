<?php

/**
 * ECSHOP 管理中心地推团队佣金变动记录
 * ============================================================================
 * 版权所有 2017-2020 月梦网络，并保留所有权利。
 * 月梦网络: http://dm299.taobao.com  开发QQ:124861234  禁止倒卖 一经发现停止任何服务
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: Freely $
 * $Id: supplier_account_log.php 2019-04-15 $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
include_once(ROOT_PATH . 'includes/lib_order.php');

/*------------------------------------------------------ */
//-- 办事处列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    $push_team_commission_money = $GLOBALS['db']->getOne("select value from ".$GLOBALS['ecs']->table('ecsmart_shop_config',1)." where code = 'push_team_commission_money' ");
    if(empty($push_team_commission_money)){$push_team_commission_money=0;}
    $smarty->assign('push_team_commission_money',      $push_team_commission_money);
    $smarty->assign('ur_here',      '地推团队佣金变动明细');
    $smarty->assign('action_link',  array('text' =>'调节地推团队佣金', 'href' => 'supplier_account_log.php?act=add&supplier_id=' . $supplier_id));
    $smarty->assign('full_page',    1);

    $account_list = get_supplieraccountlist($supplier_id);
    $smarty->assign('account_list', $account_list['account']);
    $smarty->assign('filter',       $account_list['filter']);
    $smarty->assign('record_count', $account_list['record_count']);
    $smarty->assign('page_count',   $account_list['page_count']);
    assign_query_info();
    $smarty->display('push_team_account_list.htm');
}

/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $push_team_commission_money = $GLOBALS['db']->getOne("select value from ".$GLOBALS['ecs']->table('ecsmart_shop_config',1)." where code = 'push_team_commission_money' ");
    if(empty($push_team_commission_money)){$push_team_commission_money=0;}
    $smarty->assign('push_team_commission_money',      $push_team_commission_money);

    $account_list = get_accountlist();
    $smarty->assign('account_list', $account_list['account']);
    $smarty->assign('filter',       $account_list['filter']);
    $smarty->assign('record_count', $account_list['record_count']);
    $smarty->assign('page_count',   $account_list['page_count']);

    make_json_result($smarty->fetch('push_team_account_list.htm'), '',
        array('filter' => $account_list['filter'], 'page_count' => $account_list['page_count']));
}

/*
    佣金变动
*/
elseif ($_REQUEST['act'] == 'add')
{
    $push_team_commission_money = $GLOBALS['db']->getOne("select value from ".$GLOBALS['ecs']->table('ecsmart_shop_config',1)." where code = 'push_team_commission_money' ");
    if(empty($push_team_commission_money)){$push_team_commission_money=0;}

    /* 显示模板 */
    $smarty->assign('push_team_commission_money',      $push_team_commission_money);
    $smarty->assign('ur_here', '调节地推团队佣金');
    $smarty->assign('action_link', array('href' => 'push_team_account_log.php?act=list', 'text' =>'地推团队佣金变动明细'));
    assign_query_info();
    $smarty->display('push_team_account_info.htm');
}
/*------------------------------------------------------ */
//-- 提交添加、编辑办事处
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update')
{
    //$push_team_commission_money = $GLOBALS['db']->getOne("select value from ".$GLOBALS['ecs']->table('ecsmart_shop_config',1)." where code = 'push_team_commission_money' ");
    //if(empty($push_team_commission_money)){$push_team_commission_money=0;}

    /* 提交值 */
    $change_desc    = sub_str($_POST['change_desc'], 255, false);
    $commission_money     = floatval($_POST['add_sub_commission_money']) * abs(floatval($_POST['commission_money']));
    if ($commission_money == 0)
    {
        sys_msg('地推团队佣金没有变动');
    }
    /* 保存 */
    push_team_log_account_change($commission_money, $change_desc, PT_ADJUSTING);
    /* 提示信息 */
    $links = array(
        array('href' => 'push_team_account_log.php?act=list', 'text' => '地推团队佣金变动明细')
    );
    sys_msg('记录地推团队佣金变动成功', 0, $links);
}

/**
 * 取得帐户明细
 * @param   int     $supplier_id    店铺ID
 * @return  array
 */
function get_supplieraccountlist($supplier_id)
{
    /* 检查参数 */
    $where = " WHERE 1 ";

    /* 初始化分页参数 */
    $filter = array();

    /* 查询记录总数，计算分页数 */
    $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('push_team_account_log') . $where;
    $filter['record_count'] = $GLOBALS['db']->getOne($sql);
    $filter = page_and_size($filter);

    /* 查询记录 */
    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('push_team_account_log') . $where .
            " ORDER BY log_id DESC";
    $res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);

    $arr = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $row['change_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['change_time']);
        $arr[] = $row;
    }

    return array('account' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

?>