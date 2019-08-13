<?php
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
$_REQUEST['act'] = $_REQUEST['act'] ? trim($_REQUEST['act']) : "main";

/*------------------------------------------------------ */
//-- 老会员导入
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'main'){
	
	$smarty->assign('supplier_list',   get_supplier_list());
    /* 参数赋值 */
    $smarty->assign('ur_here',   '老会员导入');
    /* 显示模板 */
    assign_query_info();
    $smarty->display('users_import.htm');
}
/*------------------------------------------------------ */
//-- 下载文件
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'download')
{
    header("Content-type: application/vnd.ms-excel; charset=GB2312");
    Header("Content-Disposition: attachment; filename=user_import.csv");
	$title_column = array('用户名','手机号','邮箱');
	echo ecs_iconv(EC_CHARSET, 'zh_cn', join(',', $title_column));
	
}
elseif($_REQUEST['act'] == 'upload'){
	set_time_limit(0);  //设置程序执行时间
	ignore_user_abort(true);    //设置断开连接继续执行
	header('X-Accel-Buffering: no');    //关闭buffer
	header('Content-type: text/html;charset=utf-8');    //设置网页编码
	ob_start(); //打开输出缓冲控制
	echo str_repeat(' ',1024*4); //字符填充 
	$width = 1000; 
	$html = '<div style="margin:100px auto; padding: 8px; border: 1px solid gray; background: #EAEAEA; width: %upx"><div style="padding: 0; background-color: white; border: 1px solid navy; width: %upx"><div id="progress" style="padding: 0; background-color: #FFCC66; border: 0; width: 0px; text-align: center; height: 16px"></div></div><div id="percent" style="position: relative; top: -37px; text-align: center; font-weight: bold; font-size: 8pt">0%%</div></div>'; 
	echo sprintf($html, $width+8, $width);
	echo ob_get_clean();    //获取当前缓冲区内容并清除当前的输出缓冲
	flush();   //刷新缓冲区的内容，输出
	$data = file($_FILES['file']['tmp_name']);
	$totalLine = count($data) - 1;
	 if(!$totalLine || $totalLine == 0){
		 $lnk[] = array(
			'text' => '返回', 'href' => 'users_import.php'
		);
		sys_msg('请先上传导入的数据', 0, $lnk);
	 }
	
	foreach ($data AS $line){
            // 跳过第一行
            if ($line_number == 0){
                $line_number++;
                continue;
            }

			$line = ecs_iconv('GB2312', 'UTF8', $line);
			$res = user_import($line,intval($_POST['supplier_id']));
			
			$script = '<script>document.getElementById("percent").innerText="%u%%";document.getElementById("progress").style.width="%upx";</script>'; 
			 echo sprintf($script,intval(($line_number/$totalLine)*100) , intval(($line_number/$totalLine)*$width)); 
			 if($res['code'] == 200){
				 echo "第",$line_number,"行：导入成功<br/>";
			 }else{
				 echo "第",$line_number,"行：导入失败,".$res['msg']."<br/>";
			 }
			 $line_number++;
			 echo ob_get_clean(); //获取当前缓冲区内容并清除当前的输出缓冲 flush(); //刷新缓冲区的内容，输出
			 flush();   //刷新缓冲区的内容，输出
	}
}

function user_import($info,$supplier_id){
	$data = explode(',',$info);
		// 全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	/* 检查权限 */
	admin_priv('users_manage');
	$username = empty($data[0]) ? '' : trim($data[0]);
	$mobile_phone = empty($data[1]) ? '' : trim($data[1]);
	$email = empty($data[2]) ? '' : trim($data[2]);
	if(!$username){
		return array('code'=>400,'msg'=>'用户名为空');
	}
	if(!$mobile_phone){
		return array('code'=>400,'msg'=>'电话为空');
	}
	//查询手机会员是否存在
	$sql = "SELECT user_id FROM " . $GLOBALS['ecs']->table('users') . " WHERE mobile_phone = '$mobile_phone'";
	$info = $GLOBALS['db']->getRow($sql);
	if(isset($info) && !empty($info)){
		return array('code'=>400,'msg'=>'该手机已存在');
	}

	$password = substr($mobile_phone,-6);
	$rank = 0;
	$status = 1;
	/* 代码增加2014-12-23 by www.68ecshop.com _end */
	$users = & init_users();

	if(! $users->add_user($username, $password, $email))
	{
		/* 插入会员数据失败 */
		if($users->error == ERR_INVALID_USERNAME)
		{
			$msg = $_LANG['username_invalid'];
		}
		elseif($users->error == ERR_USERNAME_NOT_ALLOW)
		{
			$msg = $_LANG['username_not_allow'];
		}
		elseif($users->error == ERR_USERNAME_EXISTS)
		{
			$msg = $_LANG['username_exists'];
		}
		elseif($users->error == ERR_INVALID_EMAIL)
		{
			$msg = $_LANG['email_invalid'];
		}
		elseif($users->error == ERR_EMAIL_NOT_ALLOW)
		{
			$msg = $_LANG['email_not_allow'];
		}
		elseif($users->error == ERR_EMAIL_EXISTS)
		{
			$msg = $_LANG['email_exists'];
		}
		else
		{
			//$msg = $users->error_msg();
			// die('Error:'.$users->error_msg());
		}
		
		return array('code'=>400,'msg'=>$msg);
	}

	/* 把新注册用户的扩展信息插入数据库 */
	$sql = 'SELECT id FROM ' . $ecs->table('reg_fields') . ' WHERE type = 0 AND display = 1 ORDER BY dis_order, id'; // 读出所有扩展字段的id
	$fields_arr = $db->getAll($sql);

	$extend_field_str = ''; // 生成扩展字段的内容字符串
	$user_id_arr = $users->get_profile_by_name($username);
	foreach($fields_arr as $val)
	{
		$extend_field_index = 'extend_field' . $val['id'];
		if(! empty($_POST[$extend_field_index]))
		{
			$temp_field_content = strlen($_POST[$extend_field_index]) > 100 ? mb_substr($_POST[$extend_field_index], 0, 99) : $_POST[$extend_field_index];
			$extend_field_str .= " ('" . $user_id_arr['user_id'] . "', '" . $val['id'] . "', '" . $temp_field_content . "'),";
		}
	}
	$extend_field_str = substr($extend_field_str, 0, - 1);

	if($extend_field_str) // 插入注册扩展数据
	{
		$sql = 'INSERT INTO ' . $ecs->table('reg_extend_info') . ' (`user_id`, `reg_field_id`, `content`) VALUES' . $extend_field_str;
		$db->query($sql);
	}

	/* 更新会员的其它信息 */
	$other = array();
	$other['user_rank'] = $rank;
	$other['reg_time'] = local_strtotime(local_date('Y-m-d H:i:s'));
	$other['mobile_phone'] = $mobile_phone;
	$other['is_old'] = 1;//导入的会员为老会员
	$other['status'] = 1;
	$other['supplierId'] = $supplier_id;
	//新导入的会员上级为店长的user_id edit by yhy 2019/7/10
	$other['parent_id'] = $db->getOne('SELECT user_id FROM ' . $ecs->table('supplier') . ' WHERE supplier_id = '.$supplier_id);
	$db->autoExecute($ecs->table('users'), $other, 'UPDATE', "user_name = '$username'");
	/* 代码增加2014-12-23 by www.68ecshop.com _end */
	/* 记录管理员操作 */
	admin_log('导入会员:'.$username, 'add', 'users');
	return array('code'=>200);
}

function get_supplier_list(){
	$sql = "SELECT supplier_id,supplier_name FROM " . $GLOBALS['ecs']->table('supplier') . " WHERE status = 1";
	$list = $GLOBALS['db']->getAll($sql);
	return $list;
}
?>