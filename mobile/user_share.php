<?php

/**
 * ECSHOP 海报分校
 * ============================================================================
 * * 版权所有 2017-2020 月梦网络，并保留所有权利。
 * 月梦网络: http://dm299.taobao.com  开发QQ:124861234  禁止倒卖 一经发现停止任何服务
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: derek $
 * $Id: index.php 17217 2011-01-19 06:29:08Z derek $
*/

define('IN_ECS', true);


require(dirname(__FILE__) . '/includes/init.php');
if ((DEBUG_MODE & 2) != 2)
{
    $smarty->caching = true;
}

if(empty($_SESSION['user_id']))
{
    header("Location:user.php");
}
$action = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : 'default';
if($action=='default')
{
    assign_template();
    $position = assign_ur_here();
    $smarty->assign('page_title',      "海报分享");    // 页面标题
    $smarty->assign('ur_here',         $position['ur_here']);  // 当前位置
    require('../includes/lib_user_share.php');
    $sql=" SELECT id,add_time FROM " . $ecs->table('bill_template') . " WHERE poster_type=0 order by id desc ";
    $bill_template_list=$db->getAll($sql);

    $sql=" SELECT nickname,headimgurl FROM " . $ecs->table('weixin_user') . " WHERE ecuid='" . $_SESSION['user_id'] . "'";
    $weixin_user=$db->getRow($sql);

    foreach($bill_template_list as $key=>$val) {
        $bill_template_list[$key]['key'] = $key+1;
     /*   $bill_id=$val['id'];
        //海报生成

        $sql=" SELECT * FROM " . $ecs->table('bill_template') . " WHERE id='" . $bill_id . "'";
        $bill_template=$db->getRow($sql);

        $sql=" SELECT * FROM " . $ecs->table('bill_text') . " WHERE bill_id='" . $bill_id . "'";
        $bill_text=$db->getAll($sql);

        $sql=" SELECT * FROM " . $ecs->table('bill_img') . " WHERE bill_id='" . $bill_id . "'";
        $bill_img=$db->getAll($sql);

        $sharepng_name="bill_template_user_".$_SESSION['user_id']."_".$bill_id."_".$bill_template['add_time'];
        $sharepng_file="/data/users/".$sharepng_name.".jpg";

        if((!file_exists('../'.$sharepng_file))&&$key==0)
        {
            $erweima_name="bill_user_".$_SESSION['user_id']."_".$bill_id;
            $erweima_url='http://' . $_SERVER ['HTTP_HOST'].'/mobile/weixin_login.php?user_id='.$_SESSION['user_id'].'&erweima_type=1';//$GLOBALS['ecs']->url()."user.php?u=".$_SESSION['user_id'];
            $user_name=$weixin_user['nickname'];
            $headimg_file=$weixin_user['headimgurl'];
            createSharePng($sharepng_name,$bill_template['background_w'],$bill_template['background_h'],$bill_template['background'],$erweima_name,$erweima_url,$bill_template['erweima_w'],$bill_template['erweima_h'],$bill_template['erweima_x'],$bill_template['erweima_y'],$headimg_file,$bill_template['headimg_status'],$bill_template['headimg_w'],$bill_template['headimg_h'],$bill_template['headimg_x'],$bill_template['headimg_y'],$user_name,$bill_template['user_name_status'],$bill_template['user_name_x'],$bill_template['user_name_y'],$bill_template['user_name_font'],$bill_template['user_name_color'],$bill_text,$bill_img);
        }*/
        $bill_template_list[$key]['file'] = "/data/users/bill_template_".$val['id']."_".$val['add_time'].".jpg";  //$sharepng_file;//
    }

    $smarty->assign('bill_template',      $bill_template_list);

    $smarty->assign('bill_template_count',      count($bill_template_list));


    $smarty->display('user_share_list.dwt');
}
elseif($action=="detail")
{
    $position = assign_ur_here();
    $smarty->assign('page_title',      "海报分享");    // 页面标题
    $smarty->assign('ur_here',         $position['ur_here']);  // 当前位置

    $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
    $bill_id=$id;
    $sql=" SELECT * FROM " . $ecs->table('bill_template') . " WHERE id='" . $id . "'";
    $bill_template=$db->getRow($sql);

    $sql=" SELECT * FROM " . $ecs->table('bill_text') . " WHERE bill_id='" . $id . "'";
    $bill_text=$db->getAll($sql);

    $sql=" SELECT * FROM " . $ecs->table('bill_img') . " WHERE bill_id='" . $id . "'";
    $bill_img=$db->getAll($sql);

    $sql=" SELECT nickname,headimgurl FROM " . $ecs->table('weixin_user') . " WHERE ecuid='" . $_SESSION['user_id'] . "'";
    $weixin_user=$db->getRow($sql);


    //更新一次生成默认海报
  /*  require('../includes/lib_user_share.php');

    $sql=" SELECT * FROM " . $ecs->table('bill_template') . " WHERE id='" . $bill_id . "'";
    $bill_template=$db->getRow($sql);

    $sql=" SELECT * FROM " . $ecs->table('bill_text') . " WHERE bill_id='" . $bill_id . "'";
    $bill_text=$db->getAll($sql);

    $sql=" SELECT * FROM " . $ecs->table('bill_img') . " WHERE bill_id='" . $bill_id . "'";
    $bill_img=$db->getAll($sql);

    $sharepng_name = "bill_template_user_" . $_SESSION['user_id'] . "_" . $bill_id . "_" . $bill_template['add_time'];
    $sharepng_file="/data/users/".$sharepng_name.".jpg";
    if((!file_exists('../'.$sharepng_file)))
    {
        $erweima_name = "bill_user_" . $_SESSION['user_id'] . "_" . $bill_id;
        $erweima_url = 'http://' . $_SERVER ['HTTP_HOST'] . '/mobile/weixin_login.php?user_id=' . $_SESSION['user_id'] . '&erweima_type=1';//$GLOBALS['ecs']->url()."user.php?u=".$_SESSION['user_id'];
        $user_name = $weixin_user['nickname'];
        $headimg_file = $weixin_user['headimgurl'];
        createSharePng($sharepng_name, $bill_template['background_w'], $bill_template['background_h'], $bill_template['background'], $erweima_name, $erweima_url, $bill_template['erweima_w'], $bill_template['erweima_h'], $bill_template['erweima_x'], $bill_template['erweima_y'], $headimg_file, $bill_template['headimg_status'], $bill_template['headimg_w'], $bill_template['headimg_h'], $bill_template['headimg_x'], $bill_template['headimg_y'], $user_name, $bill_template['user_name_status'], $bill_template['user_name_x'], $bill_template['user_name_y'], $bill_template['user_name_font'], $bill_template['user_name_color'], $bill_text, $bill_img);
    }
    $smarty->assign('sharepng_name', $sharepng_file);
    */
    $smarty->assign('index', $id);

    $smarty->display('user_share_detail.dwt');
}
elseif($action=="rule")
{
    $smarty->display('user_share_rule.dwt');
}
elseif($action=="img_detail")
{
    include('includes/cls_json.php');
    $json   = new JSON;

    $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
    $bill_id=$id;
    $sql=" SELECT * FROM " . $ecs->table('bill_template') . " WHERE id='" . $bill_id . "'";
    $bill_template=$db->getRow($sql);

    $sql=" SELECT * FROM " . $ecs->table('bill_text') . " WHERE bill_id='" . $bill_id . "'";
    $bill_text=$db->getAll($sql);

    $sql=" SELECT * FROM " . $ecs->table('bill_img') . " WHERE bill_id='" . $bill_id . "'";
    $bill_img=$db->getAll($sql);

    $sql=" SELECT nickname,headimgurl FROM " . $ecs->table('weixin_user') . " WHERE ecuid='" . $_SESSION['user_id'] . "'";
    $weixin_user=$db->getRow($sql);

    require('../includes/lib_user_share.php');
    $sharepng_name="bill_template_user_".$_SESSION['user_id']."_".$bill_id."_".$bill_template['add_time'];
    $sharepng_file="/data/users/".$sharepng_name.".jpg";
   // print_r($weixin_user);
   // exit;
    if(!file_exists('../'.$sharepng_file))
    {

        $erweima_name="bill_user_".$_SESSION['user_id']."_".$bill_id;
        
		$mysupllierId=$GLOBALS['db']->getOne("SELECT supplierId FROM ".$GLOBALS['ecs']->table('users')." WHERE user_id=".$_SESSION['user_id']);
		if($mysupllierId==0){
			$config = unserialize($GLOBALS['_CFG']['affiliate_supplier']);
			empty($config) && $config = array();
			$mysupllierId=$config['config']['default_reg_supplier_id'];
		}
		$erweima_url='http://' . $_SERVER ['HTTP_HOST'].'/mobile/supplier_qrcode.php?u='.$_SESSION['user_id'].'&suppId='.$mysupllierId.'&erweima_type=1';

        $user_name=$weixin_user['nickname'];
        $headimg_file=$weixin_user['headimgurl'];
        createSharePng($sharepng_name,$bill_template['background_w'],$bill_template['background_h'],$bill_template['background'],$erweima_name,$erweima_url,$bill_template['erweima_w'],$bill_template['erweima_h'],$bill_template['erweima_x'],$bill_template['erweima_y'],$headimg_file,$bill_template['headimg_status'],$bill_template['headimg_w'],$bill_template['headimg_h'],$bill_template['headimg_x'],$bill_template['headimg_y'],$user_name,$bill_template['user_name_status'],$bill_template['user_name_x'],$bill_template['user_name_y'],$bill_template['user_name_font'],$bill_template['user_name_color'],$bill_text,$bill_img);
    }
    $result['error'] = '0';
    $result['dat'] = $sharepng_file;
    die($json->encode($result));
}
?>

