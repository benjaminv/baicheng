<?php

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
include_once (ROOT_PATH . 'includes/lib_clips.php');

$pagesize = isset($_REQUEST['pagesize'])? intval($_REQUEST['pagesize']):'1'; // 默认显示行数
$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
$page = ($page < 1)? '1' :$page;

$begin = ($page-1)*$pagesize;


$where = ' where supplier_id='.$_SESSION['supplier_id'];

$total_count = $db->getOne('select count(id) from ecs_supplier_cash_order '.$where);

$info = $db->getAll(' select * from ecs_supplier_cash_order '.$where.' order by id desc limit '.$begin.','.$pagesize);

//0=>'未处理',1=>'正在付款中',2=>'付款成功',3=>'取消';
foreach ($info as $key => $value) {
  
    if($value['add_time']){

        $info[$key]['add_time'] = date('Y-m-d H:i:s',$value['add_time']);
    }

    if($value['pay_time']){

        $info[$key]['pay_time'] = date('Y-m-d H:i:s',$value['pay_time']);
    }

    switch ($value['status']) {
        case '0':
            $info[$key]['status'] = '未处理';
            break;
        case '1':
            $info[$key]['status'] = '正在付款中';
            break;
        case '2':
            $info[$key]['status'] = '付款成功';
            break;
        case '3':
            $info[$key]['status'] = '取消';
            break; 
    }

}


$money=$GLOBALS['db']->getOne("SELECT commission_money FROM ".$GLOBALS['ecs']->table('supplier')." as s WHERE supplier_id=".$_SESSION['supplier_id']);

$totalpage = ceil($total_count/$pagesize); // 总页数

$smarty->assign('page',$page);
$smarty->assign('next',$page+1);
$smarty->assign('prev',$page-1);
$smarty->assign('totalpage',$totalpage);

$smarty->assign('account_log', $info);
$smarty->assign('user_money', $money);
$smarty->display('application_log.htm');


