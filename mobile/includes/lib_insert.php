<?php

/**
 * ECSHOP 动态内容函数库
 * ============================================================================
 * * 版权所有 2017-2020 月梦网络，并保留所有权利。
 * 月梦网络: http://dm299.taobao.com  开发QQ:124861234  禁止倒卖 一经发现停止任何服务
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: derek $
 * $Id: lib_insert.php 17217 2011-01-19 06:29:08Z derek $
*/

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

/**
 * 获得查询次数以及查询时间
 *
 * @access  public
 * @return  string
 */
function insert_query_info()
{
    if ($GLOBALS['db']->queryTime == '')
    {
        $query_time = 0;
    }
    else
    {
        if (PHP_VERSION >= '5.0.0')
        {
            $query_time = number_format(microtime(true) - $GLOBALS['db']->queryTime, 6);
        }
        else
        {
            list($now_usec, $now_sec)     = explode(' ', microtime());
            list($start_usec, $start_sec) = explode(' ', $GLOBALS['db']->queryTime);
            $query_time = number_format(($now_sec - $start_sec) + ($now_usec - $start_usec), 6);
        }
    }

    /* 内存占用情况 */
    if ($GLOBALS['_LANG']['memory_info'] && function_exists('memory_get_usage'))
    {
        $memory_usage = sprintf($GLOBALS['_LANG']['memory_info'], memory_get_usage() / 1048576);
    }
    else
    {
        $memory_usage = '';
    }

    /* 是否启用了 gzip */
    $gzip_enabled = gzip_enabled() ? $GLOBALS['_LANG']['gzip_enabled'] : $GLOBALS['_LANG']['gzip_disabled'];

    $online_count = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('sessions'));

    /* 加入触发cron代码 */
    $cron_method = empty($GLOBALS['_CFG']['cron_method']) ? '<img src="api/cron.php?t=' . gmtime() . '" alt="" style="width:0px;height:0px;" />' : '';

    return sprintf($GLOBALS['_LANG']['query_info'], $GLOBALS['db']->queryCount, $query_time, $online_count) . $gzip_enabled . $memory_usage . $cron_method;
}

/**
 * 调用浏览历史
 *
 * @access  public
 * @return  string
 */
function insert_history()
{
    $str = '';
    if (!empty($_COOKIE['ECS']['history']))
    {
        $where = db_create_in($_COOKIE['ECS']['history'], 'goods_id');
        $sql   = 'SELECT goods_id, goods_name, goods_thumb, shop_price FROM ' . $GLOBALS['ecs']->table('goods') .
                " WHERE $where AND is_on_sale = 1 AND is_alone_sale = 1 AND is_delete = 0";
        $query = $GLOBALS['db']->query($sql);
        $res = array();
        while ($row = $GLOBALS['db']->fetch_array($query))
        {
            $goods['goods_id'] = $row['goods_id'];
            $goods['goods_name'] = $row['goods_name'];
            $goods['short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ? sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
            $goods['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
            $goods['shop_price'] = price_format($row['shop_price']);
            $goods['url'] = build_uri('goods', array('gid'=>$row['goods_id']), $row['goods_name']);

		  
            $str.='<li>
              <div class="p-img"><a target="_blank" href="'.$goods['url'].'"><img src="'.$goods['goods_thumb'].'" alt="'.$goods['goods_name'].'" width="50" height="50" /></a></div>
              <div class="p-name"><a target="_blank" href="'.$goods['url'].'">'.$goods['goods_name'].'</a> </div>
              <div class="p-price"> <strong class="J-p-${list.wid}">'.$goods['shop_price'].'</strong> </div>
            </li>';
        }
      
    }
    return $str;
}
function get_cainixihuan()
{
if(!empty($_COOKIE['ECS']['history']))
{
$where = db_create_in($_COOKIE['ECS']['history'], 'goods_id');
        $sql   = 'SELECT cat_id FROM ' . $GLOBALS['ecs']->table('goods') .
                " WHERE $where AND is_on_sale = 1 AND is_alone_sale = 1 AND is_delete = 0 limit 1";
$catid = $GLOBALS['db']->getOne($sql);
if($catid)
{
	$catid=$catid;
}
else
{
	$catid=0;
}
$sql = "select * from ".$GLOBALS['ecs']->table('goods')." where cat_id = ".$catid." and is_best = 1 ";
$list = $GLOBALS['db']->getAll($sql);
$arr = array();
foreach($list as $key => $rows)
{
$arr[$key]['goods_id'] = $rows['goods_id'];
$arr[$key]['goods_name'] = $rows['goods_name'];
$arr[$key]['goods_thumb'] = $rows['goods_thumb'];
$arr[$key]['shop_price'] = $rows['shop_price'];
$arr[$key]['url'] = build_uri('goods', array('gid'=>$rows['goods_id']), $rows['goods_name']);
$arr[$key]['evaluation']   = get_evaluation_sumss($rows['goods_id']);
}
return $arr;
}
}
function get_evaluation_sumss($goods_id)
{
$sql = "SELECT count(*) FROM " . $GLOBALS['ecs']->table('comment') . " WHERE status=1 and  comment_type =0 and id_value =".$goods_id ;//status=1表示通过了的评论才算  comment_type =0表示针对商品的评价 感谢zhangyh的提醒
    return $GLOBALS['db']->getOne($sql);
}
/* 代码增加_start  By  www.68ecshop.com */
/**
 * 调用浏览历史
 *
 * @access  public
 * @return  string
 */
function insert_history_list()
{
    $str = '';
    if (!empty($_COOKIE['ECS']['history']))
    {
        $where = db_create_in($_COOKIE['ECS']['history'], 'goods_id');
        $sql   = 'SELECT goods_id, goods_name, goods_thumb, shop_price, goods_type FROM ' . $GLOBALS['ecs']->table('goods') .
                " WHERE $where AND is_on_sale = 1 AND is_alone_sale = 1 AND is_delete = 0";
        $query = $GLOBALS['db']->query($sql);
        $res = array();
		$str .= '<ul>';
        while ($row = $GLOBALS['db']->fetch_array($query))
        {
            $goods['goods_id'] = $row['goods_id'];
            $goods['goods_name'] = $row['goods_name'];
			$goods['goods_type'] = $row['goods_type'];
            $goods['short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ? sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
            $goods['goods_thumb'] = get_pc_url().'/'.get_image_path($row['goods_id'], $row['goods_thumb'], true);
            $goods['shop_price'] = price_format($row['shop_price']);
            $goods['url'] = build_uri('goods', array('gid'=>$row['goods_id']), $row['goods_name']);
            //$str.='<ul class="clearfix"><li class="goodsimg"><a href="'.$goods['url'].'" target="_blank"><img src="'.$goods['goods_thumb'].'" alt="'.$goods['goods_name'].'" class="B_blue" /></a></li><li><a href="'.$goods['url'].'" target="_blank" title="'.$goods['goods_name'].'">'.$goods['short_name'].'</a><br />'.$GLOBALS['_LANG']['shop_price'].'<font class="f1">'.$goods['shop_price'].'</font><br /></li></ul>';
			$str .= '<li><div class="item_wrap">
				<div class="dt"><a href="' . $goods['url'] .'"><img width="50" height="50" src="' . $goods['goods_thumb'] . '" /></a></div>
				<div class="dd">
					<a class="name" href="' . $goods['url'] . '">' . $goods['goods_name'] . '</a>
					<div class="btn" style="padding-top:15px;">
						<a class="compare-btn" data-goods="' . $goods['goods_id'] .
						'" onClick="Compare.add(' . $goods['goods_id'] . ',\'' . $goods['goods_name'] .'\',' .
						$goods['goods_type'] . ', \'' .
						$goods['goods_thumb'] . '\', \'' . $goods['shop_price'] . '\')"></a>
						<span class="price" style="float:left"><strong>' . $goods['shop_price'] . '</strong></span>
					</div>
				</div>
			</div></li>';
        }
		$str .='</ul>';
        //$str .= '<ul id="clear_history"><a onclick="clear_history()">' . $GLOBALS['_LANG']['clear_history'] . '</a></ul>';
    }
    return $str;
}

/**
 * 调用购物车信息
 *
 * @access  public
 * @return  string
 */
function insert_cart_info()
{
	$sql_where = $_SESSION['user_id']>0 ? "c.user_id='". $_SESSION['user_id'] ."' " : "c.session_id = '" . SESS_ID . "' AND c.user_id=0 ";
    $sql = 'SELECT c.*,g.goods_name,g.goods_thumb,g.goods_id,c.goods_number,c.goods_price' .
           ' FROM ' . $GLOBALS['ecs']->table('cart') ." AS c ".
					 " LEFT JOIN ".$GLOBALS['ecs']->table('goods')." AS g ON g.goods_id=c.goods_id ".
           " WHERE $sql_where AND rec_type = '" . CART_GENERAL_GOODS . "'";
    $row = $GLOBALS['db']->GetAll($sql);
		$arr = array();
		foreach($row AS $k=>$v)
		{
				$arr[$k]['goods_thumb']  =get_pc_url().'/'.get_image_path($v['goods_id'], $v['goods_thumb'], true);
        $arr[$k]['short_name']   = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
                                               sub_str($v['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $v['goods_name'];
				$arr[$k]['url']          = build_uri('goods', array('gid' => $v['goods_id']), $v['goods_name']);
				$arr[$k]['goods_number'] = $v['goods_number'];
				$arr[$k]['goods_name']   = $v['goods_name'];
				$arr[$k]['goods_price']  = price_format($v['goods_price']);
				$arr[$k]['rec_id']       = $v['rec_id'];
		}		
    $sql = 'SELECT SUM(goods_number) AS number, SUM(goods_price * goods_number) AS amount' .
           ' FROM ' . $GLOBALS['ecs']->table('cart') ." AS c ".
           " WHERE $sql_where AND rec_type = '" . CART_GENERAL_GOODS . "'";
    $row = $GLOBALS['db']->GetRow($sql);

    if ($row)
    {
        $number = intval($row['number']);
        $amount = floatval($row['amount']);
    }
    else
    {
        $number = 0;
        $amount = 0;
    }

    $str = sprintf($GLOBALS['_LANG']['cart_info'], $number, price_format($amount, false));

    return $str;
}

/**
 * 获取客服电话
 * @return type
 */
function insert_ecsmart_tel(){
    $sql = "select value from ".$GLOBALS['ecs']->table('ecsmart_shop_config')." where code = 'service_phone'";
    $tel = $GLOBALS['db']->getOne($sql);
    return $tel;
}

/**
 * 调用指定的广告位的广告
 *
 * @access  public
 * @param   integer $id     广告位ID
 * @param   integer $num    广告数量
 * @return  string
 */
function insert_ads($arr)
{
    $arr['num'] = intval($arr['num']);
    $arr['id'] = intval($arr['id']);
    static $static_res = NULL;

    $time = gmtime();
    if (!empty($arr['num']) && $arr['num'] != 1)
    {
        $sql  = 'SELECT a.ad_id, a.position_id, a.media_type, a.ad_link, a.ad_code, a.ad_name, p.ad_width, ' .
                    'p.ad_height, p.position_style, RAND() AS rnd ' .
                'FROM ' . $GLOBALS['ecs']->table('ecsmart_ad') . ' AS a '.
                'LEFT JOIN ' . $GLOBALS['ecs']->table('ecsmart_ad_position') . ' AS p ON a.position_id = p.position_id ' .
                "WHERE enabled = 1 AND start_time <= '" . $time . "' AND end_time >= '" . $time . "' ".
                    "AND a.position_id = '" . $arr['id'] . "' " .
                'ORDER BY rnd LIMIT ' . $arr['num'];
        $res = $GLOBALS['db']->GetAll($sql);
    }
    else
    {
        if ($static_res[$arr['id']] === NULL)
        {
            $sql  = 'SELECT a.ad_id, a.position_id, a.media_type, a.ad_link, a.ad_code, a.ad_name, p.ad_width, '.
                        'p.ad_height, p.position_style, RAND() AS rnd ' .
                    'FROM ' . $GLOBALS['ecs']->table('ecsmart_ad') . ' AS a '.
                    'LEFT JOIN ' . $GLOBALS['ecs']->table('ecsmart_ad_position') . ' AS p ON a.position_id = p.position_id ' .
                    "WHERE enabled = 1 AND a.position_id = '" . $arr['id'] .
                        "' AND start_time <= '" . $time . "' AND end_time >= '" . $time . "' " .
                    'ORDER BY rnd LIMIT 1';
            $static_res[$arr['id']] = $GLOBALS['db']->GetAll($sql);
        }
        $res = $static_res[$arr['id']];
    }
    $ads = array();
    $position_style = '';

    foreach ($res AS $row)
    {
        if ($row['position_id'] != $arr['id'])
        {
            continue;
        }
        $position_style = $row['position_style'];
        switch ($row['media_type'])
        {
            case 0: // 图片广告
                $src = (strpos($row['ad_code'], 'http://') === false && strpos($row['ad_code'], 'https://') === false) ?
                        DATA_DIR . "/afficheimg/$row[ad_code]" : $row['ad_code'];
                $ads[] = "<a href='affiche.php?ad_id=$row[ad_id]&amp;uri=" .urlencode($row["ad_link"]). "'
                target='_blank'><img src='$src' width='" .$row['ad_width']. "' height='$row[ad_height]'
                border='0' /></a>";
                break;
            case 1: // Flash
                $src = (strpos($row['ad_code'], 'http://') === false && strpos($row['ad_code'], 'https://') === false) ?
                        DATA_DIR . "/afficheimg/$row[ad_code]" : $row['ad_code'];
                $ads[] = "<object classid=\"clsid:d27cdb6e-ae6d-11cf-96b8-444553540000\" " .
                         "codebase=\"http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0\"  " .
                           "width='$row[ad_width]' height='$row[ad_height]'>
                           <param name='movie' value='$src'>
                           <param name='quality' value='high'>
                           <embed src='$src' quality='high'
                           pluginspage='http://www.macromedia.com/go/getflashplayer'
                           type='application/x-shockwave-flash' width='$row[ad_width]'
                           height='$row[ad_height]'></embed>
                         </object>";
                break;
            case 2: // CODE
                $ads[] = $row['ad_code'];
                break;
            case 3: // TEXT
                $ads[] = "<a href='affiche.php?ad_id=$row[ad_id]&amp;uri=" .urlencode($row["ad_link"]). "'
                target='_blank'>" .htmlspecialchars($row['ad_code']). '</a>';
                break;
        }
    }
    $position_style = 'str:' . $position_style;

    $need_cache = $GLOBALS['smarty']->caching;
    $GLOBALS['smarty']->caching = false;

    $GLOBALS['smarty']->assign('ads', $ads);
    $val = $GLOBALS['smarty']->fetch($position_style);

    $GLOBALS['smarty']->caching = $need_cache;

    return $val;
}

/**
 * 调用会员信息
 *
 * @access  public
 * @return  string
 */
function insert_member_info()
{
    $need_cache = $GLOBALS['smarty']->caching;
    $GLOBALS['smarty']->caching = false;

    if ($_SESSION['user_id'] > 0)
    {
        $GLOBALS['smarty']->assign('user_info', get_user_info());
    }
    else
    {
        if (!empty($_COOKIE['ECS']['username']))
        {
            $GLOBALS['smarty']->assign('ecs_username', stripslashes($_COOKIE['ECS']['username']));
        }
        $captcha = intval($GLOBALS['_CFG']['captcha']);
        if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2)) && gd_version() > 0)
        {
            $GLOBALS['smarty']->assign('enabled_captcha', 1);
            $GLOBALS['smarty']->assign('rand', mt_rand());
        }
    }
    $output = $GLOBALS['smarty']->fetch('library/member_info.lbi');

    $GLOBALS['smarty']->caching = $need_cache;

    return $output;
}
/**
 * 商家入驻首页调用会员信息
 *
 * @access  public
 * @return  string
 */
function insert_member_info1()
{
    $need_cache = $GLOBALS['smarty']->caching;
    $GLOBALS['smarty']->caching = false;

    if ($_SESSION['user_id'] > 0)
    {
        $GLOBALS['smarty']->assign('user_info', get_user_info());
    }
    else
    {
        if (!empty($_COOKIE['ECS']['username']))
        {
            $GLOBALS['smarty']->assign('ecs_username', stripslashes($_COOKIE['ECS']['username']));
        }
        $captcha = intval($GLOBALS['_CFG']['captcha']);
        if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2)) && gd_version() > 0)
        {
            $GLOBALS['smarty']->assign('enabled_captcha', 1);
            $GLOBALS['smarty']->assign('rand', mt_rand());
        }
    }
    $output = $GLOBALS['smarty']->fetch('library/member_info1.lbi');

    $GLOBALS['smarty']->caching = $need_cache;

    return $output;
}
/**
 * 调用评论信息
 *
 * @access  public
 * @return  string
 */
function insert_comments($arr)
{
    $need_cache = $GLOBALS['smarty']->caching;
    $need_compile = $GLOBALS['smarty']->force_compile;

    $GLOBALS['smarty']->caching = false;
    $GLOBALS['smarty']->force_compile = true;

    /* 验证码相关设置 */
    if ((intval($GLOBALS['_CFG']['captcha']) & CAPTCHA_COMMENT) && gd_version() > 0)
    {
        $GLOBALS['smarty']->assign('enabled_captcha', 1);
        $GLOBALS['smarty']->assign('rand', mt_rand());
    }
    $GLOBALS['smarty']->assign('username',     stripslashes($_SESSION['user_name']));
    $GLOBALS['smarty']->assign('email',        $_SESSION['email']);
    $GLOBALS['smarty']->assign('comment_type', $arr['type']);
    $GLOBALS['smarty']->assign('id',           $arr['id']);
    $cmt = assign_comment($arr['id'],          $arr['type']);
    $GLOBALS['smarty']->assign('comments',     $cmt['comments']);
    $GLOBALS['smarty']->assign('pager',        $cmt['pager']);


    $val = $GLOBALS['smarty']->fetch('library/comments_list.lbi');

    $GLOBALS['smarty']->caching = $need_cache;
    $GLOBALS['smarty']->force_compile = $need_compile;

    return $val;
}


/**
 * 调用商品购买记录
 *
 * @access  public
 * @return  string
 */
function insert_bought_notes($arr)
{
    $need_cache = $GLOBALS['smarty']->caching;
    $need_compile = $GLOBALS['smarty']->force_compile;

    $GLOBALS['smarty']->caching = false;
    $GLOBALS['smarty']->force_compile = true;

    /* 商品购买记录 */
    $sql = 'SELECT u.user_name, og.goods_number, oi.add_time, IF(oi.order_status IN (2, 3, 4), 0, 1) AS order_status ' .
           'FROM ' . $GLOBALS['ecs']->table('order_info') . ' AS oi LEFT JOIN ' . $GLOBALS['ecs']->table('users') . ' AS u ON oi.user_id = u.user_id, ' . $GLOBALS['ecs']->table('order_goods') . ' AS og ' .
           'WHERE oi.order_id = og.order_id AND ' . time() . ' - oi.add_time < 2592000 AND og.goods_id = ' . $arr['id'] . ' ORDER BY oi.add_time DESC LIMIT 5';
    $bought_notes = $GLOBALS['db']->getAll($sql);

    foreach ($bought_notes as $key => $val)
    {
        $bought_notes[$key]['add_time'] = local_date("Y-m-d G:i:s", $val['add_time']);
    }

    $sql = 'SELECT count(*) ' .
           'FROM ' . $GLOBALS['ecs']->table('order_info') . ' AS oi LEFT JOIN ' . $GLOBALS['ecs']->table('users') . ' AS u ON oi.user_id = u.user_id, ' . $GLOBALS['ecs']->table('order_goods') . ' AS og ' .
           'WHERE oi.order_id = og.order_id AND ' . time() . ' - oi.add_time < 2592000 AND og.goods_id = ' . $arr['id'];
    $count = $GLOBALS['db']->getOne($sql);


    /* 商品购买记录分页样式 */
    $pager = array();
    $pager['page']         = $page = 1;
    $pager['size']         = $size = 5;
    $pager['record_count'] = $count;
    $pager['page_count']   = $page_count = ($count > 0) ? intval(ceil($count / $size)) : 1;;
    $pager['page_first']   = "javascript:gotoBuyPage(1,$arr[id])";
    $pager['page_prev']    = $page > 1 ? "javascript:gotoBuyPage(" .($page-1). ",$arr[id])" : 'javascript:;';
    $pager['page_next']    = $page < $page_count ? 'javascript:gotoBuyPage(' .($page + 1) . ",$arr[id])" : 'javascript:;';
    $pager['page_last']    = $page < $page_count ? 'javascript:gotoBuyPage(' .$page_count. ",$arr[id])"  : 'javascript:;';

    $GLOBALS['smarty']->assign('notes', $bought_notes);
    $GLOBALS['smarty']->assign('pager', $pager);


    $val= $GLOBALS['smarty']->fetch('library/bought_notes.lbi');

    $GLOBALS['smarty']->caching = $need_cache;
    $GLOBALS['smarty']->force_compile = $need_compile;

    return $val;
}


/**
 * 调用在线调查信息
 *
 * @access  public
 * @return  string
 */
function insert_vote()
{
    $vote = get_vote();
    if (!empty($vote))
    {
        $GLOBALS['smarty']->assign('vote_id',     $vote['id']);
        $GLOBALS['smarty']->assign('vote',        $vote['content']);
    }
    $val = $GLOBALS['smarty']->fetch('library/vote.lbi');

    return $val;
}

/**
 * 调用入驻商申请
 */
function insert_apply_supplier($user){
	global $_CFG;
	//申请步骤
    $apply_info = array(0=>'one',1=>'two_1',2=>'two_2',3=>'three',4=>'ing',5=>'four',6=>'fail');
    
    $sql = "select * from ".$GLOBALS['ecs']->table('supplier')." where user_id = ".$user['id'].' limit 1';
    $supplier = $GLOBALS['db']->getRow($sql);
    
    if($supplier){
    	$shownum = ++$supplier['applynum'];
    }else{
    	$shownum = 0;
    }
    //要显示的阶段
    $shownum = (isset($_GET['shownum'])) ? intval($_GET['shownum']) : $shownum;
    
	if($supplier){
    	$shownum = ($supplier['status']<0) ? 6 : (($supplier['status']>0) ? 5 : $shownum);
    }
    
    if($shownum == 1){
    	$supplier_country = $supplier['country'] ?  $supplier['country'] : $_CFG['shop_country'];
		$GLOBALS['smarty']->assign('country_list',       get_regions());	
		$GLOBALS['smarty']->assign('province_list', get_regions(1, $supplier_country));
		$GLOBALS['smarty']->assign('city_list', get_regions(2, $supplier['province']));
		$GLOBALS['smarty']->assign('district_list', get_regions(3, $supplier['city']));
		$GLOBALS['smarty']->assign('supplier_country', $supplier_country);
		$company_type = explode("\n", str_replace("\r\n", "\n", $_CFG['company_type']));
		$GLOBALS['smarty']->assign('company_type', $company_type);
    }elseif($shownum == 3){
    	$sql="select rank_id,rank_name from ". $GLOBALS['ecs']->table('supplier_rank') ." order by sort_order";
		$supplier_rank=$GLOBALS['db']->getAll($sql);
		$GLOBALS['smarty']->assign('supplier_rank', $supplier_rank);
		$sql="select str_id,str_name from ". $GLOBALS['ecs']->table('street_category') ." where is_show=1 order by sort_order";
		$supplier_type=$GLOBALS['db']->getAll($sql);
		$GLOBALS['smarty']->assign('supplier_type', $supplier_type);
    }elseif($shownum == 5){
    	/**/
    	$supplier_country = $supplier['country'] ?  $supplier['country'] : $_CFG['shop_country'];
		$GLOBALS['smarty']->assign('country_list',       get_regions());	
		$GLOBALS['smarty']->assign('province_list', get_regions(1, $supplier_country));
		$GLOBALS['smarty']->assign('city_list', get_regions(2, $supplier['province']));
		$GLOBALS['smarty']->assign('district_list', get_regions(3, $supplier['city']));
		$GLOBALS['smarty']->assign('supplier_country', $supplier_country);
    	/* 供货商等级 */
		$sql="select rank_name from ". $GLOBALS['ecs']->table('supplier_rank') ." where rank_id = ".$supplier['rank_id'];
		$rank_name=$GLOBALS['db']->getOne($sql);
		$supplier['rank_name'] = $rank_name;
		/* 店铺类型 */
		 $sql="select str_name from ". $GLOBALS['ecs']->table('street_category') ." where str_id = ".$supplier['type_id'];
		$type_name=$GLOBALS['db']->getOne($sql);
		$supplier['type_name'] = $type_name;
		
		$GLOBALS['smarty']->assign('mydomain', $GLOBALS['ecs']->url());
    }

//    var_dump($supplier);die;
    //echo "<pre>";print_r($supplier);
    $GLOBALS['smarty']->assign('supplier',       $supplier);
    
    $val = $GLOBALS['smarty']->fetch('library/apply_'.$apply_info[$shownum].'.lbi');

    return $val;
}


/*jdy add 0818*/
/**
* 调用某商品的累积售出
*/
function insert_goods_sells($arr)
{
    $sql = 'SELECT SUM(goods_number) AS number ' .
           ' FROM ' . $GLOBALS['ecs']->table('order_goods') ." AS og , " . $GLOBALS['ecs']->table('order_info') ." AS  o ".
           " WHERE og.order_id = o.order_id and og.goods_id=".$arr['goods_id'];
    $row = $GLOBALS['db']->GetRow($sql);
    if ($row)
    {
        $number = intval($row['number']);
    }
    else
    {
        $number = 0;
    }
    return $number;
}
/* 代码增加_start By www.68ecshop.com */
/**
 * 调用评论信息
 *
 * @access  public
 * @return  string
 */
function insert_question($arr)
{
    $need_cache = $GLOBALS['smarty']->caching;
    $need_compile = $GLOBALS['smarty']->force_compile;

    $GLOBALS['smarty']->caching = false;
    $GLOBALS['smarty']->force_compile = true;

    /* 验证码相关设置 */
    if ((intval($GLOBALS['_CFG']['captcha']) & CAPTCHA_QUESTION) && gd_version() > 0)
    {
        $GLOBALS['smarty']->assign('enabled_captcha_question', 1);
        $GLOBALS['smarty']->assign('rand', mt_rand());
    }
    $GLOBALS['smarty']->assign('username',     stripslashes($_SESSION['user_name']));
    $GLOBALS['smarty']->assign('email',        $_SESSION['email']);
    $GLOBALS['smarty']->assign('id',           $arr['id']);
    $cmt = assign_question($arr['id']);
    $GLOBALS['smarty']->assign('question_list',     $cmt['comments']);
    $GLOBALS['smarty']->assign('pager',        $cmt['pager']);


    $val = $GLOBALS['smarty']->fetch('library/question_list.lbi');

    $GLOBALS['smarty']->caching = $need_cache;
    $GLOBALS['smarty']->force_compile = $need_compile;

    return $val;
}
/* 代码增加_end By www.68ecshop.com */

/*
 * 调用店铺logo与首页 
 */
function insert_supplier_list(){
	
	$need_cache = $GLOBALS['smarty']->caching;
    $need_compile = $GLOBALS['smarty']->force_compile;
    
	$sql = "SELECT ssc.value,ssc.supplier_id,ssc.code FROM ".$GLOBALS['ecs']->table('supplier')." as s,".$GLOBALS['ecs']->table('supplier_shop_config')." as ssc WHERE s.`status` = 1 AND ssc.supplier_id = s.supplier_id AND code in('shop_name','shop_logo') limit 36";
    $query = $GLOBALS['db']->query($sql);
    $ret = array();
    while ($row = $GLOBALS['db']->fetch_array($query)){
    	$row['value'] = empty($row['value']) ? '/data/supplier/dianpu.jpg' : $row['value'];
    	$ret[$row['supplier_id']][$row['code']] = $row['value'];
    	$ret[$row['supplier_id']]['shop_url'] = 'supplier.php?suppId='.$row['supplier_id'];
    }
    $num = 24 - count($ret);
    if($num > 0){
    	for($i=0;$i<$num;$i++){
    		$ret[] = array('shop_name'=>'虚位以待','shop_logo'=>'/data/supplier/ad.jpg','shop_url'=>'#');
    	}
    }
    
    $newret = array_chunk($ret,6,true);

    
     $GLOBALS['smarty']->assign('supplier_logo',        $newret);
     
     $val = $GLOBALS['smarty']->fetch('library/index_stores.lbi');

    $GLOBALS['smarty']->caching = $need_cache;
    $GLOBALS['smarty']->force_compile = $need_compile;

    return $val;
}


/**
* 获取不同商家的运费方式
**/
function insert_get_shop_shipping($arr){
	global $db,$ecs;

	$need_cache = $GLOBALS['smarty']->caching;
    $need_compile = $GLOBALS['smarty']->force_compile;

	$order = $_SESSION['flow_order'];//获取订单信息
	
	$suppid = intval($arr['suppid']);
	$consignee = $arr['consignee'];
	$flow_type = $arr['flow_type'];
	$region            = array($consignee['country'], $consignee['province'], $consignee['city'], $consignee['district']);
	
	$shipping_list = available_shipping_list($region,$suppid);
	$cart_weight_price = cart_weight_price2($flow_type,$suppid);

	if(count($shipping_list)>0){
		//获取当前地址下所有的配送方式
		$shipping_id = array();
		foreach($shipping_list as $v){
			$shipping_id[] = $v['shipping_id'];
		}
		$i=0;
		$sql_where = $_SESSION['user_id']>0 ? "user_id='". $_SESSION['user_id'] ."' " : "session_id = '" . SESS_ID . "' AND user_id=0 ";
        if($_SESSION['sel_cartgoods']){
            $sql_plus = " AND rec_id in (".$_SESSION['sel_cartgoods'].") ";
        }
		$sql = 'SELECT count(*) FROM ' . $ecs->table('cart') . " WHERE $sql_where AND `extension_code` != 'package_buy' AND `is_shipping` = 0 ".$sql_plus; //jx
		$shipping_count = $db->getOne($sql);
        $order['shipping_pay'][$suppid] = 0;
		foreach($shipping_list as $key=>$val){
			
			
			// 判断如果为门店自提，那么收货人的所在地区有自提点则显示此配送方式，否则不显示此配送方式
			$shipping_code = $shipping_list[$key]['shipping_code'];
			if($shipping_code == 'pups')
			{
				$pickinfo = get_pickup_info(intval($consignee['city']), $suppid);
				
				if(empty($pickinfo) || $pickinfo == false)
				{
					unset($shipping_list[$key]);
					continue;
				}
			}
			
			$shipping_cfg = unserialize_config($val['configure']);
			$shipping_fee = ($shipping_count == 0 && $cart_weight_price['free_shipping'] == 1) ? 0 : shipping_fee($val['shipping_code'], unserialize($val['configure']),
			$cart_weight_price['weight'], $cart_weight_price['amount'], $cart_weight_price['number']);

			$shipping_list[$key]['format_shipping_fee'] = price_format($shipping_fee, false);
			$shipping_list[$key]['shipping_fee']        = $shipping_fee;
			$shipping_list[$key]['free_money1']          = $shipping_cfg['free_money'];
			$shipping_list[$key]['free_money']          = price_format($shipping_cfg['free_money'], false);
			$shipping_list[$key]['insure_formated']     = strpos($val['insure'], '%') === false ?
				price_format($val['insure'], false) : $val['insure'];

			$selected = '';
			if($i==0 && !in_array($order['shipping_pay'][$suppid],$shipping_id)){
				$selected = 'checked';
				$order['shipping_pay'][$suppid] = $val['shipping_id'];//记录第一个被选中的配送方式的id
			}
			if(isset($order['shipping_pay'][$suppid]) && intval($order['shipping_pay'][$suppid]) == $val['shipping_id'] && in_array($order['shipping_pay'][$suppid],$shipping_id)){
				$selected = 'checked';
			}
			$shipping_list[$key]['selected'] = $selected;

			if(!empty($selected)){
				$GLOBALS['smarty']->assign('checkedid',   $shipping_list[$key]['shipping_id']);
			}

			// 当前的配送方式是否支持保价 
			if ($val['shipping_id'] == $order['shipping_id'])
			{
				$insure_disabled = ($val['insure'] == 0);
				$cod_disabled    = ($val['support_cod'] == 0);
			}
		}
	}

	$GLOBALS['smarty']->assign('suppid',   $suppid);
	$GLOBALS['smarty']->assign('shipping_list',   $shipping_list);
    $GLOBALS['smarty']->assign('insure_disabled', $insure_disabled);
    $GLOBALS['smarty']->assign('cod_disabled',    $cod_disabled);
    
   $GLOBALS['smarty']->caching = $need_cache;
   $GLOBALS['smarty']->force_compile = $need_compile;
   $val = $GLOBALS['smarty']->fetch('library/shipping_list.lbi');
	$_SESSION['flow_order'] = $order;
	return $val;
}

/**
* 获得指定分类下的子分类
*
* @access  public
* @param   integer     $cat_id     分类编号

* @return  array 

*www.68ecshop.com 

*/
function get_children_tree($cat_id)
{
     if ($cat_id >0 )
    {
        $sql = 'SELECT count(*) FROM ' . $GLOBALS['ecs']->table('category') . " WHERE parent_id = '$cat_id'";
        //$cot = $GLOBALS['db']->getOne($sql);        
        if ($GLOBALS['db']->getOne($sql))
        {
            // 获取当前分类名及其子类
            $sql = 'SELECT a.cat_id, a.cat_name, a.sort_order AS parent_order, a.cat_id, ' .
                    'b.cat_id AS child_id, b.cat_name AS child_name, b.sort_order AS child_order ' .
                'FROM ' . $GLOBALS['ecs']->table('category') . ' AS a ' .
                'LEFT JOIN ' . $GLOBALS['ecs']->table('category') . ' AS b ON b.parent_id = a.cat_id ' .
                "WHERE a.cat_id = '$cat_id' ORDER BY parent_order ASC, a.cat_id ASC, child_order ASC";
        }        
        else
        {
            $sql = 'SELECT parent_id FROM ' . $GLOBALS['ecs']->table('category') . " WHERE cat_id = '$cat_id'";
            $parent_id = $GLOBALS['db']->getOne($sql);
            if ($parent_id > 0)
            {
                //获取当前分类、兄弟及其父类
                $sql = 'SELECT a.cat_id, a.cat_name, b.cat_id AS child_id, b.cat_name AS child_name, b.sort_order ' .
                    'FROM ' . $GLOBALS['ecs']->table('category') . ' AS a ' .
                    'LEFT JOIN ' . $GLOBALS['ecs']->table('category') . ' AS b ON b.parent_id = a.cat_id ' .
                    "WHERE b.parent_id = '$parent_id' ORDER BY sort_order ASC";
            }
            else
            {
                //获取当前分类
                $sql = 'SELECT a.cat_id, a.cat_name FROM '
                        . $GLOBALS['ecs']->table('category') . ' AS a ' .
                        "WHERE a.cat_id = '$cat_id'";
            }
        }
        
        
        $res = $GLOBALS['db']->getAll($sql);


    $cat_arr = array();
    foreach ($res AS $row)
    {
        $cat_arr[$row['cat_id']]['id']   = $row['cat_id'];
        $cat_arr[$row['cat_id']]['name'] = $row['cat_name'];
        $cat_arr[$row['cat_id']]['url']  = build_uri('category', array('cid' => $row['cat_id']), $row['cat_name']);


        if ($row['child_id'] != NULL) {
            $cat_arr[$row['cat_id']]['children'][$row['child_id']]['id']   = $row['child_id'];
            $cat_arr[$row['cat_id']]['children'][$row['child_id']]['name'] = $row['child_name'];
            $cat_arr[$row['cat_id']]['children'][$row['child_id']]['url']  = build_uri('category', array('cid' => $row['child_id']), $row['child_name']);
        }
    }


    return $cat_arr;
    }    
}


function insert_add_url_uid(){
    $user_id = $_SESSION['user_id'];
    if($user_id)
    {
        $url = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $url = str_replace('//', '/', $url);
        $stru = strstr($url, 'u=');
        if(empty($stru))
        {
            if(strstr($url, '?')){
                $url = 'http://'.str_replace('?', '?u='.$user_id.'&', $url);
            }
            else
            {
                $url = 'http://'.$url.'?u='.$user_id;
            }
            echo "<script>window.location='$url'</script>";
            exit();
        }
    }
}

/*
 * 微信分享 朋友、朋友圈 基础配置
 *
 */
function insert_share(){
    $weixin_info = $GLOBALS['db']->getRow("SELECT * FROM ".$GLOBALS['ecs']->table('weixin_config'));
    if($weixin_info['title'] && $weixin_info['appid'] && $weixin_info['appsecret']){
        // //判断是否微信
        $is_weixin = is_weixin();

        if($is_weixin){
        	//判断是否登陆
        	if(!empty($_SESSION['user_id'])){
        		$GLOBALS['smarty']->assign('is_login', '1');
        	}
        	
        	if($is_weixin){
        		$GLOBALS['smarty']->assign('is_weixin', '1');
        	}
            require_once "wxjs/jssdk.php";
			$jssdk = new JSSDK($weixin_info['appid'], $weixin_info['appsecret']);
			$signPackage = $jssdk->GetSignPackage();

            $need_cache = $GLOBALS['smarty']->caching;
            $GLOBALS['smarty']->caching = false;
            $GLOBALS['smarty']->assign('weixin_info', $weixin_info);
            $GLOBALS['smarty']->assign('signPackage', $signPackage);
			$GLOBALS['smarty']->assign ( 'is_thumbnail', $weixin_info['is_thumbnail'] );
			$GLOBALS['smarty']->assign ( 'weixin_logo', $weixin_info['weixin_logo'] );
			
			
			$shop_name = $weixin_info['title'];
			
			$web_url ='http://'.$_SERVER['HTTP_HOST'];
			
			if(preg_match('/goods.php/i', $_SERVER['REQUEST_URI'])){//处理商品详情页分享
			
			    $goods_id = intval($_GET['id']);
			
				$sql = "select * from ". $GLOBALS['ecs']->table('goods') ." where goods_id=".$goods_id;
			
	            $row = $GLOBALS['db']->getRow($sql);
				 
			    $goods_price = $row['shop_price'];
			   
			    $sharemsg = sprintf($weixin_info['sharemsg'],$shop_name,$goods_price);//后台自定义描述
				
				$share_tiele = $row['goods_name'];
				
			  if( $weixin_info['is_thumbnail'] == 0){
			 
			    $share_img = $web_url."/".$row['goods_thumb'];
				
        		}else{
				
				$share_img =  $web_url."/mobile/".$weixin_info['weixin_logo'];
				 
				}
				}elseif(preg_match('/tuiguang.php/i', $_SERVER['REQUEST_URI']) || preg_match('/article.php/i', $_SERVER['REQUEST_URI'])){//单品推广/文章详情
			
			    $article_id = intval($_GET['id']);
			
			    $sql = "select * from ". $GLOBALS['ecs']->table('article') ." where article_id=".$article_id;
			
	            $row = $GLOBALS['db']->getRow($sql);
				
				if(!empty($row['description'])){
				
				       $sharemsg = $row['description'];//描述
				
	                  }else{
		
		             $sharemsg = $GLOBALS['_CFG']['shop_desc'];//描述
					 
	                  }
				 
				
				$share_tiele = $row['title'];//标题
				
			  if( $weixin_info['is_thumbnail'] == 0 && !empty($row['file_url'])){//缩略图
			 
			    $share_img = $web_url."/mobile/".$row['file_url'];
				
        		}else{
				
				$share_img =  $web_url."/mobile/".$weixin_info['weixin_logo'];
				 
				}
			
			}elseif(preg_match('/article_detail.php/i', $_SERVER['REQUEST_URI'])){//文章广告植入系统
			
			    $article_id = intval($_GET['id']);
			
			    $sql = "select * from ". $GLOBALS['ecs']->table('article') ." where article_id=".$article_id;
			
	            $row = $GLOBALS['db']->getRow($sql);
				
				if(!empty($row['description'])){
				
				       $sharemsg = $row['description'];//描述
				
	                  }else{
		
		             $sharemsg = "转发文章也能赚钱？！不信您也来试试！！";//描述
					 
	                  }
				 
				
				$share_tiele = $row['title'];//标题
				
			  if( $weixin_info['is_thumbnail'] == 0 && !empty($row['file_url'])){//缩略图
			 
			    $share_img = $web_url."/mobile/".$row['file_url'];
				
        		}else{
				
				$share_img =  $web_url."/mobile/".$weixin_info['weixin_logo'];
				 
				}
			
			}else{
			
			$sharemsg = $_CFG['shop_desc'];
			
			$sql = "select value from ". $GLOBALS['ecs']->table('ecsmart_shop_config') ." where code= 'shop_name'";
			
	        $share_tiele = $GLOBALS['db']->getOne($sql);
			
			$sql = "select value from ". $GLOBALS['ecs']->table('ecsmart_shop_config') ." where code= 'shop_desc'";
			
	        $sharemsg = $GLOBALS['db']->getOne($sql);
			
			$share_img =  $web_url."/mobile/".$weixin_info['weixin_logo'];
			
			}
			
		    $user_id = $_SESSION['user_id'];
			 
			$sql = "select * from ". $GLOBALS['ecs']->table('weixin_user') ." where ecuid= '$user_id'";
			
	        $ret = $GLOBALS['db']->getRow($sql);
			
			$guide_qrcode =  $web_url."/mobile/".$weixin_info['guide_qrcode'];
			//echo $_SESSION['user_id'].'-'.$_SESSION['show_guide_qrcode'];
			if( $weixin_info['open_guide'] == 1 && $ret['isfollow'] == 0  && $_SESSION['user_id'] && empty($_SESSION['show_guide_qrcode']) ){//是否开启未关注用户引导关注二维码
			
			$guide_qrcode =  $web_url."/mobile/".$weixin_info['guide_qrcode'];
			
			
			$GLOBALS['smarty']->assign('open_guide',          1);
			
			$GLOBALS['smarty']->assign('guide_qrcode',           $guide_qrcode);
			$_SESSION['show_guide_qrcode']=1;
			
			}
			
			$GLOBALS['smarty']->assign('share_tiele',           $share_tiele);
			
			$GLOBALS['smarty']->assign('share_img',           $share_img);
			
			$GLOBALS['smarty']->assign('sharemsg',           $sharemsg);
			
            $output = $GLOBALS['smarty']->fetch('library/share.lbi');

            $GLOBALS['smarty']->caching = $need_cache;
			
            return $output;
        }

    }
}

function insert_new_share($arr){
	$share_type = trim($arr['share_type']) ? trim($arr['share_type']) : 'default';
	$weixin_info = $GLOBALS['db']->getRow("SELECT * FROM ".$GLOBALS['ecs']->table('weixin_config'));
    if($weixin_info['title'] && $weixin_info['appid'] && $weixin_info['appsecret']){
		$is_weixin = is_weixin();
        if($is_weixin){
        	//判断是否登陆
        	if(!empty($_SESSION['user_id'])){
        		$GLOBALS['smarty']->assign('is_login', '1');
        	}
        	if($is_weixin){
        		$GLOBALS['smarty']->assign('is_weixin', '1');
        	}
            require_once "wxjs/jssdk.php";
			$jssdk = new JSSDK($weixin_info['appid'], $weixin_info['appsecret']);
			$signPackage = $jssdk->GetSignPackage();
			$need_cache = $GLOBALS['smarty']->caching;
            $GLOBALS['smarty']->caching = false;
            $GLOBALS['smarty']->assign('weixin_info', $weixin_info);
            $GLOBALS['smarty']->assign('signPackage', $signPackage);
			switch($share_type){
				case "goods":
					$share_info = generate_share_info('goods');
					break;
				case "erweima":
					$share_info = generate_share_info('erweima');
					break;
				default:
			}
			
			$GLOBALS['smarty']->assign('share_info', $share_info);
            $output = $GLOBALS['smarty']->fetch('library/new_share.lbi');
            $GLOBALS['smarty']->caching = $need_cache;
            return $output;
		}
	}
}

//根据分享类型和参数,返回分享的数据
function generate_share_info($type,$param = array()){
	switch($type){
		case "goods":
			$goods_id = intval($_REQUEST['id']);
			$url = '//'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?id='.$goods_id."&parent_id=".$_SESSION['user_id'];
			$goods_info = $GLOBALS['db']->getRow("SELECT goods_name,goods_brief,goods_thumb FROM ".$GLOBALS['ecs']->table('goods')." WHERE goods_id=".$goods_id);
			$share_info['title'] = $goods_info['goods_name'];
			$share_info['msg'] = $goods_info['goods_brief'];
			$share_info['url'] = $url;
			$share_info['img'] = 'http://'.$_SERVER['HTTP_HOST']."/".$goods_info['goods_thumb'];
			return $share_info;
			break;
		case "erweima":
			$mysupllierId=$GLOBALS['db']->getOne("SELECT supplierId FROM ".$GLOBALS['ecs']->table('users')." WHERE user_id=".$_SESSION['user_id']);
			if($mysupllierId==0){
				$config = unserialize($GLOBALS['_CFG']['affiliate_supplier']);
				empty($config) && $config = array();
				$mysupllierId=$config['config']['default_reg_supplier_id'];
			}
			$selfUrl='http://' . $_SERVER ['HTTP_HOST'].'/mobile/supplier_qrcode.php?u='.$_SESSION['user_id'].'&suppId='.$mysupllierId.'&erweima_type=1';
			$user_info = $GLOBALS['db']->getRow("SELECT user_name,headimg FROM ".$GLOBALS['ecs']->table('users')." WHERE user_id=".$_SESSION['user_id']);
			//转发内容未定,先搁置
			$share_info['title'] = '我是'.$user_info['user_name'];
			$share_info['msg'] = '快来加入臻小美,拥有你的专属二维码';
			$share_info['url'] = $selfUrl;
			if(strpos($user_info['headimg'],'http') ===false && strpos($user_info['headimg'],'https') ===false ){
				$user_info['headimg'] = 'http://' . $_SERVER ['HTTP_HOST']."/mobile/".$user_info['headimg'];
			}
			$share_info['img'] = $user_info['headimg'];
			return $share_info;
			break;
		default:
			return;
	}
}


function is_weixin()
{
	$useragent = addslashes($_SERVER['HTTP_USER_AGENT']);
	if(strpos($useragent, 'MicroMessenger') === false && strpos($useragent, 'Windows Phone') === false )
	{
		return false;
	}
	else
	{
		return true;
	}
}



function insert_skill_advance($goods_id){
	
	$need_cache = $GLOBALS['smarty']->caching;
    $need_compile = $GLOBALS['smarty']->force_compile;
	$goods=get_goods_info($goods_id['id']);
	 $GLOBALS['smarty']->assign("goods_id",$goods_id['id']);
	 $GLOBALS['smarty']->assign("goods",$goods);
     $GLOBALS['smarty']->assign('promote_end_time',   $goods['gmt_end_time']);
     $val = $GLOBALS['smarty']->fetch('library/skill_advance.lbi');

    $GLOBALS['smarty']->caching = $need_cache;
    $GLOBALS['smarty']->force_compile = $need_compile;

    return $val;	
}


/**
 * 调用会员信息
 *
 * @access  public
 * @return  string
 */
function insert_supplier_stock_id()
{
    $need_cache = $GLOBALS['smarty']->caching;
    $GLOBALS['smarty']->caching = false;
	$GLOBALS['smarty']->assign('supplier_stock', intval($_GET['supplier_id']));
    $output = $GLOBALS['smarty']->fetch('library/insert_stock_id.lbi');
    $GLOBALS['smarty']->caching = $need_cache;
    return $output;
}

function insert_guide_qrcode(){
	$need_cache = $GLOBALS['smarty']->caching;
    $GLOBALS['smarty']->caching = false;
	 $is_weixin = is_weixin();
	 if($is_weixin){
        	$GLOBALS['smarty']->assign('is_weixin', '1');
			$web_url ='http://'.$_SERVER['HTTP_HOST'];
			$weixin_info = $GLOBALS['db']->getRow("SELECT open_guide,guide_qrcode FROM ".$GLOBALS['ecs']->table('weixin_config'));

			$sql = "select isfollow from ". $GLOBALS['ecs']->table('weixin_user') ." where ecuid= '".intval($_SESSION['user_id'])."'";
			$ret = $GLOBALS['db']->getRow($sql);

			if($weixin_info['open_guide'] == 1 && $_SESSION['user_id']>0 && $ret['isfollow'] == 0){
				$guide_qrcode =  $web_url."/".$weixin_info['guide_qrcode'];
				$GLOBALS['smarty']->assign('guide_qrcode',$guide_qrcode);
				$GLOBALS['smarty']->assign('open_guide',1);
			}else{
				$GLOBALS['smarty']->assign('open_guide',0);
			}
			
     }else{
		$GLOBALS['smarty']->assign('is_weixin', '0');
		$GLOBALS['smarty']->assign('open_guide',0);
	 }
	$output = $GLOBALS['smarty']->fetch('library/guide_qrcode.lbi');
    $GLOBALS['smarty']->caching = $need_cache;
    return $output;
}

function insert_supplier_nav()
{
    $need_cache = $GLOBALS['smarty']->caching;
    $GLOBALS['smarty']->caching = false;
	$opusnum = $GLOBALS['db']->getOne("SELECT COUNT(a.id) FROM ".$GLOBALS['ecs']->table('technican_opus')." a LEFT JOIN ".$GLOBALS['ecs']->table('supplier')." b ON a.supplier_id = b.supplier_id WHERE a.supplier_id = ".intval($_GET['suppId']));
	$GLOBALS['smarty']->assign('opusnum', $opusnum);
    $output = $GLOBALS['smarty']->fetch('library/supplier_nav.lbi');
    $GLOBALS['smarty']->caching = $need_cache;
    return $output;
}


function insert_service(){
	$need_cache = $GLOBALS['smarty']->caching;
    $need_compile = $GLOBALS['smarty']->force_compile;
	
	if($GLOBALS['_CFG']['qq'] != ""){
        $service = "http://wpa.qq.com/msgrd?v=3&uin=".$GLOBALS['_CFG']['qq']."&site=qq&menu=yes&from=message&isappinstalled=0";
    }else if($GLOBALS['_CFG']['ww'] != ""){
        $service = "http://p.qiao.baidu.com/cps/chat?siteId=12825575&amp;userId=24417727";
    }else if($GLOBALS['_CFG']['wx'] != ""){
        $wxUrl = "<img width=100% src=".$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].'/'.$GLOBALS['_CFG']['wx']."><br><br><div width=100%><div width=100% class=qrcode-descrption align=center>长按识别二维码</div><div width=100% class=qrcode-descrption align=center>加客服微信了解更多内容</div></div>";
        $service = "javascript:layer.open({type:1,title:'客服微信',content:'".$wxUrl."',offset: ['100px', '0px']});";
    }else{
        $service = "javascript:;";
    }
	$GLOBALS['smarty']->assign('service', $service);
    $val = $GLOBALS['smarty']->fetch('library/footer_service.lbi');
    $GLOBALS['smarty']->caching = $need_cache;
    $GLOBALS['smarty']->force_compile = $need_compile;
    return $val;	
}

function insert_technican_schedul(){
	$need_cache = $GLOBALS['smarty']->caching;
    $need_compile = $GLOBALS['smarty']->force_compile;
	//当前内容不进行缓存
	$GLOBALS['smarty']->caching = false;
	$GLOBALS['smarty']->force_compile = false;
	$goods_id = intval($_REQUEST['id']);
	$goods = $GLOBALS['db']->getRow('select is_service,supplier_id from ' . $GLOBALS['ecs']->table('goods') . ' where goods_id=' . $goods_id);
	$is_ipc_shopping =  $GLOBALS['db']->getOne('select is_ipc_shopping from ' . $GLOBALS['ecs']->table('supplier') . ' where supplier_id=' . $goods['supplier_id']);
	if($is_ipc_shopping == 2 && $goods['is_service'] == 1){
		//技师店铺额服务商品，显示该技师的行程
		include_once(ROOT_PATH. "includes/lib_order.php");
		$future_schedul_list = get_future_schedul($goods['supplier_id']);
		$GLOBALS['smarty']->assign('future_schedul_list',$future_schedul_list);
	}
	
    $val = $GLOBALS['smarty']->fetch('library/technican_schedul.lbi');
    $GLOBALS['smarty']->caching = $need_cache;
    $GLOBALS['smarty']->force_compile = $need_compile;
    return $val;
}


function insert_card_item_goods(){
	$need_cache = $GLOBALS['smarty']->caching;
    $need_compile = $GLOBALS['smarty']->force_compile;
	$supplier_id = intval($_REQUEST['suppId']);
	$sql = "SELECT promote_price,promote_start_date,promote_end_date,goods_id,exclusive,goods_name_style,goods_name,goods_brief,market_price,goods_thumb,goods_img FROM ".$GLOBALS['ecs']->table('goods')."   WHERE item_id>0 AND supplier_id=".$supplier_id." AND is_on_sale = 1 AND is_alone_sale = 1 AND is_delete = 0 AND is_virtual = 0 ORDER BY sort_order DESC "; 
	$result = $GLOBALS['db']->getAll($sql);
	
	$goods = array();
	if($result){
		foreach ($result AS $idx => $row)
        {
            if ($row['promote_price'] > 0)
            {
                $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                $goods[$idx]['promote_price'] = $promote_price > 0 ? price_format($promote_price) : '';
            }
            else
            {
                $goods[$idx]['promote_price'] = '';
            }
            $final_price = get_final_price($row['goods_id'], 1, true, array());
            $goods[$idx]['final_price']     = price_format($final_price);
            $goods[$idx]['is_exclusive']  = is_exclusive($row['exclusive'],$final_price);
            $goods[$idx]['id']           = $row['goods_id'];
            $goods[$idx]['name']         = $row['goods_name'];
            $goods[$idx]['brief']        = $row['goods_brief'];
            $goods[$idx]['brand_name']   = isset($goods_data['brand'][$row['goods_id']]) ? $goods_data['brand'][$row['goods_id']] : '';
            $goods[$idx]['goods_style_name']   = add_style($row['goods_name'],$row['goods_name_style']);
            $goods[$idx]['short_name']   = $GLOBALS['_CFG']['goods_name_length'] > 0 ?sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
            $goods[$idx]['short_style_name']   = add_style($goods[$idx]['short_name'],$row['goods_name_style']);
            $goods[$idx]['market_price'] = price_format($row['market_price']);
            $goods[$idx]['shop_price']   = price_format($row['shop_price']);
            $goods[$idx]['thumb']        = get_pc_url().'/'.get_image_path($row['goods_id'], $row['goods_thumb'], true);
            $goods[$idx]['goods_img']    = get_pc_url().'/'.get_image_path($row['goods_id'], $row['goods_img']);
            $goods[$idx]['url']          = build_uri('goods', array('gid' => $row['goods_id']), $row['goods_name'])."&supplier_id=".$_GET['suppId'];
        }
	}
	$GLOBALS['smarty']->assign('card_item_goods',$goods);
    $val = $GLOBALS['smarty']->fetch('library/card_item_goods.lbi');
    $GLOBALS['smarty']->caching = $need_cache;
    $GLOBALS['smarty']->force_compile = $need_compile;
    return $val;
}

?>