<?php

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

$pagesize = $_REQUEST['pagesize']?$_REQUEST['pagesize']:'10'; // 默认显示行数
$page = $_REQUEST['page']?intval($_REQUEST['page']):'1'; // 默认显示页数
    
$page = ($page < 1)? '1' :$page;

$begin = ($page-1)*$pagesize;

// 1.0 查看一级粉丝的订单信息
if($_REQUEST['act'] == 'first_level'){
    // 一级粉丝的总数
	$count = $db->getOne('select count(user_id) from ecs_users where parent_id="'.$_SESSION['user_id'].'"');// 一级粉丝总数

    // 一级粉丝的头像注册时间信息
	$info = $db->getAll('select user_id,user_name,headimg,reg_time from ecs_users where parent_id="'.$_SESSION['user_id'].'" limit '.$begin.','.$pagesize);
    
    // 处理粉丝的订单数
	$infos = get_order_info_by_user_id($info);

// 2.0 二级粉丝的信息处理
}elseif($_REQUEST['act'] == 'second_level'){
    
    // 二级粉丝的总数
    $count = $db->getOne('select count(user_id) from ecs_users where parent_id in (select user_id from ecs_users where parent_id='.$_SESSION['user_id'].')');

    // 二级粉丝头像 注册时间等信息
    $info = $db->getAll('select user_id,user_name,headimg,reg_time from ecs_users where parent_id in (select user_id from ecs_users where parent_id='.$_SESSION['user_id'].') limit '.$begin.','.$pagesize);

	// 处理粉丝的订单数
   $infos = get_order_info_by_user_id($info);
   
}else{

	exit();
}


$totalpage = ceil($count/$pagesize); // 总页数

$smarty->assign('pages',$page);
$smarty->assign('next',$page+1);
$smarty->assign('prev',$page-1);
$smarty->assign('totalpage',$totalpage);
$smarty->assign('info',$infos);

$smarty->assign('display_mo',$_REQUEST['act']);// 返回前端的显示信息


/* 显示模板 */
assign_query_info();
$smarty->display('v_user_fensi.dwt');



/*
 * 功能：根据用户id获得该用户的订单信息
 * 参数： $info(查询的用户头像注册时间等信息)
 * 返回： array
 *
 */
function get_order_info_by_user_id($info){

    foreach ($info as $key =>$val) {
		
		$info[$key]['reg_time'] = date('Y-m-d H:i:s',$val['reg_time']);
         
        // 每个一级粉丝的订单的分销信息
		$info[$key]['order'] = $GLOBALS['db']->getAll('select ecs_affiliate_log.money,sale_point from ecs_affiliate_log left join ecs_order_info on ecs_order_info.order_id=ecs_affiliate_log.order_id where ecs_order_info.user_id="'.$val['user_id'].'"');

		$info[$key]['order_mount'] = count($info[$key]['order']); // 一级粉丝的总订单数

		if($info[$key]['order_mount']){

			$info[$key]['order_money'] = '0'; // 一级粉丝订单总金额
			$info[$key]['order_jifen'] = '0'; // 一级粉丝订单总积分

			foreach ($info[$key]['order'] as $value) {

				$info[$key]['order_money'] += $value['money'];
				$info[$key]['order_jifen'] += $value['sale_point'];
			}
		}else{

			$info[$key]['order_money'] = '0';
			$info[$key]['order_jifen'] = '0';
		}
	}

	return $info;
}