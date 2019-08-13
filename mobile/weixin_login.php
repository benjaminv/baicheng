<?php
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
require(dirname(__FILE__) . '/weixin/wechat.class.php');
$weixinconfig = $GLOBALS['db']->getRow ( "SELECT * FROM " . $GLOBALS['ecs']->table('weixin_config') . " WHERE `id` = 1" );
$weixin = new core_lib_wechat($weixinconfig);
if($_GET['code']){
	$json = $weixin->getOauthAccessToken();
	if($json['openid']){
		$info = $weixin->getOauthUserinfo($json['access_token'],$json['openid']);
		$fans_info = $weixin->getUserInfo($json['openid']);
		$isfollow = isset($fans_info['subscribe']) ? intval($fans_info['subscribe']) : 0;
		$rows = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('weixin_user') . " WHERE fake_id='{$json['openid']}'");
		if($rows){
			//粉丝信息存在
			if($rows['ecuid'] > 0){
				//已关联会员表
				$username = $GLOBALS['db']->getOne("SELECT user_name FROM ".$GLOBALS['ecs']->table('users')." WHERE user_id='" . $rows['ecuid'] . "'");
				$rows2 = $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id='" . $rows['ecuid'] . "'");
				$GLOBALS['db']->query("UPDATE ".$GLOBALS['ecs']->table('users')." SET headimg = '".$info['headimgurl']."' WHERE user_id = '" . $rows2['user_id'] . "'");
				$GLOBALS['db']->query("UPDATE ".$GLOBALS['ecs']->table('weixin_user')." SET headimgurl = '".$info['headimgurl']."',isfollow=".intval($isfollow)." WHERE ecuid = '" . $rows2['user_id'] . "'");
			}else{
				//未关联会员表
				$userInfo = $GLOBALS['db']->getRow("SELECT user_name,user_id FROM ".$GLOBALS['ecs']->table('users')." WHERE aite_id like '%" . $json['openid'] . "%'");
				if($userInfo){
					//未关联会员表，但是有会员数据
					$username = $userInfo['user_name'];
					$user_id = $userInfo['user_id'];
				}else{
					//未关联会员表，并且没有会员数据
					if($info['nickname']){
						$info['name'] = str_replace("'", "", $info['nickname']);
						// 过滤掉emoji表情
						$info['name'] = preg_replace_callback('/./u',function (array $match) {return strlen($match[0]) >= 4 ? '' : $match[0];},$info['name']);
						if($GLOBALS['user']->check_user($info['name'])) // 重名处理
						{
							$info['name'] = $info['name'] . '_' . 'weixin' . (rand(10000, 99999));
						}
					}else{
						$info['name'] = 'weixin_' . rand(10000, 99999); 
					}
					if(!$info['name']){
						$info['name'] = 'weixin_' . rand(10000, 99999); 
					}
					$info['name'] = trim($info['name']);
					$info_user_id = 'weixin' . '_' . $info['openid'];
					$user_pass = $GLOBALS['user']->compile_password(array(
						'password' => $info['openid']
					));
					$sql = 'INSERT INTO ' . $GLOBALS['ecs']->table('users') . '(user_name , password, aite_id , sex , reg_time , is_validated,froms,headimg,is_fenxiao) VALUES ' . "('$info[name]' , '$user_pass' , '$info_user_id' , '$info[sex]' , '" . gmtime() . "' , '0','mobile','$info[headimgurl]',2)";
					
					$GLOBALS['db']->query($sql);
					$username = $info['name'];
					$user_id = $db->insert_id();
				}
				$GLOBALS['db']->query("UPDATE ".$GLOBALS['ecs']->table('weixin_user')." SET ecuid = '".$user_id."',isfollow=".intval($isfollow)." WHERE uid = '" . $rows['user_id'] . "'");
			}
			$GLOBALS['user']->set_session($username);
			$GLOBALS['user']->set_cookie($username,1);
			update_user_info();  //更新用户信息
			recalculate_price(); //重新计算购物车中的商品价格
			$url = $GLOBALS['ecs']->url()."index.php";
			if(isset($_COOKIE['callback']) && !empty($_COOKIE['callback'])){
				setcookie('callback',"",time()-3600);
				$url = trim($_COOKIE['callback']);
			}
			header("Location:$url");exit;
		}else{
			//粉丝数据不存在，但是会员数据存在
			$userInfo = $GLOBALS['db']->getRow("SELECT user_name,user_id,headimg FROM ".$GLOBALS['ecs']->table('users')." WHERE aite_id like '%" . $json['openid'] . "%'");
			if($userInfo){
				//查看用户名是否为空
				if(isset($userInfo['user_name']) && !empty($userInfo['user_name'])){
					$username = $userInfo['user_name'];
				}else{
					if($info['nickname']){
						$info['name'] = str_replace("'", "", $info['nickname']);
						// 过滤掉emoji表情
						$info['name'] = preg_replace_callback('/./u',function (array $match) {return strlen($match[0]) >= 4 ? '' : $match[0];},$info['name']);
						if($GLOBALS['user']->check_user($info['name'])) // 重名处理
						{
							$info['name'] = $info['name'] . '_' . 'weixin' . (rand(10000, 99999));
						}
					}else{
						$info['name'] = 'weixin_' . rand(10000, 99999); 
					}
					if(!$info['name']){
						$info['name'] = 'weixin_' . rand(10000, 99999); 
					}
					$GLOBALS['db']->query("UPDATE ".$GLOBALS['ecs']->table('users')." SET user_name = '".trim($info['name'])."' WHERE user_id = '" . $userInfo['user_id'] . "'");		
					$username = $info['name'];		
				}
					$createtime = gmtime();
					$createymd = date('Y-m-d',gmtime());
					$GLOBALS['db']->query("INSERT INTO ".$GLOBALS['ecs']->table('weixin_user')." (`ecuid`,`fake_id`,`createtime`,`createymd`,`isfollow`,`nickname`,`headimgurl`) value (".$userInfo['user_id'].",'" . $json['openid'] . "','{$createtime}','{$createymd}','".$isfollow."','".$username."','".$info['headimgurl']."')");
			}else{
				//新注册用户流程
					if($info['nickname']){
						$info['name'] = str_replace("'", "", $info['nickname']);
						// 过滤掉emoji表情
						$info['name'] = preg_replace_callback('/./u',function (array $match) {return strlen($match[0]) >= 4 ? '' : $match[0];},$info['name']);
						if($GLOBALS['user']->check_user($info['name'])) // 重名处理
						{
							$info['name'] = $info['name'] . '_' . 'weixin' . (rand(10000, 99999));
						}
					}else{
						$info['name'] = 'weixin_' . rand(10000, 99999); 
					}
					if(!$info['name']){
						$info['name'] = 'weixin_' . rand(10000, 99999); 
					}
					$info_user_id = 'weixin' . '_' . $info['openid'];
					$user_pass = $GLOBALS['user']->compile_password(array(
						'password' => '123456'
					));

					//扫码绑定上级//查询上级是否存在 edit by yhy 2019/5/9
					$parent_id=$_GET['user_id']?intval($_GET['user_id']):0;
					if($parent_id!=0){
							$sql = "SELECT user_id FROM " .
								$GLOBALS['ecs']->table('users') .
								" WHERE user_id = '" . $parent_id . "'";
							$parent_id = $GLOBALS['db']->getOne($sql);
					}
					//此时，如果不存在对应上级，那使用获取当前缓存内的推广用户来处理
					//if($parent_id==0){$parent_id = get_affiliate();}
					$affiliate_supplier = unserialize($GLOBALS['_CFG']['affiliate_supplier']);
					if($parent_id!=0){
						list($u_supplier_id,$parent_id,$is_old)=get_affiliate_supplier_id($parent_id);
					}else{
						$u_supplier_id=$affiliate_supplier['config']['default_reg_supplier_id'];
						$is_old = 0;
					}
					if(empty($u_supplier_id)){
						$u_supplier_id=$affiliate_supplier['config']['default_reg_supplier_id'];
					}
					$type=$_GET['erweima_type']?intval($_GET['erweima_type']):0;
					$parent = 0;
					if($type == 1){
						$parent = intval($parent_id);
					}
					/* edit by yhy 微信登录绑定手机号，手机号账号存在并且没有绑定过微信账号的时候关联两个账号*/
					//新注册会员绑定手机号，如果手机号在平台存在并且没有绑定微信账号的话，绑定该账号，手机号不存在的话，更新手机号
					//$bind=$_GET['bind']?intval($_GET['bind']):0;
                    //if(empty($bind))
                    //{
                        //ecs_header("Location: affiliate_weixin_login.php?act=bind&parent_id=$parent_id \n");
                        //exit;
                    //}else
                    //{
                        //$mobile_phone = trim($_GET['mobile_phone']);
						////查询手机号的账号是否存在，存在则绑定该账号，不存在则新建账号。
						//$sql = "SELECT user_id,user_name FROM " .$GLOBALS['ecs']->table('users') ." WHERE mobile_phone = '" . $mobile_phone . "'";
						//$user_info = $GLOBALS['db']->getRow($sql);
						////账号存在，则绑定该账号到微信账号
						//if(isset($user_info) && !empty($user_info)){
							////查看账号是否已经绑定了微信
							//$sql = "SELECT uid FROM " .$GLOBALS['ecs']->table('weixin_user') ." WHERE ecuid = '" . $user_info['user_id'] . "'";
							//$wxuser_info = $GLOBALS['db']->getRow($sql);
							//if(isset($wxuser_info) && !empty($wxuser_info)){
								//show_message('该手机号已经绑定了微信', "更换手机号", 'user.php?login.php', 'error');exit;
							//}
							//$username = $user_info['user_name'];
							//$user_id = $user_info['user_id'];
						//}else{
							//账号不存在，新建账号,绑定手机号
							$info['name'] = trim($info['name']);
							$sql = 'INSERT INTO ' . $GLOBALS['ecs']->table('users') . '(user_name , password, aite_id , sex , reg_time , is_validated,froms,headimg,is_fenxiao,parent_id,supplierId,is_old) VALUES ' . "('$info[name]' , '$user_pass' , '$info_user_id' , '$info[sex]' , '" . gmtime() . "' , '0','mobile','$info[headimgurl]',2,".$parent.",".intval($u_supplier_id).",".$is_old.")";
							$GLOBALS['db']->query($sql);
							$username = $info['name'];
							$user_id = $db->insert_id();
						//}
					//}
					$createtime = gmtime();
					$createymd = date('Y-m-d',gmtime());
					$GLOBALS['db']->query("INSERT INTO ".$GLOBALS['ecs']->table('weixin_user')." (`ecuid`,`fake_id`,`createtime`,`createymd`,`isfollow`,`nickname`,`headimgurl`) value (".$user_id.",'" . $json['openid'] . "','{$createtime}','{$createymd}','".$isfollow."','".$info['name']."','".$info['headimgurl']."')");

					//新会员注册后,增加一条默认地址,地址为店铺所在地址 2019/7/30
					add_default_consignee($user_id,$u_supplier_id);
			}
			$GLOBALS['user']->set_session($username);
			$GLOBALS['user']->set_cookie($username);
			update_user_info();
			recalculate_price();
		}
		
		$url = $GLOBALS['ecs']->url()."index.php";
		if(isset($_COOKIE['callback']) && !empty($_COOKIE['callback'])){
			setcookie('callback',"",time()-3600);
			$url = trim($_COOKIE['callback']);
		}
		header("Location:$url");exit;
	}else{
		echo "获取微信信息失败";
		//$url = $GLOBALS['ecs']->url()."index.php";
		//if(isset($_COOKIE['callback']) && !empty($_COOKIE['callback'])){
			//setcookie('callback',"",time()-3600);
			//$url = trim($_COOKIE['callback']);
		//}
		//header("Location:$url");exit;
		exit;
	}
}

//$bind=$_GET['bind']?intval($_GET['bind']):0;
//if($bind==1)
//{
    //$mobile_phone = trim($_GET['mobile_phone']);
    //$url = $GLOBALS['ecs']->url()."weixin_login.php?bind=1&user_id=".intval($_GET['user_id'])."&erweima_type=1&mobile_phone=$mobile_phone";
//}
//else
//{
    $url = $GLOBALS['ecs']->url()."weixin_login.php?user_id=".intval($_GET['user_id'])."&erweima_type=1";
//}


$url = $weixin->getOauthRedirect($url,1,'snsapi_userinfo');
header("Location:$url");exit;
?>