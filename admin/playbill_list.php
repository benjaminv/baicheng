<?php

/**
 * ECSHOP 会员管理程序
 * ============================================================================
 * 版权所有 2017-2020 月梦网络，并保留所有权利。
 * 月梦网络: http://dm299.taobao.com  开发QQ:124861234  禁止倒卖 一经发现停止任何服务
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: users.php 17217 2011-01-19 06:29:08Z liubo $
 */
define('IN_ECS', true);

require (dirname(__FILE__) . '/includes/init.php');
/* 代码增加2014-12-23 by www.68ecshop.com _star */
include_once (ROOT_PATH . '/includes/cls_image.php');
$image = new cls_image($_CFG['bgcolor']);
/* 代码增加2014-12-23 by www.68ecshop.com _end */

$action = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : 'list';

/* 路由 */

$function_name = 'action_' . $action;

if(! function_exists($function_name))
{
	$function_name = "action_list";
}

call_user_func($function_name);

/* 路由 */

/* ------------------------------------------------------ */
// -- 用户帐号列表
/* ------------------------------------------------------ */
function action_list ()
{
	// 全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];

	/* 检查权限 */
	admin_priv('users_manage');
	$sql = "SELECT rank_id, rank_name, min_points FROM " . $ecs->table('user_rank') . " ORDER BY min_points ASC ";
	$rs = $db->query($sql);

	$ranks = array();
	while($row = $db->FetchRow($rs))
	{
		$ranks[$row['rank_id']] = $row['rank_name'];
	}

	$smarty->assign('user_ranks', $ranks);
	$smarty->assign('ur_here', "海报模板");
	$smarty->assign('action_link', array(
		'text' => "添加模板",'href' => 'playbill_list.php?act=add'
	));

	$user_list = user_list();

	$smarty->assign('user_list', $user_list['user_list']);
	$smarty->assign('filter', $user_list['filter']);
	$smarty->assign('record_count', $user_list['record_count']);
	$smarty->assign('page_count', $user_list['page_count']);
	$smarty->assign('full_page', 1);
	$smarty->assign('sort_user_id', '<img src="images/sort_desc.gif">');

	assign_query_info();
	$smarty->display('playbill_list.htm');
}

/* ------------------------------------------------------ */
// -- ajax返回用户列表
/* ------------------------------------------------------ */
function action_query ()
{
	// 全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];

	$user_list = user_list();

	$smarty->assign('user_list', $user_list['user_list']);
	$smarty->assign('filter', $user_list['filter']);
	$smarty->assign('record_count', $user_list['record_count']);
	$smarty->assign('page_count', $user_list['page_count']);

	$sort_flag = sort_flag($user_list['filter']);
	$smarty->assign($sort_flag['tag'], $sort_flag['img']);

	make_json_result($smarty->fetch('playbill_list.htm'), '', array(
		'filter' => $user_list['filter'],'page_count' => $user_list['page_count']
	));
}

/* ------------------------------------------------------ */
// -- 添加会员帐号
/* ------------------------------------------------------ */
function action_add ()
{
	// 全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];

	/* 检查权限 */
	admin_priv('users_manage');

	$smarty->assign('ur_here', "添加模板");
	$smarty->assign('action_link', array(
		'text' => "模板列表",'href' => 'playbill_list.php?act=list'
	));
	$smarty->assign('form_action', 'insert');
	$smarty->assign('user', $user);


	$smarty->assign('lang', $_LANG);


	assign_query_info();
	$smarty->display('playbill_info.htm');
}

/* ------------------------------------------------------ */
// -- 添加会员帐号
/* ------------------------------------------------------ */
function action_insert ()
{
	// 全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];

	/* 检查权限 */
	admin_priv('users_manage');

    if (!$_FILES["background"]["name"]) {
        sys_msg("海报底板文件!");
    }
    if ((($_FILES["background"]["type"] == "image/gif")
            || ($_FILES["background"]["type"] == "image/jpeg")
            || ($_FILES["background"]["type"] == "image/pjpeg"))
        && ($_FILES["background"]["size"] < 20000000))
    {
        if ($_FILES["background"]["error"] > 0)
        {
            sys_msg("海报底板上传出错");
        }
        else
        {
            $img="/data/complaint_imges/" . time() .$_FILES["background"]["name"];
            if (!file_exists(ROOT_PATH ."/data/complaint_imges/")) {
                mkdir(ROOT_PATH ."/data/complaint_imges/");
            }
            move_uploaded_file($_FILES["background"]["tmp_name"],
                ROOT_PATH . $img);

        }
    }
    else
    {
        sys_msg("海报底板上传出错");
    }

    $other = array();
    $other['background'] = $img;

    $other['template_name'] = empty($_POST['template_name']) ? '' : trim($_POST['template_name']);
    $other['template_remarks'] = empty($_POST['template_remarks']) ? '' : trim($_POST['template_remarks']);
    $other['template_type'] = empty($_POST['template_type']) ? 1 : intval($_POST['template_type']);

    $other['background_w'] = empty($_POST['background_w']) ? 10 : intval($_POST['background_w']);
    $other['background_h'] = empty($_POST['background_h']) ? 10 : intval($_POST['background_h']);
    $other['headimg_status'] = empty($_POST['headimg_status']) ? 0 : intval($_POST['headimg_status']);
    $other['headimg_w'] = empty($_POST['headimg_w']) ? 10 : intval($_POST['headimg_w']);
    $other['headimg_h'] = empty($_POST['headimg_h']) ? 10 : intval($_POST['headimg_h']);
    $other['headimg_x'] = empty($_POST['headimg_x']) ? 0 : intval($_POST['headimg_x']);
    $other['headimg_y'] = empty($_POST['headimg_y']) ? 0 : intval($_POST['headimg_y']);
    $other['user_name_status'] = empty($_POST['user_name_status']) ? 0 : intval($_POST['user_name_status']);
    $other['user_name_x'] = empty($_POST['user_name_x']) ? 0 : intval($_POST['user_name_x']);
    $other['user_name_y'] = empty($_POST['user_name_y']) ? 0 : intval($_POST['user_name_y']);
    $other['user_name_font'] = empty($_POST['user_name_font']) ? 0 : intval($_POST['user_name_font']);
    $other['user_name_color'] = empty($_POST['user_name_color']) ? '' : trim($_POST['user_name_color']);
    $other['erweima_w'] = empty($_POST['erweima_w']) ? 10 : intval($_POST['erweima_w']);
    $other['erweima_h'] = empty($_POST['erweima_h']) ? 10 : intval($_POST['erweima_h']);
    $other['erweima_x'] = empty($_POST['erweima_x']) ? 0 : intval($_POST['erweima_x']);
    $other['erweima_y'] = empty($_POST['erweima_y']) ? 0 : intval($_POST['erweima_y']);
    $other['add_time'] = gmtime();
	$other['poster_type'] = empty($_POST['poster_type']) ? 0 : intval($_POST['poster_type']);

    $db->autoExecute($ecs->table('bill_template'), $other, 'INSERT');
    $bill_id = $GLOBALS['db']->insert_id();

    //指定文字
    $bill_text_count=count($_POST['cfg_value']['text_x']);
    if($bill_text_count>0)
    {
        for ($x=0; $x<$bill_text_count; $x++) {
			if(!empty($_POST['cfg_value']['text'][$x]))
			{
                $text_x = empty($_POST['cfg_value']['text_x'][$x]) ? 0 : intval($_POST['cfg_value']['text_x'][$x]);
                $text_y = empty($_POST['cfg_value']['text_y'][$x]) ? 0 : intval($_POST['cfg_value']['text_y'][$x]);
                $text_font = empty($_POST['cfg_value']['text_font'][$x]) ? 10 : intval($_POST['cfg_value']['text_font'][$x]);
                $text_color=$_POST['cfg_value']['text_color'][$x];
                $text=$_POST['cfg_value']['text'][$x];

                $sql = "insert into ".$GLOBALS['ecs']->table('bill_text')." (bill_id, text_x, text_y, text_font, text_color, text) values ('$bill_id', '$text_x', '$text_y', '$text_font', '$text_color', '$text')";
                $GLOBALS['db']->query($sql);
			}
        }
    }
    //指定图片
    $bill_img_count=count($_POST['img_value']['img_w']);
    if($bill_img_count>0)
    {
        for ($x=0; $x<$bill_img_count; $x++) {
            if(!empty($_POST['img_value']['img_w'][$x]))
            {
                $img_w = empty($_POST['img_value']['img_w'][$x]) ? 10 : intval($_POST['img_value']['img_w'][$x]);
                $img_h = empty($_POST['img_value']['img_h'][$x]) ? 10 : intval($_POST['img_value']['img_h'][$x]);
                $img_x = empty($_POST['img_value']['img_x'][$x]) ? 0 : intval($_POST['img_value']['img_x'][$x]);
                $img_y = empty($_POST['img_value']['img_y'][$x]) ? 0 : intval($_POST['img_value']['img_y'][$x]);

                //图片处理
                $img='';
                if ((($_FILES["img"]["type"][$x] == "image/gif")
                        || ($_FILES["img"]["type"][$x] == "image/jpeg")
                        || ($_FILES["img"]["type"][$x] == "image/pjpeg"))
                    && ($_FILES["img"]["size"][$x] < 20000000))
                {
                    if ($_FILES["img"]["error"][$x] > 0)
                    {
                        ;//sys_msg("海报底板上传出错");
                    }
                    else
                    {
                        $img="/data/complaint_imges/" . time() .$_FILES["img"]["name"][$x];
                        if (!file_exists(ROOT_PATH ."/data/complaint_imges/")) {
                            mkdir(ROOT_PATH ."/data/complaint_imges/");
                        }
                        move_uploaded_file($_FILES["img"]["tmp_name"][$x],
                            ROOT_PATH . $img);

                    }
                }
                else
                {
                    ;//sys_msg("海报底板上传出错");
                }
                if(!empty($img))
				{
                    $sql = "insert into ".$GLOBALS['ecs']->table('bill_img')." (bill_id, img_w, img_h, img_x, img_y,img) values ('$bill_id', '$img_w', '$img_h', '$img_x', '$img_y', '$img')";
                    $GLOBALS['db']->query($sql);
				}
            }
        }
    }

    //更新一次生成默认海报
    require('../includes/lib_user_share.php');
    $sql=" SELECT * FROM " . $ecs->table('bill_template') . " WHERE id='" . $bill_id . "'";
    $bill_template=$db->getRow($sql);

    $sql=" SELECT * FROM " . $ecs->table('bill_text') . " WHERE bill_id='" . $bill_id . "'";
    $bill_text=$db->getAll($sql);

    $sql=" SELECT * FROM " . $ecs->table('bill_img') . " WHERE bill_id='" . $bill_id . "'";
    $bill_img=$db->getAll($sql);

    $sharepng_name="bill_template_".$bill_id."_".$other['add_time'];
    $erweima_name="bill_template_user_".$bill_id;
    $erweima_url=$GLOBALS['ecs']->url();
    $user_name="用户名";
    $headimg_file=ROOT_PATH."/includes/code_png/touxiang.png";
    createSharePng($sharepng_name,$bill_template['background_w'],$bill_template['background_h'],$bill_template['background'],$erweima_name,$erweima_url,$bill_template['erweima_w'],$bill_template['erweima_h'],$bill_template['erweima_x'],$bill_template['erweima_y'],$headimg_file,$bill_template['headimg_status'],$bill_template['headimg_w'],$bill_template['headimg_h'],$bill_template['headimg_x'],$bill_template['headimg_y'],$user_name,$bill_template['user_name_status'],$bill_template['user_name_x'],$bill_template['user_name_y'],$bill_template['user_name_font'],$bill_template['user_name_color'],$bill_text,$bill_img);



    /* 提示信息 */
	$link[] = array(
		'text' => $_LANG['go_back'],'href' => 'playbill_list.php?act=list'
	);
	sys_msg("添加模板成功", 0, $link);
}

/* ------------------------------------------------------ */
// -- 编辑用户帐号
/* ------------------------------------------------------ */
function action_edit ()
{
	// 全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];

	/* 检查权限 */
	admin_priv('users_manage');

	$sql = "SELECT * " . " FROM " . $ecs->table('bill_template') . "  WHERE id ='$_GET[id]'";
    $user = $db->GetRow($sql);
    if(empty($user))
	{
        sys_msg("没有此模板信息");
	}

    $sql = "SELECT * " . " FROM " . $ecs->table('bill_text') . "  WHERE bill_id ='$_GET[id]'";
    $bill_text = $db->GetAll($sql);
	foreach($bill_text as $key=>$val) {
        $bill_text[$key]['key']=$key;
	}
    $smarty->assign('bill_text', $bill_text);

    $sql = "SELECT * " . " FROM " . $ecs->table('bill_img') . "  WHERE bill_id ='$_GET[id]'";
    $bill_img = $db->GetAll($sql);
    foreach($bill_img as $key=>$val) {
        $bill_img[$key]['key']=$key;
    }
    $smarty->assign('bill_img', $bill_img);


	assign_query_info();
	$smarty->assign('ur_here', "模板编辑");
	$smarty->assign('action_link', array(
		'text' => "模板列表",'href' => 'playbill_list.php?act=list&' . list_link_postfix()
	));
	$smarty->assign('playbill', $user);

	$smarty->assign('form_action', 'update');
	$smarty->display('playbill_info.htm');
}

/* ------------------------------------------------------ */
// -- 更新用户帐号
/* ------------------------------------------------------ */
function action_update ()
{
	// 全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];

	/* 检查权限 */
	admin_priv('users_manage');

    $other = array();
    $other['background'] = empty($_POST['background_img']) ? '' : trim($_POST['background_img']);

    $other['template_name'] = empty($_POST['template_name']) ? '' : trim($_POST['template_name']);
    $other['template_remarks'] = empty($_POST['template_remarks']) ? '' : trim($_POST['template_remarks']);
    $other['template_type'] = empty($_POST['template_type']) ? 1 : intval($_POST['template_type']);

    $other['background_w'] = empty($_POST['background_w']) ? 10 : intval($_POST['background_w']);
    $other['background_h'] = empty($_POST['background_h']) ? 10 : intval($_POST['background_h']);
    $other['headimg_status'] = empty($_POST['headimg_status']) ? 0 : intval($_POST['headimg_status']);
    $other['headimg_w'] = empty($_POST['headimg_w']) ? 10 : intval($_POST['headimg_w']);
    $other['headimg_h'] = empty($_POST['headimg_h']) ? 10 : intval($_POST['headimg_h']);
    $other['headimg_x'] = empty($_POST['headimg_x']) ? 0 : intval($_POST['headimg_x']);
    $other['headimg_y'] = empty($_POST['headimg_y']) ? 0 : intval($_POST['headimg_y']);
    $other['user_name_status'] = empty($_POST['user_name_status']) ? 0 : intval($_POST['user_name_status']);
    $other['user_name_x'] = empty($_POST['user_name_x']) ? 0 : intval($_POST['user_name_x']);
    $other['user_name_y'] = empty($_POST['user_name_y']) ? 0 : intval($_POST['user_name_y']);
    $other['user_name_font'] = empty($_POST['user_name_font']) ? 0 : intval($_POST['user_name_font']);
    $other['user_name_color'] = empty($_POST['user_name_color']) ? '' : trim($_POST['user_name_color']);
    $other['erweima_w'] = empty($_POST['erweima_w']) ? 10 : intval($_POST['erweima_w']);
    $other['erweima_h'] = empty($_POST['erweima_h']) ? 10 : intval($_POST['erweima_h']);
    $other['erweima_x'] = empty($_POST['erweima_x']) ? 0 : intval($_POST['erweima_x']);
    $other['erweima_y'] = empty($_POST['erweima_y']) ? 0 : intval($_POST['erweima_y']);
    $other['add_time'] = gmtime();
	$other['poster_type'] = empty($_POST['poster_type']) ? 0 : intval($_POST['poster_type']);
    if ($_FILES["background"]["name"]) {
        if ((($_FILES["background"]["type"] == "image/gif")
                || ($_FILES["background"]["type"] == "image/jpeg")
                || ($_FILES["background"]["type"] == "image/pjpeg"))
            && ($_FILES["background"]["size"] < 200000000))
        {
            if ($_FILES["background"]["error"] > 0)
            {
                sys_msg("海报底板上传出错");
            }
            else
            {
                $img="/data/complaint_imges/" . time() .$_FILES["background"]["name"];
                if (!file_exists(ROOT_PATH ."/data/complaint_imges/")) {
                    mkdir(ROOT_PATH ."/data/complaint_imges/");
                }
                move_uploaded_file($_FILES["background"]["tmp_name"],
                    ROOT_PATH . $img);
                $other['background'] =$img;
            }
        }
        else
        {
            sys_msg("海报底板文件格式出错");
        }
    }

	$db->autoExecute($ecs->table('bill_template'), $other, 'UPDATE', "id = '$_POST[id]'");

    $sql = "DELETE FROM ". $ecs->table('bill_text') ." where bill_id = '$_POST[id]'";
    $db->query($sql);

    $sql = "DELETE FROM ". $ecs->table('bill_img') ." where bill_id = '$_POST[id]'";
    $db->query($sql);
    $bill_id=$_POST['id'];
    //指定文字
    $bill_text_count=count($_POST['cfg_value']['text_x']);
    if($bill_text_count>0)
    {
        for ($x=0; $x<$bill_text_count; $x++) {
            if(!empty($_POST['cfg_value']['text'][$x]))
            {
                $text_x = empty($_POST['cfg_value']['text_x'][$x]) ? 0 : intval($_POST['cfg_value']['text_x'][$x]);
                $text_y = empty($_POST['cfg_value']['text_y'][$x]) ? 0 : intval($_POST['cfg_value']['text_y'][$x]);
                $text_font = empty($_POST['cfg_value']['text_font'][$x]) ? 10 : intval($_POST['cfg_value']['text_font'][$x]);
                $text_color=$_POST['cfg_value']['text_color'][$x];
                $text=$_POST['cfg_value']['text'][$x];

                $sql = "insert into ".$GLOBALS['ecs']->table('bill_text')." (bill_id, text_x, text_y, text_font, text_color, text) values ('$bill_id', '$text_x', '$text_y', '$text_font', '$text_color', '$text')";
                $GLOBALS['db']->query($sql);
            }
        }
    }
    //指定图片
    $bill_img_count=count($_POST['img_value']['img_w']);
    if($bill_img_count>0)
    {
        for ($x=0; $x<$bill_img_count; $x++) {
            if(!empty($_POST['img_value']['img_w'][$x]))
            {
                $img_w = empty($_POST['img_value']['img_w'][$x]) ? 10 : intval($_POST['img_value']['img_w'][$x]);
                $img_h = empty($_POST['img_value']['img_h'][$x]) ? 10 : intval($_POST['img_value']['img_h'][$x]);
                $img_x = empty($_POST['img_value']['img_x'][$x]) ? 0 : intval($_POST['img_value']['img_x'][$x]);
                $img_y = empty($_POST['img_value']['img_y'][$x]) ? 0 : intval($_POST['img_value']['img_y'][$x]);
                $img_old=$_POST['img_value']['img_old'][$x];
				$alias = empty($_POST['img_value']['alias'][$x]) ? "" : trim($_POST['img_value']['alias'][$x]);
                //图片处理
                $img='';
                if ((($_FILES["img"]["type"][$x] == "image/gif")
                        || ($_FILES["img"]["type"][$x] == "image/jpeg")
                        || ($_FILES["img"]["type"][$x] == "image/pjpeg"))
                    && ($_FILES["img"]["size"][$x] < 20000000))
                {
                    if ($_FILES["img"]["error"][$x] > 0)
                    {
                        ;//sys_msg("海报底板上传出错");
                    }
                    else
                    {
                        $img="/data/complaint_imges/" . time() .$_FILES["img"]["name"][$x];
                        if (!file_exists(ROOT_PATH ."/data/complaint_imges/")) {
                            mkdir(ROOT_PATH ."/data/complaint_imges/");
                        }
                        move_uploaded_file($_FILES["img"]["tmp_name"][$x],
                            ROOT_PATH . $img);

                    }
                }
                else
                {
                    //sys_msg("海报底板上传出错");
                }
                if(empty($img))
                {
                    $img=$img_old;
                }
				
                if(!empty($img))
                {
                    $sql = "insert into ".$GLOBALS['ecs']->table('bill_img')." (bill_id, img_w, img_h, img_x, img_y,img,alias) values ('$bill_id', '$img_w', '$img_h', '$img_x', '$img_y', '$img', '$alias')";
                    $GLOBALS['db']->query($sql);
                }
            }
        }
		
    }

    //更新一次生成默认海报
    require('../includes/lib_user_share.php');
    $sql=" SELECT * FROM " . $ecs->table('bill_template') . " WHERE id='" . $bill_id . "'";
    $bill_template=$db->getRow($sql);

    $sql=" SELECT * FROM " . $ecs->table('bill_text') . " WHERE bill_id='" . $bill_id . "'";
    $bill_text=$db->getAll($sql);

    $sql=" SELECT * FROM " . $ecs->table('bill_img') . " WHERE bill_id='" . $bill_id . "'";
    $bill_img=$db->getAll($sql);

    $sharepng_name="bill_template_".$bill_id."_".$other['add_time'];
    $erweima_name="bill_template_user_".$bill_id;
    $erweima_url=$GLOBALS['ecs']->url();
    $user_name="用户名";
    $headimg_file=ROOT_PATH."/includes/code_png/touxiang.png";
    createSharePng($sharepng_name,$bill_template['background_w'],$bill_template['background_h'],$bill_template['background'],$erweima_name,$erweima_url,$bill_template['erweima_w'],$bill_template['erweima_h'],$bill_template['erweima_x'],$bill_template['erweima_y'],$headimg_file,$bill_template['headimg_status'],$bill_template['headimg_w'],$bill_template['headimg_h'],$bill_template['headimg_x'],$bill_template['headimg_y'],$user_name,$bill_template['user_name_status'],$bill_template['user_name_x'],$bill_template['user_name_y'],$bill_template['user_name_font'],$bill_template['user_name_color'],$bill_text,$bill_img);
	/* 提示信息 */
	$links[0]['text'] = "模板列表";
	$links[0]['href'] = 'playbill_list.php?act=list&' . list_link_postfix();
	sys_msg("更新成功！", 0, $links);
}

/* ------------------------------------------------------ */
// -- 批量删除会员帐号
/* ------------------------------------------------------ */
function action_batch_remove ()
{
	// 全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];

	/* 检查权限 */
	admin_priv('users_drop');

	if(isset($_POST['checkboxes']))
	{
		$sql = "SELECT id FROM " . $ecs->table('bill_template') . " WHERE id " . db_create_in($_POST['checkboxes']);
		$col = $db->getCol($sql);
        $sql="DELETE FROM ". $ecs->table('bill_template') . " WHERE id " . db_create_in($_POST['checkboxes']);
        $db->query($sql);

		$lnk[] = array(
			'text' => $_LANG['go_back'], 'href' => 'playbill_list.php?act=list'
		);
		sys_msg("删除成功！", 0, $lnk);
	}
	else
	{
		$lnk[] = array(
			'text' => $_LANG['go_back'], 'href' => 'playbill_list.php?act=list'
		);
		sys_msg("未选择对应模板", 0, $lnk);
	}
}



/* ------------------------------------------------------ */
// -- 删除会员帐号
/* ------------------------------------------------------ */
function action_remove ()
{
	// 全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];

	/* 检查权限 */
	admin_priv('users_drop');
	$sql=" SELECT id FROM " . $ecs->table('bill_template') . " WHERE id='" . $_GET['id'] . "'";
	$bill_template=$db->getOne($sql);
	if(empty($bill_template)){
		sys_msg(sprintf('没有此模板！'));
	}else{

		$sql="DELETE FROM ". $ecs->table('bill_template') ." WHERE id= ". $_GET['id'];
        $db->query($sql);
		/* 提示信息 */
		$link[] = array(
			'text' => "返回模板列表", 'href' => 'playbill_list.php?act=list'
		);
		sys_msg("删除成功！", 0, $link);
	}
}


/* ------------------------------------------------------ */
// -- 收货地址查看
/* ------------------------------------------------------ */
function action_showbill ()
{
	// 全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $_SESSION['user_id'];

    $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
    $sql=" SELECT * FROM " . $ecs->table('bill_template') . " WHERE id='" . $id . "'";
    $bill_template=$db->getRow($sql);

    $sql=" SELECT * FROM " . $ecs->table('bill_text') . " WHERE bill_id='" . $id . "'";
    $bill_text=$db->getAll($sql);

    $sql=" SELECT * FROM " . $ecs->table('bill_img') . " WHERE bill_id='" . $id . "'";
    $bill_img=$db->getAll($sql);

    $sharepng_name="/data/users/bill_template_".$id."_".$bill_template['add_time'].".jpg";

    $smarty->assign('sharepng_name', $sharepng_name);


    $smarty->display('template_showbill.htm');
}


/**
 * 返回用户列表数据
 *
 * @access public
 * @param
 *
 * @return void
 */
function user_list ()
{
	$result = get_filter();
	if($result === false)
	{
		/* 过滤条件 */
		$filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
		if(isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
		{
			$filter['keywords'] = json_str_iconv($filter['keywords']);
		}


		$filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'id' : trim($_REQUEST['sort_by']);
		$filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

		$ex_where = ' WHERE 1 ';
		if($filter['keywords'])
		{
			$ex_where .= " AND  b.template_name LIKE '%" . mysql_like_quote($filter['keywords']) . "%' ";
		}


		$filter['record_count'] = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('bill_template')."as b ". $ex_where);

		/* 分页大小 */
		$filter = page_and_size($filter);
		if(!empty($filter['sort_by'])){
			$a = $filter['sort_by'];
			$b = substr($a,0,2);
			if($b !== 'b.'){
				$filter['sort_by'] ="b.".$a;
			}
		}
		$sql = "SELECT b.* ".
                " FROM " . $GLOBALS['ecs']->table('bill_template')."as b ". $ex_where . " ".
				"ORDER by " . $filter['sort_by'] . ' ' . $filter['sort_order'] . " LIMIT " . $filter['start'] . ',' . $filter['page_size'];
		$filter['keywords'] = stripslashes($filter['keywords']);
		set_filter($filter, $sql);
	}
	else
	{
		$sql = $result['sql'];
		$filter = $result['filter'];
	}

	$user_list = $GLOBALS['db']->getAll($sql);
	foreach($user_list as $key=>$val) {
        $user_list[$key]['reg_time'] = local_date($GLOBALS['_CFG']['date_format'], $val['add_time']);
	}
	$arr = array(
		'user_list' => $user_list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']
	);

	return $arr;
}

function action_sync_erp(){
	//同步会员到erp
	$user_id = intval($_REQUEST['user_id']);
	$link[] = array('href' => 'users.php?act=list', 'text' => "会员列表");
	if(!$user_id){
		sys_msg("缺少必要参数", 0, $link);
	}else{
		if(ODOO_ERP){
			$odooErpObj = OdooErp::getInstance();
			$res = $odooErpObj->syncUserByUserid($user_id);
			sys_msg("加入队列成功", 1, $link);exit;
			//if(isset($res['SuccessCode']) && $res['SuccessCode'] == 1){
				//sys_msg("同步成功", 1, $link);exit;
			//}else{
				//sys_msg("同步失败:".$res['faultString'], 0, $links);exit;
			//}
		}else{
			sys_msg("系统未开启同步", 0, $link);
		}
	}
}
?>
