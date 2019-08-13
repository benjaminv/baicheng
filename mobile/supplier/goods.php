<?php
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'includes/lib_supplier_common_wap.php');
error_reporting(E_ALL);
$action = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : 'default';
/* 路由 */
$function_name = 'action_' . $action;
if(! function_exists($function_name))
{
	$function_name = "action_default";
}
call_user_func($function_name);

//进货管理
function action_default(){
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$data = goods_plat();

    $goods = $data['goods'];
    $filter = $data['filter'];
    $smarty->assign('goods',$goods);
    $smarty->assign('filter',$filter);

	_wap_assign_header_info('平台商品','',1,0,1);
    _wap_display_page('goods_plat_list.htm');
}

//进货管理
function action_supplier(){
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$data = goods_stock();
    $goods = $data['goods'];
    $filter = $data['filter'];
    $smarty->assign('goods',$goods);
    $smarty->assign('filter',$filter);
	_wap_assign_header_info('进货管理','',1,0,1);
    _wap_display_page('goods_supplier_list.htm');
}

function action_stock(){
	global $ecs,$db;
	$goods_id = intval($_POST['goods_id']);
	if(!$_SESSION['supplier_id']){
		echo json_encode(array('code'=>400,'msg'=>'登录过期，请刷新页面重新登录','remove_flag'=>false));exit;
	}
	if(!$goods_id){
		echo json_encode(array('code'=>400,'msg'=>'参数错误','remove_flag'=>false));exit;
	}
	$stock_id = $db->getOne("SELECT id FROM ".$ecs->table('supplier_goods')." WHERE goods_id=".$goods_id." AND supplier_id=".$_SESSION['supplier_id']);
	if($stock_id){
		echo json_encode(array('code'=>400,'msg'=>'该商品已经在您的店铺中了,不能重复添加!','remove_flag'=>true));exit;
	}
	
	$stock = array("supplier_id"=>$_SESSION['supplier_id'],'goods_id'=>$goods_id,'add_time'=>gmtime());
	if ($db->autoExecute($ecs->table('supplier_goods'), $stock, 'INSERT', '', 'SILENT')){
		echo json_encode(array('code'=>200,'msg'=>'添加成功!','remove_flag'=>true));
	}else{
		echo json_encode(array('code'=>400,'msg'=>'添加失败!','remove_flag'=>false));
	}
}

function action_stock_del(){
	global $ecs,$db;
	$goods_id = intval($_POST['goods_id']);
	if(!$_SESSION['supplier_id']){
		echo json_encode(array('code'=>400,'msg'=>'登录过期，请刷新页面重新登录','remove_flag'=>false));exit;
	}
	if(!$goods_id){
		echo json_encode(array('code'=>400,'msg'=>'参数错误','remove_flag'=>false));exit;
	}
	$stock_id = $db->getOne("SELECT id FROM ".$ecs->table('supplier_goods')." WHERE goods_id=".$goods_id." AND supplier_id=".$_SESSION['supplier_id']);
	if(!$stock_id){
		echo json_encode(array('code'=>400,'msg'=>'该商品在店铺中不存在','remove_flag'=>true));exit;
	}
	
	if($db->query("DELETE FROM ".$ecs->table('supplier_goods'). " WHERE goods_id=".$goods_id." AND supplier_id=".$_SESSION['supplier_id'])){
		echo json_encode(array('code'=>200,'msg'=>'移除成功!','remove_flag'=>true));
	}else{
		echo json_encode(array('code'=>400,'msg'=>'移除失败!','remove_flag'=>false));
	}
}

/*functions*/
function goods_plat(){
	 global $ecs,$db;
    $result = get_filter();
    if($result === false)
    {
		$supplier_goods_ids = get_supplier_goods();
        $filter['goods_name'] = empty($_REQUEST['goods_name'])?'':trim($_REQUEST['goods_name']);
        $filter['goods_sn'] = empty($_REQUEST['goods_sn'])?'':trim($_REQUEST['goods_sn']);
        $where = ' WHERE supplier_id=0 AND is_on_sale=1 AND is_delete=0 ';
		if(!empty($supplier_goods_ids)){
			$goods_ids = implode(',',$supplier_goods_ids);
			$where .= ' AND goods_id NOT IN ('.$goods_ids.') ';
		}
        if(!empty($filter['goods_name']))
        {
            $where .= ' AND goods_name LIKE "%'.$filter['goods_name'].'%" ';
        }
        if(!empty($filter['goods_sn']))
        {
            $where .= ' AND goods_sn LIKE "%'.$filter['goods_sn'].'%" ';
        }
        $sql = 'SELECT COUNT(*) FROM '.$ecs->table('goods').$where;
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
        $sql = 'SELECT goods_id,goods_sn,goods_name,goods_number,goods_thumb FROM '.$ecs->table('goods').$where.$limit;
        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }

    $goods = $db->getAll($sql);

    foreach($goods as $goods_key => $goods_val)
    {
        $sql = 'SELECT goods_attr AS goods_attr_id,product_number FROM '.$ecs->table('products').' WHERE goods_id='.$goods_val['goods_id'];
        $attr = $db->getAll($sql);
        foreach($attr as $attr_key => $attr_val)
        {
            $goods_attr_arr = explode('|',$attr_val['goods_attr_id']);
            $attr_sql = implode(' OR goods_attr_id= ',$goods_attr_arr);
            $sql = 'SELECT attr_value FROM '.$ecs->table('goods_attr').' WHERE goods_attr_id='.$attr_sql;
            $attr_name_arr = $db->getAll($sql);
            $attr_name = '';
            foreach( $attr_name_arr as $name_key=>$name_val)
            {
                $attr_name .= implode(' ',$name_val);
            }
            $attr_val['goods_attr_name'] = $attr_name;
            $attr[$attr_key] = $attr_val;
        }
        $goods_val['goods_attr'] = $attr;
        $goods[$goods_key] = $goods_val;
    }
    $arr = array('goods' => $goods, 'filter' => $filter);
    return $arr;
}

function goods_stock(){
	global $ecs,$db;
    $result = get_filter();
    if($result === false){
		$filter['goods_name'] = empty($_REQUEST['goods_name'])?'':trim($_REQUEST['goods_name']);
        $filter['goods_sn'] = empty($_REQUEST['goods_sn'])?'':trim($_REQUEST['goods_sn']);
        $where = ' WHERE b.is_on_sale=1 AND b.is_delete=0 AND a.supplier_id='.$_SESSION['supplier_id'];
        if(!empty($filter['goods_name'])){
            $where .= ' AND b.goods_name LIKE "%'.$filter['goods_name'].'%" ';
        }
        if(!empty($filter['goods_sn'])){
            $where .= ' AND b.goods_sn LIKE "%'.$filter['goods_sn'].'%" ';
        }
        $sql = 'SELECT COUNT(a.id) FROM '.$ecs->table('supplier_goods').' a LEFT JOIN '.$ecs->table('goods').' b ON a.goods_id=b.goods_id '.$where;
        $filter['record_count'] = $db->getOne($sql);
        $filter['page'] = empty($_REQUEST['page']) || (intval($_REQUEST['page']) <= 0) ? 1 : intval($_REQUEST['page']);
        if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0){
            $filter['page_size'] = intval($_REQUEST['page_size']);
        }elseif (isset($_COOKIE['ECSCP']['page_size']) && intval($_COOKIE['ECSCP']['page_size']) > 0){
            $filter['page_size'] = intval($_COOKIE['ECSCP']['page_size']);
        }else{
            $filter['page_size'] = 15;
        }
        $filter = page_and_size($filter);
        $limit = ' LIMIT '. $filter['start'] .',' . $filter['page_size'];
        $sql = 'SELECT b.goods_id,b.goods_sn,b.goods_name,b.goods_number,b.goods_thumb FROM '.$ecs->table('supplier_goods').' a LEFT JOIN '.$ecs->table('goods').' b ON a.goods_id=b.goods_id '.$where.$limit;
        set_filter($filter, $sql);
	}else{
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }
	$goods = $db->getAll($sql);
    foreach($goods as $goods_key => $goods_val){
        $sql = 'SELECT goods_attr AS goods_attr_id,product_number FROM '.$ecs->table('products').' WHERE goods_id='.$goods_val['goods_id'];
        $attr = $db->getAll($sql);
        foreach($attr as $attr_key => $attr_val){
            $goods_attr_arr = explode('|',$attr_val['goods_attr_id']);
            $attr_sql = implode(' OR goods_attr_id= ',$goods_attr_arr);
            $sql = 'SELECT attr_value FROM '.$ecs->table('goods_attr').' WHERE goods_attr_id='.$attr_sql;
            $attr_name_arr = $db->getAll($sql);
            $attr_name = '';
            foreach( $attr_name_arr as $name_key=>$name_val){
                $attr_name .= implode(' ',$name_val);
            }
            $attr_val['goods_attr_name'] = $attr_name;
            $attr[$attr_key] = $attr_val;
        }
        $goods_val['goods_attr'] = $attr;
        $goods[$goods_key] = $goods_val;
    }
    $arr = array('goods' => $goods, 'filter' => $filter);
    return $arr;
}

function get_supplier_goods(){
	//获取供应商已进货商品列表
	global $ecs,$db;
	$sql = 'SELECT goods_id FROM '.$ecs->table('supplier_goods').' WHERE supplier_id='.$_SESSION['supplier_id'];
    $goods_ids = $db->getAll($sql);
	$goods_ids = array_column($goods_ids,'goods_id');
	return $goods_ids;
}

