<?php
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
require(dirname(__FILE__) . '/weixin/wechat.class.php');

$hashid = $_GET['user_id'];//user_id=vVzq5D
$rest = file_get_contents(OSS_LOGIN."/rest/api/sso/getuserinfo?hashid=" . $hashid);
$info = json_decode($rest, true);
if(!isset($info['id'])) return;
$sql = "SELECT user_id FROM " .$GLOBALS['ecs']->table('users') ." WHERE portal_id = '" . $info['id'] . "'";
$user_id = $GLOBALS['db']->getOne($sql);
//print_r($user_id);die();
if(isset($user_id) && !empty($user_id)){
	if(!isset($info['openid']) || empty($info['openid'])){
		$info['openid'] = 'register'.$info['id'];
	}	
	$info_user_id = 'weixin' . '_' . $info['openid'];
	//更新openid和头像
	$GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('weixin_user') . " SET fake_id = '" . $info['openid'] . "',headimgurl='".$info['avatar']."' WHERE ecuid = '" . $user_id . "'"); 
	$GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('users') . " SET aite_id = '" . $info_user_id . "',headimg='".$info['avatar']."' WHERE user_id = '" . $user_id . "'"); 

	$username = $GLOBALS['db']->getOne("SELECT user_name FROM ".$GLOBALS['ecs']->table('users')." WHERE user_id='" . $user_id . "'");
				$GLOBALS['user']->set_session($username);
				$GLOBALS['user']->set_cookie($username,1);
				update_user_info();  //更新用户信息
				recalculate_price(); //重新计算购物车中的商品价格

}else{
	if(!isset($info['openid']) || empty($info['openid'])){
		$info['openid'] = 'register'.$info['id'];
	}
	//print_r($info);
		$rows = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('weixin_user') . " WHERE fake_id='{$info['openid']}'");
	//print_r($rows);
		if($rows)
		{
			if($rows['ecuid'] > 0)
			{
				$username = $GLOBALS['db']->getOne("SELECT user_name FROM ".$GLOBALS['ecs']->table('users')." WHERE user_id='" . $rows['ecuid'] . "'");
				$GLOBALS['user']->set_session($username);
				$GLOBALS['user']->set_cookie($username,1);
				update_user_info();  //更新用户信息
				recalculate_price(); //重新计算购物车中的商品价格
				
				if(ODOO_ERP){
					//edit yhy 同步erp中的会员
					$res = $GLOBALS['odooErpObj']->syncUserByUserid($_SESSION['user_id']);	
				}
				exit; 
			}
		}else{
			$createtime = gmtime();
			$createymd = date('Y-m-d',gmtime());
			$GLOBALS['db']->query("INSERT INTO ".$GLOBALS['ecs']->table('weixin_user')." (`ecuid`,`fake_id`,`createtime`,`createymd`,`isfollow`,`headimgurl`,`nickname`) 
				value (0,'" . $info['openid'] . "','{$createtime}','{$createymd}',0,'". $info['avatar'] ."','" . $info['nickname'] ."')"); 
		}
		
		$info_user_id = 'weixin' . '_' . $info['openid'];
	//print_r($info_user_id);die();
		if($info['name']){
			$info['name'] = str_replace("'", "", $info['name']);
			// 过滤掉emoji表情
			$info['name'] = preg_replace_callback('/./u',function (array $match) {return strlen($match[0]) >= 4 ? '' : $match[0];},$info['name']);

			if($GLOBALS['user']->check_user($info['name'])) // 重名处理
			{
				$info['name'] = $info['name'] . '_' . 'weixin' . (rand(10000, 99999));
			}
		}else{
			$info['name'] = 'weixin_' . rand(10000, 99999); 
		}
		
		$sql = 'SELECT user_name,password,aite_id FROM ' . $GLOBALS['ecs']->table('users') . ' WHERE aite_id = \'' . $info_user_id . '\' OR aite_id=\'' . $info['openid'] . '\'';
		$user_info = $GLOBALS['db']->getRow($sql);
		if($user_info){
			$info['name'] = $user_info['user_name'];
			if($user_info['aite_id'] == $info['openid']){
				$sql = 'UPDATE ' . $GLOBALS['ecs']->table('users') . " SET aite_id = '$info_user_id' WHERE aite_id = '$user_info[aite_id]'";
				$GLOBALS['db']->query($sql);
				$tag = 2;
			}
			
		}else{
			$user_pass = $GLOBALS['user']->compile_password(array(
				'password' => $info['openid']
			));
			$sql = 'INSERT INTO ' . $GLOBALS['ecs']->table('users') . '(user_name , password, aite_id , sex , reg_time, is_validated, froms, headimg, portal_id, real_name) VALUES ' . "('$info[name]' , '$user_pass' , '$info_user_id' , '$info[sex]' , '" . gmtime() . "' , '0','mobile','$info[avatar]', '$info[id]', '$info[nickname]')";
			$GLOBALS['db']->query($sql);
			$tag = 1; //第一次注册标记
		}

		$GLOBALS['user']->set_session($info['name']);
		$GLOBALS['user']->set_cookie($info['name']);
		update_user_info();
		recalculate_price();
		//修改新注册的用户成为普通分销商
		$GLOBALS['db']->query("UPDATE ".$GLOBALS['ecs']->table('users')." SET is_fenxiao = 2 WHERE user_id = '" . $_SESSION['user_id'] . "'");

		//微信和新生成会员绑定
		$GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('weixin_user') . " SET ecuid = '" . $_SESSION['user_id'] . "' WHERE fake_id = '" . $info['openid'] . "'");

		if($tag==1){
            $parent_id=0;//根据单点系统过来的上级用户ID对应本系统内的用户
            if($info['parent_id']!=0){
                $sql = "SELECT user_id FROM " .
                    $GLOBALS['ecs']->table('users') .
                    " WHERE portal_id = '" . $info['parent_id'] . "'";
                $parent_id = $GLOBALS['db']->getOne($sql);
            }
            //此时，如果不存在对应上级，那使用获取当前缓存内的推广用户来处理，这是为了防止单点系统内对应的记录出现问题时
			if($parent_id==0){$parent_id = get_affiliate();}
            if($parent_id!=0){
				list($u_supplier_id,$parent_id)=get_affiliate_supplier_id($parent_id);
            }else{
                $parent_id=0;
                $affiliate_supplier = unserialize($GLOBALS['_CFG']['affiliate_supplier']);
                $u_supplier_id=$affiliate_supplier['config']['default_reg_supplier_id'];
            }
            //print_r('u_supplier_id='.$u_supplier_id);
            // 设置推荐人
            $sql = 'UPDATE ' . $GLOBALS['ecs']->table('users') . ' SET parent_id = ' . $parent_id . ',supplierId=' . $u_supplier_id . ' WHERE user_id = ' . $_SESSION['user_id'];
            $GLOBALS['db']->query($sql);
		}
	
	if(ODOO_ERP){
		//edit yhy 同步erp中的会员
		$res = $GLOBALS['odooErpObj']->syncUserByUserid($_SESSION['user_id']);	
	}
}
?>