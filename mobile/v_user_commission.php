<?php

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');


$pagesize = $_REQUEST['pagesize']?$_REQUEST['pagesize']:'10'; // 默认显示行数
$page = $_REQUEST['page']?intval($_REQUEST['page']):'1'; // 默认显示页数
    
$page = ($page < 1)? '1' :$page;

$begin = ($page-1)*$pagesize;

// 总订单数 
$count = $db->getOne('select count(distinct order_id) from ecs_affiliate_log  where user_id="'.$_SESSION['user_id'].'" and (money <> 0 or sale_point <> 0)');

// 这次查询的订单 单号
$infos = $db->getALL('select distinct order_id from ecs_affiliate_log  where user_id="'.$_SESSION['user_id'].'" and (money <> 0 or sale_point <> 0) order by time desc limit '.$begin.','.$pagesize);


$new_info = []; // 返回一个订单一个分成的
// 查询 订单的信息
foreach ($infos as $key => $value) {


    $info= $db->getRow('select a.order_id,a.separate_type,sum(a.money) as money,a.time,sum(a.sale_point) as sale_point,o.goods_amount,u.headimg,u.user_name 
    from ecs_affiliate_log as a 
    left join ecs_order_info as o on o.order_id=a.order_id 
    left join ecs_users as u on u.user_id=o.user_id 
    where a.user_id="'.$_SESSION['user_id'].'" and a.order_id='.$value['order_id'].' order by time desc');


    $info['time'] = date('Y-m-d H:i:s',$info['time']); // 时间的更换格式


    $new_info[] =$info; 
}

$totalpage = ceil($count/$pagesize); // 总页数

$smarty->assign('pages',$page);
$smarty->assign('next',$page+1);
$smarty->assign('prev',$page-1);
$smarty->assign('totalpage',$totalpage);

$smarty->assign('new_info',$new_info);
$smarty->assign('chongfu',$congfu);


/* 显示模板 */
assign_query_info();
$smarty->display('v_user_commission.dwt');