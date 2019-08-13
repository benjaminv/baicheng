<?php

/**
 * 管理中心 返佣管理
 * $Author: yangsong
 * 
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'includes/lib_rebate.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/lib_supplier_common_wap.php');


/*------------------------------------------------------ */
//-- 返佣列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
     /* 检查权限 */
    admin_priv('rebate_manage');

    /* 查询 */
    //$result = rebate_list();
    //$result = supplier_rebate_info_list();
	$result = supplier_commission_list();
    /* 模板赋值 */
    $smarty->assign('supplier_list',    $result['result']);
    $smarty->assign('filter',       $result['filter']);

    /* 显示模板 */
    _wap_assign_header_info('平台佣金列表','',0,0,0,0);
    _wap_assign_footer_order_info();
    _wap_display_page('supplier_rebate_list.htm');
}

///*------------------------------------------------------ */
////-- 排序、分页、查询
///*------------------------------------------------------ */
//elseif ($_REQUEST['act'] == 'query')
//{
    //check_authz_json('rebate_manage');

    //$result = rebate_list('list');

    //$smarty->assign('supplier_list',    $result['result']);
    //$smarty->assign('filter',       $result['filter']);
    //$smarty->assign('record_count', $result['record_count']);
    //$smarty->assign('page_count',   $result['page_count']);

    ///* 排序标记 */
    //$sort_flag  = sort_flag($result['filter']);
    //$smarty->assign($sort_flag['tag'], $sort_flag['img']);

    //make_json_result($smarty->fetch('supplier_rebate_list.htm'), '',
        //array('filter' => $result['filter'], 'page_count' => $result['page_count']));
//}

/**
 *  获取供应商列表信息
 *
 * @access  public
 * @param
 *
 * @return void
 */
function rebate_list($act='')
{
    $result = get_filter();
    if ($result === false)
    {
        /* 过滤信息 */
        $filter['rebate_paytime_start'] = !empty($_REQUEST['rebate_paytime_start']) ? trim($_REQUEST['rebate_paytime_start']) : 0;
        $filter['rebate_paytime_end'] = !empty($_REQUEST['rebate_paytime_start']) ? trim($_REQUEST['rebate_paytime_start']) : 0;
        $filter['unix_rebate_paytime_start'] = !empty($filter['rebate_paytime_start']) ? local_strtotime($_REQUEST['rebate_paytime_start']) : 0;
		$filter['unix_rebate_paytime_end'] = !empty($filter['rebate_paytime_end']) ? local_strtotime($_REQUEST['rebate_paytime_end']." 23:59:59") : 0;
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? ' sr.supplier_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? ' ASC' : trim($_REQUEST['sort_order']);
		$filter['is_pay_ok'] = empty($_REQUEST['is_pay_ok']) ? '0' : intval($_REQUEST['is_pay_ok']);
		$filter['actname'] = empty($act) ? trim($_REQUEST['act']) : $act;
       
	$where = (isset($_SESSION['supplier_id']) && intval($_SESSION['supplier_id'])>0) ? 'WHERE sr.supplier_id='.intval($_SESSION['supplier_id']) : 'WHERE 1';
        $where .= $filter['unix_rebate_paytime_start'] ? " AND sr.rebate_paytime_start >= '". $filter['unix_rebate_paytime_start']. "' " :  " ";
	$where .= $filter['unix_rebate_paytime_end'] ? " AND sr.rebate_paytime_end <= '". $filter['unix_rebate_paytime_end']. "' " :  " ";
	$where .= $filter['is_pay_ok'] ? " AND sr.is_pay_ok = '". $filter['is_pay_ok']. "' " :  " AND sr.is_pay_ok = '0' ";

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
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('supplier_rebate') ." AS sr  " . $where;
        $filter['record_count']   = $GLOBALS['db']->getOne($sql);
        $filter['page_count']     = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        /* 查询 */
        $sql = "SELECT sr.* , s.supplier_name, s.supplier_rebate ".
                "FROM " . $GLOBALS['ecs']->table("supplier_rebate") . " AS  sr left join " .$GLOBALS['ecs']->table("supplier") .  " AS s on sr.supplier_id=s.supplier_id 
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

	$list=array();
	$res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchRow($res))
	{
		$row['sign'] = createSign($row['rebate_id'],$row['supplier_id']);
		$row['rebate_paytime_start'] = local_date('Y.m.d', $row['rebate_paytime_start']);
		$endtime = $row['rebate_paytime_end'];//+$GLOBALS['_CFG']['tuihuan_days_qianshou']*3600*24;
		$row['rebate_paytime_end'] = local_date('Y.m.d', $endtime);
		//$row['all_money'] = $GLOBALS['db']->getOne("select sum(money_paid + surplus) from ". $GLOBALS['ecs']->table('order_info') ." where rebate_id=". $row['rebate_id'] ." and rebate_ispay=2");
		$row['all_money'] = $GLOBALS['db']->getOne("select sum(" . order_amount_field() . ") from ". $GLOBALS['ecs']->table('order_info') ." where rebate_id=". $row['rebate_id'] ." and rebate_ispay=2");
		$row['all_money_formated'] = price_format($row['all_money']);
		$row['rebate_money'] = round(($row['all_money'] * $row['supplier_rebate'])/100, 2);
		$row['rebate_money_formated'] =  price_format($row['rebate_money']);
		$row['pay_money'] = $row['all_money'] - $row['rebate_money'];
		$row['pay_money_formated'] = price_format($row['pay_money']);
		$row['pay_status'] = $row['is_pay_ok'] ? "已处理，已返佣" : "未处理";
		$row['pay_time'] = local_date('Y.m.d', $row['pay_time']);
		$row['user'] = $_SESSION['user_name'];
		$row['payable_price'] = price_format($row['payable_price']);
		$row['status_name'] = rebateStatus($row['status']);
		$row['caozuo'] = getRebateDo($row['status'],$row['rebate_id'],$filter['actname']);
		$list[]=$row;
	}
    $arr = array('result' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}

//入驻商详细佣金日志列表
function supplier_rebate_info_list()
{
	$result = get_filter();

	if ($result === false)
    {
		
		$filter['filter_start_time']    = empty($_REQUEST['start_time']) ? '' : $_REQUEST['start_time'];
		$filter['filter_end_time']    = empty($_REQUEST['end_time']) ? '' : $_REQUEST['end_time'];

		$filter['start_time']    = empty($_REQUEST['start_time']) ? '' : (strpos($_REQUEST['start_time'], '-') > 0 ?  local_strtotime($_REQUEST['start_time']) : $_REQUEST['start_time']);
		$filter['end_time']    = empty($_REQUEST['end_time']) ? '' : (strpos($_REQUEST['end_time'], '-') > 0 ?  local_strtotime($_REQUEST['end_time']) : $_REQUEST['end_time']);

		$filter['payid'] = intval($_REQUEST['payid'])>0 ? intval($_REQUEST['payid']) : 0;
		$filter['orderid'] = intval($_REQUEST['orderid'])>0 ? intval($_REQUEST['orderid']) : 0;
		$filter['sort_by'] = empty($_REQUEST['sort_by']) ? ' sr.add_time' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? ' ASC' : trim($_REQUEST['sort_order']);

		$where = (isset($_SESSION['supplier_id']) && intval($_SESSION['supplier_id'])>0) ? 'WHERE sr.supplier_id='.intval($_SESSION['supplier_id']) : 'WHERE 1';

		if ($filter['start_time'])
		{
			$where .= " and sr.add_time >= '" . $filter['start_time']."' ";
		}

		if ($filter['end_time'])
		{
			$where .= " and sr.add_time <= '" . $filter['end_time']."' ";;
		}

		if($filter['payid'])
		{
			$where .= " and sr.pay_id = ".$filter['payid'];
		}

		if($filter['orderid'])
		{
			$where .= " and sr.order_id = ".$filter['orderid'];
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
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('supplier_rebate_log') ." AS sr  " . $where;
        $filter['record_count']   = $GLOBALS['db']->getOne($sql);
        $filter['page_count']     = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        /* 查询 */
        $sql = "SELECT sr.*, s.supplier_name, s.supplier_rebate ".
                "FROM " . $GLOBALS['ecs']->table("supplier_rebate_log") . " AS  sr left join " .$GLOBALS['ecs']->table("supplier") .  " AS s on sr.supplier_id=s.supplier_id 
                $where
                ORDER BY " . $filter['sort_by'] . " " . $filter['sort_order'];
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
		$row['add_time'] = local_date('Y-m-d H:i:s', $row['add_time']);
		$list[]=$row;
	}
    $arr = array('result' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}

function supplier_commission_list(){
	$result = get_filter();

	if ($result === false)
    {

		$filter['start_time']    = empty($_REQUEST['rebate_paytime_start']) ? '' : (strpos($_REQUEST['rebate_paytime_start'], '-') > 0 ?  local_strtotime($_REQUEST['rebate_paytime_start']." 00:00:00") : $_REQUEST['rebate_paytime_start']);

		$filter['end_time']    = empty($_REQUEST['rebate_paytime_end']) ? '' : (strpos($_REQUEST['rebate_paytime_end'], '-') > 0 ?  local_strtotime($_REQUEST['rebate_paytime_end']." 23:59:59") : $_REQUEST['rebate_paytime_end']);
		
		$filter['sort_by'] = empty($_REQUEST['sort_by']) ? ' createtime' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? ' ASC' : trim($_REQUEST['sort_order']);

		$where = (isset($_SESSION['supplier_id']) && intval($_SESSION['supplier_id'])>0) ? ' WHERE supplier_id='.intval($_SESSION['supplier_id']) : ' WHERE 1';

		if ($filter['start_time'])
		{
			$where .= " and createtime >= '" . $filter['start_time']."' ";
		}

		if ($filter['end_time'])
		{
			$where .= " and createtime <= '" . $filter['end_time']."' ";;
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
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('supplier_commission_log') . $where;
        $filter['record_count']   = $GLOBALS['db']->getOne($sql);
        $filter['page_count']     = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;
		
        /* 查询 */
        $sql = "SELECT * ".
                "FROM " . $GLOBALS['ecs']->table("supplier_commission_log") .$where." ORDER BY " . $filter['sort_by'] . " " . $filter['sort_order']. "  LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ", " . $filter['page_size'] . " ";
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
		//查询分销发放和平台扣除
		$row['distrib'] = $GLOBALS['db']->getOne("SELECT IFNULL(SUM(money),0) FROM ".$GLOBALS['ecs']->table("affiliate_log")." WHERE order_id=".$row['order_id']);
		$row['plat_distrib'] = $GLOBALS['db']->getOne("SELECT IFNULL((SELECT distribute_money FROM ".$GLOBALS['ecs']->table("plat_distribute_log")." WHERE order_id=".$row['order_id']."),0)");
		$order = $GLOBALS['db']->getRow("SELECT goods_amount,tax,shipping_fee,insure_fee,pay_fee,pack_fee,card_fee,integral_money,bonus,discount FROM ".$GLOBALS['ecs']->table("order_info")." WHERE order_id=".$row['order_id']);
		$row['pay_money'] = $order['goods_amount']+$order['tax']+$order['shipping_fee']+$order['insure_fee']+$order['pay_fee']+$order['pack_fee']+$order['card_fee']-$order['integral_money']-$order['bonus']-$order['discount'];
		$list[]=$row;
	}
    $arr = array('result' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}
?>