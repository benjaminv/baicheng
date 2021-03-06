<?php
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
include_once(ROOT_PATH. "includes/lib_comment.php");

if ((DEBUG_MODE & 2) != 2){
    $smarty->caching = true;
}

$affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
$smarty->assign('affiliate', $affiliate);

if(!empty($_REQUEST['act']) && $_REQUEST['act'] == 'get_pickup_list')

{

	$select_info = array();

	$country = $_REQUEST['country'];

	$province = $_REQUEST['province'];

	$city = $_REQUEST['city'];

	$sql_cn = 'select region_id from ' . $ecs->table('region') . " where region_name='$country'";

	$country_id = $db->getOne($sql_cn);

	$sql_p = 'select region_id from ' . $ecs->table('region') . " where region_name='$province'";

	$province_id = $db->getOne($sql_p);

	$sql_c = 'select region_id from ' . $ecs->table('region') . " where region_name='$city'";

	$city_id = $db->getOne($sql_c);



	$select_info['pro_select']  = region_select(2, $country_id, $province_id);

	$select_info['city_select'] = region_select(3, $province_id, $city_id);

	$select_info['dist_select'] = region_select(4, $city_id);



	die(json_encode(array('error' => 0, 'info' => $select_info)));

}



if (!empty($_REQUEST['act']) && $_REQUEST['act'] == 'pickup_info')

{

	$district_id = $_REQUEST['district_id'];

	$sql_pinfo = "select * from " . $ecs->table('pickup_point') . " where district_id = '" . $district_id . "'";

	$p_info = $db->getAll($sql_pinfo);

	if (count($p_info) == 0)

	{

		$pickup_list_info = "<div class='pick_col'>该地区尚未开放自提点!</div>";

	}

	else

	{

		$pickup_list_info = "<div><span>自提点信息：</span><br /><ul>";

		foreach ($p_info as $p_infos)

		{

			$pickup_list_info .= "<li>" . $p_infos['shop_name'] . "<br />地址：" . $p_infos['address'] .

								"<br />联系人：" . $p_infos['contact'] . "&nbsp;&nbsp;&nbsp;&nbsp;联系方式：" . $p_infos['phone'] . "</li>";

		}

		$pickup_list_info .= "</ul></div>";

	}

	die(json_encode(array('error' => 0, 'pinfos' => $pickup_list_info)));

}



if (!empty($_REQUEST['act']) && $_REQUEST['act'] == 'get_region_select')

{

	$t = $_REQUEST['t'];

	$parent_id = $_REQUEST['parent_id'];



	switch ($t)

	{

		case 2 : $ts = 'p_list'; break;

		case 3 : $ts = 'c_list'; break;

		case 4 : $ts = 'd_list'; break;

	}



	$selcet_info = region_select($t, $parent_id);

	die(json_encode(array('error' => 0, 't' => $ts, 'selcet_info' => $selcet_info)));

}



if(!empty($_REQUEST['act']) && $_REQUEST['act'] == 'get_pickup_info')

{

	$province = $_REQUEST['province'];

	$city = $_REQUEST['city'];

	$district = $_REQUEST['district'];

	$suppid = intval($_REQUEST['suppid']);



	$city_info = get_city_info($province, $city, $district);



	$where = 'where supplier_id='.$suppid;

	if($city_info['province_id']>0 && $city_info['city_id']>0)

	{

		$where .= ' and province_id=' . $city_info['province_id'] . ' and city_id=' . $city_info['city_id'];



		$sql = 'select * from ' . $GLOBALS['ecs']->table('pickup_point') . $where;



		$pickup_point_list = $GLOBALS['db']->getAll($sql);



		echo json_encode(array('error' => 0, 'result' => $pickup_point_list, 'city_info' => $city_info));

	}

	else{

		echo json_encode(array('error' => 1));

        }

        exit;

}

elseif (!empty($_REQUEST['act']) && $_REQUEST['act'] == 'get_area_list')

{

	$parent_id = $_REQUEST['parent_id'];

	$sql = 'select region_id, region_name, region_type from ' . $GLOBALS['ecs']->table('region'). ' where parent_id=' . $parent_id;

	$area_list = $GLOBALS['db']->getAll($sql);

	die(json_encode($area_list));

}

elseif (!empty($_REQUEST['act']) && $_REQUEST['act'] == 'get_pickup_point_list')

{

	$district_id = $_REQUEST['district_id'];

	//$sql = 'select * from ' . $GLOBALS['ecs']->table('pickup_point') . ' where district_id=' . $district_id;

	$suppid = intval($_REQUEST['suppid']);

	$sql = 'select * from ' . $GLOBALS['ecs']->table('pickup_point') . ' where district_id=' . $district_id.' and supplier_id='.$suppid;

	$pickup_point_list = $GLOBALS['db']->getAll($sql);

	die(json_encode($pickup_point_list));

}

/*------------------------------------------------------ */

//-- INPUT

/*------------------------------------------------------ */



$goods_id = isset($_REQUEST['id'])  ? intval($_REQUEST['id']) : 0;





/*------------------------------------------------------ */

//-- 改变属性、数量时重新计算商品价格

/*------------------------------------------------------ */

$user_rank = $GLOBALS['db']->getOne("SELECT user_rank FROM ".$GLOBALS['ecs']->table('users')." WHERE user_id='".$_SESSION['user_id']."'");

if($user_rank>0){

    $user_rank_wh=' (find_in_set('.$user_rank.',g.level) or  g.level=0) and ';

}else{

    $user_rank_wh=' g.level=0 and ';

}

if (!empty($_REQUEST['act']) && $_REQUEST['act'] == 'price')

{

    include('includes/cls_json.php');



    $json   = new JSON;

    $res    = array('err_msg' => '', 'result' => '', 'qty' => 1);



    $attr_id    = isset($_REQUEST['attr'])&&!empty($_REQUEST['attr']) ? explode(',', $_REQUEST['attr']) : array();

    $number     = (isset($_REQUEST['number'])) ? intval($_REQUEST['number']) : 1;



    if ($goods_id == 0)

    {

        $res['err_msg'] = $_LANG['err_change_attr'];

        $res['err_no']  = 1;

    }

    else

    {

        if ($number == 0)

        {

            $res['qty'] = $number = 1;

        }

        else

        {

            $res['qty'] = $number;

        }

        $exclusive = $GLOBALS['db']->getOne("select exclusive from ".$GLOBALS['ecs']->table('goods')." where  goods_id = $goods_id");

        $shop_price  = get_final_price($goods_id, $number, true, $attr_id);

        $res['is_exclusive']  = is_exclusive($exclusive,$shop_price);

        $res['result'] = price_format($shop_price * $number);

        $res['result_jf'] = floor($shop_price * $number);

        $res['goods_attr_number'] = get_product_attr_num($goods_id,$_REQUEST['attr']);

        $res['goods_attr_thumb'] = get_goods_attr_thumb($goods_id,$attr_id);

        $res['goods_attr'] = get_goods_attr_str($attr_id);

    }



    die($json->encode($res));

}





/**

 * 获取相关属性的库存

 * @param int $goodid 商品id

 * @param string(array) $attrids 商品属性id的数组或者逗号分开的字符串

 */

function get_product_attr_num($goodid,$attrids=0){

	$ret = array();



	/* 判断商品是否参与预售活动，如果参与则获取商品的（预售库存-已售出的数量） */

	if(!empty($_REQUEST['pre_sale_id']))

	{

		$pre_sale = pre_sale_info($_REQUEST['pre_sale_id'], $goods_num);

		//如果预售为空或者预售库存小于等于0则认为不限购

		if(!empty($pre_sale) && $pre_sale['restrict_amount'] > 0){



			$product_num = $pre_sale['restrict_amount'] - $pre_sale['valid_goods'];



			return $product_num;

		}

	}



	if(empty($attrids)){

		$ginfo = get_goods_attr_value($goodid,'goods_number');

		return $ginfo['goods_number'];

		//$ret[$attrids] = $ginfo['goods_number'];

		//return $ret;

	}

	if(!is_array($attrids)){

		$attrids = explode(',',$attrids);

	}



	$goods_attr_array = sort_goods_attr_id_array($attrids);



    if(isset($goods_attr_array['sort']))

    {

        $goods_attr = implode('|', $goods_attr_array['sort']);



		$sql = "SELECT product_id, goods_id, goods_attr, product_sn, product_number

                FROM " . $GLOBALS['ecs']->table('products') . "

                WHERE goods_id = $goodid AND goods_attr = '".$goods_attr."' LIMIT 0, 1";

		$row = $GLOBALS['db']->getRow($sql);



		return empty($row['product_number'])?0:$row['product_number'];

    }

}



/**

 * 获取商品的相关信息

 * @param int $goodsid 商品id

 * @param string $name  要获取商品的属性名称,多个，就用逗号分隔

 */

function get_goods_attr_value($goodsid,$name='goods_sn,goods_name')

{

	$sql = "select ".$name." from ". $GLOBALS['ecs']->table('goods') ." where goods_id=".$goodsid;

	$row = $GLOBALS['db']->getRow($sql);

	return $row;

}



/**

 * 获取属性图片

 * @param type $goods_id

 * @param type $attr_id

 * @return type

 */

function get_goods_attr_thumb($goods_id,$attr_id=array()){

    $sql = "select goods_thumb from ".$GLOBALS['ecs']->table('goods')." where goods_id = $goods_id";

    $goods_attr_thumb = $GLOBALS['db']->getOne($sql);

    if(!empty($attr_id)){

        $attr_id_s = implode(',',$attr_id);

        $sql = "select goods_attr_id from ".$GLOBALS['ecs']->table('goods_attr')." as ga join ".$GLOBALS['ecs']->table('attribute')." as at on ga.attr_id = at.attr_id where ga.goods_id = $goods_id and at.is_attr_gallery = 1 and ga.goods_attr_id in ($attr_id_s)";

        $goods_attr_id = $GLOBALS['db']->getOne($sql);

        if($goods_attr_id){

            $sql = "select thumb_url from ".$GLOBALS['ecs']->table('goods_gallery')." where goods_attr_id = $goods_attr_id";

            $thumb_url = $GLOBALS['db']->getOne($sql);

            if($thumb_url){

                $goods_attr_thumb = $thumb_url;

            }

        }

    }

    return get_pc_url().'/'.get_image_path($goods_id,$goods_attr_thumb);

}



/**

 * 获取属性值

 * @param type $attr_id

 * @return type

 */

function get_goods_attr_str($attr_id){

    $goods_attr = "";

    $attr_price = "";



    if(!empty($attr_id)){

       $goods_attr_array = array();

    foreach($attr_id as $key=>$value){

        $sql = "select ga.attr_id, ga.attr_value, ga.attr_price, at.attr_name from ". $GLOBALS['ecs']->table('goods_attr') ." as ga left join ". $GLOBALS['ecs']->table('attribute') ." as at on ga.attr_id = at.attr_id  where ga.goods_attr_id = $value";

        $res = $GLOBALS['db']->getRow($sql);

        array_push($goods_attr_array,$res['attr_name']."：".$res['attr_value']);

        $attr_price = empty($res['attr_price'])?0:$res['attr_price']+$attr_price;

    }

        $goods_attr  =  implode('&nbsp;',$goods_attr_array);

        //$goods_attr = $goods_attr.'['.$attr_price.']';

    }

    return $goods_attr;

}



/*------------------------------------------------------ */

//-- 商品购买记录ajax处理

/*------------------------------------------------------ */



if (!empty($_REQUEST['act']) && $_REQUEST['act'] == 'gotopage')

{

    include('includes/cls_json.php');



    $json   = new JSON;

    $res    = array('err_msg' => '', 'result' => '');



    $goods_id   = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

    $page    = (isset($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;



    if (!empty($goods_id))

    {

        $need_cache = $GLOBALS['smarty']->caching;

        $need_compile = $GLOBALS['smarty']->force_compile;



        $GLOBALS['smarty']->caching = false;

        $GLOBALS['smarty']->force_compile = true;



        /* 商品购买记录 */

        $sql = 'SELECT u.user_name, og.goods_number, oi.add_time, IF(oi.order_status IN (2, 3, 4), 0, 1) AS order_status ' .

               'FROM ' . $ecs->table('order_info') . ' AS oi LEFT JOIN ' . $ecs->table('users') . ' AS u ON oi.user_id = u.user_id, ' . $ecs->table('order_goods') . ' AS og ' .

               'WHERE oi.order_id = og.order_id AND ' . time() . ' - oi.add_time < 2592000 AND og.goods_id = ' . $goods_id . ' ORDER BY oi.add_time DESC LIMIT ' . (($page > 1) ? ($page-1) : 0) * 5 . ',5';

        $bought_notes = $db->getAll($sql);



        foreach ($bought_notes as $key => $val)

        {

            $bought_notes[$key]['add_time'] = local_date("Y-m-d G:i:s", $val['add_time']);

        }



        $sql = 'SELECT count(*) ' .

               'FROM ' . $ecs->table('order_info') . ' AS oi LEFT JOIN ' . $ecs->table('users') . ' AS u ON oi.user_id = u.user_id, ' . $ecs->table('order_goods') . ' AS og ' .

               'WHERE oi.order_id = og.order_id AND ' . time() . ' - oi.add_time < 2592000 AND og.goods_id = ' . $goods_id;

        $count = $db->getOne($sql);





        /* 商品购买记录分页样式 */

        $pager = array();

        $pager['page']         = $page;

        $pager['size']         = $size = 5;

        $pager['record_count'] = $count;

        $pager['page_count']   = $page_count = ($count > 0) ? intval(ceil($count / $size)) : 1;;

        $pager['page_first']   = "javascript:gotoBuyPage(1,$goods_id)";

        $pager['page_prev']    = $page > 1 ? "javascript:gotoBuyPage(" .($page-1). ",$goods_id)" : 'javascript:;';

        $pager['page_next']    = $page < $page_count ? 'javascript:gotoBuyPage(' .($page + 1) . ",$goods_id)" : 'javascript:;';

        $pager['page_last']    = $page < $page_count ? 'javascript:gotoBuyPage(' .$page_count. ",$goods_id)"  : 'javascript:;';



        $smarty->assign('notes', $bought_notes);

        $smarty->assign('pager', $pager);





        $res['result'] = $GLOBALS['smarty']->fetch('library/bought_notes.lbi');



        $GLOBALS['smarty']->caching = $need_cache;



    }



    die($json->encode($res));

}

/* 商品套餐　*/

if($_REQUEST['act']=='taocan'){

    $goods_id = empty($_REQUEST['goods_id'])?'':intval($_REQUEST['goods_id']);

        //获取关联礼包

    $package_goods_list = get_package_goods_list($goods_id);

    $smarty->assign('package_goods_list',$package_goods_list);    // 获取关联礼包

		/* 代码增加_start By www.68ecshop.com */

    $package_goods_list_120 = get_package_goods_list_120($goods_id);

    $smarty->assign('package_goods_list_120',$package_goods_list_120);    // 获取关联礼包

		/* 代码增加_end By www.68ecshop.com */

    $smarty->display('goods_taocan.dwt');

    exit;

}



/*------------------------------------------------------ */

//-- PROCESSOR

/*------------------------------------------------------ */



$cache_id = $goods_id . '-' . $_SESSION['user_rank'].'-'.$_CFG['lang'];

$cache_id = sprintf('%X', crc32($cache_id));

/*edit by yhy 2019/7/10 如果商品属于店铺商品，更新用户的店铺cookie为商品所属店铺*/
$goods_supplierid = $db->getOne('SELECT supplier_id FROM ' . $ecs->table('goods') . ' WHERE goods_id = '.$goods_id);
if($goods_supplierid>0 && $_SESSION['user_id']>0){
	setcookie('u_supplier_id', intval($goods_supplierid), gmtime() + 3600 * 24); // 过期时间为 1 天
	//更新用户-店铺关联表 yhy edit 2019/6/24
	update_user_supplier_relate($_SESSION['user_id'],$goods_id);
}


/*end*/
if (!$smarty->is_cached('goods.dwt', $cache_id)){

    /* 获得商品的信息 */
    $goods = get_goods_info($goods_id);
    if(!in_array($user_rank,explode(',',$goods['level']))&&$goods['level']!=0){
        $content='您无权购买此商品，更多详情咨询平台管理员';
        show_message($content, '返回首页', $hrefs = './', $type = 'info', 2);
    }
	
	if(!empty($goods['service_group'])){
		$goods['service_group'] = explode(',',$goods['service_group']);
		$goods['service_group_num'] = count($goods['service_group']);
	}

	
	
    $smarty->assign('image_width',  $_CFG['image_width']);
    $smarty->assign('image_height', $_CFG['image_height']);
    $smarty->assign('helps',        get_shop_help()); // 网店帮助
    $smarty->assign('id',           $goods_id);
    $smarty->assign('type',         0);
    $smarty->assign('cfg',          $_CFG);
    $smarty->assign('promotion',       get_promotion_info($goods_id,$goods['supplier_id']));//促销信息
    $smarty->assign('promotion_info', get_promotion_info());
    $smarty->assign('shop_country',   $_CFG['shop_country']);
    $sql = 'select region_id, region_name from ' . $ecs->table('region') . ' where parent_id=' . $_CFG['shop_country'];
    $country_list = $GLOBALS['db']->getAll($sql);
    $smarty->assign('country_list',   $country_list);
    $city_id = $country_list[0]['region_id'];
    $smarty->assign('city_id',        $city_id);
    $district_id = $db->getOne('select region_id from ' . $ecs->table('region') . ' where parent_id=' . $city_id);
    $smarty->assign('district_id',    $district_id);
    /* 获取即时通讯客服信息 */
    $customers = is_customers(CUSTOMER_SERVICE, $goods['supplier_id']);
    $smarty->assign('customers',        $customers);
	/* 代码增加_end   By www.ecshop68.com  自提点 */
	$pups = $db->getOne('select * from ' . $ecs->table('shipping') . ' where shipping_code="pups"');
    $smarty->assign('pups',    $pups);
    $suppid = intval($_REQUEST['suppid']);
    $ppts = $GLOBALS['db']->getOne(
        'SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('pickup_point') . ' WHERE supplier_id = ' . $suppid
    );

    $smarty->assign('ppts', $ppts);

	/* 代码增加 By  www.68ecshop.com 自提点 End */
    if ($goods === false){
        /* 如果没有找到任何记录则跳回到首页 */
        ecs_header("Location: ./\n");
        exit;
    }else{
        if ($goods['brand_id'] > 0){
            $goods['goods_brand_url'] = build_uri('brand', array('bid'=>$goods['brand_id']), $goods['goods_brand']);
        }
		$goods['supplier_name'] ="网站自营";

		if ($goods['supplier_id'] > 0){
			$sql_supplier = "SELECT s.supplier_id,s.supplier_name,s.add_time,sr.rank_name FROM ". $ecs->table("supplier") . " as s left join ". $ecs->table("supplier_rank") ." as sr ON s.rank_id=sr.rank_id
			WHERE s.supplier_id=".$goods[supplier_id]." AND s.status=1";
			$shopuserinfo = $db->getRow($sql_supplier);
			$goods['supplier_name']= $shopuserinfo['supplier_name'];
			get_dianpu_baseinfo($goods['supplier_id'],$shopuserinfo);
		}else{
			get_service_str();
		}
        $shop_price   = $goods['shop_price'];
        $linked_goods = get_linked_goods($goods_id);
        $goods['goods_style_name'] = add_style($goods['goods_name'], $goods['goods_name_style']);
        /* 购买该商品可以得到多少钱的红包 */
        if ($goods['bonus_type_id'] > 0){
            $time = gmtime();
            $sql = "SELECT type_money FROM " . $ecs->table('bonus_type') .

                    " WHERE type_id = '$goods[bonus_type_id]' " .

                    " AND send_type = '" . SEND_BY_GOODS . "' " .

                    " AND send_start_date <= '$time'" .

                    " AND send_end_date >= '$time'";

            $goods['bonus_money'] = floatval($db->getOne($sql));
            if ($goods['bonus_money'] > 0){
                $goods['bonus_money'] = price_format($goods['bonus_money']);
            }
        }
        $smarty->assign('goods',              $goods);
        $smarty->assign('goods_id',           $goods['goods_id']);
        $smarty->assign('promote_end_time',   $goods['gmt_end_time']);
        $smarty->assign('categories',         get_categories_tree($goods['cat_id']));  // 分类树
		$smarty->assign('zhekou',  get_zhekou($goods['goods_id']));        //折扣
        /* meta */
        $smarty->assign('keywords',           htmlspecialchars($goods['keywords']));
        $smarty->assign('description',        htmlspecialchars($goods['goods_brief']));
        $catlist = array();
        foreach(get_parent_cats($goods['cat_id']) as $k=>$v){
            $catlist[] = $v['cat_id'];
        }
        assign_template('c', $catlist);
         /* 上一个商品下一个商品 */
        $prev_gid = $db->getOne("SELECT goods_id FROM " .$ecs->table('goods'). " WHERE cat_id=" . $goods['cat_id'] . " AND goods_id > " . $goods['goods_id'] . " AND is_on_sale = 1 AND is_alone_sale = 1 AND is_delete = 0 LIMIT 1");
        if (!empty($prev_gid)){
            $prev_good['url'] = build_uri('goods', array('gid' => $prev_gid), $goods['goods_name']);
            $smarty->assign('prev_good', $prev_good);//上一个商品
        }
        $next_gid = $db->getOne("SELECT max(goods_id) FROM " . $ecs->table('goods') . " WHERE cat_id=".$goods['cat_id']." AND goods_id < ".$goods['goods_id'] . " AND is_on_sale = 1 AND is_alone_sale = 1 AND is_delete = 0");
        if (!empty($next_gid)){
            $next_good['url'] = build_uri('goods', array('gid' => $next_gid), $goods['goods_name']);
            $smarty->assign('next_good', $next_good);//下一个商品
        }
        $position = assign_ur_here($goods['cat_id'], $goods['goods_name']);
        /* current position */
        $smarty->assign('page_title',          $position['title']);                    // 页面标题
        $smarty->assign('ur_here',             $position['ur_here']);                  // 当前位置
        $properties = get_goods_properties($goods_id);  // 获得商品的规格和属性
        $smarty->assign('properties',          $properties['pro']);                              // 商品属性

		/* 代码增加_start  By  www.ecshop68.com */
		$sql_zhyh_qq = "select attr_id from ".$ecs->table('attribute')." where cat_id='". $goods['goods_type'] ."' and is_attr_gallery='1' ";
		$attr_id_gallery = $db->getOne($sql_zhyh_qq);
		$sql = "SELECT goods_attr_id, attr_value FROM " . $GLOBALS['ecs']->table('goods_attr') . " WHERE goods_id = '$goods_id'";
		$results_www_ecshop68_com = $GLOBALS['db']->getAll($sql);
		$return_arr = array();
		foreach ($results_www_ecshop68_com as $value_ecshop68){
			$return_arr[$value_ecshop68['goods_attr_id']] = $value_ecshop68['attr_value'];
		}
		$prod_options_arr=array();
		$prod_exist_arr = array();
		$sql_prod  = "select goods_attr from ". $GLOBALS['ecs']->table('products') ." where product_number>0 and goods_id='$goods_id' order by goods_attr";
		$res_prod = $db->query($sql_prod);
		while ($row_prod = $GLOBALS['db']->fetchRow($res_prod)){
			$prod_exist_arr[] = "|". $row_prod['goods_attr'] ."|";
		}
		$GLOBALS['smarty']->assign('prod_exist_arr', $prod_exist_arr);
		$selected_first = array();
		foreach ($properties['spe'] AS $skey_ecshop68=>$sval_ecshop68){
			$hahaha_zhyh = 0;
			$sskey_www_ecshop68_com = '-1';
			foreach ($sval_ecshop68['values'] AS $sskey_ecshop68=>$ssval_ecshop68){
				if ( is_exist_prod($selected_first, $ssval_ecshop68['id'], $prod_exist_arr)){
					$hahaha_zhyh = $hahaha_zhyh ? $hahaha_zhyh : $ssval_ecshop68['id'];
					$sskey_www_ecshop68_com = ($sskey_www_ecshop68_com != '-1') ? $sskey_www_ecshop68_com : $sskey_ecshop68;
				}else{
					$properties['spe'][$skey_ecshop68]['values'][$sskey_ecshop68]['disabled'] = "disabled";
				}
				if ($skey_ecshop68==$attr_id_gallery){
					$goods_attr_id_qq = $ssval_ecshop68['id'] ;
					$sql_qq_qq87139667 = "select  thumb_url from ". $ecs->table('goods_gallery'). " where goods_id='$goods_id' and goods_attr_id='$goods_attr_id_qq' and is_attr_image='1' ";
					$properties['spe'][$skey_ecshop68]['values'][$sskey_ecshop68]['goods_attr_thumb'] = $db->getOne($sql_qq_qq87139667);
				}
			}
			if ($hahaha_zhyh){
				$selected_first[$skey_ecshop68] =  $hahaha_zhyh;
			}

			if ($sskey_www_ecshop68_com!='-1'){
				$properties['spe'][$skey_ecshop68]['values'][$sskey_www_ecshop68_com]['selected_key_ecshop68'] = "1";
			}
		}
		/* 代码增加_end  By  www.ecshop68.com */
        $smarty->assign('specification',       $properties['spe']);                              // 商品规格
        $smarty->assign('attribute_linked',    get_same_attribute_goods($properties));           // 相同属性的关联商品
        $smarty->assign('related_goods',       $linked_goods);                                   // 关联商品
        $smarty->assign('goods_article_list',  get_linked_articles($goods_id));                  // 关联文章
        $smarty->assign('fittings',            get_goods_fittings(array($goods_id)));                   // 配件
        $smarty->assign('rank_prices',         get_user_rank_prices($goods_id, $shop_price));    // 会员等级价格
        $smarty->assign('pictures',            get_goods_gallery($goods_id));                    // 商品相册
        $smarty->assign('bought_goods',        get_also_bought($goods_id));                      // 购买了该商品的用户还购买了哪些商品
        $smarty->assign('goods_rank',          get_goods_rank($goods_id));                       // 商品的销售排名
		//yyy添加start  //商品评价个数
		
		//edit by yhy 如果商品为卡项商品,显示所有该卡项的评论
		
		if($goods['item_id']>0){
			$item_goods_ids = $GLOBALS['db']->getAll("SELECT goods_id FROM ".$GLOBALS['ecs']->table('goods')." WHERE item_id=".$goods['item_id']);
			$item_goods_ids = array_column($item_goods_ids,'goods_id');
			
			$count = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('comment') . " where comment_type=0 and id_value IN (".implode(',',$item_goods_ids).") and status=1");
		}else{
			$count = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('comment') . " where comment_type=0 and id_value ='$goods_id' and status=1");
		}
		
        $smarty->assign('review_count',       $count);
		//yyy添加end
		$smarty->assign('order_num',			selled_count($goods_id));
		$smarty->assign('pinglun',			get_evaluation_sum($goods_id));
		if($goods['item_id']>0){
			$sql="select * from ".$ecs->table('comment')." where comment_type=0 and status=1 and comment_rank!=0 and id_value IN (".implode(',',$item_goods_ids).")";
		}else{
			$sql="select * from ".$ecs->table('comment')." where comment_type=0 and status=1 and comment_rank!=0 and id_value=$goods_id";
		}

		$comments=$db->getall($sql);
		$coun12t=count($comments);
		$yixing=0;
		$erxing=0;
		$sanxing=0;
		$sixing=0;
		$wuxing=0;
		$haoping=0;
		foreach($comments as $value){
			if($value['comment_rank'] == 1){
				$yixing=$yixing+1;
				$haoping= $haoping +1;
			}
			if($value['comment_rank'] == 2){
				$erxing=$erxing+1;
				$haoping= $haoping +2;
			}
			if($value['comment_rank'] == 3){
				$sanxing=$sanxing+1;
				$haoping= $haoping +3;
			}
			if($value['comment_rank'] == 4){
				$sixing=$sixing+1;
				$haoping= $haoping +4;
			}
			if($value['comment_rank'] == 5){
				$wuxing=$wuxing+1;
				$haoping= $haoping +5;
			}
		}
		$smarty->assign('coun12t', $coun12t);
		if($coun12t>0){
			$smarty->assign('yixing', $yixing);
			$smarty->assign('erxing', $erxing);
			$smarty->assign('sanxing',$sanxing);
			$smarty->assign('sixing', $sixing);
			$smarty->assign('wuxing', $wuxing);
			if($coun12t > 0){
				$smarty->assign('haopinglv',  round($haoping/($coun12t*5)*100,1));
			}
		}
        //获取tag
        $tag_array = get_tags($goods_id);
        $smarty->assign('tags',                $tag_array);   // 商品的标记
		if($goods['is_buy'] == 1){
			 if($goods['buymax_start_date'] < gmtime() && $goods['buymax_end_date'] > gmtime()){
				  if($goods['buymax'] > 0){
					  $tag = 1;
				  }else{
					  $tag = 0;
				  }
			 }else{
				 $tag = 0;
			 }

		}else{
			$tag = 0;
		}
		$smarty->assign('tag',$tag);
        //获取关联礼包
        $package_goods_list = get_package_goods_list($goods['goods_id']);
        $smarty->assign('package_goods_list',$package_goods_list);    // 获取关联礼包
		/* 代码增加_start By www.68ecshop.com */
		$package_goods_list_120 = get_package_goods_list_120($goods['goods_id']);
        $smarty->assign('package_goods_list_120',$package_goods_list_120);    // 获取关联礼包
		/* 代码增加_end By www.68ecshop.com */
        assign_dynamic('goods');
        $volume_price_list = get_volume_price_list($goods['goods_id'], '1');
        $smarty->assign('volume_price_list',$volume_price_list);    // 商品优惠价格区间
    }
}

//$GLOBALS['smarty']->assign('stock_id',          intval($_GET['supplier_id']));//渲染从哪个店铺购买 edit yhy 2019/6/14
$GLOBALS['smarty']->assign('goods_name',           $goods['goods_name']);

		    $GLOBALS['smarty']->assign('shop_price',           $goods['shop_price']);

		    $GLOBALS['smarty']->assign('shop_name',           $_GET['shop_name']);

/* 记录浏览历史 */

if (!empty($_COOKIE['ECS']['history']))

{

    $history = explode(',', $_COOKIE['ECS']['history']);



    array_unshift($history, $goods_id);

    $history = array_unique($history);



    while (count($history) > $_CFG['history_number'])

    {

        array_pop($history);

    }



    setcookie('ECS[history]', implode(',', $history), gmtime() + 3600 * 24 * 30);

}

else

{

    setcookie('ECS[history]', $goods_id, gmtime() + 3600 * 24 * 30);

}


/* 添加评价晒单 */

    $comment_list = get_my_comments($goods_id,0, 1);

    $smarty->assign('comments_list',$comment_list['item_list']);

/* 更新点击次数 */

$db->query('UPDATE ' . $ecs->table('goods') . " SET click_count = click_count + 1 WHERE goods_id = '$_REQUEST[id]'");

$smarty->assign('now_time',  gmtime());           // 当前系统时间
$smarty->display('goods.dwt',      $cache_id);



/*------------------------------------------------------ */

//-- PRIVATE FUNCTION

/*------------------------------------------------------ */



/**

 * 获得指定商品的关联商品

 *

 * @access  public

 * @param   integer     $goods_id

 * @return  array

 */

function get_linked_goods($goods_id)

{

    $sql = 'SELECT g.goods_id, g.goods_name, g.goods_thumb, g.goods_img, g.shop_price AS org_price, ' .

                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, ".

                'g.market_price, g.promote_price, g.promote_start_date, g.promote_end_date ' .

            'FROM ' . $GLOBALS['ecs']->table('link_goods') . ' lg ' .

            'LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g ON g.goods_id = lg.link_goods_id ' .

            "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".

                    "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".

            "WHERE lg.goods_id = '$goods_id' AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 ".

            "LIMIT " . $GLOBALS['_CFG']['related_goods_number'];

    $res = $GLOBALS['db']->query($sql);



    $arr = array();

    while ($row = $GLOBALS['db']->fetchRow($res))

    {

        $arr[$row['goods_id']]['goods_id']     = $row['goods_id'];

        $arr[$row['goods_id']]['goods_name']   = $row['goods_name'];

        $arr[$row['goods_id']]['short_name']   = $GLOBALS['_CFG']['goods_name_length'] > 0 ?

            sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];

        $arr[$row['goods_id']]['goods_thumb']  = get_pc_url().'/'.get_image_path($row['goods_id'], $row['goods_thumb'], true);

        $arr[$row['goods_id']]['goods_img']    = get_pc_url().'/'.get_image_path($row['goods_id'], $row['goods_img']);

        $arr[$row['goods_id']]['market_price'] = price_format($row['market_price']);

        $arr[$row['goods_id']]['shop_price']   = price_format($row['shop_price']);

        $arr[$row['goods_id']]['url']          = build_uri('goods', array('gid'=>$row['goods_id']), $row['goods_name']);



        if ($row['promote_price'] > 0)

        {

            $arr[$row['goods_id']]['promote_price'] = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);

            $arr[$row['goods_id']]['formated_promote_price'] = price_format($arr[$row['goods_id']]['promote_price']);

        }

        else

        {

            $arr[$row['goods_id']]['promote_price'] = 0;

        }

    }



    return $arr;

}



/**

 * 获得指定商品的关联文章

 *

 * @access  public

 * @param   integer     $goods_id

 * @return  void

 */

function get_linked_articles($goods_id)

{

    $sql = 'SELECT a.article_id, a.title, a.file_url, a.open_type, a.add_time ' .

            'FROM ' . $GLOBALS['ecs']->table('goods_article') . ' AS g, ' .

                $GLOBALS['ecs']->table('article') . ' AS a ' .

            "WHERE g.article_id = a.article_id AND g.goods_id = '$goods_id' AND a.is_open = 1 " .

            'ORDER BY a.add_time DESC';

    $res = $GLOBALS['db']->query($sql);



    $arr = array();

    while ($row = $GLOBALS['db']->fetchRow($res))

    {

        $row['url']         = $row['open_type'] != 1 ?

            build_uri('article', array('aid'=>$row['article_id']), $row['title']) : trim($row['file_url']);

        $row['add_time']    = local_date($GLOBALS['_CFG']['date_format'], $row['add_time']);

        $row['short_title'] = $GLOBALS['_CFG']['article_title_length'] > 0 ?

            sub_str($row['title'], $GLOBALS['_CFG']['article_title_length']) : $row['title'];



        $arr[] = $row;

    }



    return $arr;

}



/**

 * 获得指定商品的各会员等级对应的价格

 *

 * @access  public

 * @param   integer     $goods_id

 * @return  array

 */

function get_user_rank_prices($goods_id, $shop_price)

{

    $sql = "SELECT rank_id, IFNULL(mp.user_price, r.discount * $shop_price / 100) AS price, r.rank_name, r.discount " .

            'FROM ' . $GLOBALS['ecs']->table('user_rank') . ' AS r ' .

            'LEFT JOIN ' . $GLOBALS['ecs']->table('member_price') . " AS mp ".

                "ON mp.goods_id = '$goods_id' AND mp.user_rank = r.rank_id " .

            "WHERE r.show_price = 1 OR r.rank_id = '$_SESSION[user_rank]'";

    $res = $GLOBALS['db']->query($sql);



    $arr = array();

    while ($row = $GLOBALS['db']->fetchRow($res))

    {



        $arr[$row['rank_id']] = array(

                        'rank_name' => htmlspecialchars($row['rank_name']),

                        'price'     => price_format($row['price']));

    }



    return $arr;

}



/**

 * 获得购买过该商品的人还买过的商品

 *

 * @access  public

 * @param   integer     $goods_id

 * @return  array

 */

function get_also_bought($goods_id)

{

    $sql = 'SELECT COUNT(b.goods_id ) AS num, g.goods_id, g.goods_name, g.goods_thumb, g.goods_img, g.shop_price, g.promote_price, g.promote_start_date, g.promote_end_date ' .

            'FROM ' . $GLOBALS['ecs']->table('order_goods') . ' AS a ' .

            'LEFT JOIN ' . $GLOBALS['ecs']->table('order_goods') . ' AS b ON b.order_id = a.order_id ' .

            'LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g ON g.goods_id = b.goods_id ' .

            "WHERE a.goods_id = '$goods_id' AND b.goods_id <> '$goods_id' AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 " .

            'GROUP BY b.goods_id ' .

            'ORDER BY num DESC ' .

            'LIMIT ' . $GLOBALS['_CFG']['bought_goods'];

    $res = $GLOBALS['db']->query($sql);



    $key = 0;

    $arr = array();

    while ($row = $GLOBALS['db']->fetchRow($res))

    {

        $arr[$key]['goods_id']    = $row['goods_id'];

        $arr[$key]['goods_name']  = $row['goods_name'];

        $arr[$key]['short_name']  = $GLOBALS['_CFG']['goods_name_length'] > 0 ?

            sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];

        $arr[$key]['goods_thumb'] = get_pc_url().'/'.get_image_path($row['goods_id'], $row['goods_thumb'], true);

        $arr[$key]['goods_img']   = get_pc_url().'/'.get_image_path($row['goods_id'], $row['goods_img']);

        $arr[$key]['shop_price']  = price_format($row['shop_price']);

        $arr[$key]['url']         = build_uri('goods', array('gid'=>$row['goods_id']), $row['goods_name']);



        if ($row['promote_price'] > 0)

        {

            $arr[$key]['promote_price'] = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);

            $arr[$key]['formated_promote_price'] = price_format($arr[$key]['promote_price']);

        }

        else

        {

            $arr[$key]['promote_price'] = 0;

        }



        $key++;

    }



    return $arr;

}



/**

 * 获得指定商品的销售排名

 *

 * @access  public

 * @param   integer     $goods_id

 * @return  integer

 */

function get_goods_rank($goods_id)

{

    /* 统计时间段 */

    $period = intval($GLOBALS['_CFG']['top10_time']);

    if ($period == 1) // 一年

    {

        $ext = " AND o.add_time > '" . local_strtotime('-1 years') . "'";

    }

    elseif ($period == 2) // 半年

    {

        $ext = " AND o.add_time > '" . local_strtotime('-6 months') . "'";

    }

    elseif ($period == 3) // 三个月

    {

        $ext = " AND o.add_time > '" . local_strtotime('-3 months') . "'";

    }

    elseif ($period == 4) // 一个月

    {

        $ext = " AND o.add_time > '" . local_strtotime('-1 months') . "'";

    }

    else

    {

        $ext = '';

    }



    /* 查询该商品销量 */

    $sql = 'SELECT IFNULL(SUM(g.goods_number), 0) ' .

        'FROM ' . $GLOBALS['ecs']->table('order_info') . ' AS o, ' .

            $GLOBALS['ecs']->table('order_goods') . ' AS g ' .

        "WHERE o.order_id = g.order_id " .

        "AND o.order_status = '" . OS_CONFIRMED . "' " .

        "AND o.shipping_status " . db_create_in(array(SS_SHIPPED, SS_RECEIVED)) .

        " AND o.pay_status " . db_create_in(array(PS_PAYED, PS_PAYING)) .

        " AND g.goods_id = '$goods_id'" . $ext;

    $sales_count = $GLOBALS['db']->getOne($sql);



    if ($sales_count > 0)

    {

        /* 只有在商品销售量大于0时才去计算该商品的排行 */

        $sql = 'SELECT DISTINCT SUM(goods_number) AS num ' .

                'FROM ' . $GLOBALS['ecs']->table('order_info') . ' AS o, ' .

                    $GLOBALS['ecs']->table('order_goods') . ' AS g ' .

                "WHERE o.order_id = g.order_id " .

                "AND o.order_status = '" . OS_CONFIRMED . "' " .

                "AND o.shipping_status " . db_create_in(array(SS_SHIPPED, SS_RECEIVED)) .

                " AND o.pay_status " . db_create_in(array(PS_PAYED, PS_PAYING)) . $ext .

                " GROUP BY g.goods_id HAVING num > $sales_count";

        $res = $GLOBALS['db']->query($sql);



        $rank = $GLOBALS['db']->num_rows($res) + 1;



        if ($rank > 10)

        {

            $rank = 0;

        }

    }

    else

    {

        $rank = 0;

    }



    return $rank;

}



/**

 * 获得商品选定的属性的附加总价格

 *

 * @param   integer     $goods_id

 * @param   array       $attr

 *

 * @return  void

 */

function get_attr_amount($goods_id, $attr)

{

    $sql = "SELECT SUM(attr_price) FROM " . $GLOBALS['ecs']->table('goods_attr') .

        " WHERE goods_id='$goods_id' AND " . db_create_in($attr, 'goods_attr_id');



    return $GLOBALS['db']->getOne($sql);

}



/**

 * 取得跟商品关联的礼包列表

 *

 * @param   string  $goods_id    商品编号

 *

 * @return  礼包列表

 */

function get_package_goods_list($goods_id)

{

    $now = gmtime();

    $sql = "SELECT pg.goods_id, ga.act_id, ga.act_name, ga.act_desc, ga.goods_name, ga.start_time,

                   ga.end_time, ga.is_finished, ga.ext_info

            FROM " . $GLOBALS['ecs']->table('goods_activity') . " AS ga, " . $GLOBALS['ecs']->table('package_goods') . " AS pg

            WHERE pg.package_id = ga.act_id

            AND ga.start_time <= '" . $now . "'

            AND ga.end_time >= '" . $now . "'

            AND pg.goods_id = " . $goods_id . "

            GROUP BY ga.act_id

            ORDER BY ga.act_id ";

    $res = $GLOBALS['db']->getAll($sql);



    foreach ($res as $tempkey => $value)

    {

        $subtotal = 0;

        $row = unserialize($value['ext_info']);

        unset($value['ext_info']);

        if ($row)

        {

            foreach ($row as $key=>$val)

            {

                $res[$tempkey][$key] = $val;

            }

        }



        $sql = "SELECT pg.package_id, pg.goods_id, pg.goods_number, pg.admin_id, p.goods_attr, g.goods_sn, g.goods_name, g.market_price, g.goods_thumb, IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS rank_price

                FROM " . $GLOBALS['ecs']->table('package_goods') . " AS pg

                    LEFT JOIN ". $GLOBALS['ecs']->table('goods') . " AS g

                        ON g.goods_id = pg.goods_id

                    LEFT JOIN ". $GLOBALS['ecs']->table('products') . " AS p

                        ON p.product_id = pg.product_id

                    LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp

                        ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]'

                WHERE pg.package_id = " . $value['act_id']. "

                ORDER BY pg.package_id, pg.goods_id";



        $goods_res = $GLOBALS['db']->getAll($sql);



        foreach($goods_res as $key => $val)

        {

            $goods_id_array[] = $val['goods_id'];

            $goods_res[$key]['goods_thumb']  = get_pc_url().'/'.get_image_path($val['goods_id'], $val['goods_thumb'], true);

            $goods_res[$key]['market_price'] = price_format($val['market_price']);

            $goods_res[$key]['rank_price']   = price_format($val['rank_price']);

            $subtotal += $val['rank_price'] * $val['goods_number'];

        }



        /* 取商品属性 */

        $sql = "SELECT ga.goods_attr_id, ga.attr_value

                FROM " .$GLOBALS['ecs']->table('goods_attr'). " AS ga, " .$GLOBALS['ecs']->table('attribute'). " AS a

                WHERE a.attr_id = ga.attr_id

                AND a.attr_type = 1

                AND " . db_create_in($goods_id_array, 'goods_id');

        $result_goods_attr = $GLOBALS['db']->getAll($sql);



        $_goods_attr = array();

        foreach ($result_goods_attr as $value)

        {

            $_goods_attr[$value['goods_attr_id']] = $value['attr_value'];

        }



        /* 处理货品 */

        $format = '[%s]';

        foreach($goods_res as $key => $val)

        {

            if ($val['goods_attr'] != '')

            {

                $goods_attr_array = explode('|', $val['goods_attr']);



                $goods_attr = array();

                foreach ($goods_attr_array as $_attr)

                {

                    $goods_attr[] = $_goods_attr[$_attr];

                }



                $goods_res[$key]['goods_attr_str'] = sprintf($format, implode('，', $goods_attr));

            }

        }



        $res[$tempkey]['goods_list']    = $goods_res;

        $res[$tempkey]['subtotal']      = price_format($subtotal);

        $res[$tempkey]['saving']        = price_format(($subtotal - $res[$tempkey]['package_price']));

        $res[$tempkey]['package_price'] = price_format($res[$tempkey]['package_price']);

    }



    return $res;

}



/**

 * 获取客服字符串

 * @param $_goods_attr 平台，商铺配置数组

 */

function getServiceStr($_goods_attr){

    if($_goods_attr['qq'] != ""){

        $service = "http://wpa.qq.com/msgrd?v=3&uin=".$_goods_attr['qq']."&site=qq&menu=yes&from=message&isappinstalled=0";

    }else if($_goods_attr['ww'] != ""){

        //百度商桥 http://p.qiao.baidu.com/cps/chat?siteId=12825575&amp;userId=24417727

//        $service = "javascript:alert(".$_goods_attr['ww'].")";

        $service = "http://p.qiao.baidu.com/cps/chat?siteId=12825575&amp;userId=24417727";

    }else if($_goods_attr['wx'] != ""){

        $wxUrl = "<img width=100% src=".$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].'/'.$_goods_attr['wx']."><br><br><div width=100%><div width=100% class=qrcode-descrption align=center>长按识别二维码</div><div width=100% class=qrcode-descrption align=center>加客服微信了解更多内容</div></div>";

        $service = "javascript:layer.open({type:1,title:'客服微信',content:'".$wxUrl."',offset: ['100px', '0px']});";

    }else{

        $service = "javascript:;";

    }

    return $service;

}

function get_service_str(){

    global $smarty;

    $sql = "SELECT * FROM " .$GLOBALS['ecs']->table('shop_config');

    $shopinfo = $GLOBALS['db']->getAll($sql);

    $_goods_attr = array();

    foreach ($shopinfo as $value)

    {

        $_goods_attr[$value['code']] = $value['value'];

    }

    $service = getServiceStr($_goods_attr);

    $smarty->assign('service', $service);

}

/*

 * 获取商品所对应店铺的店铺基本信息

 * @param int $suppid 店铺id

 * @param int $suppinfo 入驻商的信息

 */

function get_dianpu_baseinfo($suppid=0,$suppinfo){

    global $smarty;

	if(intval($suppid) <= 0){

		return ;

	}

	$sql = "SELECT * FROM " .$GLOBALS['ecs']->table('supplier_shop_config'). " WHERE supplier_id = " . $suppid;

        $shopinfo = $GLOBALS['db']->getAll($sql);



        $_goods_attr = array();

        foreach ($shopinfo as $value)

        {

            $_goods_attr[$value['code']] = $value['value'];

        }

	$smarty->assign('ghs_css_path',        'themesmobile/'.$_goods_attr['template'].'/images/ghs/css/ghs_style.css');//入驻商所选模板样式路径

	  //获取店铺logo

	$shoplogo = empty($_goods_attr['shop_logo']) ? 'themesmobile/'.$_goods_attr['template'].'/images/dianpu.jpg' : $_goods_attr['shop_logo'];



    //获取店铺海报

     $sql = "select logo from ". $GLOBALS['ecs']->table('supplier_street') ." where supplier_id = ".$suppid;

        $shop_logo = $GLOBALS['db']->getOne($sql);



	$smarty->assign('shop_logo',        $shop_logo);//商家海报

	$smarty->assign('shoplogo',        $shoplogo);//商家logo

	$smarty->assign('shopname',        htmlspecialchars($_goods_attr['shop_name']));//店铺名称

	$smarty->assign('suppid',        $suppinfo['supplier_id']);//商家名称

        $smarty->assign('is_guanzhu',    is_guanzhu($suppinfo['supplier_id']));//是否关注

	$smarty->assign('suppliername',        htmlspecialchars($suppinfo['supplier_name']));//商家名称

	$smarty->assign('userrank',        htmlspecialchars($suppinfo['rank_name']));//商家等级

   	//$smarty->assign('region', get_province_city($_goods_attr['shop_province'],$_goods_attr['shop_city']));

	$smarty->assign('address', $_goods_attr['shop_address']);

    $service = getServiceStr($_goods_attr);

    $smarty->assign('service', $service);

    $smarty->assign('serviceqq', $_goods_attr['qq']);

	$smarty->assign('serviceww', $_goods_attr['ww']);

	$smarty->assign('serviceemail', $_goods_attr['service_email']);

	$smarty->assign('servicephone', $_goods_attr['service_phone']);

	$smarty->assign('createtime',      gmdate('Y-m-d',$suppinfo['add_time']));//商家创建时间

	  $smarty->assign('goodsnum',      get_supplier_goods_count($suppinfo['supplier_id']));//商家商品数量

        $smarty->assign('fensi',get_supplier_fensi_count($suppinfo['supplier_id'])); //商家被关注数量

	$sql1 = "SELECT AVG(comment_rank) FROM " . $GLOBALS['ecs']->table('comment') . " c" . " LEFT JOIN " . $GLOBALS['ecs']->table('order_info') . " o"." ON o.order_id = c.order_id"." WHERE c.status > 0 AND  o.supplier_id = " .$suppid;

        $avg_comment = $GLOBALS['db']->getOne($sql1);

        $avg_comment = number_format(round($avg_comment), 1);

        $sql2 = "SELECT AVG(server), AVG(shipping) FROM " . $GLOBALS['ecs']->table('shop_grade') . " s" . " LEFT JOIN " . $GLOBALS['ecs']->table('order_info') . " o"." ON o.order_id = s.order_id"." WHERE s.is_comment > 0 AND  s.server >0 AND o.supplier_id = " .$suppid;

        $row = $GLOBALS['db']->getRow($sql2);

        $avg_server = number_format(round($row['AVG(server)']), 1);

        $avg_shipping = number_format(round($row['AVG(shipping)']), 1);

        //$haoping = round((($avg_comment+$avg_server+$avg_shipping)/3)/5,2)*100;

        $smarty->assign('c_rank', $avg_comment);

        $smarty->assign('serv_rank', $avg_server);

        $smarty->assign('shipp_rank', $avg_shipping);



	$suppid = (intval($suppid)>0) ? intval($suppid) : intval($_GET['suppId']);

	//$sql="SELECT count(`goods_id`) FROM ".$GLOBALS['ecs']->table('goods')." as g WHERE  g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.supplier_id='$suppid'";

	//$smarty->assign('goodsnum',      $GLOBALS['db']->getOne($sql));//商家商品数量

}



function region_select($type, $parent_id, $start_id = "")

{

	switch ($type)

	{

		case 1 : $select_id = 'country_list'; $select_name = '国家'; $_js = " onchange='get_region_list(2,this.value)'"; break;

		case 2 : $select_id = 'province_list'; $select_name = '省份'; $_js = " onchange='get_region_list(3,this.value)'"; break;

		case 3 : $select_id = 'city_list'; $select_name = '城市'; $_js = " onchange='get_region_list(4,this.value)'"; break;

		case 4 : $select_id = 'district_list'; $select_name = '地区'; $_js = " onchange='get_pickup_point_list(this.value)'"; break;

	}



	$region_info = "<select name='" . $select_id . "' " . $_js . ">";



	if ($start_id != "")

	{

		$sql_s = "select region_id, region_name from " . $GLOBALS['ecs']->table('region') . " where region_id = '" . $start_id . "'";

		$s_info = $GLOBALS['db']->getRow($sql_s);

		$sql_a = "select region_id, region_name from " . $GLOBALS['ecs']->table('region') . " where parent_id = '" . $parent_id . "' and region_id <> '" . $start_id . "'";

		$a_info = $GLOBALS['db']->getAll($sql_a);



		$region_info .= "<option value='" . $s_info['region_id'] . "'>" . $s_info['region_name'] . "</option>";

		foreach ($a_info as $minfo)

		{

			$region_info .= "<option value='" . $minfo['region_id'] . "'>" . $minfo['region_name'] . "</option>";

		}

	}

	else

	{

		$sql_all = "select region_id, region_name from " . $GLOBALS['ecs']->table('region') . " where parent_id = '" . $parent_id . "'";

		$all_info = $GLOBALS['db']->getAll($sql_all);



		$region_info .= "<option value='0'>请选择" . $select_name . "</option>";

		foreach ($all_info as $info)

		{

			$region_info .= "<option value='" . $info['region_id'] . "'>" . $info['region_name'] . "</option>";

		}

	}



	$region_info .= "</select>";



	return $region_info;

}





function get_zhekou($goods_id)

{

	 $zhekou  = 0;



    /* 取得商品信息 */

    $sql = "SELECT s.seckill_price,s.seckill_start_date,s.seckill_end_date,s.is_seckill,g.is_promote,g.promote_start_date,g.promote_end_date,g.promote_price, g.shop_price , g.market_price ".



           " FROM " .$GLOBALS['ecs']->table('goods'). " AS g  left join ".$GLOBALS['ecs']->table('seckill_goods')." AS s on g.goods_id=s.goods_id ".



           " WHERE g.goods_id = '" . $goods_id . "'";



    $goods = $GLOBALS['db']->getRow($sql);



	if(intval($goods['market_price']) == 0)

	{

		$zhekou = 0;

	}

	else

	{

		if($goods['is_seckill'] == 1 && $goods['seckill_start_date'] <= gmtime() && $goods['seckill_end_date'] >= gmtime()){

			$zhekou = (number_format(intval($goods['seckill_price'])/intval($goods['market_price']),2))*10;

		}else{

			if($goods['is_promote'] == 1 && $goods['promote_start_date'] <= gmtime() && $goods['promote_end_date'] >= gmtime())

			{

				$zhekou = (number_format(intval($goods['promote_price'])/intval($goods['market_price']),2))*10;

			}

			else

			{

				$zhekou = (number_format(intval($goods['shop_price'])/intval($goods['market_price']),2))*10;

			}

		}

	}



    return $zhekou;

}



function get_package_goods_list_120($goods_id)

{

	$now = gmtime();

    $sql = "SELECT ga.act_id,ga.ext_info

            FROM " . $GLOBALS['ecs']->table('goods_activity') . " AS ga, " . $GLOBALS['ecs']->table('package_goods') . " AS pg

            WHERE pg.package_id = ga.act_id

            AND ga.start_time <= '" . $now . "'

            AND ga.end_time >= '" . $now . "'

            AND pg.goods_id = " . $goods_id . "

            GROUP BY pg.package_id

            ORDER BY ga.act_id";



	$res = $GLOBALS['db']->getAll($sql);



    foreach ($res as $tempkey => $value)

    {

        $subtotal = 0;

		$i=1;



		//获取礼包价

		$row = unserialize($value['ext_info']);

        unset($value['ext_info']);

        if ($row)

        {

            foreach ($row as $key=>$val)

            {

                $res[$tempkey][$key] = $val;

            }

        }



        $sql = "SELECT pg.package_id, pg.goods_id, pg.product_id, pg.goods_number, pg.admin_id, p.goods_attr, g.goods_sn, g.goods_name, g.market_price, g.goods_thumb, IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS rank_price

                FROM " . $GLOBALS['ecs']->table('package_goods') . " AS pg

                    LEFT JOIN ". $GLOBALS['ecs']->table('goods') . " AS g

                        ON g.goods_id = pg.goods_id

                    LEFT JOIN ". $GLOBALS['ecs']->table('products') . " AS p

                        ON p.product_id = pg.product_id

                    LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp

                        ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]'

                WHERE pg.package_id = " . $value['act_id']. "

                ORDER BY pg.package_id, pg.goods_id";



		$goods_ress = $GLOBALS['db']->query($sql);

		$goods_res = array();

		while ($row = $GLOBALS['db']->fetchRow($goods_ress))

		{

			if ($row['goods_id'] == $goods_id )

			{

				$goods_res[0]=$row;

			}

			else

			{

				$goods_res[$i]=$row;

				$i++;

			}

		}



        foreach($goods_res as $key => $val)

        {

            $goods_id_array[] = $val['goods_id'];

            $goods_res[$key]['goods_thumb']  = get_pc_url().'/'.get_image_path($val['goods_id'], $val['goods_thumb'], true);

            $goods_res[$key]['market_price'] = price_format($val['market_price']);

            $goods_res[$key]['rank_price']   = $val['rank_price'];

            $subtotal += $val['rank_price'] * $val['goods_number'];

        }



        /* 取商品属性 */

        $sql = "SELECT ga.goods_attr_id, ga.attr_value

                FROM " .$GLOBALS['ecs']->table('goods_attr'). " AS ga, " .$GLOBALS['ecs']->table('attribute'). " AS a

                WHERE a.attr_id = ga.attr_id

                AND a.attr_type = 1

                AND " . db_create_in($goods_id_array, 'goods_id');

        $result_goods_attr = $GLOBALS['db']->getAll($sql);



        $_goods_attr = array();

        foreach ($result_goods_attr as $value)

        {

            $_goods_attr[$value['goods_attr_id']] = $value['attr_value'];

        }



        /* 处理货品 */

        $format = '[%s]';

        foreach($goods_res as $key => $val)

        {

            if ($val['goods_attr'] != '')

            {

                $goods_attr_array = explode('|', $val['goods_attr']);



                $goods_attr = array();

                foreach ($goods_attr_array as $_attr)

                {

                    $goods_attr[] = $_goods_attr[$_attr];

                }



                $goods_res[$key]['goods_attr_str'] = sprintf($format, implode('，', $goods_attr));

            }

        }



		ksort($goods_res); //重新排序数组



		/* 重新计算套餐内的商品折扣价 */

		$zhekou=  round(($res[$tempkey]['package_price'] / $subtotal), 8);

		foreach($goods_res as $key => $val)

		{

			$goods_res[$key]['rank_price_zk']=$val['rank_price'] * $zhekou;

			$goods_res[$key]['rank_price_zk_format']= price_format($goods_res[$key]['rank_price_zk']);

		}



        $res[$tempkey]['goods_list']    = $goods_res;

        $res[$tempkey]['subtotal']      = price_format($subtotal);

		$res[$tempkey]['zhekou']      = $zhekou*100;

        $res[$tempkey]['saving']        = price_format(($subtotal - $res[$tempkey]['package_price']));

        $res[$tempkey]['package_price'] = price_format($res[$tempkey]['package_price']);



    }



	return $res;

}



/**

 * 获取本店铺商品数量

 */

function get_supplier_goods_count($suppid=0){



	$suppid = (intval($suppid)>0) ? intval($suppid) : intval($_GET['suppId']);

	$sql="SELECT count(`goods_id`) FROM ".$GLOBALS['ecs']->table('goods')." as g WHERE  g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.supplier_id='$suppid'";

        return $GLOBALS['db']->getOne($sql);

}



/**

 * 获取店铺被搜藏数量

 */

function get_supplier_fensi_count($suppid=0){

    $suppid = (intval($suppid)>0) ? intval($suppid) : intval($_GET['suppId']);

    $sql = "SELECT count(*) FROM " .$GLOBALS['ecs']->table('supplier_guanzhu') ." WHERE supplierid=$suppid";

    return $GLOBALS['db']->getOne($sql);

}





$row=array(



    'goods_id'=>$goods_id,



	'user_name'=>$_SESSION['user_name'],



	'user_ip'=>real_ip(),



	'visit_time'=>gmtime(),



	'laiyuan'=>'mobile',



	'user_id'=>$_SESSION['user_name'],







);





$db->autoExecute($ecs->table('jilu'), $row, 'INSERT');



/* 代码增加_start  By www.68ecshop.com */

