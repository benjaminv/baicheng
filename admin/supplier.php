<?php

/**
 * ECSHOP 管理中心供货商管理
 * ============================================================================
 * * 版权所有 2017-2020 月梦网络，并保留所有权利。
 * 月梦网络: http://www.dm299.com  开发QQ:124861234  禁止倒卖 一经发现停止任何服务；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: 68ecshop $
 * $Id: suppliers.php 15013 2009-05-13 09:31:42Z 68ecshop $
 */

define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
require(ROOT_PATH . 'languages/' .$_CFG['lang']. '/admin/supplier.php');
$smarty->assign('lang', $_LANG);

// echo '<pre>';
// print_r($_REQUEST);
// exit();
// function get_flash_xmls($supplier_id) 
// {
//     global $flash_file;
//     $flashdb = array();
//     if (file_exists(ROOT_PATH . DATA_DIR . '/flash_data_supplier'.$supplier_id.'.xml'))
//     {

//         // 兼容v2.7.0及以前版本
//         if (!preg_match_all('/item_url="([^"]+)"\slink="([^"]+)"\stext="([^"]*)"\ssort="([^"]*)"/', file_get_contents(ROOT_PATH .DATA_DIR . '/flash_data_supplier'.$supplier_id.'.xml'), $t, PREG_SET_ORDER))
//         {
//             preg_match_all('/item_url="([^"]+)"\slink="([^"]+)"\stext="([^"]*)"/', file_get_contents(ROOT_PATH . DATA_DIR . '/flash_data_supplier'.$supplier_id.'.xml'), $t, PREG_SET_ORDER);
//         }

//         if (!empty($t))
//         {
//             foreach ($t as $key => $val)
//             {
//                 $val[4] = isset($val[4]) ? $val[4] : 0;
//                 $flashdb[] = array('src'=>$val[1],'url'=>$val[2],'text'=>$val[3],'sort'=>$val[4]);
//             }
//         }
//     }
//     return $flashdb;
// }


/*------------------------------------------------------ */
//-- 供货商列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    /* 检查权限 */
    admin_priv('supplier_manage');

    /* 查询 */
    $result = suppliers_list();
    //print_r($result);die();
    /* 模板赋值 */
    $ur_here_lang = $_REQUEST['status'] =='1' ? $_LANG['supplier_list'] : $_LANG['supplier_reg_list'];
    $smarty->assign('ur_here', $ur_here_lang); // 当前导航
    $smarty->assign('action_link',  array('href' => 'supplier.php?act=add', 'text' => $_LANG['supplier_add']));

    $smarty->assign('full_page',        1); // 翻页参数

    $smarty->assign('status',    $_REQUEST['status']);
    $smarty->assign('supplier_list',    $result['result']);
    $smarty->assign('filter',       $result['filter']);
    $smarty->assign('record_count', $result['record_count']);
    $smarty->assign('page_count',   $result['page_count']);
    $smarty->assign('sort_suppliers_id', '<img src="images/sort_desc.gif">');
    $sql="select rank_id,rank_name from ". $ecs->table('supplier_rank') ." order by sort_order";
    //print_r($sql);die();
    $supplier_rank=$db->getAll($sql);
    $smarty->assign('supplier_rank', $supplier_rank);

    /* 显示模板 */
    assign_query_info();
    $smarty->display('supplier_list.htm');
}

/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    check_authz_json('supplier_manage');
    
    $result = suppliers_list();

   

    $smarty->assign('supplier_list',    $result['result']);
    $smarty->assign('filter',       $result['filter']);
    $smarty->assign('record_count', $result['record_count']);
    $smarty->assign('page_count',   $result['page_count']);

    /* 排序标记 */
    $sort_flag  = sort_flag($result['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('supplier_list.htm'), '',array('filter' => $result['filter'], 'page_count' => $result['page_count']));
}
/*------------------------------------------------------ */
//-- 添加供货商
/*------------------------------------------------------ */
elseif($_REQUEST['act']=='add')
{    

    /*检查权限*/
    admin_priv('supplier_manage');
    $supplier=array();
    $supplier['id']=0;
    /* 省市县 */
    $supplier_country = $supplier['country'] ?  $supplier['country'] : $_CFG['shop_country'];
    $smarty->assign('country_list',       get_regions());
    $smarty->assign('province_list', get_regions(1, $supplier_country));
    $smarty->assign('city_list', get_regions(2, $supplier['province']));
    $smarty->assign('district_list', get_regions(3, $supplier['city']));
    $smarty->assign('supplier_country', $supplier_country);
    /* 供货商等级 */
    //$sql="select rank_name from ". $ecs->table('supplier_rank') ." where rank_id = ".$supplier['rank_id'];
    //$rank_name=$db->getOne($sql);
    //$supplier['rank_name'] = $rank_name;
    // $sql="select rank_id,rank_name from ". $ecs->table('supplier_rank') ." order by sort_order";
    //$supplier_rank=$db->getAll($sql);
    //$smarty->assign('supplier_rank', $supplier_rank);

    /* 店铺类型 */
    //$sql="select str_name from ". $ecs->table('street_category') ." where str_id = ".$supplier['type_id'];
    //$type_name=$db->getOne($sql);
    //$supplier['type_name'] = $type_name;
    $suppliers = get_supplier_list(0);   // 所有门店

    $smarty->assign('supplier_list', $suppliers);

    $smarty->assign('ur_here', $_LANG['edit_supplier']);
    // 	 $lang_supplier_list = $status=='1' ? $_LANG['supplier_list'] :  $_LANG['supplier_reg_list'];
    //      $smarty->assign('action_link', array('href' => 'supplier.php?act=list', 'text' =>$lang_supplier_list ));
    if ($_REQUEST['status'] == '1')
    {
        $lang_supplier_list = $_LANG['supplier_list'];
        $smarty->assign('action_link', array('href' => 'supplier.php?act=list&status=1', 'text' =>$lang_supplier_list ));
    }
    else
    {
        $lang_supplier_list = $_LANG['supplier_reg_list'];
        $smarty->assign('action_link', array('href' => 'supplier.php?act=list', 'text' =>$lang_supplier_list ));
    }
    //print_r($name_of_region);die();
    $smarty->assign('form_action', 'update');
    $smarty->assign('supplier', $supplier);
    /* 代码增加 By  www.68ecshop.com Start */
    // 商品等级
    //$smarty->assign('rank_id', $supplier['rank_id']);
    $smarty->assign('supplier_rank_list', get_supplier_rank_list());
    /* 代码增加 By  www.68ecshop.com End */

    $company_type = explode("\n", str_replace("\r\n", "\n", $_CFG['company_type']));
    $smarty->assign('company_type', $company_type);
    $sql="select str_id,str_name from ". $ecs->table('street_category') ." where is_show=1 order by sort_order";
    $supplier_type=$db->getAll($sql);
    $smarty->assign('supplier_type', $supplier_type);
    assign_query_info();
    $smarty->display('supplier_info.htm');
}
/*------------------------------------------------------ */
//-- 查看、编辑供货商
/*------------------------------------------------------ */
elseif ($_REQUEST['act']== 'edit')
{
    /* 检查权限 */
    admin_priv('supplier_manage');
    $suppliers = array();
    

    /* 取得供货商信息 */
    $id = $_REQUEST['id'];
    // 	 $status = intval($_REQUEST['status']);
        $sql = "SELECT *,(select user_name from ".$ecs->table('users')." as u where u.user_id=s.user_id) as user_name FROM " . $ecs->table('supplier') . " as s WHERE supplier_id = '$id'";
        $supplier = $db->getRow($sql);

        if (count($supplier) <= 0)
        {
            sys_msg('该供应商不存在！');
        }
        /* 省市县 */
        $supplier_country = $supplier['country'] ?  $supplier['country'] : $_CFG['shop_country'];
        $smarty->assign('country_list',       get_regions());
        $smarty->assign('province_list', get_regions(1, $supplier_country));
        $smarty->assign('city_list', get_regions(2, $supplier['province']));
        $smarty->assign('district_list', get_regions(3, $supplier['city']));
        $smarty->assign('supplier_country', $supplier_country);
        /* 供货商等级 */
        $sql="select rank_name from ". $ecs->table('supplier_rank') ." where rank_id = ".$supplier['rank_id'];
        $rank_name=$db->getOne($sql);
        $supplier['rank_name'] = $rank_name;
        // $sql="select rank_id,rank_name from ". $ecs->table('supplier_rank') ." order by sort_order";
        //$supplier_rank=$db->getAll($sql);
        //$smarty->assign('supplier_rank', $supplier_rank);

        /* 店铺类型 */
        //$type_name=$db->getOne($sql);
        //$supplier['type_name'] = $type_name;

        $smarty->assign('ur_here', $_LANG['edit_supplier']);
    // 	 $lang_supplier_list = $status=='1' ? $_LANG['supplier_list'] :  $_LANG['supplier_reg_list'];
    //      $smarty->assign('action_link', array('href' => 'supplier.php?act=list', 'text' =>$lang_supplier_list ));
    if ($_REQUEST['status'] == '1')
    {
        $lang_supplier_list = $_LANG['supplier_list'];
        $smarty->assign('action_link', array('href' => 'supplier.php?act=list&status=1', 'text' =>$lang_supplier_list ));
    }
    else
    {
        $lang_supplier_list = $_LANG['supplier_reg_list'];
        $smarty->assign('action_link', array('href' => 'supplier.php?act=list', 'text' =>$lang_supplier_list ));
    } 
    $suppliers = get_supplier_list(intval($supplier['is_ipc_shopping']));   // 所有门店
    

    $smarty->assign('supplier_list', $suppliers);
    $company_type = explode("\n", str_replace("\r\n", "\n", $_CFG['company_type']));
    $smarty->assign('company_type', $company_type);
    $sql="select str_id,str_name from ". $ecs->table('street_category') ." where is_show=1 order by sort_order";
    $supplier_type=$db->getAll($sql);

    $smarty->assign('supplier_type', $supplier_type);

    $smarty->assign('form_action', 'update');
    $smarty->assign('supplier', $supplier);

    /* 代码增加 By  www.68ecshop.com Start */
    // 商品等级
    $smarty->assign('rank_id', $supplier['rank_id']);
    $smarty->assign('supplier_rank_list', get_supplier_rank_list());
    /* 代码增加 By  www.68ecshop.com End */

    assign_query_info();

    $smarty->display('supplier_info.htm');


}

/*------------------------------------------------------ */
//-- 查看供货商佣金日志
/*------------------------------------------------------ */
elseif ($_REQUEST['act']== 'view')
{
    /* 检查权限 */
    admin_priv('supplier_manage');

    /* 查询 */
    $result = rebate_log_list();

    /* 模板赋值 */
    $smarty->assign('ur_here', '佣金日志记录'); // 当前导航

    $smarty->assign('full_page',        1); // 翻页参数

    $smarty->assign('log_list',    $result['result']);
    $smarty->assign('filter',       $result['filter']);
    $smarty->assign('record_count', $result['record_count']);
    $smarty->assign('page_count',   $result['page_count']);
    $smarty->assign('sort_suppliers_id', '<img src="images/sort_desc.gif">');

    /* 显示模板 */
    assign_query_info();
    $smarty->display('supplier_log_list.htm');
}

/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query_log')
{
    check_authz_json('supplier_manage');

    $result = rebate_log_list();

    $smarty->assign('log_list',    $result['result']);
    $smarty->assign('filter',       $result['filter']);
    $smarty->assign('record_count', $result['record_count']);
    $smarty->assign('page_count',   $result['page_count']);
    ;

    make_json_result($smarty->fetch('supplier_log_list.htm'), '',
        array('filter' => $result['filter'], 'page_count' => $result['page_count']));
}

/*------------------------------------------------------ */
//-- 提交添加、编辑供货商
/*------------------------------------------------------ */
elseif ($_REQUEST['act']=='update')
{ 
    /* 检查权限 */
    admin_priv('supplier_manage');
    //审核通过，必须要填写的项目
    /* 代码删除 By www.68ecshop.com Start */
    //    if(intval($_POST['status']) == 1){
    //        if(intval($_POST['supplier_rebate_paytime'])<=0){
    //            sys_msg('结算类型必须选择！');
    //        }
    //    }
    /* 代码删除 By www.68ecshop.com End */
    //print_r($_POST);die();
    /* 提交值 */
    
    $upload_size_limit = $_CFG['upload_size_limit'] == '-1' ? ini_get('upload_max_filesize') : $_CFG['upload_size_limit'];

    $supplier_id =  intval($_POST['id']);
    $status_url = intval($_POST['status_url']);
    $company_type=trim($_POST['company_type']);
    $user_id=intval($_POST['user_id']);

    if($_POST['apply_type'] == 'company'){

        $type = '1';

    }else{

        $type = '0';
    }
    //共用参数
    $supplier = array(
        'company_type'   => $company_type,
        'company_name'=>trim($_POST['company_name']),
        'country'=>intval($_POST['country']),
        'province'=>intval($_POST['province']),
        'city'=>intval($_POST['city']),
        'district'=>intval($_POST['district']),
        'address'=>trim($_POST['address']),
        'contacts_name'=>trim($_POST['contacts_name']),
        'contacts_phone'=>trim($_POST['contacts_phone']),
        'email'=>trim($_POST['email']),
        'id_card_no'=>trim($_POST['id_card_no']),
        //'bank_account_name'=>trim($_POST['bank_account_name']),
        //'bank_account_number'=>trim($_POST['bank_account_number']),
       // 'bank_name'=>trim($_POST['bank_name']),
        //'bank_code'=>trim($_POST['bank_code']),
        'supplier_name'=>trim($_POST['supplier_name']),
        /* 代码增加 By  www.68ecshop.com Start */
        'rank_id' => $_POST['rank_id'],
        /* 代码增加 By  www.68ecshop.com End */
        'type_id'=>$type,
        'system_fee'=>trim($_POST['system_fee']),
        'supplier_bond'=>trim($_POST['supplier_bond']),
        'supplier_rebate'=>trim($_POST['supplier_rebate']),
        'supplier_remark'=>trim($_POST['supplier_remark']),
        'status'   => intval($_POST['status']),

        //企业相关参数
        'tel'   => trim($_POST['tel']),
        'guimo'   => trim($_POST['guimo']),
        'business_licence_number'   => trim($_POST['business_licence_number']),
        'business_sphere'   => trim($_POST['business_sphere']),
        'organization_code'   => trim($_POST['organization_code']),
        'tax_registration_certificate'   => trim($_POST['tax_registration_certificate']),
        'taxpayer_id'   => trim($_POST['taxpayer_id']),
        //'settlement_bank_account_name'	=>	trim($_POST['settlement_bank_account_name']),
       /// 'settlement_bank_account_number'	=>	trim($_POST['settlement_bank_account_number']),
       // 'settlement_bank_name'	=>	trim($_POST['settlement_bank_name']),
        //'settlement_bank_code'	=>	trim($_POST['settlement_bank_code']),
        'supplier_rebate_paytime'   => intval($_POST['supplier_rebate_paytime']),
        'databases_name'=>intval($_POST['databases']),
		'is_ipc_shopping'   => intval($_POST['is_ipc_shopping']),
		'old_user_rate'   => floatval($_POST['old_user_rate'])
    );
	
	if($supplier['is_ipc_shopping'] == 2){
		if(isset($_FILES['technican_bodyimg']['name']) && !empty($_FILES['technican_bodyimg']['name'])){
				
			if($_FILES['technican_bodyimg']['size'] / 1024 > $upload_size_limit)
			{
				sys_msg(sprintf($_LANG['upload_file_limit'], $upload_size_limit));
			}
					
			$logosrc = upload_file2($_FILES['technican_bodyimg'], 'supplier');
			if ($logosrc === false)
			{
				sys_msg('个人艺术照上传失败');
			}
			$supplier['technican_bodyimg'] = $logosrc;
			unset($logosrc);
		}
		$supplier['opus_remark'] = trim($_POST['opus_remark']);
	}
    $step_act='edit';
    if($supplier_id!=0){
        $sql = "select s.supplier_id,s.add_time,s.status,u.* from " . $ecs->table('supplier') . " as s left join ". $ecs->table('users') .
            " as u on s.user_id=u.user_id where s.supplier_id=".$supplier_id;
        $supplier_old = $db->getRow($sql);
        if (empty($supplier_old['supplier_id'])) {
            sys_msg('该供货商信息不存在！');
        }
    }else{
        $sql = "select * from " . $ecs->table('users') . " where user_id=".$user_id;
        $user = $db->getRow($sql);
        if (empty($user['user_id'])) {
            sys_msg('该店铺所属会员不存在！');
        }
        $supplier['applynum']=3;//表示已经申请
        $supplier['user_id']=$user_id;
        $step_act='add';
    }

    //print_r($supplier);die();
    /* 代码增加_start  By  supplier.68ecshop.com */
    /* 取得供货商信息 */
    //$sql = "SELECT * FROM " . $ecs->table('supplier') . " WHERE supplier_id = '" . $supplier_id ."' ";
    if($company_type==''){//为空是个人
        //上传处理
        if (isset($_FILES['handheld_idcard']) && $_FILES['handheld_idcard']['tmp_name'] != '' &&  isset($_FILES['handheld_idcard']['tmp_name']) && $_FILES['handheld_idcard']['tmp_name'] != 'none')
        {
            if($_FILES['handheld_idcard']['size'] / 1024 > $upload_size_limit)
            {
                sys_msg(sprintf($_LANG['upload_file_limit'], $upload_size_limit));
            }
            $handheld_idcard_img = upload_file2($_FILES['handheld_idcard'], 'supplier');
            //echo $handheld_idcard_img;
            //exit;
            if ($handheld_idcard_img === false) {
                sys_msg('手持身份证照片上传失败！');
            } else {
                $supplier['handheld_idcard'] = $handheld_idcard_img;
            }
        }
        if (isset($_FILES['idcard_front']) && $_FILES['idcard_front']['tmp_name'] != '' &&  isset($_FILES['idcard_front']['tmp_name']) && $_FILES['idcard_front']['tmp_name'] != 'none')
        {
            if($_FILES['idcard_front']['size'] / 1024 > $upload_size_limit)
            {
                sys_msg(sprintf($_LANG['upload_file_limit'], $upload_size_limit));
            }
            $idcard_front_img = upload_file2($_FILES['idcard_front'], 'supplier');
            if ($idcard_front_img === false) {
                sys_msg('身份证正面照片上传失败！');
            } else {
                $supplier['idcard_front'] = $idcard_front_img;
            }
        }
        if (isset($_FILES['idcard_reverse']) && $_FILES['idcard_reverse']['tmp_name'] != '' &&  isset($_FILES['idcard_reverse']['tmp_name']) && $_FILES['idcard_reverse']['tmp_name'] != 'none')
        {
            if($_FILES['idcard_reverse']['size'] / 1024 > $upload_size_limit)
            {
                sys_msg(sprintf($_LANG['upload_file_limit'], $upload_size_limit));
            }
            $idcard_reverse_img = upload_file2($_FILES['idcard_reverse'], 'supplier');
            if ($idcard_reverse_img === false) {
                sys_msg('身份证反面照片上传失败！');
            } else {
                $supplier['idcard_reverse'] = $idcard_reverse_img;
            }
        }
    }else{//企业
        if (isset($_FILES['zhizhao']) && $_FILES['zhizhao']['tmp_name'] != '' &&  isset($_FILES['zhizhao']['tmp_name']) && $_FILES['zhizhao']['tmp_name'] != 'none')
        {
            if($_FILES['zhizhao']['size'] / 1024 > $upload_size_limit)
            {
                sys_msg(sprintf($_LANG['upload_file_limit'], $upload_size_limit));
            }
            $zhizhao_img = upload_file2($_FILES['zhizhao'], 'supplier');
            if ($zhizhao_img === false)
            {
                sys_msg('营业执照号电子版图片上传失败！');
            }
            else
            {
                $supplier['zhizhao'] = $zhizhao_img;
            }
        }
        if (isset($_FILES['organization_code_electronic']) && $_FILES['organization_code_electronic']['tmp_name'] != '' &&  isset($_FILES['organization_code_electronic']['tmp_name']) && $_FILES['organization_code_electronic']['tmp_name'] != 'none')
        {
            if($_FILES['organization_code_electronic']['size'] / 1024 > $upload_size_limit)
            {
                sys_msg(sprintf($_LANG['upload_file_limit'], $upload_size_limit));
            }

            $organization_code_electronic_img = upload_file2($_FILES['organization_code_electronic'], 'supplier');
            if ($organization_code_electronic_img === false)
            {
                sys_msg('组织机构代码证电子版图片上传失败！');
            }
            else
            {
                $supplier['organization_code_electronic'] = $organization_code_electronic_img;
            }
        }
        if (isset($_FILES['general_taxpayer']) && $_FILES['general_taxpayer']['tmp_name'] != '' &&  isset($_FILES['general_taxpayer']['tmp_name']) && $_FILES['general_taxpayer']['tmp_name'] != 'none')
        {
            if($_FILES['general_taxpayer']['size'] / 1024 > $upload_size_limit)
            {
                sys_msg(sprintf($_LANG['upload_file_limit'], $upload_size_limit));
            }
            $general_taxpayer_img = upload_file2($_FILES['general_taxpayer'], 'supplier');
            if ($general_taxpayer_img === false)
            {
                sys_msg('一般纳税人证明图片上传失败！');
            }
            else
            {
                $supplier['general_taxpayer'] = $general_taxpayer_img;
            }
        }
        if (isset($_FILES['tax_registration_certificate_electronic']) && $_FILES['tax_registration_certificate_electronic']['tmp_name'] != '' &&  isset($_FILES['tax_registration_certificate_electronic']['tmp_name']) && $_FILES['tax_registration_certificate_electronic']['tmp_name'] != 'none')
        {
            if($_FILES['tax_registration_certificate_electronic']['size'] / 1024 > $upload_size_limit)
            {
                sys_msg(sprintf($_LANG['upload_file_limit'], $upload_size_limit));
            }
            $tax_registration_certificate_electronic_img = upload_file2($_FILES['tax_registration_certificate_electronic'], 'supplier');
            if ($tax_registration_certificate_electronic_img === false)
            {
                sys_msg('税务登记证号电子版图片上传失败！');
            }
            else
            {
                $supplier['tax_registration_certificate_electronic'] = $tax_registration_certificate_electronic_img;
            }
        }
        if (isset($_FILES['bank_licence_electronic']) && $_FILES['bank_licence_electronic']['tmp_name'] != '' &&  isset($_FILES['bank_licence_electronic']['tmp_name']) && $_FILES['bank_licence_electronic']['tmp_name'] != 'none')
        {
            if($_FILES['bank_licence_electronic']['size'] / 1024 > $upload_size_limit)
            {
                sys_msg(sprintf($_LANG['upload_file_limit'], $upload_size_limit));
            }
            $bank_licence_electronic_img = upload_file2($_FILES['bank_licence_electronic'], 'supplier');
            if ($bank_licence_electronic_img === false)
            {
                sys_msg('开户银行许可证电子版图片上传失败！');
            }
            else
            {
                $supplier['bank_licence_electronic'] = $bank_licence_electronic_img;
            }
        }
    }
    if((empty($supplier_old['add_time']) && $supplier['status'] == 1)||($step_act=='add')){
        //审核通过时就是店铺创建成功的时间
        $supplier['add_time'] = time();
    }
    //当为0时表示是添加
    if($step_act=='add'){
        //print_r($supplier);
        //判断之前此用户是否有申请店铺，如果有更新，否则才添加
        $supplier_old=$db->getRow("select s.supplier_id,s.add_time,s.status,u.* from " . $ecs->table('supplier') . " as s left join ". $ecs->table('users') .
            " as u on s.user_id=u.user_id where s.user_id=".$supplier['user_id']);
        if($supplier_old){
            $result=$db->autoExecute($ecs->table('supplier'), $supplier, 'UPDATE', 'supplier_id='.$supplier_old['supplier_id']);
            if ($result === false){
                sys_msg('生成店铺失败，请刷新后再试！');
            }
            $supplier_id = $supplier_old['supplier_id'];
        }else{
            //没有再添加
            $result=$db->autoExecute($ecs->table('supplier'), $supplier);
            if ($result === false){
                sys_msg('生成店铺失败，请刷新后再试！');
            }
            $supplier_id = $db->insert_id();
            $sql = "select s.supplier_id,s.add_time,s.status,u.* from " . $ecs->table('supplier') . " as s left join ". $ecs->table('users') .
                " as u on s.user_id=u.user_id where s.supplier_id=".$supplier_id;
            $supplier_old = $db->getRow($sql);
            if (empty($supplier_old['supplier_id'])) {
                sys_msg('生成店铺失败，请刷新后再试！');
            }
        }
        //print_r($supplier_old);die();
    }

    //print_r($supplier);die();
    //操作店铺商品与店铺街信息
    if($supplier['status'] != $supplier_old['status'] && $supplier['status'] == -1){
        //审核不通过
        //店铺街信息失效
        $check_info = array(
            'is_groom' => 0,
            'is_show' => 0,
            'supplier_notice' => '',
            'status' => 0
        );
        $db->autoExecute($ecs->table('supplier_street'), $check_info, 'UPDATE', "supplier_id = '" . $supplier_id . "'");
        //商品下架
        $good_info = array(
            'is_on_sale' => 0
        );
        $db->autoExecute($ecs->table('goods'), $good_info, 'UPDATE', "supplier_id = '" . $supplier_id . "'");
        //删除店铺所在的标签
        $db->query('delete FROM '.$ecs->table('supplier_tag_map').' WHERE supplier_id = '.$supplier_id);
    }

    //更新相关店铺的管理员状态
    $sql = "select * from ". $ecs->table('supplier_admin_user') ." where supplier_id=".$supplier_old['supplier_id'];
    $info = $db->getAll($sql);
    if(count($info)>0){
        $sql = "UPDATE ". $ecs->table('supplier_admin_user') ." SET user_name = '".$supplier_old['user_name']."',password = '".$supplier_old['password']."',email='".$supplier_old['email']."',mobile_phone='".$supplier_old['mobile_phone']."',ec_salt='".$supplier_old['ec_salt']."', checked = ".intval($_POST['status'])." WHERE supplier_id=".$supplier_old['supplier_id']." and uid=".$supplier_old['user_id'];
        $db->query($sql);
    }else{
        $insql = "INSERT INTO " . $ecs->table('supplier_admin_user') . " (`uid`, `user_name`, `email`, `password`, `ec_salt`, `add_time`, `last_login`, `last_ip`, `action_list`, `nav_list`, `lang_type`, `agency_id`, `supplier_id`, `todolist`, `role_id`, `checked`, `mobile_phone`) ".
            "VALUES(".$supplier_old['user_id'].", '".$supplier_old['user_name']."', '".$supplier_old['email']."', '".$supplier_old['password']."', '".$supplier_old['ec_salt']."', ".$supplier_old['last_login'].", ".$supplier_old['last_login'].", '".$supplier_old['last_ip']."', 'all', '', '', 0, ".$supplier_old['supplier_id'].", NULL, NULL, ".intval($_POST['status']).", '".$supplier_old['mobile_phone']."')";
        $db->query($insql);
    }
    /* 代码增加_end  By  supplier.68ecshop.com */

    if($step_act!='add'){
        /* 保存供货商信息 */
        $db->autoExecute($ecs->table('supplier'), $supplier, 'UPDATE', "supplier_id = '" . $supplier_id . "'");
    }
     

    // 同步数据库的名称值数据库中
    
    $databases = $_REQUEST['databases'];
    $sql = 'update ecs_supplier set databases_name="'.$databases.'" where supplier_id="'.$supplier_id.'"';
    $db->query($sql);
	if($_POST['status'] == 1){
		$supplier_user_id = $db->getOne('SELECT user_id FROM'.$ecs->table('supplier').' WHERE supplier_id='.$supplier_id);
		//审核通过后,店长所属店铺更改为该店铺 edit by yhy
		$sql="update ". $ecs->table('users') ." set supplierId=".$supplier_id." where user_id='$supplier_user_id' ";
        $db->query($sql);
	}



    if ($_POST['status']!='1')
    {
        $sql="update ". $ecs->table('goods') ." set is_on_sale=0 where supplier_id='$supplier_id' ";
        $db->query($sql);
    }
    /* 清除缓存 */
    clear_cache_files();
    /* 提示信息 */
    $links[] = array('href' => ($status_url >0 ? 'supplier.php?act=list&status=1' : 'supplier.php?act=list'), 'text' => ($status_url >0 ? $_LANG['back_supplier_list'] : $_LANG['back_supplier_reg']));

    // 复制店铺吧操作 获取是否已经同步字段

    $is_have_to_copy = $db->getOne('select is_have_copy_shop from ecs_supplier where supplier_id="'.$supplier_id.'"');
    if($_POST['status'] == 1 && (!$is_have_to_copy) && intval($_REQUEST['copy_supplier_id']) && intval($_REQUEST['copy_supplier_id']) > 0 ){

        $copy_rel = copy_shops($_REQUEST['copy_supplier_id'],$supplier_id);

    }


    // 获取是否已经同步字段
    $is_have_to_erp = $db->getOne('select is_to_erp from ecs_supplier where supplier_id="'.$supplier_id.'"');

    if($_POST['status'] == 1 && ODOO_BC_ERP && (!$is_have_to_erp)){

        $rel = transport_dian_pu_to_erp($supplier_id);

        if( isset($rel['SuccessCode']) && $rel['SuccessCode'] == '1'){

            $db->query('update ecs_supplier set is_to_erp=1 where supplier_id="'.$supplier_id.'"');
        }

    }

	/*异业商圈 yhy 2019/7/1  暂时注释掉，商圈由后台手动添加*/
	//if($_POST['status'] == 1 && $_POST['is_ipc_shopping'] == 1){
		//addOrUpdateSupplierCircle($supplier_id);	
	//}
	/*end*/
    sys_msg($step_act=='add'?$_LANG['add_supplier_ok']:$_LANG['edit_supplier_ok'], 0, $links);
}

//删除店铺信息
elseif ($_REQUEST['act'] == 'delete'){
    /* 检查权限 */
    admin_priv('supplier_manage');
    $supplier_id =  intval($_GET['id']);

    $sql = "SELECT * FROM " . $ecs->table('supplier') . " WHERE supplier_id = ".$supplier_id;
    $supplier = $db->getRow($sql);
    if (count($supplier) <= 0)
    {
        sys_msg('该供应商不存在！');
    }


    if($supplier_id > 0){
        $ret = array();
        //入驻商相关删除信息
        $supplier_info = array(
            'delete FROM '.$ecs->table('supplier_admin_user').' WHERE supplier_id = '.$supplier_id,
            'delete FROM '.$ecs->table('supplier_article').' WHERE supplier_id = '.$supplier_id,
            'delete FROM '.$ecs->table('supplier_category').' WHERE supplier_id = '.$supplier_id,
            'delete FROM '.$ecs->table('supplier_cat_recommend').' WHERE supplier_id = '.$supplier_id,
            'delete FROM '.$ecs->table('supplier_goods_cat').' WHERE supplier_id = '.$supplier_id,
            'delete FROM '.$ecs->table('supplier_guanzhu').' WHERE supplierid = '.$supplier_id,
            'delete FROM '.$ecs->table('supplier_money_log').' WHERE supplier_id = '.$supplier_id,
            'delete FROM '.$ecs->table('supplier_nav').' WHERE supplier_id = '.$supplier_id,
            /* 代码删除 By  www.68ecshop.com Start */
    //            'delete FROM '.$ecs->table('supplier_rebate_log').' WHERE rebateid in (SELECT rebate_id FROM '.$ecs->table('supplier_rebate').' WHERE supplier_id ='.$supplier_id.')',
                /* 代码删除 By  www.68ecshop.com End */
                'delete FROM '.$ecs->table('supplier_shop_config').' WHERE supplier_id = '.$supplier_id,
                'delete FROM '.$ecs->table('supplier_street').' WHERE supplier_id = '.$supplier_id,
                'delete FROM '.$ecs->table('supplier_tag_map').' WHERE supplier_id = '.$supplier_id
            );
            /* 代码增加 By  www.68ecshop.com Start */
    //        if($db->getOne('SELECT COUNT(*) FROM ' . $ecs->table('supplier_rebate') . ' WHERE supplier_id = ' . $supplier_id))
    //        {
    //            array_push($supplier_info, 'delete FROM '.$ecs->table('supplier_rebate_log').' WHERE rebateid in (SELECT rebate_id FROM '.$ecs->table('supplier_rebate').' WHERE supplier_id ='.$supplier_id.')');
    //        }
        /* 代码增加 By  www.68ecshop.com End */
        foreach($supplier_info as $sk=>$sv){
            if($db->query($sv)){}else{
                $ret[] = $sv;
            }
        }
        //delete_supplier_pic($supplier_id);
        //商品相关删除信息
        $goods_info = array(
            'delete FROM '.$ecs->table('goods_activity').' WHERE goods_id in (SELECT goods_id FROM '.$ecs->table('goods').' WHERE supplier_id ='.$supplier_id.')',
            'delete FROM '.$ecs->table('goods_attr').' WHERE goods_id in (SELECT goods_id FROM '.$ecs->table('goods').' WHERE supplier_id ='.$supplier_id.')',
            'delete FROM '.$ecs->table('goods_cat').' WHERE goods_id in (SELECT goods_id FROM '.$ecs->table('goods').' WHERE supplier_id ='.$supplier_id.')',
            'delete FROM '.$ecs->table('goods_gallery').' WHERE goods_id in (SELECT goods_id FROM '.$ecs->table('goods').' WHERE supplier_id ='.$supplier_id.')',
            'delete FROM '.$ecs->table('goods_tag').' WHERE goods_id in (SELECT goods_id FROM '.$ecs->table('goods').' WHERE supplier_id ='.$supplier_id.')',
            'delete FROM '.$ecs->table('products').' WHERE goods_id in (SELECT goods_id FROM '.$ecs->table('goods').' WHERE supplier_id ='.$supplier_id.')'
        );
        foreach($goods_info as $gk=>$gv){
            if($db->query($gv)){}else{
                $ret[] = $gv;
            }
        }
        //最后删除中间表信息
        $other_info = array(
            'delete FROM '.$ecs->table('goods').' WHERE supplier_id = '.$supplier_id,
            'delete FROM '.$ecs->table('supplier').' WHERE supplier_id = '.$supplier_id
   //            'delete FROM '.$ecs->table('supplier_rebate').' WHERE supplier_id = '.$supplier_id
        );
        foreach($other_info as $ok=>$ov){
            if($db->query($ov)){}else{
                $ret[] = $ov;
            }
        }

    }
    if(count($ret)>0){
        echo "如下删除语句执行失败:";
        echo "<pre>";
        print_r($ret);
        sleep(10);
    }

    /* 提示信息 */
    $links[0] = array('href' => 'supplier.php?act=list&status='.$supplier['status'], 'text' =>'返回上一页');
    sys_msg('删除成功!',0,$links);
}

/*------------------------------------------------------ */
//-- 修改促销商品状态
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'toggle_lucky_buy' or $_REQUEST['act'] == 'toggle_extpintuan' or $_REQUEST['act'] == 'toggle_cut')
{
    $supplier_id       = intval($_POST['id']);
    $is_toggle        = intval($_POST['val']);
	$filed = array('toggle_lucky_buy'=>'lucky_buy','toggle_extpintuan'=>'extpintuan','toggle_cut'=>'cut');
	$sql = "UPDATE ". $ecs->table('supplier') ." SET ".$filed[$_REQUEST['act']]." = '".$is_toggle."' WHERE supplier_id='".$supplier_id."'";
    if ( $db->query($sql) )
    {
        clear_cache_files();
        make_json_result($is_toggle);
    }
}


/* 代码增加 By  www.68ecshop.com Start */
/*------------------------------------------------------ */
//-- 批量导出供货商
/*------------------------------------------------------ */
elseif ($_REQUEST['act']=='export')
{
    $where = " WHERE s.applynum = 3 AND s.status = 1 ";

    // 入驻商名称
    if (isset($_REQUEST['supplier_name']) && !empty($_REQUEST['supplier_name']))
    {
        $where .= " AND s.supplier_name LIKE '%" . mysql_like_quote($_REQUEST['supplier_name']) . "%'";
    }
    // 入驻商等级
    if (isset($_REQUEST['rank_id']) && !empty($_REQUEST['rank_id']))
    {
        $where .= " AND s.rank_id = " . $_REQUEST['rank_id'];
    }

     // 店铺类型
    if (isset($_REQUEST['is_ipc_shopping']) && trim($_REQUEST['is_ipc_shopping']) >=0)
    {
        $where .= " AND s.is_ipc_shopping = " . $_REQUEST['is_ipc_shopping'];
    }
    // 用户名
    if(trim($_REQUEST['keyword'])){

        $where .= ' and u.user_name like "%'.$_REQUEST['keyword'].'%" or u.email like "%'.$_REQUEST['keyword'].'%" or u.mobile_phone like "%'.$_REQUEST['keyword'].'%"';
    }

    /* 查询 */
    $sql = "SELECT "
        . "u.user_name, " // 会员名称
        . "s.supplier_name, " // 入驻商名称
        . "s.rank_id, " // 入驻商等级
        . "s.tel, " // 公司电话
        . "s.system_fee, " // 平台使用费
        . "s.supplier_bond, " // 商家保证金    
        . "s.supplier_rebate, " // 分成利率
        . "s.supplier_remark, " // 入驻商备注
        . "s.status " // 状态
        . "FROM "
        . $GLOBALS['ecs']->table("supplier") . " AS s LEFT JOIN "
        . $GLOBALS['ecs']->table("users") . " AS u ON s.user_id = u.user_id "
        . $where;


    $res = $GLOBALS['db']->getAll($sql);

    // 引入phpexcel核心类文件
    require_once ROOT_PATH . '/includes/phpexcel/Classes/PHPExcel.php';
    // 实例化excel类
    $objPHPExcel = new PHPExcel();
    // 操作第一个工作表
    $objPHPExcel->setActiveSheetIndex(0);
    // 设置sheet名
    $objPHPExcel->getActiveSheet()->setTitle('入驻商列表');
    // 设置表格宽度
    $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);
    $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
    $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
    $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
    $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
    $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
    $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
    $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
    $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(15);
    // 列名表头文字加粗
    $objPHPExcel->getActiveSheet()->getStyle('A1:I1')->getFont()->setBold(true);
    // 列表头文字居中
    $objPHPExcel->getActiveSheet()->getStyle('A1:I1')->getAlignment()
        ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    // 列名赋值
    $objPHPExcel->getActiveSheet()->setCellValue('A1', '会员名称');
    $objPHPExcel->getActiveSheet()->setCellValue('B1', '入驻商名称');
    $objPHPExcel->getActiveSheet()->setCellValue('C1', '入驻商等级');
    $objPHPExcel->getActiveSheet()->setCellValue('D1', '公司电话');
    $objPHPExcel->getActiveSheet()->setCellValue('E1', '平台使用费');
    $objPHPExcel->getActiveSheet()->setCellValue('F1', '商家保证金');
    $objPHPExcel->getActiveSheet()->setCellValue('G1', '分成利率');
    $objPHPExcel->getActiveSheet()->setCellValue('H1', '入驻商备注');
    $objPHPExcel->getActiveSheet()->setCellValue('I1', '状态');

    // 数据起始行
    $row_num = 2;
    // 向每行单元格插入数据
    foreach($res as $value)
    {
        // 入驻商等级
        switch ($value['rank_id'])
        {
            case 1:
                $rank_name = '初级店铺';
                break;
            case 2:
                $rank_name = '中级店铺';
                break;
            case 3:
                $rank_name = '高级店铺';
                break;
            default:
                $rank_name = '';
        }

        // 设置所有垂直居中
        $objPHPExcel->getActiveSheet()->getStyle('A' . $row_num . ':' . 'I' . $row_num)->getAlignment()
            ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        // 设置平台使用费和商家保证金为数字格式
        $objPHPExcel->getActiveSheet()->getStyle('E' . $row_num . ':' . 'F' . $row_num)->getNumberFormat()
            ->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        // 设置分成利率为数字格式
        $objPHPExcel->getActiveSheet()->getStyle('G' . $row_num)->getNumberFormat()
            ->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER);

        // 设置单元格数值
        $objPHPExcel->getActiveSheet()->setCellValueExplicit('A' . $row_num, $value['user_name'], PHPExcel_Cell_DataType::TYPE_STRING);
        $objPHPExcel->getActiveSheet()->setCellValue('B' . $row_num, $value['supplier_name']);
        $objPHPExcel->getActiveSheet()->setCellValue('C' . $row_num, $rank_name);
        $objPHPExcel->getActiveSheet()->setCellValueExplicit('D' . $row_num, $value['tel'], PHPExcel_Cell_DataType::TYPE_STRING);
        $objPHPExcel->getActiveSheet()->setCellValue('E' . $row_num, $value['system_fee']);
        $objPHPExcel->getActiveSheet()->setCellValue('F' . $row_num, $value['supplier_bond']);
        $objPHPExcel->getActiveSheet()->setCellValue('G' . $row_num, $value['supplier_rebate']);
        $objPHPExcel->getActiveSheet()->setCellValue('H' . $row_num, $value['supplier_remark']);
        $objPHPExcel->getActiveSheet()->setCellValue('I' . $row_num, $value['status'] ? '通过' : '');
        $row_num++;
    }
    $outputFileName = '入驻商_' . time() . '.xls';
    $xlsWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream");
    header("Content-Type: application/download");
    header('Content-Disposition:inline;filename="' . $outputFileName . '"');
    header("Content-Transfer-Encoding: binary");
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Pragma: no-cache");
    $xlsWriter->save("php://output");
    echo file_get_contents($outputFileName);
    /* 代码增加 By  www.68ecshop.com End */

    /*------------------------------------------------------ */
    //-- 根据关键字和id搜索用户
    /*------------------------------------------------------ */
}elseif($_REQUEST['act']=='search_users'){
        include_once(ROOT_PATH . 'includes/cls_json.php');
        $json = new JSON();
        $id_name = empty($_GET['id_name']) ? '' : json_str_iconv(trim($_GET['id_name']));
        $result = array('error'=>0, 'message'=>'', 'content'=>'');
        if ($id_name != '')
        {
            $sql = "SELECT user_id, user_name FROM " . $GLOBALS['ecs']->table('users') .
                " WHERE user_id LIKE '%" . mysql_like_quote($id_name) . "%'" .
                " OR user_name LIKE '%" . mysql_like_quote($id_name) . "%'" .
                " LIMIT 20";
            $res = $GLOBALS['db']->query($sql);

            $result['userlist'] = array();
            while ($row = $GLOBALS['db']->fetchRow($res))
            {
                $result['userlist'][] = array('user_id' => $row['user_id'], 'user_name' => $row['user_name']);
            }
        }
        else
        {
            $result['error'] = 1;
            $result['message'] = 'NO KEYWORDS!';
        }
        die($json->encode($result));
}elseif($_REQUEST['act']=='transport_to_erp'){
     
   if( ODOO_BC_ERP ){

        $rel = transport_dian_pu_to_erp($_REQUEST['id']);


        if( isset($rel['SuccessCode']) && $rel['SuccessCode'] == '1'){

            $db->query('update ecs_supplier set is_to_erp=1 where supplier_id="'.$_REQUEST['id'].'"');


            $links[] = array('href' => 'supplier.php?act=list&status=1');

            sys_msg('同步erp商铺成功',0,$links);
        }else{

            sys_msg($rel,1,[],false);

        }


        
    }   
}
elseif($_REQUEST['act'] == 'circle'){
	 /* 检查权限 */
     admin_priv('suppliers_manage');
    /* 查询 */
    $result = get_supplier_circle();
    /* 模板赋值 */
    $smarty->assign('ur_here', $_LANG['supplier_list']); // 当前导航
    $smarty->assign('full_page',        1); // 翻页参数
    $smarty->assign('supplier_list',    $result['result']);
    $smarty->assign('filter',       $result['filter']);
    $smarty->assign('record_count', $result['record_count']);
    $smarty->assign('page_count',   $result['page_count']);
    $smarty->assign('sort_suppliers_id', '<img src="images/sort_desc.gif">');
	$smarty->assign('action_link', array('href' => 'supplier.php?act=add_circle', 'text' =>'新增商圈' ));
    /* 显示模板 */
    assign_query_info();
    $smarty->display('supplier_circle.htm');
}
elseif($_REQUEST['act']=='add_circle'){
	$smarty->assign('form_action', 'circle_update');
	assign_query_info();
    $smarty->display('supplier_circle_info.htm');
}
elseif($_REQUEST['act']=='circle_info'){
	$id = intval($_REQUEST['id']);
	$info = $GLOBALS['db']->getRow("SELECT * FROM ".$GLOBALS['ecs']->table('supplier_circle')." WHERE id=".$id);
	$smarty->assign('info',$info);
	$smarty->assign('form_action', 'circle_update');
    /* 显示模板 */
    assign_query_info();
    $smarty->display('supplier_circle_info.htm');
}
elseif($_REQUEST['act']=='circle_update'){
	$data['name'] = trim($_POST['name']);
	$data['remark'] = trim($_POST['remark']);
	/* 检查图片：如果有错误，检查尺寸是否超过最大值；否则，检查文件类型 */
	if(isset($_FILES['logo'])  && $_FILES['logo']['tmp_name'] != '' && isset($_FILES['logo']['tmp_name']) && $_FILES['logo']['tmp_name'] != 'none'){
		if (isset($_FILES['logo']['error'])) // php 4.2 版本才支持 error
		{
			include_once(ROOT_PATH . '/includes/cls_image.php');
			$image = new cls_image($_CFG['bgcolor']);
			// 最大上传文件大小
			$php_maxsize = ini_get('upload_max_filesize');
			$htm_maxsize = '2M';

			if ($_FILES['logo']['error'] == 0)
			{
				if (!$image->check_img_type($_FILES['logo']['type']))
				{
					sys_msg('非法的图片类型', 1, array(), false);
				}
			}
			elseif ($_FILES['logo']['error'] == 1)
			{
				sys_msg(sprintf("图片文件太大了（最大值：%s），无法上传。", $php_maxsize), 1, array(), false);
			}
			elseif ($_FILES['logo']['error'] == 2)
			{
				sys_msg(sprintf("图片文件太大了（最大值：%s），无法上传。", $htm_maxsize), 1, array(), false);
			}
		}
		$logosrc = $image->upload_image($_FILES['logo']);
		if ($logosrc === false)
        {
            sys_msg($image->error_msg(), 1, array(), false);
        }
		$data['logo'] = trim($logosrc);
	}
	if($_POST['id']){
		if($GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('supplier_circle'), $data, 'UPDATE', 'id = "'.$_POST['id'].'"')){
			$links[] = array('href' => 'supplier.php?act=circle');
			sys_msg('操作成功',0,$links);
		}else{
			$links[] = array('href' => 'supplier.php?act=circle');
			sys_msg('操作失败',0,$links);
		}
	}else{
		if($GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('supplier_circle'), $data, 'INSERT')){
			$links[] = array('href' => 'supplier.php?act=circle');
			sys_msg('操作成功',0,$links);
		}else{
			$links[] = array('href' => 'supplier.php?act=circle');
			sys_msg('操作失败',0,$links);
		}
	}
}
elseif($_REQUEST['act']=='supplier_circle_list'){
	//商圈的店铺列表
	 /* 检查权限 */
     admin_priv('suppliers_manage');
    /* 查询 */
    $result = get_supplier_circle_list();
	//echo "<pre>";print_r($result);exit;
    /* 模板赋值 */
    $smarty->assign('ur_here', $_LANG['supplier_list']); // 当前导航
    $smarty->assign('full_page',        1); // 翻页参数
    $smarty->assign('supplier_list',    $result['result']);
    $smarty->assign('filter',       $result['filter']);
    $smarty->assign('record_count', $result['record_count']);
    $smarty->assign('page_count',   $result['page_count']);
    $smarty->assign('sort_suppliers_id', '<img src="images/sort_desc.gif">');
	$smarty->assign('page_count',   $result['page_count']);
	$smarty->assign('action_link', array('href' => 'supplier.php?act=add_supplier_circle&circle_id='.$_REQUEST['circle_id'], 'text' =>'新增商圈店铺' ));
    /* 显示模板 */
    assign_query_info();
    $smarty->display('supplier_circle_list.htm');
}
elseif($_REQUEST['act']=='supplier_circle_del'){
	$sql = 'DELETE FROM '.$ecs->table('supplier_circle_list').' WHERE id = '.intval($_REQUEST['id']);
	if($db->query($sql)){
		$links[] = array('href' => 'supplier.php?act=supplier_circle_list&circle_id='.intval($_REQUEST['circle_id']));
        sys_msg('操作成功',0,$links);
	}else{
		$links[] = array('href' => 'supplier.php?act=supplier_circle_list&circle_id='.intval($_REQUEST['circle_id']));
        sys_msg('操作失败',0,$links);
	}
}
elseif($_REQUEST['act']=='add_supplier_circle'){
	$circle_list = get_circle_list();
	$smarty->assign('circle_list',   $circle_list);
	$smarty->assign('form_action','insert_supplier_circle');
	
	assign_query_info();
	$smarty->display('supplier_circle_list_add.htm');
}
elseif($_REQUEST['act']=='search_supplier'){
	//查询不属于商圈的店铺
	$search_key=trim($_REQUEST['search_key']);
	$sql = "SELECT a.supplier_id,a.supplier_name,IFNULL(b.circle_id,0) as circle_id FROM ".$GLOBALS['ecs']->table('supplier')." a LEFT JOIN ".$GLOBALS['ecs']->table('supplier_circle_list')." b ON a.supplier_id=b.supplier_id WHERE a.status=1 AND a.supplier_name like '%".$search_key."%' HAVING circle_id=0 ORDER BY a.supplier_id DESC LIMIT 0,20 ";
	$supplier_list = $GLOBALS['db']->getAll($sql);
	echo json_encode($supplier_list);
}
elseif($_REQUEST['act']=='insert_supplier_circle'){
	$supplier_id = intval($_REQUEST['supplier_id']);
	$circle_id = intval($_REQUEST['circle_id']);

	$links[] = array('href' => 'supplier.php?act=add_supplier_circle');
	if(empty($supplier_id) || empty($circle_id)){
        sys_msg('请选择店铺和商圈',0,$links);exit;
	}
	$supplier_circle = $GLOBALS['db']->getOne('SELECT circle_id FROM '.$GLOBALS['ecs']->table('supplier_circle_list').' WHERE supplier_id='.$supplier_id);
	if($supplier_circle){
        sys_msg('该店铺已经属于其他商圈，不能重复添加',0,$links);exit;
	}
	$a = array('supplier_id'=>$supplier_id,'circle_id'=>$circle_id,'addtime'=>gmtime());
	if($GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('supplier_circle_list'), array('supplier_id'=>$supplier_id,'circle_id'=>$circle_id,'addtime'=>gmtime()), 'INSERT')){
		sys_msg('操作成功',0,$links);
	}else{
		sys_msg('操作失败',0,$links);
	}
}

// 更换复制店铺时的店铺类型的店铺
elseif($_REQUEST['act'] == 'get_copy_shop_type'){

   $suppliers = get_supplier_list(intval($_REQUEST['is_ipc_shopping']));
   make_json_result($suppliers); 
}


function get_circle_list(){
	$sql = "SELECT * FROM ".$GLOBALS['ecs']->table('supplier_circle')." WHERE 1";
	return $GLOBALS['db']->getAll($sql);
}

function get_supplier_circle_list(){
	$result = get_filter();
    if ($result === false)
    {
        $aiax = isset($_GET['is_ajax']) ? $_GET['is_ajax'] : 0;
        /* 过滤信息 */
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'ASC' : trim($_REQUEST['sort_order']);

        $where = ' WHERE circle_id= '.intval($_REQUEST['circle_id']);

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
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('supplier_circle_list') . $where;
        $filter['record_count']   = $GLOBALS['db']->getOne($sql);
        $filter['page_count']     = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        /* 查询 */
        $sql = "SELECT *
                FROM " . $GLOBALS['ecs']->table("supplier_circle_list") . "
                $where
                ORDER BY " . $filter['sort_by'] . " " . $filter['sort_order']. "
                LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ", " . $filter['page_size'] . " ";
		
        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }
	
    $row = $GLOBALS['db']->getAll($sql);
	foreach($row as $k=>$v){
		$row[$k]['supplier_info'] = $GLOBALS['db']->getRow("SELECT a.supplier_id,a.supplier_name,a.is_ipc_shopping,b.region_name as province,c.region_name as city,d.region_name as district,a.address FROM ".$GLOBALS['ecs']->table('supplier')." a LEFT JOIN ".$GLOBALS['ecs']->table('region')." b ON a.province=b.region_id LEFT JOIN ".$GLOBALS['ecs']->table('region')." c ON a.city=c.region_id LEFT JOIN ".$GLOBALS['ecs']->table('region')." d ON a.district=d.region_id WHERE supplier_id=".$v['supplier_id']);
	}
    $arr = array('result' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
    return $arr;
}

	

function get_supplier_circle(){
	$result = get_filter();
    if ($result === false)
    {
        $aiax = isset($_GET['is_ajax']) ? $_GET['is_ajax'] : 0;
        /* 过滤信息 */
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'ASC' : trim($_REQUEST['sort_order']);

        $where = 'WHERE 1 ';

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
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('supplier_circle') . $where;
        $filter['record_count']   = $GLOBALS['db']->getOne($sql);
        $filter['page_count']     = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        /* 查询 */
        $sql = "SELECT *
                FROM " . $GLOBALS['ecs']->table("supplier_circle") . "
                $where
                ORDER BY " . $filter['sort_by'] . " " . $filter['sort_order']. "
                LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ", " . $filter['page_size'] . " ";
		
        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }
	
    $row = $GLOBALS['db']->getAll($sql);

    $arr = array('result' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
    return $arr;
}

/*审核通过的店铺加入商圈*/
function addOrUpdateSupplierCircle($supplier_id){
	$supplier_info =$GLOBALS['db']->getRow('SELECT a.user_id,a.supplier_id,b.parent_id,IFNULL(c.supplier_id,0) as psupplier_id,c.supplier_name,IFNULL(d.circle_id,0) as circle_id,IFNULL(e.circle_id,0) as new_circle_id FROM '.$GLOBALS['ecs']->table('supplier').' a LEFT JOIN '.$GLOBALS['ecs']->table('users').' b ON a.user_id=b.user_id  LEFT JOIN '.$GLOBALS['ecs']->table('supplier').' c ON c.user_id=b.parent_id LEFT JOIN '.$GLOBALS['ecs']->table('supplier_circle_list').' d ON c.supplier_id=d.supplier_id LEFT JOIN '.$GLOBALS['ecs']->table('supplier_circle_list').' e ON a.supplier_id=e.supplier_id WHERE a.supplier_id='.$supplier_id.' AND c.status=1');
	//查询上级店铺是否属于商圈。不是就新增商圈
	if(empty($supplier_info['psupplier_id'])){
		return;
	}elseif(empty($supplier_info['circle_id'])){
		//新增商圈,并且新老店铺加入该商圈
		$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('supplier_circle'), array('name'=>$supplier_info['supplier_name']), 'INSERT');
		$circle_id = $GLOBALS['db']->insert_id();
		//老店铺加入商圈
		$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('supplier_circle_list'), array('supplier_id'=>$supplier_info['psupplier_id'],'circle_id'=>$circle_id,'addtime'=>gmtime()), 'INSERT');
	}else{
		$circle_id = $supplier_info['circle_id'];
	}
	if(empty($supplier_info['new_circle_id'])){
		//新店铺加入商圈
		$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('supplier_circle_list'), array('supplier_id'=>$supplier_id,'circle_id'=>$circle_id,'addtime'=>gmtime()), 'INSERT');
	}
}


/**
 *  获取供应商列表信息
 *
 * @access  public
 * @param
 *
 * @return void
 */
function suppliers_list()
{
    $result = get_filter();

    if ($result === false)
    {
        $aiax = isset($_GET['is_ajax']) ? $_GET['is_ajax'] : 0;

        /* 过滤信息 */
        $filter['supplier_name'] = empty($_REQUEST['supplier_name']) ? '' : trim($_REQUEST['supplier_name']);
        $filter['rank_name'] = empty($_REQUEST['rank_name']) ? '' : trim($_REQUEST['rank_name']);
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'supplier_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'ASC' : trim($_REQUEST['sort_order']);
        $filter['status'] = empty($_REQUEST['status']) ? '0' : intval($_REQUEST['status']);

        //$filter['is_ipc_shopping'] = ($_REQUEST['is_ipc_shopping'] > -1) ? $_REQUEST['is_ipc_shopping']:'0';
        

        $where = 'WHERE applynum = 3 ';
        $where .= $filter['status'] ? " AND s.status = '". $filter['status']. "' " : " AND s.status in('0','-1') ";
        if ($filter['supplier_name'])
        {
            $where .= " AND supplier_name LIKE '%" . mysql_like_quote($filter['supplier_name']) . "%'";
        }
        if ($filter['rank_name'])
        {
            $where .= " AND rank_id = '$filter[rank_name]'";
        }

        if(isset($_REQUEST['is_ipc_shopping']) && $_REQUEST['is_ipc_shopping'] >=0){

            $filter['is_ipc_shopping'] = $_REQUEST['is_ipc_shopping'];

            $where .=' AND is_ipc_shopping='.$_REQUEST['is_ipc_shopping'];
        }

        // 用户名的添加条件
        if(trim($_REQUEST['keyword'])){

            $where .= ' and u.user_name like "%'.$_REQUEST['keyword'].'%" or u.email like "%'.$_REQUEST['keyword'].'%" or u.mobile_phone like "%'.$_REQUEST['keyword'].'%"';
        }
       
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
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('supplier') ." as s left join ". $GLOBALS['ecs']->table("users") . " as u on s.user_id = u.user_id ". $where;
        $filter['record_count']   = $GLOBALS['db']->getOne($sql);
        $filter['page_count']     = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        /* 查询 */
        $sql = "SELECT s.supplier_id,u.user_name, rank_id, supplier_name, tel, system_fee,old_user_rate,is_ipc_shopping, supplier_bond, supplier_rebate, supplier_remark,is_to_erp,  ".
            "s.status,s.cut,s.extpintuan,s.lucky_buy ".
            "FROM " . $GLOBALS['ecs']->table("supplier") . " as s left join " . $GLOBALS['ecs']->table("users") . " as u on s.user_id = u.user_id
                $where
                ORDER BY " . $filter['sort_by'] . " " . $filter['sort_order']. "
                LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ", " . $filter['page_size'] . " ";

        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }

    $rankname_list =array();
    $sql2 = "select * from ". $GLOBALS['ecs']->table("supplier_rank") ;
    $res2 = $GLOBALS['db']->query($sql2);
    while ($row2=$GLOBALS['db']->fetchRow($res2))
    {
        $rankname_list[$row2['rank_id']] = $row2['rank_name'];
    }

    $list=array();
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $row['rank_name'] = $rankname_list[$row['rank_id']];
        $row['status_name'] = $row['status']=='1' ? '通过' : ($row['status']=='0' ? "未审核" : "未通过");
        $open = $GLOBALS['db']->getRow("select value from ".$GLOBALS['ecs']->table("supplier_shop_config")." where supplier_id=".$row['supplier_id']." and code='shop_closed'");
        if($open && $open['value'] == 0){
            $row['open'] = 1;
        }else{
            $row['open'] = 0;
        }
        $list[]=$row;
    }

    $arr = array('result' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}
/*
* 入驻商的佣金记录
*/
function rebate_log_list()
{
    $result = get_filter();
    if ($result === false)
    {
        /* 过滤信息 */
        $filter['id'] = intval($_REQUEST['id']);
        $filter['addtime_start'] = !empty($_REQUEST['addtime_start']) ? local_strtotime($_REQUEST['addtime_start']) : 0;
        $filter['addtime_end'] = !empty($_REQUEST['addtime_end']) ? local_strtotime($_REQUEST['addtime_end']." 23:59:59") : 0;
        $filter['status'] = empty($_REQUEST['status']) ? '0' : intval($_REQUEST['status']);

        $where = ' WHERE supplier_id = '.$filter['id'];
        $where .= $filter['addtime_start'] ? " AND addtime >= '". $filter['addtime_start']. "' " :  " ";
        $where .= $filter['addtime_end'] ? " AND addtime_end <= '". $filter['addtime_end']. "' " :  " ";
        $where .= $filter['status']>0 ? " AND status = '". $filter['status']. "' " : "";

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
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('supplier_money_log') . $where;
        $filter['record_count']   = $GLOBALS['db']->getOne($sql);
        $filter['page_count']     = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        /* 查询 */
        $sql = "SELECT * ".
            "FROM " . $GLOBALS['ecs']->table("supplier_money_log") . $where ."
                ORDER BY addtime desc 
                LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ", " . $filter['page_size'] . " ";

        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }

    $list=array();
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $row['add_time'] = local_date("Y-m-d H:i:s",$row['addtime']);
        $list[]=$row;
    }

    $arr = array('result' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}

/*
删除店铺下所有商品的上传的图片
*/
function delete_supplier_pic($suppid){
    global $db,$ecs;

    $sql = "select goods_thumb,goods_img,original_img from ".$ecs->table('goods')." where supplier_id=".$suppid;

    $query = $db->query($sql);
    while($row = $db->fetchRow($query)){
        @unlink(ROOT_PATH.$row['goods_thumb']);
        @unlink(ROOT_PATH.$row['goods_img']);
        @unlink(ROOT_PATH.$row['original_img']);
    }

    $sql = "select gg.img_url,gg.thumb_url,gg.img_original from ".$ecs->table('goods_gallery')." as gg,".$ecs->table('goods')." as g where g.supplier_id=".$suppid." and g.goods_id=gg.goods_id";

    $query = $db->query($sql);
    while($row = $db->fetchRow($query)){
        @unlink(ROOT_PATH.$row['img_url']);
        @unlink(ROOT_PATH.$row['thumb_url']);
        @unlink(ROOT_PATH.$row['img_original']);
    }
}

/* 代码增加 By  www.68ecshop.com Start */
/**
 * 取得店铺等级列表
 * @return array 店铺等级列表 id => name
 */
function get_supplier_rank_list()
{
    $sql = 'SELECT rank_id, rank_name FROM ' . $GLOBALS['ecs']->table('supplier_rank') . ' ORDER BY sort_order';
    $res = $GLOBALS['db']->getAll($sql);

    $rank_list = array();
    foreach ($res AS $row)
    {
        $rank_list[$row['rank_id']] = addslashes($row['rank_name']);
    }

    return $rank_list;
}
/* 代码增加 By  www.68ecshop.com End */



/*
 * 功能：同步店铺信息至erp中
 * 参数： db, $data
 * 返回：
 */
function transport_dian_pu_to_erp($supplier_id){

    $infos =  $GLOBALS['db']->getRow('select * from ecs_supplier where supplier_id="'.$supplier_id.'"');

    $rel =  $GLOBALS['db']->getAll('select region_name from ecs_region where region_id in ("'.$infos['country'].'","'.$infos['city'].'","'.$infos['district'].'")');
        

        // 处理number字段的问题
        $lens = strlen($supplier_id);

        switch ($lens) {
            case 1:
                $number = '000'.$supplier_id;
                break;
            case 2:
                $number = '00'.$supplier_id;
                break;
            case 3:
                $number = '0'.$supplier_id;
                break;
            case 4:
                $number = $supplier_id;
                break;
            
            default:
                $number = rand(1000,9999);
                break;
        }
  

        // 封装数据
        $data = [
            'name'=>trim($infos['supplier_name']),// 门店名称
            'location_id'=>$infos['address'],// 仓库地址
            'country_id'=>$rel[0]['region_name'],//国家
            'city'=>$rel[1]['region_name'],//城市
            'street'=>$rel[2]['region_name'],// 街道
            'mobile'=>$infos['tel'],//手机
            'external_id'=>$supplier_id,//商城门店id
            'description'=>$infos['supplier_remark'],//描述
            'number' =>$number,
        ];
     
        $erp = new OdooErp($infos['databases_name']);
        $rels = $erp->transmit_data_to_shang_pu($data);

        return $rels;
}




function get_affiliate_supplier()
{

    $sql = "select value from " . $GLOBALS['ecs']->table('ecsmart_shop_config') ."  WHERE code = 'affiliate_supplier'";
    $config = $GLOBALS['db']->getOne($sql);
    $config = unserialize($config);
    empty($config) && $config = array();
    return $config;
}



/*
 * 功能：复制店铺
 * 参数： copy_supplier_id(复制的店铺模板),supplier_id
 * 返回：true
 
 */

function copy_shops($copy_supplier_id,$supplier_id){
	if(intval($copy_supplier_id)<=0){
		return false;
	}
	//过滤掉店铺的卡项商品
    $goods =  $GLOBALS['db']->getAll('select * from ecs_goods where supplier_id="'.$copy_supplier_id.'" AND item_id=0');
	
    foreach ($goods as $val) {

        unset($val['goods_id']);
        $val['supplier_id'] = $supplier_id;

        $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('goods'), $val, 'INSERT'); 
    }

    // 更改货号 和  添加 ecs_goods_attr 表的属性
    $new_goods =  $GLOBALS['db']->getAll('select goods_id,goods_sn,cat_id from ecs_goods where supplier_id="'.$supplier_id.'"');

    $arr_connect = [];

    // 复制商品的分销比例 //过滤掉店铺的卡项商品
    $distrib_goods = $GLOBALS['db']->getAll('select ecs_ecsmart_distrib_goods.* from ecs_ecsmart_distrib_goods left join ecs_goods on  ecs_goods.goods_id=ecs_ecsmart_distrib_goods.goods_id where ecs_goods.item_id=0 AND ecs_goods.supplier_id="'.$copy_supplier_id.'"');

    
    $good_attr_connect = [];
    foreach ($new_goods as $val) {
 

        $good_sn = generate_goods_sn($val['goods_id']);

        $old_goodid = $GLOBALS['db']->getOne('select goods_id from ecs_goods where supplier_id="'.$copy_supplier_id.'" and goods_sn="'.$val['goods_sn'].'"');

        $arr_connect[$old_goodid]= $val['goods_id']; //  复制的货品和原货品对应

        // 替换商品的图片信息 ecs_goods_gallery 表
        $galler_old = $GLOBALS['db']->getRow('select * from ecs_goods_gallery where goods_id="'.$old_goodid.'"');

        unset($galler_old['img_id']);
        $galler_old['goods_id'] = $val['goods_id'];

        $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('goods_gallery'), $galler_old, 'INSERT');

        $all_attr =  $GLOBALS['db']->getAll('select * from ecs_goods_attr where goods_id="'.$old_goodid.'"');
        
        // 复制商品分销比例
        foreach ($distrib_goods as $dis_good) {
            
            if($dis_good['goods_id'] == $old_goodid){

                unset($dis_good['id']); // 删除自增id
                $dis_good['goods_id'] = $val['goods_id']; // 更换商品id

                $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('ecsmart_distrib_goods'), $dis_good, 'INSERT');      

            }  
        }

        
        foreach ($all_attr as $values) {
                
            $GLOBALS['db']->query('insert into ecs_goods_attr (goods_id,attr_id,attr_value,attr_price) values("'.$val['goods_id'].'","'.$values['attr_id'].'","'.$values['attr_value'].'","'.$values['attr_price'].'")');

            // 获得刚插入数据的 goods_attr_id
            $new_good_attr_id = $GLOBALS['db']->getOne('select goods_attr_id from ecs_goods_attr where goods_id="'.$val['goods_id'].'" and attr_id="'.$values['attr_id'].'" and attr_value="'.$values['attr_value'].'" and attr_price="'.$values['attr_price'].'"');

            $good_attr_connect[$values['goods_attr_id']] = $new_good_attr_id; //新旧的attr_id的对应
        }

        $GLOBALS['db']->query('update ecs_goods set goods_sn="'.$good_sn.'" where goods_id ='.$val['goods_id']);   
         //$GLOBALS['db']->query('insert into ecs_supplier_goods_cat (goods_id,cat_id,supplier_id) values("'.$val['goods_id'].'","'.$val['cat_id'].'","'.$supplier_id.'")');
    }


     
         
        // 复制默认店铺的广告图片 ROOT_PATH . DATA_DIR . '/flash_data_supplier'.$supplier_id.'.xml'
        if(file_exists(ROOT_PATH . DATA_DIR . '/flash_data_supplier'.$copy_supplier_id.'.xml')){

            copy(ROOT_PATH . DATA_DIR . '/flash_data_supplier'.$copy_supplier_id.'.xml',ROOT_PATH . DATA_DIR . '/flash_data_supplier'.$supplier_id.'.xml');
        }

        //  ecs_products 表
        foreach ($arr_connect as $keys => $value) {
           
            $rel =  $GLOBALS['db']->getAll('select * from ecs_products where goods_id="'.$keys.'"');

            foreach ($rel as  $val) {

                unset($val['product_id']);
                $val['goods_id'] = $value;
                $val['goods_attr'] = $good_attr_connect[$val['goods_attr']];

                $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('products'), $val, 'INSERT'); 
                
            }
        }
       
        // 处理 ecs_product 表的 product_sn
        foreach ($arr_connect as $value) {

            $rel =  $GLOBALS['db']->getAll('select * from ecs_products where goods_id="'.$value.'"');

            foreach ($rel as $key => $val) {
                

                $product_sn =  generate_goods_sn($val['goods_id']).'g_p'.$val['product_id'];

                 $GLOBALS['db']->query('update ecs_products set product_sn="'.$product_sn.'" where product_id='.$val['product_id']);   
            }
           
        }

        // 添加 至 ecs_supplier_category 表中 即复制商品分类
        $all_categorys =  $GLOBALS['db']->getAll('select * from ecs_supplier_category where supplier_id="'.$copy_supplier_id.'"');
         
        $cat_connect = [];
        foreach ($all_categorys as $vals) {
            $aa = $vals['cat_id'];
            unset($vals['cat_id']);
            $vals['supplier_id'] = $supplier_id;
            $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('supplier_category'), $vals, 'INSERT');

            $cat_connect[$aa] = $GLOBALS['db']->getOne('select cat_id from ecs_supplier_category where supplier_id="'.$supplier_id.'" and cat_name="'.$vals['cat_name'].'"'); // 新旧的cat_id关系 对应
        }
        

        // 添加 关系至 ecs_supplier_goods_cat 表中
        $good_cat = $GLOBALS['db']->getAll('select * from ecs_supplier_goods_cat where supplier_id="'.$copy_supplier_id.'"');
        foreach ($good_cat as $value) {
            
            // 防止删除商品 表中存在废弃的数据
            if( isset($arr_connect[$value['goods_id']]) &&  $arr_connect[$value['goods_id']] > 0){

                $value['goods_id'] = $arr_connect[$value['goods_id']];
                $value['cat_id'] =  $cat_connect[$value['cat_id']];
                $value['supplier_id'] = $supplier_id;

                $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('supplier_goods_cat'), $value, 'INSERT'); 
            }      
        }


        // 添加 ecs_supplier_cat_recommend 表
        $recommend = $GLOBALS['db']->getAll('select * from ecs_supplier_cat_recommend where supplier_id="'.$copy_supplier_id.'"');
        foreach ($recommend as $value) {

            $value['cat_id'] = $cat_connect[$value['cat_id']]; // 替换cat_id
            $value['supplier_id'] = $supplier_id; // 替换supplier_id

            $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('supplier_cat_recommend'), $value, 'INSERT');
            
        }

        // 复制模板的模板
        $rel = $GLOBALS['db']->getOne('select count(id) from ecs_supplier_shop_config where supplier_id="'.$supplier_id.'"');
        if($rel > 0){

            $old_shop_config = $GLOBALS['db']->getOne('select value from ecs_supplier_shop_config where supplier_id="'.$copy_supplier_id.'" and code="template"');

            $GLOBALS['db']->query('update ecs_supplier_shop_config set value="'.$old_shop_config.'" where supplier_id="'.$supplier_id.'" and code="template"');
        }else{

            $templates = $GLOBALS['db']->getOne('select value from ecs_supplier_shop_config where supplier_id="'.$copy_supplier_id.'" and code="template"');

            $supplier_name = $GLOBALS['db']->getOne('select supplier_name from ecs_supplier where supplier_id='.$supplier_id);

            // 插入新的店铺信息
            set_shop_config($supplier_name,$templates,$supplier_id);  

        }
       

        // 复制店铺的配送方式
        $shipping =  $GLOBALS['db']->getAll('select * from ecs_shipping where supplier_id="'.$copy_supplier_id.'"');
        
        foreach ($shipping as $vals) {  

            // 获取对应的配送范围
            $area = $GLOBALS['db']->getRow('select * from ecs_shipping_area where shipping_id="'.$vals['shipping_id'].'"');

            $area_regin = $GLOBALS['db']->getRow('select * from ecs_area_region where shipping_area_id="'.$area['shipping_area_id'].'"');

            unset($vals['shipping_id']);
            $vals['supplier_id'] = $supplier_id;
            $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('shipping'), $vals, 'INSERT'); 

            // 刚插入数据的shipping_id
            $area['shipping_id'] = $GLOBALS['db']->insert_id();
            unset($area['shipping_area_id']);
            $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('shipping_area'), $area, 'INSERT');


            //ecs_area_region 表关系
            $area_regin['shipping_area_id'] = $GLOBALS['db']->insert_id();
          
            $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('area_region'), $area_regin, 'INSERT');


        }

        $GLOBALS['db']->query('update ecs_supplier set is_have_copy_shop=1 where supplier_id='.$supplier_id);

		

        return true;

}



/**
 * 取得入驻商列表
 * @return array    二维数组
 */
function get_supplier_list($con='')
{
    if($con || $con === 0){
		$where = ' and is_ipc_shopping='.$con;
	}
    $sql = 'SELECT supplier_id,supplier_name,is_ipc_shopping  
            FROM ' . $GLOBALS['ecs']->table('supplier') . '
            WHERE status=1 '.$where.'  
            ORDER BY supplier_name ASC';
    $res = $GLOBALS['db']->getAll($sql);

    if (!is_array($res))
    {
        $res = array();
    }

    return $res;
}


/**
 * 为某商品生成唯一的货号
 * @param   int     $goods_id   商品编号
 * @return  string  唯一的货号
 */
function generate_goods_sn($goods_id)
{
    $goods_sn = $GLOBALS['_CFG']['sn_prefix'] . str_repeat('0', 6 - strlen($goods_id)) . $goods_id;

    $sql = "SELECT goods_sn FROM " . $GLOBALS['ecs']->table('goods') .
            " WHERE goods_sn LIKE '" . mysql_like_quote($goods_sn) . "%' AND goods_id <> '$goods_id' " .
            " ORDER BY LENGTH(goods_sn) DESC";
    $sn_list = $GLOBALS['db']->getCol($sql);
    if (in_array($goods_sn, $sn_list))
    {
        $max = pow(10, strlen($sn_list[0]) - strlen($goods_sn) + 1) - 1;
        $new_sn = $goods_sn . mt_rand(0, $max);
        while (in_array($new_sn, $sn_list))
        {
            $new_sn = $goods_sn . mt_rand(0, $max);
        }
        $goods_sn = $new_sn;
    }

    return $goods_sn;
}





// 添加店铺的初始基本设置

function set_shop_config($supplier_name,$template,$supplier_id){

    global $db, $ecs;

    $supplier = $db->getRow('select * from '.$ecs->table('supplier').' where supplier_id="'.$supplier_id.'"');

    if(empty($supplier)){
        return ;
    }

    $insql = "INSERT INTO ".$ecs->table('supplier_shop_config')." (`id`,`parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `supplier_id`) VALUES
                (1, 0, 'shop_info', 'group', '', '', '', 1, ".$supplier_id."),
                (2, 0, 'hidden', 'hidden', '', '', '', 1, ".$supplier_id."),
                (8, 0, 'sms', 'group', '', '', '', 1, ".$supplier_id."),
                (101, 1, 'shop_name', 'text', '', '', '".$supplier['supplier_name']."', 1, ".$supplier_id."),
                (102, 1, 'shop_title', 'text', '', '', '商家店铺标题', 1, ".$supplier_id."),
                (103, 1, 'shop_desc', 'text', '', '', '".$supplier['description']."', 1, ".$supplier_id."),
                (104, 1, 'shop_keywords', 'text', '', '', '商家店铺关键字', 1, ".$supplier_id."),
                (105, 1, 'shop_country', 'manual', '', '', '".$supplier['country']."', 1, ".$supplier_id."),
                (106, 1, 'shop_province', 'manual', '', '', '".$supplier['province']."', 2, ".$supplier_id."),
                (107, 1, 'shop_city', 'manual', '', '', '".$supplier['city']."', 52, ".$supplier_id."),
                (108, 1, 'shop_address', 'text', '', '', '".$supplier['address']."', 1, ".$supplier_id."),
                (109, 1, 'qq', 'text', '', '', '', 1, ".$supplier_id."),
                (110, 1, 'ww', 'text', '', '', '', 1, ".$supplier_id."),
                (111, 1, 'wx', 'file', '', '', '', 1, ".$supplier_id."),
                (112, 1, 'skype', 'hidden', '', '', '', 1, ".$supplier_id."),
                (113, 1, 'ym', 'hidden', '', '', '', 1, ".$supplier_id."),
                (114, 1, 'msn', 'hidden', '', '', '', 1, ".$supplier_id."),
                (115, 1, 'service_email', 'text', '', '', '', 1, ".$supplier_id."),
                (116, 1, 'service_phone', 'text', '', '', '', 1, ".$supplier_id."),
                (117, 1, 'shop_closed', 'select', '0,1', '', '0', 1, ".$supplier_id."),
                (118, 1, 'close_comment', 'hidden', '', '', '', 1, ".$supplier_id."),
                (119, 1, 'shop_logo', 'file', '', 'data/supplier', '".$supplier['logo']."', 1, ".$supplier_id."),
                (120, 1, 'licensed', 'hidden', '0,1', '', '1', 1, ".$supplier_id."),
                (121, 1, 'user_notice', 'hidden', '', '', '用户中心公告！', 1, ".$supplier_id."),
                (122, 1, 'shop_notice', 'textarea', '', '', '商家店铺介绍:欢迎光临手机网,我们的宗旨：诚信经营、服务客户！\r\n<MARQUEE onmouseover=this.stop() onmouseout=this.start() \r\nscrollAmount=3><U><FONT color=red>\r\n<P>咨询电话010-10124444  010-21252454 8465544</P></FONT></U></MARQUEE>', 1, ".$supplier_id."),
                (123, 1, 'shop_reg_closed', 'hidden', '1,0', '', '0', 1, ".$supplier_id."),
                (124, 1, 'shop_index_num', 'textarea', '', '', '8\r\n6\r\n4', 1, ".$supplier_id."),
                (125, 1, 'shop_search_price', 'textarea', '', '', '0-1000元\r\n1000-2000元\r\n2000-4000元', 1, ".$supplier_id."),
                (201, 1, 'close_comment', 'textarea', '', '', '该店铺正在装修', 1, ".$supplier_id."),
                (202, 2, 'shop_header_color', 'hidden', '', '', '#E4368F', 1, ".$supplier_id."),
                (203, 2, 'shop_header_text', 'hidden', '', '', '请上传logo和banner', 1, ".$supplier_id."),
                (204, 2, 'template', 'hidden', '', '', '".$template."', 1, ".$supplier_id."),
                (205, 2, 'stylename', 'hidden', '', '', '', 1, ".$supplier_id."),
                (206, 2, 'flash_theme', 'hidden', '', '', '".$supplier_name.$supplier_id."', 1, ".$supplier_id."),
                (801, 8, 'sms_shop_mobile', 'text', '', '', '', 1, ".$supplier_id."),
                (802, 8, 'sms_order_placed', 'select', '1,0', '', '0', 0, ".$supplier_id."),
                (803, 8, 'sms_order_payed', 'select', '1,0', '', '0', 1, ".$supplier_id."),
                (804, 8, 'sms_order_shipped', 'select', '1,0', '', '0', 1, ".$supplier_id.");";
    $GLOBALS['db']->query($insql);
}


/**
 * 功能： 处理数据库中的图片路径，更换为唯一的新的路径
 * 说明： 在原来的路径中加入店铺的supplier_id，并把图片复制到相应的路径
 * 参数： old_path,supplier_id
 * 返回： new_path(string)
 */

function copy_old_path_to_new_index($old_path,$supplier_id){
    // images/201907/thumb_img/905_thumb_G_1562576775130.jpg
    if(file_exists('../'.$old_path) ) {

        $len = strlen($old_path);
        $index = strrpos($old_path,'.');
        $prev_path = substr($old_path,0,$index); // images/201907/thumb_img/905_thumb_G_1562576775130
        $extension = substr($old_path,$index,$len-$index); // 后缀名 .jpg

        $new_img_path  = $prev_path.'_'.$supplier_id.$extension; // 新路径
        if(!file_exists($new_img_path)){

            copy('../'.$old_path,'../'.$new_img_path);
        }

        return  $new_img_path;
    } 

    return $old_path;
}