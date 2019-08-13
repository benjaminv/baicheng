<?php

/**
 * ECSHOP 定期删除未付款订单
 * ===========================================================
 * 版权所有 2017-2020 月梦网络，并保留所有权利。
 * 淘宝地址: http://dm299.taobao.com  开发QQ:124861234   dm299
 * ----------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ==========================================================
 * $Author: liubo $
 * $Id: ipdel.php 17217 2011-01-19 06:29:08Z liubo $
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}
$cron_lang_www_ecshop68_com = ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/cron/order_confirm.php';
if (file_exists($cron_lang_www_ecshop68_com))
{
    global $_LANG;
    include_once($cron_lang_www_ecshop68_com);
}


/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == TRUE)
{
    $i = isset($modules) ? count($modules) : 0;

    /* 代码 */
    $modules[$i]['code']    = basename(__FILE__, '.php');

    /* 描述对应的语言项 */
    $modules[$i]['desc']    = 'order_confirm_desc';

    /* 作者 */
    $modules[$i]['author']  = 'xiaoyan';

    /* 网址 */
    $modules[$i]['website'] = '#';

    /* 版本号 */
    $modules[$i]['version'] = '1.0.0';

    /* 配置信息 */
    $modules[$i]['config']  = array(
        array('name' => 'order_confirm_day', 'type' => 'select', 'value' => '1'),
		array('name' => 'order_confirm_action', 'type' => 'select', 'value' => '2'),
    );

    return;
}
$cron['order_confirm_day'] = !empty($cron['order_confirm_day'])  ?  $cron['order_confirm_day'] : 1 ;
$confirm_time = gmtime() - $cron['order_confirm_day'] * 3600 * 24;

$cron['order_confirm_action'] = !empty($cron['order_confirm_action'])  ?  $cron['order_confirm_action'] : 'confirm' ;
//echo $cron['order_confirm_action'];

require_once(ROOT_PATH . 'includes/lib_pay_fencheng.php');
require_once(ROOT_PATH . 'includes/lib_time.php');
if($cron['order_confirm_action']=='confirm'){
    // 自动确认收货
    $okg = $GLOBALS['db']->getAll("select order_id, add_time from " . $GLOBALS['ecs']->table('order_info') . " where  shipping_status = 1 and order_status in(1,5,6)");
    $okgoods_time = $GLOBALS['db']->getOne("select value from " . $GLOBALS['ecs']->table('shop_config') . " where code='okgoods_time'");
    foreach($okg as $okg_id)
    {
        //get_pay_fencheng( $okg_id['order_id'],1);
         //gets_split_money_by_order_id( $okg_id['order_id']);
        /* 记录log */
        order_action($okg_id['order_sn'], $okg_id['order_status'], SS_RECEIVED, $okg_id['pay_status'], '系统自动确认收货');
        $okg_time = $okgoods_time - (local_date('d',gmtime()) - local_date('d',$okg_id['add_time']));
        $is_back_now = 0;
        $is_back_now = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $ecs->table('back_order') . " WHERE order_id = " . $okg_id['order_id'] . " AND status_back < 6 AND status_back != 3");
        if ($okg_time <= 0 && $is_back_now == 0)
        {
            $GLOBALS['db']->query("update " . $ecs->table('order_info') . " set shipping_status = 2, shipping_time_end = " . gmtime() . "  where order_id = " . $okg_id['order_id']);
            //log_supplier_commission($okg_id['order_id']);/*店铺佣金和地推团队处理 By Freely*/
			//确认收货发放分成 edit yhy 2019/6/13
			distrib_separate($okg_id['order_id']);
			supplier_separate_by_orderid($okg_id['order_id']);
			
        }

    }
}


?>