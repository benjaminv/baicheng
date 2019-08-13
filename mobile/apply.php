<?php

/**
 * ECSHOP 专题前台
 * ============================================================================
 * 版权所有 2005-2011 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * @author:     palenggege  QQ497401495 
 * @version:    v2.1
 * ---------------------------------------------
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

if ((DEBUG_MODE & 2) != 2)
{
    $smarty->caching = true;
}
if (empty($_SESSION['user_id'])){
	//立即登录
	setcookie('callback','http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'],time()+3600);
	header("location: user.php?act=login");
	exit;
}


$userid = $_SESSION['user_id'];
$act = $_REQUEST['act']?trim($_REQUEST['act']):"";
if($act == 'index'){
	//判断是否已经有审核过的店铺
	$sql = "SELECT supplier_id FROM ".$ecs->table('supplier')." WHERE user_id=".$userid." AND status=1 limit 1";
	$info = $db->getRow($sql);
	if(isset($info) && !empty($info)){
		ecs_header("Location: supplier\n");
		
        exit;
	}else{
		ecs_header("Location: apply.php\n");
        exit;
	}
}else{
    
	$shownum = (isset($_REQUEST['shownum'])) ? intval($_REQUEST['shownum']) : 0;

	//$upload_size_limit = $_CFG['upload_size_limit'] == '-1' ? ini_get('upload_max_filesize') : $_CFG['upload_size_limit'];
    $upload_size_limit = 10 * 1024; //kb
	if(isset($_POST['do']) && $_POST['do']){
		unset($apply,$save);
		if($shownum == 1){
			if($_POST['company'])
			{
				$save['company_name'] = isset($_POST['company_name']) ? trim(addslashes(htmlspecialchars($_POST['company_name']))) : '';
				$save['country'] = isset($_POST['country']) ? intval($_POST['country']) : 1;
				$save['province'] = isset($_POST['province']) ? intval($_POST['province']) : 1;
				$save['city'] = isset($_POST['city']) ? intval($_POST['city']) : 1;
				$save['district'] = isset($_POST['district']) ? intval($_POST['district']) : 1;
				$save['address'] = isset($_POST['address']) ? trim(addslashes(htmlspecialchars($_POST['address']))) : '';
				$save['tel'] = isset($_POST['tel']) ? trim(addslashes(htmlspecialchars($_POST['tel']))) : '';
				$save['guimo'] = isset($_POST['guimo']) ? trim(addslashes(htmlspecialchars($_POST['guimo']))) : '';
				$save['email'] = isset($_POST['email']) ? trim($_POST['email']) : '';
				$save['company_type'] = isset($_POST['company_type']) ? trim($_POST['company_type']) : '';
				$save['contacts_name'] = isset($_POST['contacts_name']) ? trim(addslashes(htmlspecialchars($_POST['contacts_name']))) : '';
				$save['contacts_phone'] = isset($_POST['contacts_phone']) ? trim(addslashes(htmlspecialchars($_POST['contacts_phone']))) : '';
				$save['business_licence_number'] = isset($_POST['business_licence_number']) ? trim(addslashes(htmlspecialchars($_POST['business_licence_number']))) : '';
				$save['business_sphere'] = isset($_POST['business_sphere']) ? trim(addslashes(htmlspecialchars($_POST['business_sphere']))) : '';
				//$save['organization_code'] = isset($_POST['organization_code']) ? trim(addslashes(htmlspecialchars($_POST['organization_code']))) : '';

				if (isset($_FILES['zhizhao']) && $_FILES['zhizhao']['tmp_name'] != '' &&  isset($_FILES['zhizhao']['tmp_name']) && $_FILES['zhizhao']['tmp_name'] != 'none')
				{
					if($_FILES['zhizhao']['size'] / 1024 > $upload_size_limit)
					{
						$err->add(sprintf($_LANG['upload_file_limit'], $upload_size_limit));
						$err->show($_LANG['back_up_page']);
					}
					$zhizhao_img = upload_file2($_FILES['zhizhao'], 'supplier');
					if ($zhizhao_img === false)
					{
						$err->add('营业执照号电子版图片上传失败！');
						$err->show($_LANG['back_up_page']);
					}
					else
					{
					   $save['zhizhao'] = $zhizhao_img;
					}
				}
				if (isset($_FILES['organization_code_electronic']) && $_FILES['organization_code_electronic']['tmp_name'] != '' &&  isset($_FILES['organization_code_electronic']['tmp_name']) && $_FILES['organization_code_electronic']['tmp_name'] != 'none')
				{
					if($_FILES['organization_code_electronic']['size'] / 1024 > $upload_size_limit)
					{
						$err->add(sprintf($_LANG['upload_file_limit'], $upload_size_limit));
						$err->show($_LANG['back_up_page']);
					}

					$organization_code_electronic_img = upload_file2($_FILES['organization_code_electronic'], 'supplier');
					if ($organization_code_electronic_img === false)
					{
						$err->add('组织机构代码证电子版图片上传失败！');
						$err->show($_LANG['back_up_page']);
					}
					else
					{
						$save['organization_code_electronic'] = $organization_code_electronic_img;
					}
				}
				if (isset($_FILES['general_taxpayer']) && $_FILES['general_taxpayer']['tmp_name'] != '' &&  isset($_FILES['general_taxpayer']['tmp_name']) && $_FILES['general_taxpayer']['tmp_name'] != 'none')
				{
					if($_FILES['general_taxpayer']['size'] / 1024 > $upload_size_limit)
					{
						$err->add(sprintf($_LANG['upload_file_limit'], $upload_size_limit));
						$err->show($_LANG['back_up_page']);
					}
					$general_taxpayer_img = upload_file2($_FILES['general_taxpayer'], 'supplier');
					if ($general_taxpayer_img === false)
					{
						$err->add('组织机构代码证电子版图片上传失败！');
						$err->show($_LANG['back_up_page']);
					}
					else
					{
						$save['general_taxpayer'] = $general_taxpayer_img;
					}
				}


				$save['tax_registration_certificate'] = isset($_POST['tax_registration_certificate']) ? trim(addslashes(htmlspecialchars($_POST['tax_registration_certificate']))) : '';
				$save['taxpayer_id'] = isset($_POST['taxpayer_id']) ? trim(addslashes(htmlspecialchars($_POST['taxpayer_id']))) : '';
				
				if (isset($_FILES['bank_licence_electronic']) && $_FILES['bank_licence_electronic']['tmp_name'] != '' &&  isset($_FILES['bank_licence_electronic']['tmp_name']) && $_FILES['bank_licence_electronic']['tmp_name'] != 'none')
				{
					if($_FILES['bank_licence_electronic']['size'] / 1024 > $upload_size_limit)
					{
						$err->add(sprintf($_LANG['upload_file_limit'], $upload_size_limit));
						$err->show($_LANG['back_up_page']);
					}
					$bank_licence_electronic_img = upload_file2($_FILES['bank_licence_electronic'], 'supplier');
					if ($bank_licence_electronic_img === false)
					{
						$err->add('开户银行许可证电子版图片上传失败！');
						$err->show($_LANG['back_up_page']);
					}
					else
					{
						$save['bank_licence_electronic'] = $bank_licence_electronic_img;
					}
				}
				if (isset($_FILES['tax_registration_certificate_electronic']) && $_FILES['tax_registration_certificate_electronic']['tmp_name'] != '' &&  isset($_FILES['tax_registration_certificate_electronic']['tmp_name']) && $_FILES['tax_registration_certificate_electronic']['tmp_name'] != 'none')
				{
					if($_FILES['tax_registration_certificate_electronic']['size'] / 1024 > $upload_size_limit)
					{
						$err->add(sprintf($_LANG['upload_file_limit'], $upload_size_limit));
						$err->show($_LANG['back_up_page']);
					}
					$tax_registration_certificate_electronic_img = upload_file2($_FILES['tax_registration_certificate_electronic'], 'supplier');
					if ($tax_registration_certificate_electronic_img === false)
					{
						$err->add('税务登记证号电子版图片上传失败！');
						$err->show($_LANG['back_up_page']);
					}
					else
					{
						$save['tax_registration_certificate_electronic'] = $tax_registration_certificate_electronic_img;
					}
				}


				$save['applynum'] = 2;//公司信息认证一
				//必填项验证
				$save1 = array_filter($save);
				if(count($save1)!=count($save)){
					show_message('请认真填写必填申请资料！', '返回', 'apply.php', 'wrong');
				}
		        $save['type_id'] = '1';
				// 添加店铺的类型  
				if ($db->autoExecute($ecs->table('supplier'), $save, 'UPDATE', 'user_id='.$userid) !== false){

					header("location:apply.php");
					exit;
				}else{
					show_message('操作失败！', '返回', 'apply.php', 'wrong');
				}
			}
			if($_POST['person'])
			{
				//$save['company_name'] = isset($_POST['company_name']) ? trim(addslashes(htmlspecialchars($_POST['company_name']))) : '';
				$save['country'] = isset($_POST['country']) ? intval($_POST['country']) : 1; 
				$save['province'] = isset($_POST['province']) ? intval($_POST['province']) : 1; 
				$save['city'] = isset($_POST['city']) ? intval($_POST['city']) : 1; 
				$save['district'] = isset($_POST['district']) ? intval($_POST['district']) : 1; 
				$save['address'] = isset($_POST['address']) ? trim(addslashes(htmlspecialchars($_POST['address']))) : '';
				
				$save['contacts_name'] = isset($_POST['contacts_name']) ? trim(addslashes(htmlspecialchars($_POST['contacts_name']))) : '';
				$save['contacts_phone'] = isset($_POST['contacts_phone']) ? trim(addslashes(htmlspecialchars($_POST['contacts_phone']))) : '';
				//$save['email'] = isset($_POST['email']) ? trim($_POST['email']) : '';

				//$save['id_card_no'] = isset($_POST['id_card_no']) ? trim(addslashes(htmlspecialchars($_POST['id_card_no']))) : '';

				//$save['bank_account_name'] = isset($_POST['bank_account_name']) ? trim(addslashes(htmlspecialchars($_POST['bank_account_name']))) : '';
				//$save['bank_account_number'] = isset($_POST['bank_account_number']) ? trim(addslashes(htmlspecialchars($_POST['bank_account_number']))) : '';
				//$save['bank_name'] = isset($_POST['bank_name']) ? trim(addslashes(htmlspecialchars($_POST['bank_name']))) : '';
				//$save['bank_code'] = isset($_POST['bank_code']) ? trim(addslashes(htmlspecialchars($_POST['bank_code']))) : '';

				
			
				
				// if (isset($_FILES['handheld_idcard']) && $_FILES['handheld_idcard']['tmp_name'] != '' &&  isset($_FILES['handheld_idcard']['tmp_name']) && $_FILES['handheld_idcard']['tmp_name'] != 'none')
				// {
				// 	if($_FILES['handheld_idcard']['size'] / 1024 > $upload_size_limit)
				// 	{
				// 		$err->add(sprintf($_LANG['upload_file_limit'], $upload_size_limit));
				// 		$err->show($_LANG['back_up_page']);
				// 	}
				// 	$handheld_idcard_img = upload_file2($_FILES['handheld_idcard'], 'supplier');
				// 	//echo $handheld_idcard_img;
				// 	//exit;
				// 	if ($handheld_idcard_img === false)
				// 	{
				// 		$err->add('手持身份证照片上传失败！');
				// 		$err->show($_LANG['back_up_page']);
				// 	}
				// 	else
				// 	{
				// 		$save['handheld_idcard'] = $handheld_idcard_img;
				// 	}
				// }
				// if (isset($_FILES['idcard_front']) && $_FILES['idcard_front']['tmp_name'] != '' &&  isset($_FILES['idcard_front']['tmp_name']) && $_FILES['idcard_front']['tmp_name'] != 'none')
				// {
				// 	if($_FILES['idcard_front']['size'] / 1024 > $upload_size_limit)
				// 	{
				// 		$err->add(sprintf($_LANG['upload_file_limit'], $upload_size_limit));
				// 		$err->show($_LANG['back_up_page']);
				// 	}
				// 	$idcard_front_img = upload_file2($_FILES['idcard_front'], 'supplier');
				// 	if ($idcard_front_img === false)
				// 	{
				// 		$err->add('身份证正面照片上传失败！');
				// 		$err->show($_LANG['back_up_page']);
				// 	}
				// 	else
				// 	{
				// 		$save['idcard_front'] = $idcard_front_img;
				// 	}
				// }
				// if (isset($_FILES['idcard_reverse']) && $_FILES['idcard_reverse']['tmp_name'] != '' &&  isset($_FILES['idcard_reverse']['tmp_name']) && $_FILES['idcard_reverse']['tmp_name'] != 'none')
				// {
				// 	if($_FILES['idcard_reverse']['size'] / 1024 > $upload_size_limit)
				// 	{
				// 		$err->add(sprintf($_LANG['upload_file_limit'], $upload_size_limit));
				// 		$err->show($_LANG['back_up_page']);
				// 	}
				// 	$idcard_reverse_img = upload_file2($_FILES['idcard_reverse'], 'supplier');
				// 	if ($idcard_reverse_img === false)
				// 	{
				// 		$err->add('身份证反面照片上传失败！');
				// 		$err->show($_LANG['back_up_page']);
				// 	}
				// 	else
				// 	{
				// 		$save['idcard_reverse'] = $idcard_reverse_img;
				// 	}
				// }


				
				$save['applynum'] = 2;//公司信息认证一

				

				
				//必填项验证
				$save1 = array_filter($save);

				if(count($save1)!=count($save)){
					show_message('请认真填写必填申请资料！', '返回', 'apply.php', 'wrong');
				}
				$save['type_id'] = '0';
				 if ($db->autoExecute($ecs->table('supplier'), $save, 'UPDATE', 'user_id='.$userid) !== false){
					header("location:apply.php");
					exit;
				 }else{
					show_message('操作失败！', '返回', 'apply.php', 'wrong');
				 }
			}
		}elseif($shownum == 2){
			
			//$save['bank_account_name'] = isset($_POST['bank_account_name']) ? trim(addslashes(htmlspecialchars($_POST['bank_account_name']))) : '';
			//$save['bank_account_number'] = isset($_POST['bank_account_number']) ? trim(addslashes(htmlspecialchars($_POST['bank_account_number']))) : '';
			//$save['bank_name'] = isset($_POST['bank_name']) ? trim(addslashes(htmlspecialchars($_POST['bank_name']))) : '';
			//$save['bank_code'] = isset($_POST['bank_code']) ? trim(addslashes(htmlspecialchars($_POST['bank_code']))) : '';
		   // $save['settlement_bank_account_name'] = isset($_POST['settlement_bank_account_name']) ? trim(addslashes(htmlspecialchars($_POST['settlement_bank_account_name']))) : '';
		   //$save['settlement_bank_account_number'] = isset($_POST['settlement_bank_account_number']) ? trim(addslashes(htmlspecialchars($_POST['settlement_bank_account_number']))) : '';
			//$save['settlement_bank_name'] = isset($_POST['settlement_bank_name']) ? trim(addslashes(htmlspecialchars($_POST['settlement_bank_name']))) : '';
			//$save['settlement_bank_code'] = isset($_POST['settlement_bank_code']) ? trim(addslashes(htmlspecialchars($_POST['settlement_bank_code']))) : '';
			$save['tax_registration_certificate'] = isset($_POST['tax_registration_certificate']) ? trim(addslashes(htmlspecialchars($_POST['tax_registration_certificate']))) : '';
			$save['taxpayer_id'] = isset($_POST['taxpayer_id']) ? trim(addslashes(htmlspecialchars($_POST['taxpayer_id']))) : '';
			
			if (isset($_FILES['bank_licence_electronic']) && $_FILES['bank_licence_electronic']['tmp_name'] != '' &&  isset($_FILES['bank_licence_electronic']['tmp_name']) && $_FILES['bank_licence_electronic']['tmp_name'] != 'none')
			{
				if($_FILES['bank_licence_electronic']['size'] / 1024 > $upload_size_limit)
				{
					$err->add(sprintf($_LANG['upload_file_limit'], $upload_size_limit));
					$err->show($_LANG['back_up_page']);
				}
				$bank_licence_electronic_img = upload_file2($_FILES['bank_licence_electronic'], 'supplier');
				if ($bank_licence_electronic_img === false)
				{
					$err->add('开户银行许可证电子版图片上传失败！');
					$err->show($_LANG['back_up_page']);
				}
				else
				{
					$save['bank_licence_electronic'] = $bank_licence_electronic_img;
				}
			}
			if (isset($_FILES['tax_registration_certificate_electronic']) && $_FILES['tax_registration_certificate_electronic']['tmp_name'] != '' &&  isset($_FILES['tax_registration_certificate_electronic']['tmp_name']) && $_FILES['tax_registration_certificate_electronic']['tmp_name'] != 'none')
			{
				if($_FILES['tax_registration_certificate_electronic']['size'] / 1024 > $upload_size_limit)
				{
					$err->add(sprintf($_LANG['upload_file_limit'], $upload_size_limit));
					$err->show($_LANG['back_up_page']);
				}
				$tax_registration_certificate_electronic_img = upload_file2($_FILES['tax_registration_certificate_electronic'], 'supplier');
				if ($tax_registration_certificate_electronic_img === false)
				{
					$err->add('税务登记证号电子版图片上传失败！');
					$err->show($_LANG['back_up_page']);
				}
				else
				{
					$save['tax_registration_certificate_electronic'] = $tax_registration_certificate_electronic_img;
				}
			}
			
			$save['applynum'] = 2;//公司信息认证二
			
			//必填项验证
			$save1 = array_filter($save);
			if(count($save1)!=count($save)){
				show_message('请认真填写必填申请资料！', '返回', 'apply.php', 'wrong');
			}
			 $save['type_id'] = '1';
			if ($db->autoExecute($ecs->table('supplier'), $save, 'UPDATE', 'user_id='.$userid) !== false){

				header("location:apply.php");
				exit;
			 }else{
				show_message('操作失败！', '返回', 'apply.php', 'wrong');
			 }
			
		}elseif($shownum == 3){

			$save['supplier_name'] = isset($_POST['supplier_name']) ? trim(addslashes(htmlspecialchars($_POST['supplier_name']))) : '';

            $save['description'] = isset($_POST['description']) ? trim(addslashes(htmlspecialchars($_POST['description']))) : '';



            if(empty($save['supplier_name']) || empty($save['description']) || $_FILES['logo']['size'] <= 0){
                show_message('资料不完善', '返回', 'apply.php', 'wrong');
            }

            if($_FILES['logo']['size'] <= 0) {
                $sql = "SELECT * FROM " . $ecs->table('supplier') . " WHERE user_id=" . $userid . " AND limit 1";
                $supperInfo = $db->getRow($sql);
                if (empty($supperInfo)) {
                    show_message('请上传店铺LOGO', '返回', 'apply.php', 'wrong');
                }
            }


            //$save['rank_id'] = isset($_POST['rank_id']) ? intval($_POST['rank_id']) : 0;
			//$save['type_id'] = isset($_POST['type_id']) ? intval($_POST['type_id']) : 0; 
			
			$save['applynum'] = 3;//店铺信息设置
			$save['is_ipc_shopping'] = isset($_POST['is_ipc_shopping']) ?intval($_POST['is_ipc_shopping']):0;//是否为异业联盟店铺 yhy 2019/7/1
			//必填项验证
			//$save1 = array_filter($save);
			//if(count($save1)!=count($save)){
				//show_message('请认真填写必填申请资料！', '返回', 'apply.php', 'wrong');
			//}
			// 最大上传文件大小
			$php_maxsize = ini_get('upload_max_filesize');
			$htm_maxsize = '2M';

			include_once dirname(__FILE__).'/includes/cls_image.php';
            $image = new cls_image();

            //店铺logo，需要压缩图片处理
            if(isset($_FILES['logo']['name']) && !empty($_FILES['logo']['name'])){

                $upload_size_limit = 10 * 1024 * 1024;
                if($_FILES['logo']['size'] > $upload_size_limit)
                {
                    $err->add(sprintf($_LANG['upload_file_limit'], $upload_size_limit));
                    $err->show($_LANG['back_up_page']);
                }

                //$image->upload_image($_FILES['logo'],'supplier');

//                $pcUrl = get_pc_url();
//
                $logo   = $image->upload_image_root($_FILES['logo'],'supplier'); // 原始图片

                //$logo = upload_file2($_FILES['logo'], 'supplier');
                if ($logo === false)
                {
                    $err->add('店铺Logo上传失败');
                    $err->show($_LANG['back_up_page']);
                    exit;
                }

                $logo_thumb = $image->make_thumb_mobile(ROOT_PATH_WAP.$logo , 800,  800);

                $save['logo'] = $logo_thumb ? $logo_thumb : $logo;
                unset($logo);
            }
			

			if(isset($_FILES['technican_bodyimg']['name']) && !empty($_FILES['technican_bodyimg']['name'])){
				
				if($_FILES['technican_bodyimg']['size'] / 1024 > $upload_size_limit)
				{
					$err->add(sprintf($_LANG['upload_file_limit'], $upload_size_limit));
					$err->show($_LANG['back_up_page']);
				}
						
				$logosrc = upload_file2($_FILES['technican_bodyimg'], 'supplier');
				if ($logosrc === false)
				{
					$err->add('个人艺术照上传失败');
					$err->show($_LANG['back_up_page']);
					exit;
				}
				$save['technican_bodyimg'] = $logosrc;
				unset($logosrc);
			}
			$save['opus_remark'] = trim($_POST['opus_remark']);
			 if ($db->autoExecute($ecs->table('supplier'), $save, 'UPDATE', 'user_id='.$userid) !== false){
				header("location:apply.php");
				exit;
			 }else{
				show_message('操作失败！', '返回', 'apply.php', 'wrong');
			 }
			
		}else{//同意入驻协议
			
			
			if(isset($_POST['input_apply_agreement']) && intval($_POST['input_apply_agreement']) > 0){
				
				$sql = "select * from ".$ecs->table('supplier')." where user_id=".$userid." limit 1";
				$info = $db->getRow($sql);
				
				$apply['user_id'] = $userid;
				$apply['status'] = 0;
				$apply['applynum'] = 0;//同意入驻协议
				if($info){
					if ($db->autoExecute($ecs->table('supplier'), $apply, 'UPDATE', 'user_id='.$userid) !== false){
						header("location:apply.php");
						exit;
					 }else{
						show_message('请点击同意入驻协议！', '返回', 'apply.php', 'wrong');
					 }
				}else{
					 if ($db->autoExecute($ecs->table('supplier'), $apply) !== false){
						header("location:apply.php");
						exit;
					 }else{
						show_message('请点击同意入驻协议！', '返回', 'apply.php', 'wrong');
					 }
				}
			}else{
				$err->add('请点击同意入驻协议！');
				$err->show($_LANG['back_up_page']);
			}
		}
	}


	if (!$smarty->is_cached($templates, $cache_id))
	{ 

		/* 模板赋值 */
		assign_template();
		$position = assign_ur_here();

		$smarty->assign('page_title',       $position['title']);       // 页面标题
		$smarty->assign('ur_here',          $position['ur_here'] . '> ' . $topic['title']);     // 当前位置
		
	}
	$smarty->assign('piclimit',$upload_size_limit);
	$smarty->assign('userid',intval($_SESSION['user_id']));
	$smarty->display('apply.dwt');

}
?>