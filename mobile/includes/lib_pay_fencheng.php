<?php

/**
 * ECSHOP 付款自动分成文件
 * ===========================================================
 * * 版权所有 2017-2020 月梦网络，并保留所有权利。
 * 月梦网络: http://www.dm299.com  开发QQ:124861234  禁止倒卖 一经发现停止任何服务；  寒冰   QQ   paleng
 * ----------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ==========================================================
 * $Author: derek $
 * $Id: affiliate_ck.php 17217 2011-01-19 06:29:08Z derek $
 */

define('IN_ECS', true);

//订单支付后根据配置生成分成记录  edit by yhy 2019/6/12
function get_pay_fencheng($order_id,$type=0)
{
	//此处改为按商品分销
	$sql = 'SELECT value FROM ' . $GLOBALS['ecs']->table('ecsmart_shop_config')." WHERE code = 'affiliate'";
	$affiliatearr = $GLOBALS['db']->getOne($sql);
	$affiliate = unserialize($affiliatearr);
	empty($affiliate) && $affiliate = array();
	$separate_by = $affiliate['config']['separate_by'];
	$oid = $order_id;

	$order = $GLOBALS['db']->getRow("SELECT o.order_sn,u.parent_id,u.user_name, o.is_separate,(o.goods_amount - o.discount) AS goods_amount,o.order_amount, o.user_id,s.is_ipc_shopping FROM " . $GLOBALS['ecs']->table('order_info') . " o"." LEFT JOIN " . $GLOBALS['ecs']->table('users') . " u ON o.user_id = u.user_id"." LEFT JOIN ".$GLOBALS['ecs']->table('supplier')." s ON o.supplier_id=s.supplier_id WHERE o.order_id = '$oid'");
    if(!$order){return;}
    $order_sn = $order['order_sn'];
	
	
    //判断是否可以分销订单
    if($type==0 || $type==1)
    {
        if($order['is_separate']!=0){
            return;
        }
    }
	//获取订单所有商品
	$sql_where=' order_id='.$order_id.' ';

	$sql = "SELECT * FROM ".$GLOBALS['ecs']->table('order_goods')."WHERE $sql_where";
	$order_goods = $GLOBALS['db']->getAll($sql);
	
	$setmoney_all=0;
	$distribute_list = array();
	foreach($order_goods as $k=>$v){
		//初始化会员id
		$row['user_id'] = $order['user_id'];

		//获取商品分成金额
		$sql = "SELECT * FROM ".$GLOBALS['ecs']->table('ecsmart_distrib_goods')."WHERE goods_id=$v[goods_id]";
		$goods = $GLOBALS['db']->getRow($sql);
		$split_money = $sale_point = $level_point = 0;
		//商品配置了分成，按照商品分成去做
		if(!empty($goods)){
			if($goods['distrib_type']==1){//按照金额
				$split_money = $goods['distrib_money'];
			}else{
				$split_money = $v['goods_price'] * ($goods['distrib_money']/100);
			}
			//如果该订单为拼团订单，按照拼团的分销金额作为分销基数
			if($order['extension_code']){
				//查询该拼团商品的分成金额基数
				$activity = $GLOBALS['db']->getRow("SELECT b.ext_info,a.need_people FROM ".$GLOBALS['ecs']->table('extpintuan')." a LEFT JOIN ".$GLOBALS['ecs']->table('goods_activity')." b ON a.act_id=b.act_id WHERE b.goods_id=".$v['goods_id']);
				$price_ladder = unserialize($activity['ext_info'])['price_ladder'];
				$ladder_key = array_search($activity['need_people'],array_column($price_ladder,'amount'));
				$split_money = $price_ladder[$ladder_key]['fencheng'];
			}
			if($goods['distrib_sale_type']==1){//按照金额
				$sale_point = $goods['distrib_sale_money'];
			}else{
				$sale_point = $v['goods_price'] * ($goods['distrib_sale_money']/100);
			}
			if($goods['distrib_level_type']==1){//按照金额
				$level_point = $goods['distrib_level_money'];
			}else{
				$level_point = $v['goods_price'] * ($goods['distrib_level_money']/100);
			}
			$affiliate1= unserialize($goods['value']);

			$num = count($affiliate1['item']);
			for ($i=0; $i < $num; $i++){

				$affiliate1['item'][$i]['level_money'] = (float)$affiliate1['item'][$i]['level_money'];
				if($affiliate1['item'][$i]['level_money_type']==0){
					if ($affiliate1['item'][$i]['level_money']){
						$affiliate1['item'][$i]['level_money'] /= 100;
					}
					$setmoney = round($split_money * $affiliate1['item'][$i]['level_money'], 2)*$v['goods_number'];
				}else{
					$setmoney=$affiliate1['item'][$i]['level_money_num']*$v['goods_number'];
				}

				if($affiliate1['item'][$i]['level_sale_point_type']==0){
					if ($affiliate1['item'][$i]['level_sale_point']){
						$affiliate1['item'][$i]['level_sale_point'] /= 100;
					}
					$setmoney_sale = round($sale_point * $affiliate1['item'][$i]['level_sale_point'], 2)*$v['goods_number'];
				}else{
					$setmoney_sale=$affiliate1['item'][$i]['level_sale_point_num']*$v['goods_number'];
				}
				
				if($affiliate1['item'][$i]['level_point_type']==0){
					if ($affiliate1['item'][$i]['level_point']){
						$affiliate1['item'][$i]['level_point'] /= 100;
					}
					$setmoney_level = round($level_point * $affiliate1['item'][$i]['level_point'], 2)*$v['goods_number'];
				}else{
					$setmoney_level=$affiliate1['item'][$i]['level_point_num']*$v['goods_number'];
				}
				$row = $GLOBALS['db']->getRow("SELECT o.parent_id as user_id,u.user_name FROM " . $GLOBALS['ecs']->table('users') . " o" .
					" LEFT JOIN" . $GLOBALS['ecs']->table('users') . " u ON o.parent_id = u.user_id".
					" WHERE o.user_id = '$row[user_id]'"
				);
				$up_uid = $row['user_id'];
				
				if (empty($up_uid) || empty($row['user_name'])){
					continue;
				}
				if(!empty($distribute_list[$up_uid])){
					$distribute_list[$up_uid]['setmoney'] += $setmoney;
					$distribute_list[$up_uid]['setmoney_sale'] += $setmoney_sale;
					$distribute_list[$up_uid]['setmoney_level'] += $setmoney_level;
				}else{
					$distribute_list[$up_uid] = array(
						'setmoney'		=>$setmoney,
						'setmoney_sale' =>$setmoney_sale,
						'setmoney_level'=>$setmoney_level,
						'user_name'		=>$row['user_name']
					);
				}
			}
		}
	}
	
	if($type==0){
		//如果订单为技师店铺订单并且上级为店长的话，不给店铺分成
		foreach($distribute_list as $k=>$v){
			$supplier_count = $GLOBALS['db']->getOne("SELECT COUNT(supplier_id) FROM ". $GLOBALS['ecs']->table('supplier') ." WHERE user_id=".$k." AND status=1");
			if($order['is_ipc_shopping'] == 2 && $supplier_count>0){
				continue;
			}else{
				$log_count = $GLOBALS['db']->getOne("SELECT COUNT(log_id) FROM ". $GLOBALS['ecs']->table('affiliate_log') ." WHERE order_id=".$oid." AND user_id=".$k);
				//避免下单成功后，重复产生分销金额 edit yhy 2019/6/12
				if($log_count == 0){
					write_affiliate_log($oid, $k, $v['user_name'], $v['setmoney'], $separate_by,'订单:'.$order_sn.'金额分成');
					write_affiliate_log($oid, $k, $v['user_name'], 0,$separate_by,'订单:'.$order_sn.'消费积分分成', 0,$v['setmoney_sale'],0);
					write_affiliate_log($oid, $k, $v['user_name'], 0,$separate_by,'订单:'.$order_sn.'等级积分分成', 0,0,$v['setmoney_level']);
				}
			}
		}
	}
}

//发放产生的分成 edit by yhy 2019/6/12
function distrib_separate($order_id){
	$order_sn =  $GLOBALS['db']->getOne("SELECT order_sn FROM ". $GLOBALS['ecs']->table('order_info') ." WHERE order_id=".$order_id);
	$list = $GLOBALS['db']->getAll('SELECT * FROM ' . $GLOBALS['ecs']->table('affiliate_log')." WHERE order_id = '$order_id'");
	foreach($list as $k=>$v){
		if($v['separate_type'] == 0){
			//未发放分成进行发放
			if($v['money']>0){
				log_account_change($v['user_id'], $v['money'],  0,  0, 0 ,  '订单:'.$order_sn.'的金额分成',  ACT_OTHER,$order_sn);
			}
			if($v['sale_point']>0){
				log_account_change($v['user_id'],  0,  0,  0, $v['sale_point'] ,  '订单:'.$order_sn.'赠送的消费积分分成',  ACT_OTHER,$order_sn);
			}
			if($v['level_point']>0){
				log_account_change($v['user_id'],  0,  0,  0, $v['sale_point'] ,  '订单:'.$order_sn.'赠送的消费积分分成',  ACT_OTHER,$order_sn);
			}
			$sql = "UPDATE " . $GLOBALS['ecs']->table('affiliate_log') ." SET separate_type = 1 WHERE log_id = '$v[log_id]'";
			$GLOBALS['db']->query($sql);
		}
	}
	$sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') ." SET is_separate = 1 WHERE order_id = '$order_id'";
	$GLOBALS['db']->query($sql);
}

//撤销分成 edit by yhy 2019/6/12
function distrib_rollback($order_id){
	$order_sn =  $GLOBALS['db']->getOne("SELECT order_sn FROM ". $GLOBALS['ecs']->table('order_info') ." WHERE order_id=".$order_id);
	$list = $GLOBALS['db']->getAll('SELECT * FROM ' . $GLOBALS['ecs']->table('affiliate_log')." WHERE order_id = '$order_id'");
	foreach($list as $k=>$v){
		if($v['separate_type'] == 1){
			//未发放分成进行发放
			if($v['money']>0){
				log_account_change($v['user_id'], -$v['money'],  0,  0, 0 ,  '订单:'.$order_sn.'的金额分成撤销',  ACT_OTHER,$order_sn);
			}
			if($v['sale_point']>0){
				log_account_change($v['user_id'],  0,  0,  0, -$v['sale_point'] ,  '订单:'.$order_sn.'赠送的消费积分分成撤销',  ACT_OTHER,$order_sn);
			}
			if($v['level_point']>0){
				log_account_change($v['user_id'],  0,  0,  0, -$v['sale_point'] ,  '订单:'.$order_sn.'赠送的消费积分分成撤销',  ACT_OTHER,$order_sn);
			}
			$sql = "UPDATE " . $GLOBALS['ecs']->table('affiliate_log') ." SET separate_type = 2 WHERE log_id = '$v[log_id]'";
			$GLOBALS['db']->query($sql);
		}
	}
	$sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') . " SET is_separate = 2 WHERE order_id = '" . $order_id . "'";
	$GLOBALS['db']->query($sql);
}


//店铺自营商品和服务的抽成 by yhy 2019/6/18
function log_plat_commission($order){
	//error_reporting(E_ALL);
	if($order['supplier_id']>0){
		//平台抽成
		$plat_distribute = $GLOBALS['db']->getRow("SELECT id,distribute_money FROM ".$GLOBALS['ecs']->table('plat_distribute_log')." WHERE  order_id=".$order['order_id']);
		if(!isset($plat_distribute) || empty($plat_distribute)){
			//实际支付金额
			//$pay_money = $order['goods_amount']+$order['tax']+$order['shipping_fee']+$order['insure_fee']+$order['pay_fee']+$order['pack_fee']+$order['card_fee']+$order['integral_money']-$order['bonus']-$order['discount'];
			$pay_money = $order['goods_amount']+$order['tax']+$order['shipping_fee']+$order['insure_fee']+$order['pay_fee']+$order['pack_fee']+$order['card_fee']-$order['bonus']-$order['discount'];

			$is_old = $GLOBALS['db']->getOne("SELECT is_old FROM ".$GLOBALS['ecs']->table('users')." WHERE  user_id=".$order['user_id']);
			//如果下单人为店铺老会员
			$affiliate_supplier = unserialize($GLOBALS['_CFG']['affiliate_supplier']);
			if($is_old){
				$rate = $GLOBALS['db']->getOne("SELECT old_user_rate FROM ".$GLOBALS['ecs']->table('supplier')." WHERE supplier_id=".$order['supplier_id']);
				//老会员使用老会员的平台比例
				//$rate = floatval($affiliate_supplier['config']['supplier_owner_rate']); 
			}else{
				//新会员按照商铺抽成比例
				$rate = $GLOBALS['db']->getOne("SELECT system_fee FROM ".$GLOBALS['ecs']->table('supplier')." WHERE supplier_id=".$order['supplier_id']);
				
				//$rate = floatval($affiliate_supplier['config']['supplier_no_owner_rate']); 
			}
			$rate = bcdiv($rate,100,2);
			$money = bcmul($pay_money,$rate,2);
			//增加一条平台分成明细
			$GLOBALS['db']->query("INSERT INTO ".$GLOBALS['ecs']->table('plat_distribute_log')." (`year_month`,`plat_day`,`addtime`,`order_id`,`order_sn`,`distribute_money`,`pay_money`) value ('".local_date('Y-m')."','" . local_date('Y-m-d') . "','".gmtime()."','".$order['order_id']."','".$order['order_sn']."','".$money."','".$pay_money."')");

			return $money;
		}else{
			return $plat_distribute['distribute_money'];
		}
	}else{
		return 0;
	}
	
}

/*用户在技师店铺或者平台下单,所属店铺获得提成*/
function technican_supplier_commission($order_id){
	$commission_money = $GLOBALS['db']->getRow("SELECT commission_money FROM ".$GLOBALS['ecs']->table('supplier_commission_log')." WHERE commission_type=1 AND  order_id=".$order_id);
	if(!empty($commission_money)){
		return $commission_money['commission_money'];
	}else{
		$sql = "select o.order_id,o.supplier_id,o.user_id,o.order_sn,u.user_name,s.supplier_name from ".$GLOBALS['ecs']->table('order_info')." as o LEFT JOIN ".$GLOBALS['ecs']->table('users')." as u ON u.user_id=o.user_id LEFT JOIN  ".$GLOBALS['ecs']->table('supplier')." s ON o.supplier_id = s.supplier_id where u.user_id=o.user_id And o.order_id=".$order_id;
		$order = $GLOBALS['db']->getRow($sql);
		
		if(!$order){return 0;}
		//获取订单所有商品
		$sql = "SELECT * FROM ".$GLOBALS['ecs']->table('order_goods')." WHERE order_id=".$order_id;
		$order_goods = $GLOBALS['db']->getAll($sql);
		$setmoney_all = 0;
		foreach($order_goods as $k=>$v){
			//获取商品分成金额
			$sql = "SELECT * FROM ".$GLOBALS['ecs']->table('ecsmart_distrib_goods')."WHERE goods_id=$v[goods_id]";
			$goods = $GLOBALS['db']->getRow($sql);
			//商品配置了分成，按照商品分成去做
			if(!empty($goods)){
				if($goods['distrib_supplier_type']==1){//按照金额
					$setmoney_all += $v['goods_number']*$goods['distrib_supplier_money'];
				}else{
					$setmoney_all += $v['goods_number']*$v['goods_price'] * ($goods['distrib_supplier_money']/100);
				}
			}
		}
		$supplier_name = $GLOBALS['db']->getOne("SELECT supplier_name FROM ".$GLOBALS['ecs']->table('supplier')." WHERE  supplier_id=".$order['supplier_id']);
		//获取用户所属店铺没有的话按照默认店铺
		$default_supplier_id = get_user_default_supplier($order['user_id'],true);
		if($setmoney_all){
			$commission_log = array(
				'supplier_id'=>$default_supplier_id,//用户所属店铺
				'supplier_name'=>$supplier_name,
				'order_id'=>$order_id,
				'order_sn'=>$order['order_sn'],
				'buy_goods_id'=>0,
				'buy_goods_name'=>'',
				'buy_supplier_id'=>$order['supplier_id'],//订单所属店铺
				'buy_supplier_name'=>$order['supplier_name']?$order['supplier_name']:'平台',
				'buy_goods_price'=>0,//参与分成金额
				'buy_user_id'	=>$order['user_id'],
				'buy_user_name'	=>$order['user_name'],
				'commission_rate'=>0,
				'commission_type'=>1,
				'commission_money'=>$setmoney_all,
				'status'=>0,
				'createtime'=>gmtime()
			);
			$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('supplier_commission_log'), $commission_log, 'INSERT');
		}
		return $setmoney_all;
	}
}

//记录店铺佣金变动
/*By Freely*/
function log_supplier_commission($order_id){
	//error_reporting(E_ALL);
    $sql = "select o.order_id,o.supplier_id,o.user_id,o.pay_status,o.shipping_status,o.order_sn,o.goods_amount,o.tax,o.shipping_fee,o.insure_fee,o.pay_fee,o.pack_fee,o.card_fee,o.integral_money,o.bonus,o.discount,u.user_name,o.extension_code,o.extension_id,s.is_ipc_shopping from ".$GLOBALS['ecs']->table('order_info')." as o LEFT JOIN ".$GLOBALS['ecs']->table('users')." as u ON u.user_id=o.user_id LEFT JOIN  ".$GLOBALS['ecs']->table('supplier')." s ON o.supplier_id = s.supplier_id where u.user_id=o.user_id And o.order_id=".$order_id;
    $order = $GLOBALS['db']->getRow($sql);
	

	if($order['supplier_id'] == 0 || ($order['supplier_id']>0 && $order['is_ipc_shopping']==2)){
			$technican_supplier_commission = technican_supplier_commission($order_id);
	}
	if($order['supplier_id']>0){
		$plat_money = log_plat_commission($order);//平台抽成
		$technican_supplier_commission = 0;//初始化店铺抽成
		//店铺的订单扣除分销发放剩余的全部给店铺 edit by yhy 2019/6/12
		$total_distrib = $GLOBALS['db']->getOne("SELECT SUM(money) FROM ".$GLOBALS['ecs']->table('affiliate_log')." WHERE  order_id=".$order_id);//分销扣除
		//实际支付金额
		$pay_money = $order['goods_amount']+$order['tax']+$order['shipping_fee']+$order['insure_fee']+$order['pay_fee']+$order['pack_fee']+$order['card_fee']-$order['bonus']-$order['discount'];
		//根据订单id获取订单应该发放的积分
		$integral = integral_to_give($order);
		$integral_give = intval($integral['custom_points']);
		$integral_give = value_of_integral($integral_give);
		//商家结算金额为总支付减去分销减去平台抽成减去积分发放
		$order_pay_money = $pay_money - $total_distrib -$plat_money - $integral_give - $technican_supplier_commission;
		$supplier_name = $GLOBALS['db']->getOne("SELECT supplier_name FROM ".$GLOBALS['ecs']->table('supplier')." WHERE  supplier_id=".$order['supplier_id']);
		//避免重复插入数据
		$supplier_commission_count = $GLOBALS['db']->getOne("SELECT COUNT(id) FROM ".$GLOBALS['ecs']->table('supplier_commission_log')." WHERE commission_type=0 AND order_id=".$order_id);
		if($supplier_commission_count == 0){
			$sql="INSERT INTO ".$GLOBALS['ecs']->table('supplier_commission_log')."(`supplier_id`,`supplier_name`,`order_id`,`order_sn`,`buy_goods_id`,`buy_goods_name`,`buy_supplier_id`,`buy_supplier_name`,`buy_user_id`,`buy_user_name`,`buy_goods_price`,`commission_rate`,`commission_money`,`status`,`createtime`) VALUES('".$order['supplier_id']."','".$supplier_name."','".$order_id."','".$order['order_sn']."','0','','".$order['supplier_id']."','".$supplier_name."','".$order['user_id']."','".$order['user_name']."','0','0','".$order_pay_money."','0',".gmtime().")";
			$GLOBALS['db']->query($sql);
		}
	}else{
		return true;
	}
}

//发放产生的分成 edit by yhy 2019/6/12
function supplier_separate($log_id){
	$list = $GLOBALS['db']->getRow('SELECT * FROM ' . $GLOBALS['ecs']->table('supplier_commission_log')." WHERE id = '$log_id'");
	if($list['status'] == 0){
		supplier_log_account_change($list['supplier_id'],$list['commission_money'],'店铺佣金发放',SUPPLIER_OTHER);
		$sql = "UPDATE " . $GLOBALS['ecs']->table('supplier_commission_log') ." SET status = 1 WHERE id = '$log_id'";
		$GLOBALS['db']->query($sql);
	}
}

function supplier_rollback($log_id){
	$list = $GLOBALS['db']->getRow('SELECT * FROM ' . $GLOBALS['ecs']->table('supplier_commission_log')." WHERE id = '$log_id'");
	if($list['status'] == 1){
		supplier_log_account_change($list['supplier_id'],-$list['commission_money'],'店铺佣金发放撤回',SUPPLIER_OTHER);
		$sql = "UPDATE " . $GLOBALS['ecs']->table('supplier_commission_log') ." SET status = 2 WHERE id = '$log_id'";
		$GLOBALS['db']->query($sql);
	}
}

//发放产生的分成 edit by yhy 2019/6/12
function supplier_separate_by_orderid($order_id){
	$list = $GLOBALS['db']->getAll('SELECT * FROM ' . $GLOBALS['ecs']->table('supplier_commission_log')." WHERE order_id = '$order_id'");
	foreach($list as $v){
		if($v['status'] == 0){
			supplier_log_account_change($v['supplier_id'],$v['commission_money'],'店铺佣金发放',SUPPLIER_OTHER);
			$sql = "UPDATE " . $GLOBALS['ecs']->table('supplier_commission_log') ." SET status = 1 WHERE id = '$v[id]'";
			$GLOBALS['db']->query($sql);
		}
	}
}



function write_affiliate_log($oid, $uid, $username, $money, $separate_by,$change_desc,$point,$sale_point,$level_point)
{
	$time = gmtime();
	$sql = "INSERT INTO " . $GLOBALS['ecs']->table('affiliate_log') . "( order_id, user_id, user_name, time, money,point, separate_type,change_desc,sale_point,level_point)".
		" VALUES ( '$oid', '$uid', '$username', '$time', '$money','$point', '$separate_by','$change_desc','$sale_point','$level_point')";
	if ($oid)
	{
		$GLOBALS['db']->query($sql);
	}
}

//获取某一个订单的分成金额
function get_split_money_by_orderid($order_id)
{
   $sql = 'SELECT value FROM ' . $GLOBALS['ecs']->table('ecsmart_shop_config')." WHERE code = 'distrib_type'";
    $distrib_type = $GLOBALS['db']->getOne($sql);

	 if($distrib_type == 0)
	 {
		 $total_fee = " (goods_amount - discount + tax + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee) AS total_money";
		 //按订单分成
		 $sql = "SELECT " . $total_fee . " FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE order_id = '$order_id'";
		 $total_fee = $GLOBALS['db']->getOne($sql);
		 $sql = 'SELECT value FROM ' . $GLOBALS['ecs']->table('ecsmart_shop_config')." WHERE code = 'distrib_percent'";
         $distrib_percent = $GLOBALS['db']->getOne($sql);
		 $split_money = $total_fee*($distrib_percent/100);
	 }
	 else
	 {
		//按商品分成
	 	$sql = "SELECT sum(split_money*goods_number) FROM " . $GLOBALS['ecs']->table('order_goods') . " WHERE order_id = '$order_id'";
	 	$split_money = $GLOBALS['db']->getOne($sql);
	 }
	 if($split_money > 0)
	 {
		 return $split_money;
	 }
	 else
	 {
		 return 0;
	 }
}

//分成后，推送到各个上级分销商微信
function push_user_msg($ecuid,$order_sn,$split_money){
	$type = 1;
	$text = "订单".$order_sn."分成，您得到的分成金额为".$split_money;
	$user = $GLOBALS['db']->getRow("select * from " . $GLOBALS['ecs']->table('weixin_user') . " where ecuid='{$ecuid}'");
	if($user && $user['fake_id']){
		$content = array(
			'touser'=>$user['fake_id'],
			'msgtype'=>'text',
			'text'=>array('content'=>$text)
		);
		$content = serialize($content);
		$sendtime = $sendtime ? $sendtime : time();
		$createtime = time();
		$sql = "insert into ".$GLOBALS['ecs']->table('weixin_corn')."

(`ecuid`,`content`,`createtime`,`sendtime`,`issend`,`sendtype`)
			value ({$ecuid},'{$content}','{$createtime}','{$sendtime}','0',

{$type})";
		$GLOBALS['db']->query($sql);
		return true;
	}else{
		return false;
	}
}

//根据订单号获取分成日志信息
function get_all_affiliate_log($order_id)
{
	$sql = "SELECT * FROM " . $GLOBALS['ecs']->table('affiliate_log') . " WHERE order_id = '$order_id'";
	$list = $GLOBALS['db']->getAll($sql);
	$arr = array();
	$str = '';
	foreach($list as $val)
	{
		 $str .= sprintf($GLOBALS['_LANG']['separate_info2'], $val['user_id'], $val['user_name'], $val['money'])."<br />";
		 $arr['log_id'] = $val['log_id'];
		 $arr['separate_type'] = $val['separate_type'];
	}
	$arr['info'] = $str;
	return $arr;
}
//根据订单号修改分成日志信息
function update_all_affiliate_log($order_id,$type=0)
{
	$sql="UPDATE " . $GLOBALS['ecs']->table('affiliate_log') . " SET separate_type='$type' where order_id = '$order_id'";
	$GLOBALS['db']->query($sql);
}

/**
 * 功能：添加分销记录
 * 参数：order_od
 * 返回：
 */

function get_fencheng($order_idd,$type=0){
	$sql = 'select value from ecs_ecsmart_shop_config where id=915';
    $fencheng_type = $GLOBALS['db']->getOne($sql);

    if( $fencheng_type == 1){ //此处改为按商品分销
 	        //此处改为按商品分销
                    $sql = 'SELECT value FROM ' . $GLOBALS['ecs']->table('ecsmart_shop_config')." WHERE code = 'affiliate'";
                    $affiliatearr = $GLOBALS['db']->getOne($sql);
                    $affiliate = unserialize($affiliatearr);
                    empty($affiliate) && $affiliate = array();
                    $separate_by = $affiliate['config']['separate_by'];
                    $oid = $order_idd;

                    $order = $GLOBALS['db']->getRow("SELECT o.order_sn,u.parent_id,u.user_name, o.is_separate,(o.goods_amount - o.discount) AS goods_amount,o.order_amount, o.user_id FROM " . $GLOBALS['ecs']->table('order_info') . " o"." LEFT JOIN " . $GLOBALS['ecs']->table('users') . " u ON o.user_id = u.user_id"." WHERE order_id = '$oid'");
                    if(!$order){return;}
                    
                    $order_sn = $order['order_sn'];

                    //判断是否可以分销订单
                    if($type==0)
                    {
                       
                        if($order['is_separate']==0){
                            //获取订单所有商品

                            $sql = "SELECT * FROM ".$GLOBALS['ecs']->table('order_goods')."WHERE order_id=$order_idd";
                            $order_goods = $GLOBALS['db']->getAll($sql);

                            $setmoney_all=0;
                            foreach($order_goods as $k=>$v){
                                //获取商品分成金额
                                $sql = "SELECT * FROM ".$GLOBALS['ecs']->table('ecsmart_distrib_goods')."WHERE goods_id=$v[goods_id]";
                                $goods = $GLOBALS['db']->getRow($sql);
                                if(!empty($goods['value'])){
                                    $affiliate1= unserialize($goods['value']);
                                }else{
                                    $affiliate1=$affiliate;
                                }

                                $split_money = $v['split_money'];
                                $sale_point = $v['sale_point'];
                                $level_point = $v['level_point'];
                                $num = count($affiliate1['item']);
                                for ($i=0; $i < $num; $i++)
                                {
                                    $affiliate1['item'][$i]['level_money'] = (float)$affiliate1['item'][$i]['level_money'];
                                    if($affiliate['config']['level_money_all']==100 )
                                    {
                                        $setmoney = $split_money;
                                    }

                                    else
                                    {
                                        if($affiliate1['item'][$i]['level_money_type']==0){
                                            if ($affiliate1['item'][$i]['level_money'])
                                            {
                                                $affiliate1['item'][$i]['level_money'] /= 100;
                                            }

                                            $setmoney = round($split_money * $affiliate1['item'][$i]['level_money'], 2)*$v['goods_number'];
                                        }else{
                                            $setmoney=$affiliate1['item'][$i]['level_money_num']*$v['goods_number'];
                                        }

                                    }

                                    if($affiliate1['item'][$i]['level_sale_point_type']==0){

                                        if ($affiliate1['item'][$i]['level_sale_point'])
                                        {
                                            $affiliate1['item'][$i]['level_sale_point'] /= 100;
                                        }

                                        $setmoney_sale = round($sale_point * $affiliate1['item'][$i]['level_sale_point'], 2)*$v['goods_number'];

                                    }else{
                                        $setmoney_sale=$affiliate1['item'][$i]['level_sale_point_num']*$v['goods_number'];
                                    }
                                    if($affiliate1['item'][$i]['level_point_type']==0){


                                        if ($affiliate1['item'][$i]['level_point'])
                                        {
                                            $affiliate1['item'][$i]['level_point'] /= 100;
                                        }

                                        $setmoney_level = round($level_point * $affiliate1['item'][$i]['level_point'], 2)*$v['goods_number'];
                                    }else{
                                        $setmoney_level=$affiliate1['item'][$i]['level_point_num']*$v['goods_number'];
                                    }
                                    if(!(isset($row['user_id']) && $row['user_id'])){
                                        $row['user_id'] = $GLOBALS['db']->getOne('select user_id from ecs_order_info where order_id='.$order_idd);
                                    }
                                    $row = $GLOBALS['db']->getRow("SELECT o.parent_id as user_id,u.user_name FROM " . $GLOBALS['ecs']->table('users') . " o" .
                                        " LEFT JOIN" . $GLOBALS['ecs']->table('users') . " u ON o.parent_id = u.user_id".
                                        " WHERE o.user_id = '$row[user_id]'");
                                 
                                    $up_uid = $row['user_id'];
                                    

                                    if (empty($up_uid) || empty($row['user_name']))
                                    {
                                        break;
                                    }
                                    else
                                    {
                                        $info = sprintf($_LANG['separate_info'], $order_sn, $setmoney, 0);
                                        //push_user_msg($up_uid,$order_sn,$setmoney);
                                    }
                                    if($type==0){
                                   
                                       if(isset($setmoney) && $setmoney >0){
                                            write_affiliate_log($oid, $up_uid, $row['user_name'], $setmoney, $separate_by,'订单:'.$order_sn.'金额分成');
                                        }
                                        if(isset($setmoney_sale) && $setmoney_sale >0){

                                             write_affiliate_log($oid, $up_uid, $row['user_name'], 0,$separate_by,'订单:'.$order_sn.'消费积分分成', 0,$setmoney_sale,0);
                                        }
                                        if(isset($setmoney_level) && $setmoney_level >0){

                                            write_affiliate_log($oid, $up_uid, $row['user_name'], 0,$separate_by,'订单:'.$order_sn.'等级积分分成', 0,0,$setmoney_level);
                                        }    

                                    }

                                }
                            }

                        }  
                    }

 	}
 }



  /**
 * 功能：分成按钮修改 account_log affilate_log users三张表的有关信息  
 * 参数：订单表的id
 * 返回：true
 */

//确认收货后调用，自动分成到账
function gets_split_money_by_order_id($oid){

    // 1 修改affilate_log 表的订单状态，并获得相关的数据
    $order_sn = $GLOBALS['db']->getOne('select order_sn from ecs_order_info where order_id='.$oid);
    if(!$order_sn){

        return false;
    }
    $status=  $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('affiliate_log') ." WHERE order_id = '$oid' and separate_type=0");

    if($status){

        $info = $GLOBALS['db']->getAll('select user_id,user_name,money,sale_point,level_point from ecs_affiliate_log where order_id='.$oid);
    
	    foreach ($info as  $val) {
	        if($val['money'] > 0 ){
	            log_account_change($val['user_id'], $val['money'], 0, 0, 0, '订单:'.$order_sn.'赠送的金额分成');

	        }elseif($val['sale_point'] > 0 ){
	            log_account_change($val['user_id'], 0, 0, 0,$val['sale_point'], '订单:'.$order_sn.'赠送的消费积分分成');

	        }elseif($val['level_point'] > 0){
	        	log_account_change($val['user_id'], 0, 0, $val['level_point'],0, '订单:'.$order_sn.'赠送的等级积分分成');
	        }
	    }
    }
 
    $sqls = 'update ecs_affiliate_log set separate_type="1" where order_id='.$oid;

    $rel = $GLOBALS['db']->query($sqls);


    $sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') .
			" SET is_separate = 1" .
			" WHERE order_id = '$oid'";
		$GLOBALS['db']->query($sql);
		update_all_affiliate_log($oid,1);

    return true;
    
}

function putOrderIntoQueue($ordersn){
		
		$supplier_info = $GLOBALS['db']->getRow("SELECT supplier_id,order_id FROM ". $GLOBALS['ecs']->table('order_info') ." WHERE  order_sn ='".$ordersn."'");
		//只有平台服务订单才同步到pos
		if($supplier_info['supplier_id'] == 0){
			//查询是否存在服务类商品
			$service_count = $GLOBALS['db']->getRow("SELECT COUNT(a.rec_id) FROM ".$GLOBALS['ecs']->table('order_goods')." a LEFT JOIN ".$GLOBALS['ecs']->table('goods')." b ON a.goods_id=b.goods_id WHERE b.is_service = 1 AND a.order_id=".$supplier_info['order_id']);
			if($service_count>0){
				$queue_data = array('queue_type'=>1,'queue_param'=>serialize(array('ordersn'=>$ordersn)),'operate_status'=>0,'create_time'=>gmtime());
				$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('queue'), $queue_data, 'INSERT', '', 'SILENT');
			}
		}
		
		
}

//技师店铺的订单并且选择了预约时间，付款完成后增加预约记录
function schedul_change($order_id){
	$supplier_info = $GLOBALS['db']->getRow("SELECT a.supplier_id,a.schedul_date,a.schedul_time,b.user_id,b.is_ipc_shopping,c.user_id as uid FROM ". $GLOBALS['ecs']->table('order_info') ." a LEFT JOIN ". $GLOBALS['ecs']->table('supplier') ." b ON a.supplier_id = b.supplier_id LEFT JOIN ". $GLOBALS['ecs']->table('supplier_admin_user') ." c ON b.user_id = c.uid WHERE  a.supplier_id>0 AND a.order_id =".$order_id);
	
	if($supplier_info['is_ipc_shopping'] == 2 && !empty($supplier_info['schedul_date']) && !empty($supplier_info['schedul_time'])){
		if(!$GLOBALS['db']->getOne('SELECT id FROM '.$GLOBALS['ecs']->table('technican_schedul').' WHERE order_id='.$order_id)){
			$insert_data = array('datetime'=>$supplier_info['schedul_date'],'user_id'=>$supplier_info['uid'],'timepick'=>$supplier_info['schedul_time'],'update_time'=>gmtime(),'order_id'=>$order_id,'schedul'=>1,'supplier_id'=>$supplier_info['supplier_id']);
			$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('technican_schedul'), $insert_data, 'INSERT', '', 'SILENT');
		}
	}
}
