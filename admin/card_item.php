<?php
/*纹绣师店铺入口*/
define('IN_ECS', true);
require (dirname(__FILE__) . '/includes/init.php');

$action = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : 'default';
/* 路由 */
$function_name = 'action_' . $action;
if(! function_exists($function_name))
{
	$function_name = "action_default";
}
call_user_func($function_name);

function action_default(){
	echo "参数出错";
}

//卡项列表
function action_list(){
	$smarty = $GLOBALS['smarty'];
	/* 检查权限 */
    admin_priv('supplier_manage');
    /* 查询 */
    $result = get_item_card_list();
	foreach($result['result'] as $k=>$v){
		$result['result'][$k]['add_time'] = local_date('Y-m-d H:i:s',$v['add_time']);
		$result['result'][$k]['update_time'] = local_date('Y-m-d H:i:s',$v['update_time']);
 	}
    ///* 模板赋值 */
    $smarty->assign('ur_here', '卡项列表'); // 当前导航
    $smarty->assign('full_page',        1); // 翻页参数
    $smarty->assign('item_card_list',    $result['result']);
    $smarty->assign('filter',       $result['filter']);
    $smarty->assign('record_count', $result['record_count']);
    $smarty->assign('page_count',   $result['page_count']);
    $smarty->assign('sort_item_id', '<img src="images/sort_desc.gif">');
	$smarty->assign('action_link', array('href' => 'card_item.php?act=add_item_card', 'text' =>'新增卡项' ));
    /* 显示模板 */
    assign_query_info();
    $smarty->display('item_card.htm');
}

function action_add_item_card(){
	$smarty = $GLOBALS['smarty'];
	$smarty->assign('form_action', 'item_card_update');
	$smarty->assign('circle_list', get_circle_list());
	assign_query_info();
    $smarty->display('item_card_info.htm');
}

function action_item_card_info(){
	$smarty = $GLOBALS['smarty'];
	$id = intval($_REQUEST['id']);
	$smarty->assign('form_action', 'item_card_update');
	$smarty->assign('circle_list', get_circle_list());
	$smarty->assign('info', get_item_card_info($id));
	assign_query_info();
    $smarty->display('item_card_info.htm');
}

function action_item_card_update(){
	
	$data = array(
		'name'			=> trim($_POST['name']),
		'circle_id'		=> intval($_POST['circle_id']),
		'status'		=> intval($_POST['status']),
		'update_time'	=> gmtime()
	);
	if(empty($data['circle_id'])){
		sys_msg('商圈不能为空', 1, array(), false);
	}
	/* 检查图片：如果有错误，检查尺寸是否超过最大值；否则，检查文件类型 */
	if(isset($_FILES['thumb']) && $_FILES['thumb']['tmp_name'] != '' && isset($_FILES['thumb']['tmp_name']) && $_FILES['thumb']['tmp_name'] != 'none'){
		if (isset($_FILES['thumb']['error'])) // php 4.2 版本才支持 error
		{
			include_once(ROOT_PATH . '/includes/cls_image.php');
			$image = new cls_image($_CFG['bgcolor']);
			// 最大上传文件大小
			$php_maxsize = ini_get('upload_max_filesize');
			$htm_maxsize = '2M';

			if ($_FILES['thumb']['error'] == 0)
			{
				if (!$image->check_img_type($_FILES['thumb']['type']))
				{
					sys_msg('非法的图片类型', 1, array(), false);
				}
			}
			elseif ($_FILES['thumb']['error'] == 1)
			{
				sys_msg(sprintf("图片文件太大了（最大值：%s），无法上传。", $php_maxsize), 1, array(), false);
			}
			elseif ($_FILES['thumb']['error'] == 2)
			{
				sys_msg(sprintf("图片文件太大了（最大值：%s），无法上传。", $htm_maxsize), 1, array(), false);
			}
		}
		$logosrc = $image->upload_image($_FILES['thumb']);
		if ($logosrc === false)
        {
            sys_msg($image->error_msg(), 1, array(), false);
        }

		$logosrc = $image->make_thumb('../'. $logosrc ,'800', 0);
		$data['thumb'] = trim($logosrc);
	}
	if($_POST['id']){
		if($GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('card_item'), $data, 'UPDATE', 'id = "'.$_POST['id'].'"')){
			$links[] = array('href' => 'card_item.php?act=list');
			sys_msg('操作成功',0,$links);
		}else{
			$links[] = array('href' => 'card_item.php?act=list');
			sys_msg('操作失败',0,$links);
		}
	}else{
		$data['add_time'] = gmtime();
		if($GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('card_item'), $data, 'INSERT')){
			$links[] = array('href' => 'card_item.php?act=list');
			sys_msg('操作成功',0,$links);
		}else{
			$links[] = array('href' => 'card_item.php?act=list');
			sys_msg('操作失败',0,$links);
		}
	}
}

function action_item_card_del(){
	$id = intval($_REQUEST['id']);
	//如果存在卡项商品和卡项项目的话 不允许删除
	$card_item_column = $GLOBALS['db']->getOne("SELECT COUNT(id) FROM ".$GLOBALS['ecs']->table('card_item_column')." WHERE item_id=".$id);
	if($card_item_column){
		$links[] = array('href' => 'card_item.php?act=list');
		sys_msg('存在卡项项目,不能删除',0,$links);
	}

	$card_item_column = $GLOBALS['db']->getOne("SELECT COUNT(id) FROM ".$GLOBALS['ecs']->table('card_item_column')." WHERE item_id=".$id);
	if($card_item_column){
		$links[] = array('href' => 'card_item.php?act=list');
		sys_msg('存在卡项项目,不能删除',0,$links);
	}
	$goods = $GLOBALS['db']->getOne("SELECT COUNT(goods_id) FROM ".$GLOBALS['ecs']->table('goods')." WHERE item_id=".$id);
	if($goods){
		$links[] = array('href' => 'card_item.php?act=list');
		sys_msg('存在卡项商品,不能删除',0,$links);
	}

	if($GLOBALS['db']->query("DELETE FROM" .$GLOBALS['ecs']->table('card_item')." WHERE id =".$id)){
		$links[] = array('href' => 'card_item.php?act=list');
		sys_msg('操作成功',0,$links);
	}else{
		$links[] = array('href' => 'card_item.php?act=list');
		sys_msg('操作失败',0,$links);
	}
}

function action_column_list(){
	$smarty = $GLOBALS['smarty'];
	/* 检查权限 */
    admin_priv('supplier_manage');
    /* 查询 */
    $result = get_item_card_column_list();
	foreach($result['result'] as $k=>$v){
		$result['result'][$k]['add_time'] = local_date('Y-m-d H:i:s',$v['add_time']);
		$result['result'][$k]['update_time'] = local_date('Y-m-d H:i:s',$v['update_time']);
 	}
    ///* 模板赋值 */
    $smarty->assign('ur_here', '卡项项目列表'); // 当前导航
    $smarty->assign('full_page',        1); // 翻页参数
    $smarty->assign('list',    $result['result']);
    $smarty->assign('filter',       $result['filter']);
    $smarty->assign('record_count', $result['record_count']);
    $smarty->assign('page_count',   $result['page_count']);
    $smarty->assign('sort_item_id', '<img src="images/sort_desc.gif">');
	$smarty->assign('action_link', array('href' => 'card_item.php?act=add_item_card_column&card_id='.intval($_REQUEST['id']), 'text' =>'新增项目' ));
	$smarty->assign('action_link2',array('href' => 'card_item.php?act=list', 'text' => '返回卡项列表'));
    /* 显示模板 */
    assign_query_info();
    $smarty->display('card_item_column.htm');
}

function action_add_item_card_column(){
	$card_id = intval($_REQUEST['card_id']);
	$smarty = $GLOBALS['smarty'];
	$smarty->assign('form_action', 'item_card_column_update');
	$smarty->assign('card_id', $card_id);
	$smarty->assign('supplier_list', get_supplier_list($card_id));
	assign_query_info();
    $smarty->display('item_card_column_info.htm');
}

function action_item_card_column_info(){
	$smarty = $GLOBALS['smarty'];
	$id = intval($_REQUEST['id']);
	$info = get_item_card_column_info($id);
	$smarty->assign('form_action', 'item_card_column_update');
	$smarty->assign('info',$info);
	$smarty->assign('card_id',$info['item_id']);
	$smarty->assign('supplier_list', get_supplier_list($info['item_id']));
	assign_query_info();
    $smarty->display('item_card_column_info.htm');
}

function action_item_card_column_update(){
	$data = array(
		'project_name'			=> trim($_POST['project_name']),
		'project_number'			=> intval($_POST['project_number']),
		'supplier_id'		=> intval($_POST['supplier_id']),
		'update_time'	=> gmtime(),
	);
	if($_POST['id']){
		if($GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('card_item_column'), $data, 'UPDATE', 'id = "'.$_POST['id'].'"')){
			$links[] = array('href' => 'card_item.php?act=column_list&id='.intval($_REQUEST['card_id']));
			sys_msg('操作成功',0,$links);
		}else{
			$links[] = array('href' => 'card_item.php?act=column_list&id='.intval($_REQUEST['card_id']));
			sys_msg('操作失败',0,$links);
		}
	}else{
		$data['item_id'] = intval($_REQUEST['card_id']);
		$data['add_time'] = gmtime();
		if($GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('card_item_column'), $data, 'INSERT')){
			$links[] = array('href' => 'card_item.php?act=column_list&id='.intval($_REQUEST['card_id']));
			sys_msg('操作成功',0,$links);
		}else{
			$links[] = array('href' => 'card_item.php?act=column_list&id='.intval($_REQUEST['card_id']));
			sys_msg('操作失败',0,$links);
		}
	}
}

function action_item_card_column_del(){
	$id = intval($_REQUEST['id']);
	$info = get_item_card_column_info($id);
	if(empty($info)){
		sys_msg('不存在的数据',0,array());
	}
	if($GLOBALS['db']->query("DELETE FROM" .$GLOBALS['ecs']->table('card_item_column')." WHERE id =".$id)){
		$links[] = array('href' => 'card_item.php?act=column_list&id='.intval($info['item_id']));
		sys_msg('操作成功',0,$links);
	}else{
		$links[] = array('href' => 'card_item.php?act=column_list&id='.intval($info['item_id']));
		sys_msg('操作失败',0,$links);
	}
}

//维护卡项商品
function action_goods(){
	
	$smarty = $GLOBALS['smarty'];
	/* 检查权限 */
    admin_priv('supplier_manage');
    /* 查询 */
    $result = get_card_item_goods();
    ///* 模板赋值 */
    $smarty->assign('ur_here', '卡项商品列表'); // 当前导航
    $smarty->assign('full_page',        1); // 翻页参数
    $smarty->assign('goods_list',    $result['result']);
    $smarty->assign('filter',       $result['filter']);
    $smarty->assign('record_count', $result['record_count']);
    $smarty->assign('page_count',   $result['page_count']);
	$smarty->assign('item_id',   intval($_REQUEST['item_id']));
    $smarty->assign('sort_item_id', '<img src="images/sort_desc.gif">');
	if(empty($result['result'])){
		$smarty->assign('action_link', array('href' => 'goods.php?act=add&goods_item=1&item_id='.intval($_REQUEST['item_id']), 'text' =>'新增卡项商品' ));
	}
	$smarty->assign('action_link2',array('href' => 'card_item.php?act=list', 'text' => '返回卡项列表'));
    /* 显示模板 */
    assign_query_info();
    $smarty->display('card_item_goods.htm');
}

function action_goods_remove(){
	require_once(ROOT_PATH . '/' . ADMIN_PATH . '/includes/lib_goods.php');
	require_once(ROOT_PATH . '/' . ADMIN_PATH. "/nusoap/nusoap.php");
	$exc = new exchange($GLOBALS['ecs']->table('goods'),  $GLOBALS['db'], 'goods_id', 'goods_name');
    $goods_id = intval($_REQUEST['goods_id']);
    $sql = 'SELECT bonus_type_id FROM ' . $GLOBALS['ecs']->table('goods') . " WHERE goods_id = $goods_id";
    $bonus_type_id =  $GLOBALS['db']->getOne($sql);
    if(!empty($bonus_type_id))
    {
        $sales_pro_list = ' [商品红包] ';
    }
    $sql = 'SELECT is_exchange FROM ' . $GLOBALS['ecs']->table('exchange_goods') . " WHERE goods_id = $goods_id";
    $is_exchange =  $GLOBALS['db']->getOne($sql);
    if(!empty($is_exchange))
    {
        $sales_pro_list .= ' [积分商城] ';
    }
    $sql = 'SELECT package_id FROM ' . $GLOBALS['ecs']->table('package_goods') . " WHERE goods_id = $goods_id";
    $package_id =  $GLOBALS['db']->getOne($sql);
    if(!empty($package_id))
    {
        $sales_pro_list .= ' [超值礼包] ';
    }
    $sql = 'SELECT act_id FROM ' . $GLOBALS['ecs']->table('goods_activity') . " WHERE goods_id = $goods_id AND act_type = 2";
    $goods_activity =  $GLOBALS['db']->getOne($sql);
    if(!empty($goods_activity))
    {
        $sales_pro_list .= ' [拍卖] ';
    }
    $sql = 'SELECT data FROM ' . $GLOBALS['ecs']->table('topic');
    $data_list = $GLOBALS['db']->getAll($sql);
    $goods_ids = '';
    foreach($data_list as $data)
    {
        $data_arr[] = explode('";', $data[data]);
        foreach($data_arr as $da)
        {
            foreach($da as $d)
            {
                $d = $d . "'";
                $goods_ids[] = getNeedBetween($d, '|', "'");
            }
        }
    }
    if(!empty($goods_ids) && in_array($goods_id, $goods_ids))
    {
        $sales_pro_list .= ' [专题] ';
    }
    if(!empty($sales_pro_list))
    {
		$link2[] = array('href' => 'card_item.php?act=goods&item_id=' . intval($_REQUEST['item_id']), 'text' => '卡项商品');
		sys_msg('此商品参加了' . $sales_pro_list . '促销活动，不能放入回收站。', 0, $link2);
        exit;
    }
    if ($exc->edit("is_delete = 1", $goods_id))
    {
        clear_cache_files();
        $goods_name = $exc->get_name($goods_id);
        admin_log(addslashes($goods_name), 'trash', 'goods'); // 记录日志
		$link2[] = array('href' => 'card_item.php?act=goods&item_id=' . intval($_REQUEST['item_id']), 'text' => '卡项商品');
		sys_msg('操作成功', 0, $link2);
        exit;
    }
}

function action_goods_sync(){
	$goods_id	= intval($_REQUEST['goods_id']);
	$item_id	= intval($_REQUEST['item_id']);
	if(item_card_goods_sync($goods_id,$item_id)){
		$links[] = array('href' => 'card_item.php?act=goods&item_id='.intval($item_id));
		sys_msg('操作成功',0,$links);
	}else{
		$links[] = array('href' => 'card_item.php?act=goods&item_id='.intval($item_id));
		sys_msg('操作失败',0,$links);
	}
}


function get_item_card_list(){
	$result = get_filter();
    if ($result === false)
    {
        $aiax = isset($_GET['is_ajax']) ? $_GET['is_ajax'] : 0;
        /* 过滤信息 */
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'a.id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'ASC' : trim($_REQUEST['sort_order']);
        $where = 'WHERE 1 ';
        /* 分页大小 */
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

        /* 记录总数 */
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('card_item') . $where;
        $filter['record_count']   = $GLOBALS['db']->getOne($sql);
        $filter['page_count']     = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;
        /* 查询 */
        $sql = "SELECT a.*,b.name as circle_name
                FROM " . $GLOBALS['ecs']->table("card_item") . " a
				LEFT JOIN ".$GLOBALS['ecs']->table("supplier_circle")." b
				ON a.circle_id = b.id
                $where
                ORDER BY " . $filter['sort_by'] . " " . $filter['sort_order']. "
                LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ", " . $filter['page_size'] . " ";
		
        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }
    $row = $GLOBALS['db']->getAll($sql);
	
    $arr = array('result' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
    return $arr;
}

function get_item_card_column_list(){
	$result = get_filter();
    if ($result === false)
    {
        $aiax = isset($_GET['is_ajax']) ? $_GET['is_ajax'] : 0;
        /* 过滤信息 */
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'a.id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'ASC' : trim($_REQUEST['sort_order']);
        $where = 'WHERE a.item_id='.intval($_REQUEST['id']);
        /* 分页大小 */
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

        /* 记录总数 */
        $sql = "SELECT COUNT(a.id) FROM " . $GLOBALS['ecs']->table('card_item_column') . ' a '. $where;
        $filter['record_count']   = $GLOBALS['db']->getOne($sql);
        $filter['page_count']     = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;
        /* 查询 */
        $sql = "SELECT a.*,b.name as card_name,c.supplier_name
                FROM " . $GLOBALS['ecs']->table("card_item_column") . " a
				LEFT JOIN ".$GLOBALS['ecs']->table("card_item")." b ON a.item_id = b.id
				LEFT JOIN ".$GLOBALS['ecs']->table("supplier")." c ON a.supplier_id = c.supplier_id
                $where
                ORDER BY " . $filter['sort_by'] . " " . $filter['sort_order']. "
                LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ", " . $filter['page_size'] . " ";
		
        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }
	
    $row = $GLOBALS['db']->getAll($sql);
	
    $arr = array('result' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
    return $arr;
}

function get_card_item_goods(){
	$result = get_filter();
    if ($result === false)
    {
        $aiax = isset($_GET['is_ajax']) ? $_GET['is_ajax'] : 0;
        /* 过滤信息 */
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'a.goods_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'ASC' : trim($_REQUEST['sort_order']);
        $where = 'WHERE a.is_delete=0 AND a.item_id = '.intval($_REQUEST['item_id']);
        /* 分页大小 */
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

        /* 记录总数 */
        $sql = "SELECT COUNT(a.goods_id) FROM " . $GLOBALS['ecs']->table('goods') .' a '. $where;
        $filter['record_count']   = $GLOBALS['db']->getOne($sql);
        $filter['page_count']     = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;
        /* 查询 */
        $sql = "SELECT a.*,IFNULL(b.supplier_name,'平台方') as supplier_name,c.name as item_name
                FROM " . $GLOBALS['ecs']->table("goods") . " a
				LEFT JOIN ".$GLOBALS['ecs']->table("supplier")." b
				ON a.supplier_id = b.supplier_id
				LEFT JOIN ".$GLOBALS['ecs']->table("card_item")." c
				ON a.item_id = c.id
                $where
                ORDER BY " . $filter['sort_by'] . " " . $filter['sort_order']. "
                LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ", " . $filter['page_size'] . " ";
		
        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }
    $row = $GLOBALS['db']->getAll($sql);
	
    $arr = array('result' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
    return $arr;
}

function get_circle_list(){
	return $GLOBALS['db']->getAll("SELECT name,id FROM ".$GLOBALS['ecs']->table('supplier_circle')." WHERE 1");
}

function get_item_card_info($id){
	return $GLOBALS['db']->getRow("SELECT * FROM ".$GLOBALS['ecs']->table('card_item')." WHERE id=".$id);
}

//获取卡项所属商圈的店铺
function get_supplier_list($card_id){
	return $GLOBALS['db']->getAll("SELECT a.supplier_id,a.supplier_name FROM ".$GLOBALS['ecs']->table('supplier')." a LEFT JOIN ".$GLOBALS['ecs']->table('supplier_circle_list')." b ON a.supplier_id=b.supplier_id LEFT JOIN ".$GLOBALS['ecs']->table('supplier_circle')." c ON b.circle_id = c.id LEFT JOIN ".$GLOBALS['ecs']->table('card_item')." d ON d.circle_id = c.id WHERE d.id=".$card_id);
}

function get_item_card_column_info($id){
	return $GLOBALS['db']->getRow("SELECT * FROM ".$GLOBALS['ecs']->table('card_item_column')." WHERE id=".$id);
}

//将商品同步到卡项所有的店铺中  关联表:商品分销表,商品相册表
function item_card_goods_sync($goods_id,$item_id){
	require_once(ROOT_PATH . '/' . ADMIN_PATH . '/includes/lib_goods.php');
	$db		= $GLOBALS['db'];
	$ecs	= $GLOBALS['ecs'];
	//查询卡项所属商圈的所有店铺
	$supplier_res = get_card_item_suppliers($item_id);
	$supplier_ids = array_column($supplier_res,'supplier_id');
	$goods_info = $db->getRow("SELECT * FROM ".$ecs->table('goods')." WHERE is_delete = 0 AND supplier_id = 0 AND goods_id=".$goods_id." AND item_id=".$item_id);
	
	if(empty($goods_info)){
		return false;
	}
	$goods_distrib = $db->getRow("SELECT * FROM ".$ecs->table('ecsmart_distrib_goods')." WHERE goods_id=".$goods_id);
	$goods_gallery = $db->getAll("SELECT * FROM ".$ecs->table('goods_gallery')." WHERE goods_id=".$goods_id);
	foreach($supplier_ids as $v){
		//查询店铺是否已经有该卡项商品,存在即更新，不存在即新增
		$supplier_goodsid = exist_card_item_goods($item_id,$v);
		if($supplier_goodsid){
			$goods_temp = $goods_info;
			unset($goods_temp['goods_id'],$goods_temp['goods_sn'],$goods_temp['supplier_id'],$goods_temp['supplier_status'],$goods_temp['is_on_sale'],$goods_temp['is_delete']);
			//更新商品
			$db->autoExecute($ecs->table('goods'), $goods_temp , 'UPDATE', 'goods_id = "'.$supplier_goodsid.'"');
			if(empty($goods_distrib)){
				$db->query("DELETE FROM ".$ecs->table('ecsmart_distrib_goods')." WHERE goods_id=".$supplier_goodsid);
			}else{
				//平台商品存在分销但是店铺商品不存在分销，则插入数据。
				$supplier_goods_distrib = $db->getRow("SELECT id FROM ".$ecs->table('ecsmart_distrib_goods')." WHERE goods_id=".$supplier_goodsid);
				if(empty($supplier_goods_distrib)){
					$distrib_temp = $goods_distrib;
					unset($distrib_temp['id']);
					$distrib_temp['goods_id'] = $supplier_goodsid;
					$db->autoExecute($ecs->table('ecsmart_distrib_goods'), $distrib_temp, 'INSERT', '', 'SILENT');
					unset($distrib_temp);
				}else{
					$distrib_temp = $goods_distrib;
					unset($distrib_temp['id'],$distrib_temp['goods_id']);
					$db->autoExecute($ecs->table('ecsmart_distrib_goods'), $distrib_temp , 'UPDATE', 'id = "'.$supplier_goods_distrib['id'].'"');
					unset($distrib_temp);
				}
			}
			
			//相册全部删除后再插入
			$db->query("DELETE FROM ".$ecs->table('goods_gallery')." WHERE goods_id=".$supplier_goodsid);
			if(!empty($goods_gallery)){
				$gallery_temp = $goods_gallery;
				$sql = "INSERT INTO ".$ecs->table('goods_gallery')." (goods_id,img_url,img_desc,thumb_url,img_original,img_sort) VALUES " ;
				foreach($gallery_temp as $v){
					$sql .= "(".$supplier_goodsid.",'".$v['img_url']."','".$v['img_desc']."','".$v['thumb_url']."','".$v['img_original']."','".$v['img_sort']."'),";
				}
				$sql = substr($sql,0,-1);
				$db->query($sql);
				unset($gallery_temp);
			}

		}else{
			//新增商品,以及该商品的相册和分销
			$goods_temp = $goods_info;
			unset($goods_temp['goods_id']);
			$goods_temp['goods_sn'] = '';
			$goods_temp['supplier_id'] = $v;
			$goods_temp['supplier_status'] = 1;
			if($db->autoExecute($ecs->table('goods'), $goods_temp, 'INSERT', '', 'SILENT')){
				$supplier_goods_id = $db->insert_id();
				$db->autoExecute($ecs->table('goods'), array('goods_sn'=>generate_goods_sn($supplier_goods_id)), 'UPDATE', 'goods_id = "'.$supplier_goods_id.'"');
				if(!empty($goods_distrib)){
					$distrib_temp = $goods_distrib;
					unset($distrib_temp['id']);
					$distrib_temp['goods_id'] = $supplier_goods_id;
					$db->autoExecute($ecs->table('ecsmart_distrib_goods'), $distrib_temp, 'INSERT', '', 'SILENT');
					unset($distrib_temp);
				}
				if(!empty($goods_gallery)){
					$gallery_temp = $goods_gallery;
					$sql = "INSERT INTO ".$ecs->table('goods_gallery')." (goods_id,img_url,img_desc,thumb_url,img_original,img_sort) VALUES " ;
					foreach($gallery_temp as $v){
						$sql .= "(".$supplier_goods_id.",'".$v['img_url']."','".$v['img_desc']."','".$v['thumb_url']."','".$v['img_original']."','".$v['img_sort']."'),";
					}
					$sql = substr($sql,0,-1);
					$db->query($sql);
					unset($gallery_temp);
				}
			}
			unset($goods_temp);
		}
	}
	return true;
}

//item_id 为card_item主键id
function get_card_item_suppliers($item_id){
	$db		= $GLOBALS['db'];
	$ecs	= $GLOBALS['ecs'];
	$sql = "SELECT a.supplier_id FROM ".$ecs->table('supplier_circle_list')." a LEFT JOIN  ".$ecs->table('card_item')." b ON a.circle_id=b.circle_id WHERE b.id=".$item_id;
	$info = $db->getAll($sql);
	return $info;
}

//查询该店铺是否已经存在卡项的商品
function exist_card_item_goods($item_id,$supplier_id){
	return $GLOBALS['db']->getOne("SELECT goods_id FROM ".$GLOBALS['ecs']->table('goods')." WHERE is_delete = 0 AND supplier_id=".$supplier_id." AND item_id=".$item_id);
}