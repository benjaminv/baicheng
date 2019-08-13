<?php
define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

//error_reporting(E_ALL);


//进入页面 获取技术的相关信息 


	$pagesize = isset($_REQUEST['pagesize'])?$_REQUEST['pagesize']:'15';
	$nowpage = isset($_REQUEST['nowpage'])?$_REQUEST['nowpage']:'1';

    if($nowpage < 1){

    	$nowpage = 1;
    }

    if($pagesize<1){

    	$pagesize =1;
    }
    $begin = ($nowpage-1)*$pagesize;

	$count = $db->getOne('select count(id) from ecs_technican');

    $info = $db->getAll('select * from ecs_technican order by id desc limit '.$begin.','.$pagesize);

    foreach ($info as $key => $value) {
    	
    	$info[$key]['add_time'] = date('Y-m-d H:i:s',$value['add_time']);

    	$info[$key]['update_time'] = date('Y-m-d H:i:s',$value['update_time']);
    }
    $lastpage = ceil($count/$pagesize);

    $smarty->assign('count', $count); // 记录总数
    $smarty->assign('nowpage', $nowpage);
    $smarty->assign('prepage', $nowpage-1);
    $smarty->assign('nextpage', $nowpage+1);
    $smarty->assign('pagesize', $pagesize);
    $smarty->assign('lastpage', $lastpage);
    $smarty->assign('info',$info); // 详细信息





$smarty->display('supplier_jishi.htm');