<?php

/**
 * ECSHOP 程序说明
 * ===========================================================
 * * 版权所有 2017-2020 月梦网络，并保留所有权利。
 * 月梦网络: http://www.dm299.com  开发QQ:124861234  禁止倒卖 一经发现停止任何服务；
 * ----------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ==========================================================
 * $Author: derek $
 * $Id: mobile_affiliate.php 17217 2011-01-19 06:29:08Z derek $

 By Freely 2019.04.02
 */

define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
admin_priv('affiliate_supplier');
$config = get_affiliate_supplier();
//print_r($config);die();
/*------------------------------------------------------ */
//-- 分成管理页
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    assign_query_info();
    if (empty($_REQUEST['is_ajax']))
    {
        $smarty->assign('full_page', 1);
    }
    $supplier_list=get_supplier_list();
    //print_r($config);die();
    $smarty->assign('supplier_list',$supplier_list);
    $smarty->assign('ur_here', $_LANG['distrib_set']);  /*微分销*/
    $smarty->assign('config', $config);
    $smarty->display('mobile_affiliate_supplier.htm');
}
elseif ($_REQUEST['act'] == 'query')
{
    $smarty->assign('ur_here', $_LANG['affiliate_supplier']);
    $smarty->assign('config', $config);
    make_json_result($smarty->fetch('mobile_affiliate_supplier.htm'), '', null);
}
/*------------------------------------------------------ */
//-- 增加下线分配方案
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'add')
{
    if (count($config['item']) < 3)
    {
        //下线不能超过2层
        $_POST['level_bendian'] = (float)$_POST['level_bendian'];
        $_POST['level_ydian'] = (float)$_POST['level_ydian'];
        $_POST['level_fuwufe'] = (float)$_POST['level_fuwufe'];

        $_POST['level_bendian'] > 100 && $_POST['level_bendian'] = 100;
        $_POST['level_ydian'] > 100 && $_POST['level_ydian'] = 100;
        if (!empty($_POST['level_bendian']) && strpos($_POST['level_bendian'],'%') === false)
        {
            $_POST['level_bendian'] .= '%';
        }
        if (!empty($_POST['level_ydian']) && strpos($_POST['level_ydian'],'%') === false)
        {
            $_POST['level_ydian'] .= '%';
        }
        $items = array('level_bendian'=>$_POST['level_bendian'],'level_ydian'=>$_POST['level_ydian'],'level_fuwufe'=>$_POST['level_fuwufe']);
        $links[] = array('text' => $_LANG['affiliate'], 'href' => 'mobile_affiliate_supplier.php?act=list');
        $config['item'][] = $items;
        put_affiliate_supplier($config);    }
    else
    {
       make_json_error($_LANG['level_error']);
    }
    ecs_header("Location: mobile_affiliate_supplier.php?act=query\n");
    exit;
}
/*------------------------------------------------------ */
//-- 修改配置
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'updata')
{

    $default_reg_supplier_id = (float)$_POST['default_reg_supplier_id'];
    $level_push_team_all=(float)$_POST['level_push_team_all'];
    $level_not_push_team_all=(float)$_POST['level_not_push_team_all'];

    $level_push_team_all>100 && $level_push_team_all=100;
    $level_push_team_all<0 && $level_push_team_all=0;

    $level_not_push_team_all>100 && $level_not_push_team_all=100;
    $level_not_push_team_all<0 && $level_not_push_team_all=0;

    $supplier_info = $db->getOne('SELECT supplier_name FROM ' . $GLOBALS['ecs']->table('supplier') . " WHERE status=1 And supplier_id= '".$default_reg_supplier_id."'");
    if(empty($supplier_info)){
        $links[] = array('text' => $_LANG['affiliate'], 'href' => 'mobile_affiliate_supplier.php?act=list');
        sys_msg("输入ID对应店铺不存在", 0 ,$links);
    }
    if (!empty($level_push_team_all) && strpos($level_push_team_all,'%') === false)
    {
        $level_push_team_all .= '%';
    }
    if (!empty($level_not_push_team_all) && strpos($level_not_push_team_all,'%') === false)
    {
        $level_not_push_team_all .= '%';
    }

    $temp = array();
     $sql = "select value from " . $GLOBALS['ecs']->table('ecsmart_shop_config',1) ." WHERE code = 'affiliate_supplier'";
     $affiliate_supplier = $GLOBALS['db']->getOne($sql);
    $affiliate_supplier = unserialize($affiliate_supplier);
	 
    $temp['config'] = array(
                            'default_reg_supplier_id'=>$default_reg_supplier_id,//默认注册会员所属店铺
                            'level_push_team_all'    => $level_push_team_all, //地堆团队直属分成比
                            'level_not_push_team_all'    => $level_not_push_team_all, //地堆团队非直属分成比
							
          );
    $temp['item'] = $config['item'];
    put_affiliate_supplier($temp);
    $links[] = array('text' => $_LANG['affiliate'], 'href' => 'mobile_affiliate_supplier.php?act=list');
    sys_msg($_LANG['edit_ok'], 0 ,$links);
}
/*------------------------------------------------------ */
//-- Ajax修改设置
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_bendian')
{
    /* 取得参数 */
    $key = trim($_POST['id']) - 1;
    $val = (float)trim($_POST['val']);
    if (!empty($val) && strpos($val,'%') === false)
    {
        $val .= '%';
    }
    $config['item'][$key]['level_bendian'] = $val;
    put_affiliate_supplier($config);
    make_json_result(stripcslashes($val));
}
/*------------------------------------------------------ */
//-- Ajax修改设置
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_ydian')
{
    $key = trim($_POST['id']) - 1;
    $val = (float)trim($_POST['val']);
    if (!empty($val) && strpos($val,'%') === false)
    {
        $val .= '%';
    }
    $config['item'][$key]['level_ydian'] = $val;
    put_affiliate_supplier($config);
    make_json_result(stripcslashes($val));
}
/*------------------------------------------------------ */
//-- Ajax修改设置店铺分成
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_fuwufe')
{
    $key = trim($_POST['id']) - 1;
    $val = (float)trim($_POST['val']);
    /*if (!empty($val) && strpos($val,'%') === false)
    {
        $val .= '%';
    }*/
    $config['item'][$key]['level_fuwufe'] = $val;
    put_affiliate_supplier($config);
    make_json_result(stripcslashes($val));
}

/*------------------------------------------------------ */
//-- 删除下线分成
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'del')
{
    $key = trim($_GET['id']) - 1;
    unset($config['item'][$key]);
    $temp = array();
    foreach ($config['item'] as $key => $val)
    {
        $temp[] = $val;
    }
    $config['item'] = $temp;
    put_affiliate_supplier($config);
    ecs_header("Location: mobile_affiliate_supplier.php?act=list\n");
    exit;
}

function get_affiliate_supplier()
{

	$sql = "select value from " . $GLOBALS['ecs']->table('ecsmart_shop_config') ."  WHERE code = 'affiliate_supplier'";
	$config = $GLOBALS['db']->getOne($sql);
    $config = unserialize($config);
    empty($config) && $config = array();
    return $config;
}

function put_affiliate_supplier($config)
{
    $temp = serialize($config);
    $sql = "UPDATE " . $GLOBALS['ecs']->table('ecsmart_shop_config',1) .
           "SET  value = '$temp'" .
           "WHERE code = 'affiliate_supplier'";
    $GLOBALS['db']->query($sql);
	clear_all_files_mobile();
}


/**
 * 取得入驻商列表
 * @return array    二维数组
 */
function get_supplier_list()
{
    $sql = 'SELECT supplier_id,supplier_name
            FROM ' . $GLOBALS['ecs']->table('supplier') . '
            WHERE status=1
            ORDER BY supplier_name ASC';
    $res = $GLOBALS['db']->getAll($sql);

    if (!is_array($res))
    {
        $res = array();
    }

    return $res;
}
?>