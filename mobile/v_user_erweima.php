<?php


define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(dirname(__FILE__) . '/includes/lib_v_user.php');
require(dirname(__FILE__) . '/weixin/wechat.class.php');
if ((DEBUG_MODE & 2) != 2)
{
    $smarty->caching = true;
}

if($_CFG['is_distrib'] == 0)
{
	show_message('没有开启微信分销服务！','返回首页','index.php');
}


//if(isset($_GET['user_id']) && intval($_GET['user_id']) > 0)
//{
	//$user_id = intval($_GET['user_id']);
//}
//else
//{
	 //ecs_header("Location:./\n");
	 //exit;
//}

//if($_SESSION['user_id'] != $user_id && $user_id > 0)
//{
	//$weixinconfig = $GLOBALS['db']->getRow( "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_config') . " WHERE `id` = 1" );
	//$weixin = new core_lib_wechat($weixinconfig);
	//$openid = '';
	//if($_GET['code'])
	//{
		//$json = $weixin->getOauthAccessToken();
		//$openid = $json['openid'];
		//if($openid)
		//{
			//$info = $weixin->getOauthUserinfo($json['access_token'],$openid);
			//$nickname = $info['nickname'];
			//$sex = intval($info['sex']);
			//$country = $info['country'];
			//$province = $info['province'];
			//$city = $info['city'];
			//$headimgurl = $info['headimgurl'];
			//$createtime = gmtime();
			//$createymd = date('Y-m-d');
			//$rows = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('weixin_user') . " WHERE fake_id='{$openid}'");
			//if($rows)
			//{
				 //$set = "`nickname`='{$nickname}',`sex`='$sex'," .
				 		//"`country`='$country',`province`='$province'," .
						//"`city`='$city',`headimgurl`='$headimgurl'";
				 //$sql = "UPDATE " . $GLOBALS['ecs']->table('weixin_user') .
				 		//" SET {$set} WHERE fake_id='" . $openid . "'";
				 //$GLOBALS['db']->query($sql);
			//}
			//else
			//{
				 //$sql = "INSERT INTO " .
				 		//$GLOBALS['ecs']->table('weixin_user') .
						//" (`ecuid`,`fake_id`,`createtime`,`createymd`," .
						//"`isfollow`,`nickname`,`sex`,`country`,`province`,".
						//"`city`,`headimgurl`) values " .
						//"(0,'{$openid}','{$createtime}','{$createymd}',".
						//"0,'{$nickname}','{$sex}','{$country}','{$province}'," .
						//"'{$city}','{$headimgurl}')";
				 //$GLOBALS['db']->query($sql);
			//}
			//$user_info = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('weixin_user') . " WHERE fake_id='{$openid}'");
			//if($user_info['ecuid'] == 0)
			//{
				 //ecs_header("Location:register.php?u={$user_id}\n");
				 //exit;
			//}
		//}
	//}
	//if(empty($openid) || $openid == '')
	//{
		//$url = $GLOBALS['ecs']->url()."v_user_erweima.php?user_id=" . $user_id;  // prince qq 1 2 00 29121
		//$url = $weixin->getOauthRedirect($url,1,'snsapi_userinfo');
		//// header("Location:$url");exit;
	//}
//}


//获取用户对应的所属店铺
$myuid = intval($_SESSION['user_id']);
if(!$myuid){
	ecs_header("Location: user.php?act=login");
    exit;
}


//是否生成过二维码
if(is_erweima($_SESSION['user_id']) == 0)
{
	$mysupllierId=$GLOBALS['db']->getOne("SELECT supplierId FROM ".$GLOBALS['ecs']->table('users')." WHERE user_id=".$myuid);
	if($mysupllierId==0){
		$config = unserialize($GLOBALS['_CFG']['affiliate_supplier']);
		empty($config) && $config = array();
		$mysupllierId=$config['config']['default_reg_supplier_id'];
	}
	$selfUrl='http://' . $_SERVER ['HTTP_HOST'].'/mobile/supplier_qrcode.php?u='.$_SESSION['user_id'].'&suppId='.$mysupllierId.'&erweima_type=1';
	$smarty->assign('self_url',$selfUrl);

	require('includes/phpqrcode.php');
	$qrcode = new QRcode();
	$imagesname = time().$myuid.".png";
	$qcode = $qrcode->png($selfUrl,"images/qrcode/".$imagesname,"L",6,2);
	$sql = "INSERT INTO ".$GLOBALS['ecs']->table('user_qrcode')."(user_id, code) value ('$myuid', 'images/qrcode/$imagesname')";
	$GLOBALS['db']->query($sql);
}

if (!$smarty->is_cached('v_user_erweima.dwt', $cache_id))
{
    assign_template();

    $position = assign_ur_here();
    $smarty->assign('page_title',      $position['title']);    // 页面标题
    $smarty->assign('ur_here',         $position['ur_here']);  // 当前位置

    /* meta information */
    $smarty->assign('keywords',        htmlspecialchars($_CFG['shop_keywords']));
    $smarty->assign('description',     htmlspecialchars($_CFG['shop_desc']));
	$smarty->assign('user_info',	   get_user_info_by_user_id($_SESSION['user_id']));
	$smarty->assign('erweima',get_erweima_by_user_id($_SESSION['user_id']));
	$smarty->assign('user_id',$_SESSION['user_id']);
    //zhouhui 如果是获取的uid 则读取它.
    //if($user_id){
    	//$smarty->assign('user_info',	   get_user_info_by_user_id($user_id));
    	//$smarty->assign('erweima',get_erweima_by_user_id($user_id));
		//$smarty->assign('user_id',$user_id);
    //}else
    //{
		//$smarty->assign('user_info',	   get_user_info_by_user_id($_SESSION['user_id']));
		//$smarty->assign('erweima',get_erweima_by_user_id($_SESSION['user_id']));
		//$smarty->assign('user_id',$_SESSION['user_id']);
	//}


    /* 页面中的动态内容 */
    assign_dynamic('v_user_erweima');
}

$smarty->display('v_user_erweima.dwt', $cache_id);

?>