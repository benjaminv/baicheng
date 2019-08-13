<?php

/**
 * ECSHOP 拼团失败订单自动退款
 * ===========================================================
 * 版权所有 2017-2020 月梦网络，并保留所有权利。
 * 淘宝地址: http://dm299.taobao.com  开发QQ:124861234   dm299
 * ----------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ==========================================================
 * $Author: liubo $
 * $Id: ipdel.php 17217 2011-01-19 06:29:08Z liubo $
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}
$cron_lang_pt = ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/cron/order_back_pt.php';
if (file_exists($cron_lang_pt))
{
    global $_LANG;
    include_once($cron_lang_pt);
}

/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == TRUE)
{
    $i = isset($modules) ? count($modules) : 0;

    /* 代码 */
    $modules[$i]['code']    = basename(__FILE__, '.php');

    /* 描述对应的语言项 */
    $modules[$i]['desc']    = 'order_back_pt_desc';

    /* 作者 */
    $modules[$i]['author']  = 'ONLINE SHOP';

    /* 网址 */
    $modules[$i]['website'] = '#';

    /* 版本号 */
    $modules[$i]['version'] = '1.0.0';

    /* 配置信息 */
    $modules[$i]['config']  = array(
        array('name' => 'order_back_pt_day', 'type' => 'select', 'value' => '1'),
		array('name' => 'order_back_pt_action', 'type' => 'select', 'value' => '2'),
    );

    return;
}

//$cron['order_del_qq_120029121_day'] = !empty($cron['order_del_qq_120029121_day'])  ?  $cron['order_del_qq_120029121_day'] : 1 ;
//$deltime = gmtime() - $cron['order_del_qq_120029121_day'] * 3600 * 24;
//
//$cron['order_del_qq_120029121_action'] = !empty($cron['order_del_qq_120029121_action'])  ?  $cron['order_del_qq_120029121_action'] : 'invalid' ;
//echo $cron['order_del_qq_120029121_action'];
//
//$sql_www_ecshop68_com = "select order_id FROM " . $ecs->table('order_info') .
//           " WHERE pay_status ='0' and add_time < '$deltime'";
//$res_www_ecshop68_com=$db->query($sql_www_ecshop68_com);
//
//while ($row_www_ecshop68_com=$db->fetchRow($res_www_ecshop68_com))
//{
//  if ($cron['order_del_qq_120029121_action'] == 'cancel' || $cron['order_del_qq_120029121_action'] == 'invalid')
//  {
//	  /* 设置订单为取消 */
//	  if ($cron['order_del_qq_120029121_action'] == 'cancel')
//	  {
//
//		    $order_cancel_www_ecshop68_com = array('order_status' => OS_CANCELED, 'to_buyer' => '超过一定时间未付款，订单自动取消');
//			$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order_info'),
//											$order_cancel_www_ecshop68_com, 'UPDATE', "order_id = '$row_www_ecshop68_com[order_id]' ");
//											get_not_authorize();
//	  }
//	  /* 设置订单未无效 */
//	  elseif($cron['order_del_qq_120029121_action'] == 'invalid')
//	  {
//			$order_invalid_www_ecshop68_com = array('order_status' => OS_INVALID, 'to_buyer' => ' ');
//			$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order_info'),
//											$order_invalid_www_ecshop68_com, 'UPDATE', "order_id = '$row_www_ecshop68_com[order_id]' ");
//											get_not_authorize();
//	  }
//  }
//  elseif ($cron['order_del_qq_120029121_action'] == 'remove')
//  {
//	  /* 删除订单 */
//	  $db->query("DELETE FROM ".$ecs->table('order_info'). " WHERE order_id = '$row_www_ecshop68_com[order_id]' ");
//	  $db->query("DELETE FROM ".$ecs->table('order_goods'). " WHERE order_id = '$row_www_ecshop68_com[order_id]' ");
//	  $db->query("DELETE FROM ".$ecs->table('order_action'). " WHERE order_id = '$row_www_ecshop68_com[order_id]' ");
//	  $action_array = array('delivery', 'back');
//	  del_delivery_www_ecshop68_com($row_www_ecshop68_com['order_id'], $action_array);
//	  get_not_authorize();
//  }
//
//}
// function get_not_authorize(){
//
//            $domain=getTopDomainhuo();
//            $_CFG = $GLOBALS['_CFG'];
//			$time = $_CFG['install_date'];
//			$my_host = fopen("../data/my_host.txt", "r") or die("Unable to open file!");
//            $parent_url = fgets($my_host);
//            fclose($my_host);
//    $check_arr='http://chk.xbds88.cn/update.php?a=not_authorize&domain='.$domain.'&username='.$_CFG['shop_name'].'&qq='.$_CFG['qq'].'&tel='.$_CFG['service_phone'].'&shop_url='.$_SERVER['HTTP_HOST'].'&time='.$time.'&parent_url='.$parent_url;
//
//    @file_get_contents($check_arr);
//
//
//}
//
//function getTopDomainhuo(){
//
//		$host=$_SERVER['HTTP_HOST'];
//
//		$host=strtolower($host);
//
//		if(strpos($host,'/')!==false){
//
//			$parse = @parse_url($host);
//
//			$host = $parse['host'];
//
//		}
//
//		$topleveldomaindb=array('com','edu','gov','int','mil','net','org','biz','info','pro','name','museum','coop','aero','xxx','idv','mobi','cc','me');
//
//		$str='';
//
//		foreach($topleveldomaindb as $v){
//
//			$str.=($str ? '|' : '').$v;
//
//		}
//
//		$matchstr="[^\.]+\.(?:(".$str.")|\w{2}|((".$str.")\.\w{2}))$";
//
//		if(preg_match("/".$matchstr."/ies",$host,$matchs)){
//
//			$domain=$matchs['0'];
//
//		}else{
//
//			$domain=$host;
//
//		}
//
//		return $domain;
//
//
//}
//
//
//function del_delivery_www_ecshop68_com($order_id, $action_array)
//{
//    $return_res = 0;
//
//    if (empty($order_id) || empty($action_array))
//    {
//        return $return_res;
//    }
//
//    $query_delivery = 1;
//    $query_back = 1;
//    if (in_array('delivery', $action_array))
//    {
//        $sql = 'DELETE O, G
//                FROM ' . $GLOBALS['ecs']->table('delivery_order') . ' AS O, ' . $GLOBALS['ecs']->table('delivery_goods') . ' AS G
//                WHERE O.order_id = \'' . $order_id . '\'
//                AND O.delivery_id = G.delivery_id';
//        $query_delivery = $GLOBALS['db']->query($sql, 'SILENT');
//    }
//    if (in_array('back', $action_array))
//    {
//        $sql = 'DELETE O, G
//                FROM ' . $GLOBALS['ecs']->table('back_order') . ' AS O, ' . $GLOBALS['ecs']->table('back_goods') . ' AS G
//                WHERE O.order_id = \'' . $order_id . '\'
//                AND O.back_id = G.back_id';
//        $query_back = $GLOBALS['db']->query($sql, 'SILENT');
//    }
//
//    if ($query_delivery && $query_back)
//    {
//        $return_res = 1;
//    }
//
//    return $return_res;
//}

$debug = $_GET['debug'] ? true : false;
$debugOrderId = $_GET['debug_order_id'] ? (int)$_GET['debug_order_id'] : 0;

//$sql = "SELECT pto.order_id" .
//
//    " FROM  " . $GLOBALS['ecs']->table('extpintuan_orders') . " AS pto  " .
//
//    " LEFT JOIN " . $GLOBALS['ecs']->table('extpintuan') . " AS pt ON pt.pt_id  = pto.pt_id  " .
//
//    " WHERE pt.status = 2";

$sql = 'select oi.order_id as order_id from '.$GLOBALS['ecs']->table('order_info').' as oi where oi.pt_status = 2 and pay_status != 3';

//echo $sql;die;
if($debug){
    $sql .=  " and oi.order_id = ".$debugOrderId;
}
$add_time = 1563811200; //2019-07-23
$sql .=  " and oi.add_time > 1563811200 and oi.pt_status = 2 and oi.pay_status != 3 order by oi.order_id DESC LIMIT 10";
//echo $sql;die;
//$sql = "select * from " . $ecs->table('order_info') . " where order_id='$order_id' ";
$pintuan_order = $GLOBALS['db']->getALL($sql);
$add_time=time();
if ($pintuan_order){
    foreach ($pintuan_order as $k => $v){
        $order_id = $v['order_id'];
        $sql = "select * from " . $ecs->table('order_info') . " where order_id='$order_id' ";
        $order_info = $db->getRow($sql);
//var_dump($order_info);die;
        if(empty($order_info))
        {
            continue;
        }

        $user_id = $order_info['user_id'];

        $order_sn = $order_info['order_sn'];

        $sql_og = "SELECT * FROM " . $GLOBALS['ecs']->table('order_goods') . " WHERE order_id = " . $order_id;
        //拼单也就一个商品
        $good_info = $GLOBALS['db']->getRow($sql_og);
        $order_info['goods_list'] = $GLOBALS['db']->getAll($sql_og);

        $goods_id =  $good_info['goods_id'];

        $refund_money_2 = 0; //总退款


        $sql="select count(order_sn) from ". $ecs->table('back_order')."where order_sn='$order_sn' ";

        if($db->getOne($sql)==0) {

            $postscript  =  $imgs = '';

            $back_reason = '拼团失败，自动退款'; //退货原因

            $back_pay = 2; //默认原路返回

            $back_type = 4; //仅退款
//            $back_type = 9; //自动退款(新增)

            $sql = "insert into " . $ecs->table('back_order') . "(order_sn, order_id, goods_id,  user_id, shipping_fee, consignee, address, "
                . "zipcode, mobile, add_time, postscript , back_reason, goods_name, imgs, back_pay, country, province, city, 
                district, back_type, status_back, supplier_id,is_robot) "
                . " values('$order_sn', '$order_id', '$goods_id',  '$user_id', '$order_info[shipping_fee]', '$order_info[consignee]', '$order_info[address]', "
                . "'$order_info[zipcode]', '$order_info[mobile]', '$add_time', '$postscript', '$back_reason', '$good_info[goods_name].', '$imgs', '$back_pay', '$order_info[country]', '$order_info[province]', '$order_info[city]',
              
                 '$order_info[district]', '$back_type', '5', '$order_info[supplier_id]',1)";

            $db->query($sql);

            // 插入退换货商品 80_back_goods
            $back_id = $db->insert_id();
            $have_tuikuan = 0; // 是否有退货

            $price_refund_all =  0;



            if($back_type == 4)
            {//退款
                $have_tuikuan = 1;
                $have_tuikuan2 = 1;

                foreach($order_info['goods_list'] as $goods_info)
                {
                    $price_refund_all += ($goods_info['goods_price'] * $goods_info['goods_number']);

                    $sql = "INSERT INTO " . $GLOBALS['ecs']->table('back_goods') . "(back_id, goods_id, goods_name, goods_sn, product_id, goods_attr, back_type, "
                        . "back_goods_number, back_goods_price, status_back, use_points_by_one, deduction_amount_by_one) "
                        . " values('$back_id', '".$goods_info['goods_id']."', '".$goods_info['goods_name']."', '".$goods_info['goods_sn']."', '"
                        .$goods_info['product_id']."', '".$goods_info['goods_attr']."', '4', '".$goods_info['goods_number']."', '"
                        .$goods_info['goods_price']."', '5'," . "'" . $goods_info['use_points_by_one'] ."'".  ",'" . $goods_info['deduction_amount_by_one']."') ";

                    $db->query($sql);
                }

//                $sql_og = "SELECT * FROM " . $GLOBALS['ecs']->table('order_goods') . " WHERE order_id = " . $order_id;
//                $order_info['goods_list'] = $GLOBALS['db']->getAll($sql_og);
            }

            /* 更新back_order */
            if($have_tuikuan)
            {

                $price_refund = $price_refund_all;
                $refund_money_2 = $price_refund + $order_info['shipping_fee'];
                $sql = "update " . $ecs->table('back_order') . " set refund_money_1= '$price_refund',refund_money_2 ='$refund_money_2' where back_id='$back_id' ";
                $db->query($sql);

            }
            else
            {
                $sql = "update " . $ecs->table('back_order') . " set status_refund= '9' where back_id='$back_id' ";
                $db->query($sql);
            }

        }else{
            $sql="select * from ". $ecs->table('back_order')."where order_sn='$order_sn' ";
            $order_back = $db->getRow($sql);
            $back_id = $order_back['id'];
        }

        $status_back = 5; //通过审核

        update_back($back_id, $status_back);


        ////退款////
        $status_refund = 1;
        require_once(ROOT_PATH . 'prince/wxrefund.php');
        require_once(ROOT_PATH . 'prince/alirefund.php');
        $order = back_order_info($back_id);
        $order_id=$order['order_id'];
        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('pay_log') ." WHERE order_id = '$order_id' and order_type=0 ";
        $row=$GLOBALS['db']->getRow($sql);
        $order_sql = "select * from " . $GLOBALS['ecs']->table('order_info') ." WHERE order_id = '$order_id'";
        $order_row=$GLOBALS['db']->getRow($order_sql);

        $order_id=$order['order_id'];
        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('pay_log') ." WHERE order_id = '$order_id' and order_type=0 ";
        $row=$GLOBALS['db']->getRow($sql);
        $order_sql = "select * from " . $GLOBALS['ecs']->table('order_info') ." WHERE order_id = '$order_id'";
        $order_row=$GLOBALS['db']->getRow($order_sql);

        /*如果订单支付的时候使用了积分抵扣，则需要返回退货商品使用的积分到会员账户*/
        if ($order_row['deductible_amount'] > 0) {
            // 找出退货单中的商品, 算出一共使用积分抵扣的金额，然后减去积分抵扣金额
            $sql = "SELECT * FROM " . $ecs->table('back_goods') ." where back_id = '{$back_id}'";
            $back_goods = $db->getAll($sql);

            $back_points = 0;
            foreach ($back_goods as $v) {
                $back_points += $v['use_points_by_one'] * $v['back_goods_number'];
            }
            if ($back_points > 0)
            {
                $back_points = floor($back_points * (1));
                $desc_back = "订单". $order_row['order_sn'] .'退货，返回积分';
                log_account_change($order['user_id'], 0,0,0,$back_points, $desc_back, $order['order_sn'] );
            }
        }

        /* 退回用户余额 */
        if ($order_row['pay_name'] == '余额支付')
//        if ($order_row['pay_id'] == 3)
        {
            $desc_back = "订单". $order['order_id'] .'退款';
            refund_log('balance refund:',$desc_back,'balance');

//            echo $desc_back;
            log_account_change($order['user_id'], $refund_money_2,0,0,0, $desc_back );
            //是否开启余额变动给客户发短信-退款
            if($_CFG['sms_user_money_change'] == 1)
            {
                $sql = "SELECT user_money,mobile_phone FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '" . $order['user_id'] . "'";
                $users = $GLOBALS['db']->getRow($sql);
                $content = sprintf($_CFG['sms_return_goods_tpl'],$refund_money_2,$users['user_money'],$_CFG['sms_sign']);
                if($users['mobile_phone'])
                {
                    include_once(ROOT_PATH.'send.php');
                    sendSMS($users['mobile_phone'],$content);
                }
            }
        }


        elseif($order_row['pay_name'] == '微信支付'){
//        elseif($order_row['pay_id'] == 5){
            $order['log_id'] = $row['log_id'];
            $order_sn=$order['log_id']."-".($row['order_amount'] * 100);
            $money_paid=$row['order_amount'];
            $money_refund=$refund_money_2;
            $wx_refund_status=do_wx_refund($order_id,$order_sn,$money_paid,$money_refund);
            $doing=$order_id.'1-'.$order_sn.'-'.$money_paid.'-'.$money_refund;
            if(!$wx_refund_status){
                /* 操作失败 */
                $links[] = array('text' => '返回退款/退货及维修详情', 'href' => 'back.php?act=back_info&back_id=' . $back_id);
//                sys_msg("订单:".$order_sn." 操作微信退款失败，批处理中断，请检查该订单是否使用微信支付或者已在微信商户平台退款！", 0, $links);
                $rlt_str =  "订单:".$order_sn." 操作微信退款失败，批处理中断，请检查该订单是否使用微信支付或者已在微信商户平台退款！";
                wx_refund_log('refund rs',$rlt_str);
                exit;
            }
        }else if ($order_row['pay_name'] == '支付宝') {
//        }else if ($order_row['pay_id'] == 1) {
            $order['log_id'] = $row['log_id'];
            $order_sn=$order['log_id'];
            $money_paid=$row['order_amount'];
            $money_refund=$refund_money_2;
            $wx_refund_status=do_alipay_refund($order_id,$order_sn,$money_refund);

            $doing=$order_id.'1-'.$order_sn.'-'.$money_paid.'-'.$money_refund;
            if(!$wx_refund_status){
                /* 操作失败 */
                $links[] = array('text' => '返回退款/退货及维修详情', 'href' => 'back.php?act=back_info&back_id=' . $back_id);
//                sys_msg("订单:".$order_sn." 操作支付宝退款失败，批处理中断，请检查该订单是否使用支付宝支付或者已在支付宝商户平台退款！", 0, $links);
                $rlt_str =  "订单:".$order_sn." 操作支付宝退款失败，批处理中断，请检查该订单是否使用支付宝支付或者已在支付宝商户平台退款！";
                alipay_refund_log('refund rs',$rlt_str);
                exit;
            }
        } else {
            //  未知的支付方式
        }

        $sql = "update ". $ecs->table('order_info') ." set pay_status=3  where order_id='$order[order_id]' "; //pay_status =3 -已退款
        $db->query($sql);//palenggege添加

        $sql = "update ". $ecs->table('back_goods') ." set status_refund='$status_refund'  where back_id='$back_id' and (back_type='0' or back_type='4') ";
        $db->query($sql);

        $refund_desc = $_REQUEST['refund_desc'] . ($_REQUEST['refund_shipping'] ? '\n（已退运费：'. $_REQUEST['refund_shipping_fee']. '）' : '');
        $sql2 = "update ". $ecs->table('back_order') ." set  status_refund='$status_refund',  refund_money_2='$refund_money_2', refund_type='$_REQUEST[refund_type]', refund_desc='$refund_desc' where back_id='$back_id' ";
        $db->query($sql2);


        //完成退换货
        $status_back='3';
        update_back($back_id, $status_back);

        /* 记录log */
//        back_action($back_id, $order['status_back'], $status_refund,  $action_note);
        //$links[] = array('text' => '返回退款/退货及维修详情', 'href' => 'back.php?act=back_info&back_id=' . $back_id);
//        echo '恭喜，成功操作';

        $rlt_str = 'back_id=>'.$back_id.' 返回退款/退货及维修详情 OK';
//        refund_log('refund rs',$rlt_str);
        refund_log('refund rs:',$rlt_str);


    }
}


/**
 * 取得退货单信息
 * @param   int     $back_id   退货单 id（如果 back_id > 0 就按 id 查，否则按 sn 查）
 * @return  array   退货单信息（金额都有相应格式化的字段，前缀是 formated_ ）
 */
function back_order_info($back_id)
{
    $return_order = array();
    if (empty($back_id) || !is_numeric($back_id)) {
        return $return_order;
    }

    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('back_order') . "
            WHERE back_id = '$back_id'
            LIMIT 0, 1";
    $back = $GLOBALS['db']->getRow($sql);

    if ($back)
    {
        /* 格式化金额字段 */
        $back['formated_insure_fee']     = price_format($back['insure_fee'], false);
        $back['formated_shipping_fee']   = price_format($back['shipping_fee'], false);

        /* 格式化时间字段 */
        $back['formated_add_time']       = local_date($GLOBALS['_CFG']['time_format'], $back['add_time']);

        $order_info = $GLOBALS['db']->getRow("SELECT deductible_amount, system_point_exchange, order_available_point, money_paid  FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE order_id = " . $back['order_id']);

        if ($back['back_type'] == 4)
        {
            $back['money_paid'] = $order_info['money_paid'];
            //$back['money_paid'] = $GLOBALS['db']->getOne("SELECT money_paid FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE order_id = " . $back['order_id']);
        }

        $back['deductible_amount'] = $order_info['deductible_amount'];
        $back['system_point_exchange'] = $order_info['system_point_exchange'];
        $back['order_available_point'] = $order_info['order_available_point'];

        /* 退换货状态   退款状态 */

        $return_order = $back;
    }

    return $return_order;
}

/**
 * 更新退换货订单状态
 * @param $back_id
 * @param $status_back 0:审核通过,1:收到寄回商品,2:换回商品已寄出,3:完成退货/返修,4:退款(无需退货),5:审核中,6:申请被拒绝,7:管理员取消,8:用户自己取消
 * @param int $status_refund 退款状态 0 未退款 1 已退款
 */
function update_back($back_id, $status_back, $status_refund = 0 )
{

    $setsql = "";
    if ($status_back)
    {
        $setsql .= $setsql ? "," : "";
        $setsql .= "status_back='$status_back'";
    }
    if ($status_refund)
    {
        $setsql .= $setsql ? "," : "";
        $setsql .= "status_refund='$status_refund'";
    }
    $sql = "update ". $GLOBALS['ecs']->table('back_order') ." set  $setsql where back_id='$back_id' ";
    $GLOBALS['db']->query($sql);

    if($status_back ==5) //通过申请
    {
        $status_b = $GLOBALS['db']->getOne("select back_type from " . $GLOBALS['ecs']->table('back_order') . " where back_id='$back_id'");
        $status_b = ($status_b == 4) ? 4 : 0;
        $status_bo = $GLOBALS['db']->getOne("select order_sn from " . $GLOBALS['ecs']->table('back_order') . " where back_id='$back_id'");
        $close_order = $GLOBALS['db']->getOne("select shipping_status from " . $GLOBALS['ecs']->table('order_info') . " where order_sn = '" . $status_bo . "'");
        if ($close_order < 1)
        {
            $sql3="update ". $GLOBALS['ecs']->table('order_info') ." set order_status='2', to_buyer='自动退款并取消订单' where order_sn = '" . $status_bo . "'";
            $GLOBALS['db']->query($sql3);
        }

        $sql="update ". $GLOBALS['ecs']->table('back_goods') ." set status_back='$status_b' where back_id='$back_id' ";
        $GLOBALS['db']->query($sql);
        $sql2="update ". $GLOBALS['ecs']->table('back_order') ." set status_back='$status_b' where back_id='$back_id' ";
        $GLOBALS['db']->query($sql2);
    }

    if($status_refund==1) //退款
    {
        $sql="update ". $GLOBALS['ecs']->table('back_goods') ." set status_refund='$status_refund' where back_type ='0' and back_id='$back_id' ";
        $GLOBALS['db']->query($sql);
    }

    if($status_back ==1 || $status_back ==3) //收到退换回的货物，完成退换货
    {
        $sql="update ". $GLOBALS['ecs']->table('back_goods') ." set status_back='$status_back' where back_id='$back_id' ";
        $GLOBALS['db']->query($sql);
        $sql2="UPDATE ". $GLOBALS['ecs']->table('back_order') ." SET status_back='$status_back' WHERE back_id='$back_id' ";
        $GLOBALS['db']->query($sql2);

        $get_order_id = $GLOBALS['db']->getOne("SELECT order_id FROM " . $GLOBALS['ecs']->table('back_order') . " WHERE back_id = '" . $back_id . "'");
        $get_goods_id = $GLOBALS['db']->getCol("SELECT goods_id FROM " . $GLOBALS['ecs']->table('back_order') . " WHERE order_id = '" . $get_order_id . "' AND status_back = '3' AND back_type <> '3'");
        if (count($get_goods_id) > 0)
        {
            $get_goods_id_c =  (count($get_goods_id) == 1 ? ("<> '" . implode(',', $get_goods_id) . "'") : ("NOT IN (" . implode(',', $get_goods_id) . ")"));
            $no_back = $GLOBALS['db']->getOne("SELECT COUNT(rec_id) FROM " . $GLOBALS['ecs']->table('order_goods') . " WHERE order_id = '" . $get_order_id . "' AND goods_id " . $get_goods_id_c);
            if ($no_back == 0)
            {
                $sql3="UPDATE ". $GLOBALS['ecs']->table('order_info') ." SET order_status='2' WHERE order_id='" . $get_order_id . "' ";
                $GLOBALS['db']->query($sql3);
            }
        }
        $get_goods_info = $GLOBALS['db']->getRow("SELECT goods_id, back_type FROM " . $GLOBALS['ecs']->table('back_goods') . " WHERE back_id = '" . $back_id . "'");
        if ($status_back == '3' && $get_goods_info['back_type'] != '3') // 退款退货完成时，改变订单中商品的is_back值
        {
            $sql4 = "UPDATE " .$GLOBALS['ecs']->table('order_goods') . " SET is_back = 1 WHERE goods_id = '" . $get_goods_info['goods_id'] . "' AND order_id = '" . $get_order_id . "'";
            $GLOBALS['db']->query($sql4);

            //退款完成后，进行返库
            $back_type = $GLOBALS['db']->getOne("SELECT back_type FROM " . $GLOBALS['ecs']->table('back_order') . " WHERE back_id = '" . $back_id . "'");
            $stock_dec_time = $GLOBALS['db']->getOne("SELECT value FROM " . $GLOBALS['ecs']->table('shop_config') . " WHERE code =  'stock_dec_time'");
            if ($back_type == 4 && $stock_dec_time == 1)
            {
                $back_go = $GLOBALS['db']->getAll("SELECT * FROM " . $GLOBALS['ecs']->table('order_goods') . " WHERE order_id = " . $get_order_id);
                foreach($back_go as $back_g)
                {
                    if ($back_g['product_id'] > 0)
                    {
                        $GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('products') . " SET product_number = product_number + " . $back_g['goods_number'] . " WHERE product_id = " . $back_g['product_id']);
                    }
                    $GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('goods') . " SET goods_number = goods_number + " . $back_g['goods_number'] . " WHERE goods_id = " . $back_g['goods_id']);
                }
            }
        }
    }
    if($status_back =='2') //换出商品寄回
    {
        $sql="update ". $GLOBALS['ecs']->table('back_goods') ." set status_back='$status_back' where back_type in(1,2,3) and back_id='$back_id' ";
        $GLOBALS['db']->query($sql);
    }


}

?>