<?php


define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(dirname(__FILE__) . '/includes/lib_v_user.php');

if ((DEBUG_MODE & 2) != 2)
{
    $smarty->caching = true;
}

if($_CFG['is_distrib'] == 0)
{
	show_message('没有开启微信分销服务！','返回首页','index.php');
}

if($_SESSION['user_id'] == 0)
{
	ecs_header("Location: ./\n");
    exit;
}

$is_distribor = is_distribor($_SESSION['user_id']);
if($is_distribor != 1)
{
	show_message('您还不是分销商！','去首页','index.php');
	exit;
}


if (!$smarty->is_cached('v_user.dwt', $cache_id))
{
    assign_template();

    $position = assign_ur_here();
    $smarty->assign('page_title',      $position['title']);    // 页面标题
    $smarty->assign('ur_here',         $position['ur_here']);  // 当前位置

    /* meta information */
    $smarty->assign('keywords',        htmlspecialchars($_CFG['shop_keywords']));
    $smarty->assign('description',     htmlspecialchars($_CFG['shop_desc']));

	$user_info = get_user_info_by_user_id($_SESSION['user_id']); //用户信息，包括昵称和头像
	$user_money = get_user_money_by_user_id($_SESSION['user_id']); //用户余额
	$split_money = get_split_money_by_user_id($_SESSION['user_id']); //分成总金额
	$user_info['parent_id'] = $db->getOne("select parent_id from ".$ecs->table('users')." where user_id=".$_SESSION['user_id']);
	$user_info['parent_name'] = $db->getOne("select user_name from ".$ecs->table('users')." where user_id=".$user_info['parent_id']);
	$points = $db->getOne('select pay_points from ecs_users where user_id='.$_SESSION['user_id']);
    
    $last_end_time = strtotime(date("Y-m-d 23:59:59", strtotime(-date('d').'day'))); // 上月时间截止的时间戳
    $last_begin_time = strtotime(date('Y-m-01 00:00:00',strtotime('-1 month'))); // 上月时间起始的时间戳

    // 本月佣金结算
	$this_month = $db->getOne('select sum(user_money)  from ecs_account_log where user_id="'.$_SESSION['user_id'].'" and user_money > 0 and change_time >'.$last_end_time);
    // 上月佣金结算
	$last_month = $db->getOne('select sum(user_money) from ecs_account_log where user_id="'.$_SESSION['user_id'].'" and user_money > 0 and change_time >'.$last_begin_time.'  and change_time <='.$last_end_time);
   
    $smarty->assign('this_month',$this_month);
    $smarty->assign('last_month',$last_month);
	$smarty->assign('user_info',$user_info);
	$smarty->assign('info',get_user_info($_SESSION['user_id'])); 
	$smarty->assign('user_money',$user_money);
	$smarty->assign('points',$points);
	$smarty->assign('split_money',$split_money);
	$smarty->assign('user_id',$_SESSION['user_id']);
	$smarty->assign('total_split_money',my_get_total_money($_SESSION['user_id'],1));
	$yes_distrib_count = get_count_distrib_order_by_user_id($_SESSION['user_id'],1); //已分成订单数量
	$no_distrib_count = get_count_distrib_order_by_user_id($_SESSION['user_id'],0);	//未分成订单数量
	$cancel_distrib_count = get_count_distrib_order_by_user_id($_SESSION['user_id'],2);	//撤销分成订单数量
	$total_order_count = $yes_distrib_count + $no_distrib_count + $cancel_distrib_count;
	$smarty->assign('total_order_count',$total_order_count);
    /* 页面中的动态内容 */
    assign_dynamic('v_user');
}

$smarty->display('v_user.dwt', $cache_id);

