<?php
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
require(dirname(__FILE__) . '/includes/lib_v_user.php');
if ((DEBUG_MODE & 2) != 2)
{
    $smarty->caching = true;
}
if($_REQUEST['act']=="bind")
{
    $parent_id = empty($_REQUEST['parent_id'])?0:  intval($_REQUEST['parent_id']);
    $smarty->assign('parent_id',      $parent_id);
    $position = assign_ur_here();
    $smarty->assign('page_title',      $position['title']);    // 页面标题
    $smarty->assign('ur_here',         $position['ur_here']);  // 当前位置
    /* meta information */
    $smarty->assign('keywords',        htmlspecialchars($_CFG['shop_keywords']));
    $smarty->assign('description',     htmlspecialchars($_CFG['shop_desc']));
    $smarty->assign('user_info',get_user_info_by_user_id($_SESSION['user_id']));
    $smarty->display('affiliate_login_bind.dwt');
}elseif($_REQUEST['act']=="checkmobile"){
	$mobile = trim($_REQUEST['mobile_phone']);
	$verifycode = trim($_REQUEST['verifycode']);
	$sql = "SELECT COUNT(a.uid) FROM ".$GLOBALS['ecs']->table('weixin_user')." a LEFT JOIN ".$GLOBALS['ecs']->table('users')." b ON a.ecuid=b.user_id WHERE b.mobile_phone = '$mobile'";
    if ($GLOBALS['db']->getOne($sql) > 0)
    {
        $result['error'] = 3;
		$result['message'] = '该手机已经绑定微信账号！';
		echo json_encode($result);exit;
    }

	$sql = "SELECT verifycode,dateline FROM ". $GLOBALS['ecs']->table('verifycode') ." WHERE status=1 AND mobile='$mobile' ORDER BY dateline DESC LIMIT 1";
	$verify_info = $GLOBALS['db']->getRow($sql);
	$nowtime = gmtime()-1800;
	if(!isset($verify_info) || empty($verify_info)){
		echo json_encode(array('error'=>1,'message'=>'验证码错误'));exit;
	}elseif($verifycode != $verify_info['verifycode']){
		echo json_encode(array('error'=>2,'message'=>'验证码错误'));exit;
	}elseif($nowtime>$verify_info['dateline']){
		$db->query("delete from ".$ecs->table('verifycode')." where mobile='$mobile'");
		echo json_encode(array('error'=>3,'message'=>'验证码已过期'));exit;
	}else{
		$db->query("delete from ".$ecs->table('verifycode')." where mobile='$mobile'");
		echo json_encode(array('error'=>0,'message'=>'验证成功'));exit;
	}
}
?>