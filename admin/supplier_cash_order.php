<?php

/**
 * ECSHOP 订单管理
 * ============================================================================
 * 版权所有 2017-2020 月梦网络，并保留所有权利。
 * 月梦网络: http://dm299.taobao.com  开发QQ:124861234  禁止倒卖 一经发现停止任何服务
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: yehuaixiao $
 * $Id: order.php 17219 2011-01-27 10:49:19Z yehuaixiao $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
error_reporting(E_ALL);
/*------------------------------------------------------ */
//-- 订单查询
/*------------------------------------------------------ */

if ($_REQUEST['act'] == 'order_query')
{
    /* 检查权限 */
    admin_priv('supplier_manage');

    /* 载入配送方式 */
    $smarty->assign('shipping_list', shipping_list());

    /* 载入支付方式 */
    $smarty->assign('pay_list', payment_list());

    /* 载入国家 */
    $smarty->assign('country_list', get_regions());

    /* 载入订单状态、付款状态、发货状态 */
    $smarty->assign('os_list', get_status_list('order'));
    $smarty->assign('ps_list', get_status_list('payment'));
    $smarty->assign('ss_list', get_status_list('shipping'));

    /* 模板赋值 */
    $smarty->assign('ur_here', $_LANG['03_order_query']);
    $smarty->assign('action_link', array('href' => 'order.php?act=list', 'text' => $_LANG['02_order_list']));

    /* 显示模板 */
    assign_query_info();
    $smarty->display('order_query.htm');
}

/*------------------------------------------------------ */
//-- 提现订单列表
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'list')
{
    /* 检查权限 */
    admin_priv('supplier_cash_order_manage');

    $result = supplier_cash_order_list();

    $smarty->assign('cash_order_list',    $result['result']);
    $smarty->assign('filter',       $result['filter']);
    $smarty->assign('record_count', $result['record_count']);
    $smarty->assign('page_count',   $result['page_count']);
    $smarty->assign('full_page',        1); // 翻页参数

    assign_query_info();
    $smarty->display('supplier_cash_order_list.htm');
}

/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    /* 检查权限 */
    admin_priv('supplier_manage');
    
	if(!isset($_REQUEST['isreb']) || intval($_REQUEST['isreb'])==1){
    	$ur_here = '待处理佣金订单';
    	$disply  = 'supplier_cash_order_list.htm';
    }else{
    	$ur_here = '已完成佣金订单';
    	$disply  = 'supplier_cash_order_list.htm';
    }

    $order_list = supplier_cash_order_list();

    $smarty->assign('cash_order_list',   $order_list['result']);
    $smarty->assign('filter',       $order_list['filter']);
    $smarty->assign('record_count', $order_list['record_count']);
    $smarty->assign('page_count',   $order_list['page_count']);
    $sort_flag  = sort_flag($order_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);
    make_json_result($smarty->fetch($disply), '', array('filter' => $order_list['filter'], 'page_count' => $order_list['page_count']));
}
//审核处理
elseif($_REQUEST['act']=='audit'){
    $oid = intval($_REQUEST['oid']);
    /* 检查权限 */
    check_authz_json('supplier_cash_order_manage');
    $sql="select sco.*,s.supplier_name from ".$GLOBALS['ecs']->table('supplier_cash_order')." as sco LEFT JOIN ".$GLOBALS['ecs']->table('supplier')." as s ON s.supplier_id=sco.supplier_id WHERE sco.id=".$oid." LIMIT 1";
    $order=$GLOBALS['db']->getRow($sql);
    if(empty($order)){sys_msg('此提现订单不存在，无法处理！');}

    $order['add_time'] = local_date('Y-m-d H:i:s', $order['add_time']);
    $smarty->assign('order',$order);
    $smarty->display('supplier_cash_order_info.htm');
}//审核通过并支付
elseif($_REQUEST['act']=='auditpay'){
    //审核成功并处理
    $oid = intval($_POST['oid']);
    /* 检查权限 */
    check_authz_json('supplier_cash_order_manage');
    /* 检查订单是否允许删除操作 */
    $order=$GLOBALS['db']->getRow("SELECT * FROM ".$GLOBALS['ecs']->table('supplier_cash_order')." WHERE id=".$oid);
    if(empty($order)){sys_msg('此提现订单不存在，无法处理！');}
    $supplier=get_supplier($order['supplier_id']);
    if(empty($supplier)){sys_msg('此提现订单所属店铺不存在，无法处理！');}
    if($order['status']==3){sys_msg("此提现订单已取消，无需再处理");}
    if($order['status']==2){sys_msg("此提现订单已成功付款了，无需再处理!");}
    if($order['status']==1){sys_msg("此提现订单已提交付款申请了，请等会儿使用查询付款状态!");}
    $status=$_POST['status'];
    if($status!=2&&$status!=3){sys_msg("请选择要操作的状态");}
    if($status==2){//付款处理
        $pay_code=$order['pay_typ']==1?'alipay':'weixin';
        include_once(dirname ( __FILE__ ).'/includes/payment/' . $pay_code . '.php');
        $payclass="cash_".$pay_code;
        $pay_obj    = new $payclass;
        $rdata = $pay_obj->payToUser($order['order_sn'],$order['pay_number'],$order['pay_name'],'提现成功',$order['money']);
        if($rdata['status']!=1){
            sys_msg('付款出错了，出错信息：'.$rdata['err_msg']);
        }
        //更新订单状态并扣除用户可提现的佣金
        $sql="UPDATE ".$GLOBALS['ecs']->table('supplier_cash_order')." SET status=2,online_number='".$rdata['online_number']."',pay_time='".strtotime($rdata['time'])."' WHERE status=0 AND id=".$oid;
        $result=$GLOBALS['db']->query($sql);
        
        $msg='审核通过并成功给【'.$supplier['supplier_name'].'】付款【'.$order['money'].'元】';
    }elseif($status==3){
        $err_msg=$_POST['err_msg'];
        $sql="UPDATE ".$GLOBALS['ecs']->table('supplier_cash_order')." SET status=3,err_msg='".$err_msg."' WHERE status=0 AND id=".$oid;
        $result=$GLOBALS['db']->query($sql);
		//申请驳回,退还提现金额
		
        $updatesql=',withdrawals_money=withdrawals_money-('.$order['money'].')';
        supplier_log_account_change($order['supplier_id'],$order['money'], '【'.$supplier['supplier_name'].'】佣金提现驳回', SUPPLIER_OTHER,$updatesql);

        $msg='处理成功，将状态改为审核失败！';
    }else{
        sys_msg("请不要非法操作！");
    }
    $link= array(array('text' => '返回店铺佣金提现列表页', 'href' => 'supplier_cash_order.php?act=list'));
    sys_msg($msg,0,$link);
}
elseif($_REQUEST['act']=='remove_order'){

    $oid = intval($_REQUEST['id']);
    /* 检查权限 */
    check_authz_json('supplier_cash_order_manage');
    /* 检查订单是否允许删除操作 */
    $order=$GLOBALS['db']->getRow("SELECT * FROM ".$GLOBALS['ecs']->table('supplier_cash_order')." WHERE id=".$oid);
    if(empty($order)){sys_msg('此提现订单不存在，无法处理！');}
    if($order['status']==1){sys_msg('此提现订单已审核通过，将不能删除了！');}
    $GLOBALS['db']->query("DELETE FROM ".$GLOBALS['ecs']->table('supplier_cash_order'). " WHERE id = '$oid'");
    //给增加回去
    //$GLOBALS['db']->query("UPDATE ".$GLOBALS['ecs']->table('supplier')." SET commission_money=commission_money+".$order['money']." WHERE supplier_id=".$order['supplier_id']);
    if ($GLOBALS['db'] ->errno() == 0)
    {
        $url = 'supplier_cash_order.php?act=query&' . str_replace('act=remove_order', '', $_SERVER['QUERY_STRING']);
        ecs_header("Location: $url\n");
        exit;
    }
    else
    {
        make_json_error($GLOBALS['db']->errorMsg());
    }
}
//获取供货商名称
function get_supplier($supplier_id)
{
    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('supplier') . " WHERE supplier_id = '$supplier_id'";
    return $GLOBALS['db']->getRow($sql);
}

//入驻商佣金提现列表
function supplier_cash_order_list()
{
    $result = get_filter();
    if ($result === false)
    {
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? ' add_time' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? ' ASC' : trim($_REQUEST['sort_order']);
        $where=' WHERE 1 ';
        //$where = (isset($_SESSION['supplier_id']) && intval($_SESSION['supplier_id'])>0) ? 'WHERE supplier_id='.intval($_SESSION['supplier_id']) : 'WHERE 1';
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
        $sql="select count(*) from ".$GLOBALS['ecs']->table('supplier_cash_order')." as sco LEFT JOIN ".$GLOBALS['ecs']->table('supplier')." as s ON s.supplier_id=sco.supplier_id ".$where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);
        $filter['page_count']     = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;
        /* 查询 */
        $sql="select sco.*,s.supplier_name from ".$GLOBALS['ecs']->table('supplier_cash_order')." as sco LEFT JOIN ".$GLOBALS['ecs']->table('supplier')." as s ON s.supplier_id=sco.supplier_id ".$where." ORDER BY id DESC";
        $sql .= " LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ", " . $filter['page_size'] . " ";
        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }
    $statuslist=[0=>'未处理',1=>'正在付款中',2=>'付款成功',3=>'取消'];
    $list=array();
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $row['pay_time'] = local_date('Y-m-d H:i:s', $row['pay_time']);
        $row['add_time'] = local_date('Y-m-d H:i:s', $row['add_time']);
        $row['status_txt']=isset($statuslist[$row['status']])?$statuslist[$row['status']]:'';
        //$row['can_remove']=
        $list[]=$row;
    }
    $arr = array('result' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}
?>