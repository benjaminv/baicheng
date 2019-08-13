<?php
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/lib_supplier_common_wap.php');
$act = empty($_REQUEST['act'])?'default':trim($_REQUEST['act']);

$action = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : 'default';
/* 路由 */
$function_name = 'action_' . $action;
if(! function_exists($function_name))
{
	$function_name = "action_default";
}
call_user_func($function_name);

function action_default(){
	die('请访问正确的地址');
}

function action_opus(){
	global $smarty;
	$data = opus_list();
    $opus = $data['opus'];
    $filter = $data['filter'];
    $smarty->assign('opus',$opus);
    $smarty->assign('filter',$filter);
    _wap_assign_header_info('我的作品');
    _wap_assign_footer_order_info();
    _wap_display_page('technican_opus_list.htm');
}

function action_opus_add(){
	global $smarty;
	
    _wap_assign_header_info('新增作品');
    _wap_assign_footer_order_info();
    _wap_display_page('technican_opus_info.htm');
}

function action_opus_save(){
	global $ecs,$db,$_CFG;
	//技师的作品上传
	include_once ('includes/cls_json.php');
	include_once ('includes/cls_image.php');
	$json = new JSON();
	$save_data = array(
		'opus_title' =>trim($_POST['opus_title']),
		'opus_desc' =>trim($_POST['opus_desc']),
		'user_id'	=>$_SESSION['supplier_user_id'],
		'supplier_id'	=>$_SESSION['supplier_id'],
	); 
	if(empty($save_data['opus_title'])){
		die($json->encode(array('code'=>400,'msg'=>'作品标题不能为空')));
	}
	if(empty($save_data['opus_desc'])){
		die($json->encode(array('code'=>400,'msg'=>'作品描述不能为空')));
	}

	$image = new cls_image();
	$opus_img = (isset($_POST['opus_img']) && !empty($_POST['opus_img'])) ? $_POST['opus_img'] : array();
	//$upload_size_limit = $_CFG['upload_size_limit'] == '-1' ? ini_get('upload_max_filesize') : $_CFG['upload_size_limit'];
	foreach($_FILES['file']['name'] as $k=>$v){
		if(isset($_FILES['file']['name'][$k]) && !empty($_FILES['file']['name'][$k])){
			//if($_FILES['file']['size'][$k] / 1024 > $upload_size_limit)
			//{
				//die($json->encode(array('code'=>400,'msg'=>sprintf('文件大小超过了限制 %dKB',$upload_size_limit))));
			//}
			$temp = array(
				'name'=>$_FILES['file']['name'][$k],
				'type'=>$_FILES['file']['type'][$k],
				'tmp_name'=>$_FILES['file']['tmp_name'][$k],
				'error'=>$_FILES['file']['error'][$k],
				'size'=>$_FILES['file']['size'][$k]
			);
			$logosrc = $image->upload_image($temp, 'supplier_opus');
			if ($logosrc === false)
			{
				die($json->encode(array('code'=>400,'msg'=>'作品上传失败')));
			}
			$thumb = $image->make_thumb($logosrc, 600, 0,ROOT_PATH . 'data/supplier_opus/'.date('Y-m-d').'/');
			@unlink($logosrc);
			$opus_img[] = trim($thumb);
			unset($temp,$logosrc);
		}
	}
	$save_data['opus_img'] = implode(',',$opus_img);
	if(empty($save_data['opus_img'])){
		die($json->encode(array('code'=>400,'msg'=>'作品不能为空')));
	}

	if($_POST['id']){
		$save_data['update_time'] = gmtime();
		if($db->autoExecute($ecs->table('technican_opus'), $save_data, 'UPDATE', 'id = "'.intval($_POST['id']).'"')){
			die($json->encode(array('code'=>200,'msg'=>'作品修改成功')));
		}else{
			die($json->encode(array('code'=>400,'msg'=>'服务器异常,请刷新页面重新操作!')));
		}
	}else{
		$save_data['add_time'] = gmtime();
		if($db->autoExecute($ecs->table('technican_opus'), $save_data, 'INSERT', '', 'SILENT')){
			die($json->encode(array('code'=>200,'msg'=>'新增作品成功')));
		}else{
			die($json->encode(array('code'=>400,'msg'=>'服务器异常,请刷新页面重新操作!')));
		}
	}

}

function action_opus_edit(){
	global $ecs,$db,$smarty;
	$opus_info = $db->getRow("SELECT * FROM ".$ecs->table('technican_opus')." WHERE id=".intval($_REQUEST['id']));
	if(!empty($opus_info['opus_img'])){
		$opus_info['opus_img'] = explode(',',$opus_info['opus_img']);
	}
	$smarty->assign('opus_info',$opus_info);
	_wap_assign_header_info('作品编辑');
    _wap_assign_footer_order_info();
    _wap_display_page('technican_opus_info.htm');
}

function action_opus_del(){
	global $ecs,$db;
	include_once ('includes/cls_json.php');
	$json = new JSON();
	$id = $_POST['id'] ? intval($_POST['id']) : 0;
	if(empty($id)){
		die($json->encode(array('code'=>400,'msg'=>'参数出错')));
	}
	if($db->query("DELETE FROM ".$ecs->table('technican_opus')." where id=".$id)){
		die($json->encode(array('code'=>200,'msg'=>'操作成功!')));
	}else{
		die($json->encode(array('code'=>400,'msg'=>'操作失败,请刷新页面后重试!')));
	}
}

function action_schedul(){
	global $smarty;
	//按照日期分组查询已经排掉的行程
	$result = schedul_data();
	$smarty->assign('technican_order',json_encode($result['technican_order']));
	$smarty->assign('technican_schedul',json_encode($result['technican_schedul']));
	$smarty->assign('order_schedul',json_encode($result['order_schedul']));
    _wap_assign_header_info('我的行程');
    _wap_assign_footer_order_info();
    _wap_display_page('technican_schedul.htm');
}

//技师行程更新
function action_schedul_update(){
	global $ecs,$db,$_CFG;
	include_once ('includes/cls_json.php');
	$json = new JSON();
	$user_id = $_SESSION['supplier_user_id'];
	$supplier_id = $_SESSION['supplier_id'];
	$mode = intval($_POST['mode']);
	$datepick = trim($_POST['datepick']);
	$time_pick = trim($_POST['timepick']);
	unset($_POST);
	$info = $db->getRow('SELECT id,timepick FROM '.$ecs->table('technican_schedul').' WHERE user_id='.$user_id.' AND supplier_id = '.$supplier_id.' AND datetime="'.$datepick.'"'.' AND timepick="'.$time_pick.'"');
	if($mode == 1){
		if(!empty($info)){
			if($db->autoExecute($ecs->table('technican_schedul'), array('schedul_status'=>1,'update_time'=>gmtime()), 'UPDATE', 'id = "'.intval($info['id']).'"')){
				die($json->encode(array('mode'=>200,'msg'=>'操作成功!')));
			}else{
				die($json->encode(array('mode'=>400,'msg'=>'操作失败,请刷新页面!')));
			}
		}else{
			//新增行程
			$save_data = array(
				'datetime'=>$datepick,
				'timepick'=>$time_pick,
				'user_id'=>$user_id,
				'supplier_id'=>$supplier_id,
				'update_time'=>gmtime()
			);
			if($db->autoExecute($ecs->table('technican_schedul'), $save_data, 'INSERT', '', 'SILENT')){
				die($json->encode(array('mode'=>200,'msg'=>'操作成功!')));
			}else{
				die($json->encode(array('mode'=>400,'msg'=>'操作失败,请刷新页面!')));
			}
		}
	}else{
		if(empty($info)){
			die($json->encode(array('mode'=>400,'msg'=>'不存在的行程,请刷新页面!')));
		}else{
			if($db->autoExecute($ecs->table('technican_schedul'), array('schedul_status'=>0,'update_time'=>gmtime()), 'UPDATE', 'id = "'.intval($info['id']).'"')){
				die($json->encode(array('mode'=>200,'msg'=>'操作成功!')));
			}else{
				die($json->encode(array('mode'=>400,'msg'=>'操作失败,请刷新页面!')));
			}
		}
	}
}

function schedul_data(){
	global $ecs,$db;
	$list = $db->getAll('SELECT datetime,schedul,group_concat(timepick) as time_pick FROM '.$ecs->table('technican_schedul').' WHERE user_id='.$_SESSION['supplier_user_id'].'  AND datetime>="'.date('Y-m-d').'" GROUP BY datetime,schedul');
	
	$order_schedul = $technican_schedul = $orders = $technican_order = array();
	foreach($list as $v){
		//0为技师自己安排的行程，1为订单行程
		if($v['schedul'] == 0){
			$technican_schedul[$v['datetime']] = explode(',',$v['time_pick']);
		}else{
			$order_schedul[$v['datetime']] = explode(',',$v['time_pick']);
		}
	}
	
	//返回所有订单行程的列表
	$order = $db->getAll('SELECT datetime,schedul,timepick,order_id FROM '.$ecs->table('technican_schedul').' WHERE user_id='.$_SESSION['supplier_user_id'].' AND schedul=1 AND schedul_status=1 AND datetime>="'.date('Y-m-d').'"');
	foreach($order as $k=>$v){
		$orderinfo = $db->getRow('SELECT a.order_sn,a.province,a.city,a.district,a.address,a.consignee,a.mobile,a.shipping_status,b.user_name,a.schedul_time FROM '.$ecs->table('order_info').' a LEFT JOIN '.$ecs->table('users').' b ON a.user_id=b.user_id WHERE a.order_id='.$v['order_id']);
		
		if($orderinfo['district']){
			$orderinfo['address'] = get_region_info($orderinfo['district']).$orderinfo['address'];
		}
		if($orderinfo['city']){
			$orderinfo['address'] = get_region_info($orderinfo['city']).$orderinfo['address'];
		}
		if($orderinfo['province']){
			$orderinfo['address'] = get_region_info($orderinfo['province']).$orderinfo['address'];
		}
		

		$technican_order[$v['datetime']][] = array(
				'order_id'=>$v['order_id'],
				'order_sn'=>$orderinfo['order_sn'],
				'consignee'=>$orderinfo['consignee'],
				'mobile'=>$orderinfo['mobile'],
				'address'=>$orderinfo['address'],
				'timepick'=>$v['timepick'],
				'status'=> $orderinfo['shipping_status'],
				'user_name'=> $orderinfo['user_name'],
		);
	}
	return array('technican_schedul'=>$technican_schedul,'order_schedul'=>$order_schedul,'technican_order'=>$technican_order);
}

function opus_list()
{
    global $ecs,$db;
    $result = get_filter($param_str);
    if($result === false)
    {
        $where = ' WHERE user_id='.$_SESSION['supplier_user_id'];
        $sql = 'SELECT COUNT(*) FROM '.$ecs->table('technican_opus').$where;
        $filter['record_count'] = $db->getOne($sql);
        $filter['page'] = empty($_REQUEST['page']) || (intval($_REQUEST['page']) <= 0) ? 1 : intval($_REQUEST['page']);

        if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0)
        {
            $filter['page_size'] = intval($_REQUEST['page_size']);
        }
        elseif (isset($_COOKIE['ECSCP']['page_size']) && intval($_COOKIE['ECSCP']['page_size']) > 0)
        {
            $filter['page_size'] = intval($_COOKIE['ECSCP']['page_size']);
        }
        else
        {
            $filter['page_size'] = 15;
        }
        $filter = page_and_size($filter);
        $limit = ' LIMIT '. $filter['start'] .',' . $filter['page_size'];
        $sql = 'SELECT * FROM '.$ecs->table('technican_opus').$where.$limit;
        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }

    $list = $db->getAll($sql);
    foreach($list as $k=>$v){
		$opus_img = explode(',',$v['opus_img']);
		$list[$k]['opus_logo'] = $opus_img[0];
	}
    $arr = array('opus' => $list, 'filter' => $filter);
    return $arr;
}
