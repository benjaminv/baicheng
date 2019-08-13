<?php

/**
 * 管理中心 佣金管理
 * $Author: Freely
 * 
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
//require_once(ROOT_PATH . 'includes/lib_rebate.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
//require(ROOT_PATH . 'languages/' .$_CFG['lang']. '/admin/supplier.php');
$smarty->assign('lang', $_LANG);


/*------------------------------------------------------ */
//-- 佣金列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
     /* 检查权限 */
     admin_priv('affiliate_manage');

	$ur_here_lang = '店铺佣金列表';
    $smarty->assign('ur_here', $ur_here_lang); // 当前导航


	$result = supplier_affiliate_list();
    //print_r($result);die();
	$today['start'] = local_date('Y-m-d 00:00');
	$today['ends'] = local_date('Y-m-d 00:00',local_strtotime("+1 day"));
	$yestoday['start'] = local_date('Y-m-d 00:00',local_strtotime("-1 day"));
	$yestoday['ends'] = local_date('Y-m-d 00:00',local_strtotime("+1 day"));
	$week['start'] = local_date('Y-m-d 00:00',local_strtotime("-7 day"));
	$week['ends'] = local_date('Y-m-d 00:00',local_strtotime("+1 day"));
	$month['start'] = local_date('Y-m-d 00:00',local_strtotime("-1 month"));
	$month['ends'] = local_date('Y-m-d 00:00',local_strtotime("+1 day"));

	$smarty->assign('supplier_list',    $result['result']);
    $smarty->assign('filter',       $result['filter']);
    $smarty->assign('record_count', $result['record_count']);
    $smarty->assign('page_count',   $result['page_count']);

	$smarty->assign('full_page',        1); // 翻页参数
	$smarty->assign('today',$today);
	$smarty->assign('yestoday',$yestoday);
	$smarty->assign('week',$week);
	$smarty->assign('month',$month);

	$supplier_order = get_all_supplier_order();
	$smarty->assign('supplier_order',$supplier_order);

	assign_query_info();
    $smarty->display('supplier_affiliate_list.htm');
}

/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    check_authz_json('affiliate_manage');

    $result = supplier_affiliate_list();
	$smarty->assign('supplier_list',    $result['result']);
	$smarty->assign('filter',       $result['filter']);
	$smarty->assign('record_count', $result['record_count']);
	$smarty->assign('page_count',   $result['page_count']);

	/* 排序标记 */
	$sort_flag  = sort_flag($result['filter']);
	$smarty->assign($sort_flag['tag'], $sort_flag['img']);

	make_json_result($smarty->fetch('supplier_affiliate_list.htm'), '',
		array('filter' => $result['filter'], 'page_count' => $result['page_count']));
}

//导出
elseif ($_REQUEST['act'] == 'export_goods')
{
	admin_priv('affiliate_manage');
	header("Content-type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=rebate_list.xls");

	$export = "<table border='1'><tr><td colspan='2'>商品名称</td><td colspan='2'>购买价格（元）</td><td colspan='2'>佣金类型</td><td colspan='2'>可得佣金（元）</td></tr>";

	$result = supplier_affiliate_list();
	foreach($result['result'] as $key=>$val)
	{
		$export .= "<tr><td colspan='2'>".$val['buy_goods_name']."</td><td colspan='2'>".$val['buy_goods_price']."</td><td colspan='2'>".($supplier.commission_type==1?'服务费':'佣金')."</td><td colspan='2'>".$val['commission_money']."</td></tr>";
		
	}
	$export .= "</table>";
	if (EC_CHARSET != 'utf-8')
    {
        echo ecs_iconv(EC_CHARSET, 'utf-8', $export) . "\t";
    }
    else
    {
        echo $export. "\t";
    }
}

//入驻商详细佣金日志列表
function supplier_affiliate_list()
{
	$result = get_filter();

	if ($result === false)
    {
		$filter['start_time']    = empty($_REQUEST['start_time']) ? '' : (strpos($_REQUEST['start_time'], '-') > 0 ?  local_strtotime($_REQUEST['start_time']) : $_REQUEST['start_time']);
		$filter['end_time']    = empty($_REQUEST['end_time']) ? '' : (strpos($_REQUEST['end_time'], '-') > 0 ?  local_strtotime($_REQUEST['end_time']) : $_REQUEST['end_time']);

		$filter['orderid'] = intval($_REQUEST['orderid'])>0 ? intval($_REQUEST['orderid']) : 0;
		$filter['sort_by'] = empty($_REQUEST['sort_by']) ? ' createtime' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? ' ASC' : trim($_REQUEST['sort_order']);

		$where = (isset($_SESSION['supplier_id']) && intval($_SESSION['supplier_id'])>0) ? 'WHERE supplier_id='.intval($_SESSION['supplier_id']) : 'WHERE 1';

		if ($filter['start_time'])
		{
			$where .= " and createtime >= '" . $filter['start_time']."' ";
		}

		if ($filter['end_time'])
		{
			$where .= " and createtime <= '" . $filter['end_time']."' ";;
		}


		if($filter['orderid'])
		{
			$where .= " and order_id = ".$filter['orderid'];
		}

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
        $sql="select count(*) from ".$GLOBALS['ecs']->table('supplier_commission_log').' '.$where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);
        $filter['page_count']     = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;
        /* 查询 */
        $sql="select * from ".$GLOBALS['ecs']->table('supplier_commission_log').' '.$where." ORDER BY id DESC";
        if(!isset($_REQUEST['is_export'])){
            $sql .= " LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ", " . $filter['page_size'] . " ";
        }
        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }
	$list=array();
	$res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchRow($res))
	{
		$row['createtime'] = local_date('Y-m-d H:i:s', $row['createtime']);
		$list[]=$row;
	}
    $arr = array('result' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}
//获取所有商家的订单
function get_all_supplier_order()
{
	global $db,$ecs;
	$suppid = intval($_SESSION['supplier_id']);
	$sql = "select order_id,order_sn from ".$ecs->table('supplier_commission_log')." where supplier_id=".$suppid;
	return $db->getAll($sql);
}

?>