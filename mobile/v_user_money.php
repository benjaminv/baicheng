<?php

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

// 1.0 获得用户的账户明细 和 积分操作
if($_REQUEST['act']){

	if($_REQUEST['act'] == 'look_money'){

		$action = 'user_money'; // 表明是查看金额明细

	}elseif($_REQUEST['act'] == 'look_jifen'){

		$action = 'pay_points'; // 表明是查看积分明细

	}else{

		$action = 'user_money';
		$_REQUEST['act'] ='look_money';
	}
    
    $moeny_jifen = $db->getOne('select '.$action.' from ecs_users where user_id="'.$_SESSION['user_id'].'"');

	$pagesize = $_REQUEST['pagesize']?$_REQUEST['pagesize']:'10'; // 默认显示行数
	$page = $_REQUEST['page']?intval($_REQUEST['page']):'1'; // 默认显示页数
    
    $page = ($page < 1)? '1' :$page;

	$begin = ($page-1)*$pagesize;

	$count = $db->getOne('select count(log_id) from ecs_account_log where user_id="'.$_SESSION['user_id'].'" and '.$action.' != "0"'); // 总记录数
    
    // 显示内容
	$info = $db->getAll('select '.$action.',change_time,change_desc from ecs_account_log where user_id="'.$_SESSION['user_id'].'" and '.$action.' != "0" limit '.$begin.','.$pagesize);

    // 处理数据的格式
	foreach ($info as $key => $value) {

		if($value[$action] < 0 ){

			$info[$key][$action] = abs($value[$action]);

			$info[$key]['add_dec'] = '减少'; // 增加还是减少操作

		}else{

			$info[$key]['add_dec'] = '增加';
		}
		
		$info[$key]['change_time'] = date('Y-m-d H:i:s',$value['change_time']);
		
	}

}




$totalpage = ceil($count/$pagesize); // 总页数

$smarty->assign('pages',$page);
$smarty->assign('next',$page+1);
$smarty->assign('prev',$page-1);
$smarty->assign('totalpage',$totalpage);
$smarty->assign('info',$info);
$smarty->assign('moeny_jifen',$moeny_jifen);

$smarty->assign('display_mo',$_REQUEST['act']);// 返回前端的显示信息


 /* 显示模板 */
assign_query_info();
$smarty->display('v_user_money.dwt');