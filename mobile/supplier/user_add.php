<?php
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');

$pagesize = trim($_REQUEST['pagesize'])?trim($_REQUEST['pagesize']):'10';
$page     = trim($_REQUEST['page'])?trim($_REQUEST['page']):'1';

// 获取 ecs_users表的总记录数
$count = $db->getOne('select count(user_id) from ecs_users where supplierId="'.$_SESSION['supplier_id'].'"');

$count_other = $db->getOne('select count(id) from ecs_user_supplier_relate left join ecs_users on ecs_user_supplier_relate.user_id=ecs_users.user_id where ecs_user_supplier_relate.supplier_id="'.$_SESSION['supplier_id'].'" and ecs_users.supplierId !="'.$_SESSION['supplier_id'].'"');


// 获取分页的三种情况 
$begin = $pagesize*($page-1); 
if( $count >= $pagesize*$page){
   
	$user_infos = $db->getAll('select headimg,user_name,pay_points,mobile_phone,is_old from ecs_users where supplierId="'.$_SESSION['supplier_id'].'" limit '.$begin.','.$pagesize);

	$user_info = [];

    foreach ($user_infos as $value) {

    	$value['differ'] = '1'; // 代表同一店铺
		$user_info[] = $value;
    }

   
}elseif($pagesize*($page-1)< $count &&  $count < $pagesize*$page ){
 
	$user_info1 = $db->getAll('select headimg,user_name,pay_points,mobile_phone,is_old from ecs_users where supplierId="'.$_SESSION['supplier_id'].'" limit '.$begin.','.$pagesize);

    $len = $pagesize*$page - $count; // 需要查询的几条数据
   
	$user_info2 = $db->getAll('select headimg,user_name,mobile_phone from ecs_users left join ecs_user_supplier_relate on ecs_users.user_id=ecs_user_supplier_relate.user_id where ecs_user_supplier_relate.supplier_id="'.$_SESSION['supplier_id'].'" and ecs_users.supplierId !="'.$_SESSION['supplier_id'].'" limit 0,'.$len);

	$user_info = [];
	foreach ($user_info1 as  $value) {
        $value['differ'] = '1'; // 代表同一店铺
		$user_info[] = $value;

	}

	foreach ($user_info2 as  $value) {
		$value['differ'] = '2'; // 代表异店店铺
		$user_info[] = $value;

	}



}elseif($pagesize*($page-1)>=$count){

	$begin = ($page-ceil($count/$pagesize) -1)*$pagesize + ($pagesize-($count%$pagesize));

	$user_infos = $db->getAll('select headimg,user_name,mobile_phone from ecs_users left join ecs_user_supplier_relate on ecs_users.user_id=ecs_user_supplier_relate.user_id where ecs_user_supplier_relate.supplier_id="'.$_SESSION['supplier_id'].'" and ecs_users.supplierId !="'.$_SESSION['supplier_id'].'" limit '.$begin.','.$pagesize);

    $user_info = [];

    foreach ($user_infos as $value) {

    	$value['differ'] = '2'; // 代表异店店铺
		$user_info[] = $value;
    }

}

$totalpage = ceil(($count+$count_other)/$pagesize);


$smarty->assign('page',$page);
$smarty->assign('next',$page+1);
$smarty->assign('prev',$page-1);
$smarty->assign('totalpage',$totalpage);
$smarty->assign('user_info',$user_info);
$smarty->display('user_add.htm');