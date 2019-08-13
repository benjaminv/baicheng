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
	//需要判断订单使用了红包的话不参与分成



	//此处改为按商品分销
	$sql = 'SELECT value FROM ' . $GLOBALS['ecs']->table('ecsmart_shop_config')." WHERE code = 'affiliate'";
	$affiliatearr = $GLOBALS['db']->getOne($sql);
	$affiliate = unserialize($affiliatearr);
	empty($affiliate) && $affiliate = array();
	$separate_by = $affiliate['config']['separate_by'];
	$oid = $order_id;

	$order = $GLOBALS['db']->getRow("SELECT o.order_sn,u.parent_id,u.user_name, o.is_separate,(o.goods_amount - o.discount) AS goods_amount,o.order_amount, o.user_id FROM " . $GLOBALS['ecs']->table('order_info') . " o"." LEFT JOIN " . $GLOBALS['ecs']->table('users') . " u ON o.user_id = u.user_id"." WHERE order_id = '$oid'");
    if(!$order){return;}
    $order_sn = $order['order_sn'];
	
	$row['user_id'] = $order['user_id'];
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
	foreach($order_goods as $k=>$v){
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
		}

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
			}else{
				$info = sprintf($_LANG['separate_info'], $order_sn, $setmoney, 0);
			}
			
			if($type==0){
				$log_count = $GLOBALS['db']->getOne("SELECT COUNT(log_id) FROM ". $GLOBALS['ecs']->table('affiliate_log') ." WHERE order_id=".$oid." AND user_id=".$up_uid);
				//避免下单成功后，重复产生分销金额 edit yhy 2019/6/12
				if($log_count == 0){
					write_affiliate_log($oid, $up_uid, $row['user_name'], $setmoney, $separate_by,'订单:'.$order_sn.'金额分成');
					write_affiliate_log($oid, $up_uid, $row['user_name'], 0,$separate_by,'订单:'.$order_sn.'消费积分分成', 0,$setmoney_sale,0);
					write_affiliate_log($oid, $up_uid, $row['user_name'], 0,$separate_by,'订单:'.$order_sn.'等级积分分成', 0,0,$setmoney_level);
				}
			}
			/*更改分销流程,支付成功后根据配置生成分销金额,此时用户的分销金额不再跟随配置变化而变化 2019/6/12 edit by yhy*/		
			//elseif($type==1){
				////假如已经分成成功，禁止重复操作
				//$status=  $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('affiliate_log') ." WHERE order_id = '$oid' and separate_type=0");
				//if(!empty($status)){
					//log_account_change($up_uid, $setmoney,  0,  0, 0 ,  '订单:'.$order_sn.'的金额分成',  ACT_OTHER,$order_sn);
					//log_account_change($up_uid,  0,  0,  0, $setmoney_sale ,  '订单:'.$order_sn.'赠送的消费积分分成',  ACT_OTHER,$order_sn);
					//log_account_change($up_uid,  0,  0,  $setmoney_level, 0 ,  '订单:'.$order_sn.'赠送的等级积分分成',  ACT_OTHER,$order_sn);
				//}
			//}
			
			//elseif($type==2){
				//$status=  $GLOBALS['db']->getRow("SELECT * FROM " . $GLOBALS['ecs']->table('affiliate_log') ." WHERE order_id = '$oid' and separate_type=1");
				//if(!empty($status)){
					////减少等级积分
					//$sql = "UPDATE " . $GLOBALS['ecs']->table('users') .
						//" SET rank_points = rank_points-'$setmoney_sale'" .
						//" WHERE user_id = '$up_uid'";
					//$GLOBALS['db']->query($sql);
					////减少消费积分
					//$sql = "UPDATE " . $GLOBALS['ecs']->table('users') .
						//" SET pay_points = pay_points-'$setmoney_level'" .
						//" WHERE user_id = '$up_uid'";
					//$GLOBALS['db']->query($sql);
					////减少余额
					//$sql = "UPDATE " . $GLOBALS['ecs']->table('users') .
						//" SET user_money = user_money-'$setmoney'" .
						//" WHERE user_id = '$up_uid'";
					//$GLOBALS['db']->query($sql);
					//log_account_change($up_uid, -$setmoney,  0,  0, 0 ,  '订单:'.$order_sn.'撤销的金额分成',  ACT_OTHER,$order_sn);
					//log_account_change($up_uid,  0,  0,  0, -$setmoney_sale ,  '订单:'.$order_sn.'撤销的消费积分分成',  ACT_OTHER,$order_sn);
					//log_account_change($up_uid,  0,  0,  -$setmoney_level, 0 ,  '订单:'.$order_sn.'撤销的等级积分分成',  ACT_OTHER,$order_sn);
				//}
			//}
		}
	}

	//if($type==1){
        //$sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') .
			//" SET is_separate = 1" .
			//" WHERE order_id = '$order_id'";
		//$GLOBALS['db']->query($sql);
		//update_all_affiliate_log($order_id,1);

	//}
	//if($type==2){
		//update_all_affiliate_log($order_id,2);
	//}
	  //个人购买增加分成  个人分成功能暂时屏蔽
		//if($type==0){
			//$separate_personal = $affiliate['config']['ex_fenxiao_personal'];
			//$personal_lever_money = $affiliate['config']['personal_lever_money'];
			//if ($separate_personal > 0){
				//$personal_data = $GLOBALS['db']->getRow("SELECT o.user_id,u.user_name,u.rank_points,u.is_fenxiao FROM " . $GLOBALS['ecs']->table('order_info') . " o".
					//" LEFT JOIN " . $GLOBALS['ecs']->table('users') . " u ON o.user_id = u.user_id".
					//" WHERE order_id = '$oid'");
				//$personal_pay_money = $GLOBALS['db']->getOne("SELECT sum(goods_amount) FROM " . $GLOBALS['ecs']->table('order_info')." where user_id = ".$personal_data['user_id']);
				////消费金额小于设置的最少消费金额时，个人分成 0
				//if ($personal_pay_money < $personal_lever_money){
					//$affiliate['config']['level_money_personal'] = 0;
					//$affiliate['config']['level_point_personal'] = 0;
				//}

				//if($personal_data['is_fenxiao'] == 1){
					//$personalMoney = round($split_money * $affiliate['config']['level_money_personal']*0.01, 2);
					//$personalPoint = round($point * $affiliate['config']['level_point_personal']*0.01, 0);
					//$info = sprintf($_LANG['separate_info'], $order_sn, $personalMoney, $personalPoint);
					//log_account_change($personal_data['user_id'], $personalMoney, 0, $personalPoint, 0, $info);
					//push_user_msg($personal_data['user_id'],$order_sn,$personalMoney);
					//write_affiliate_log($oid, $personal_data['user_id'] , $personal_data['user_name'], $personalMoney, $personalPoint, $separate_by,$separate_personal);
				//}else{
					////如果不是分销商，自己的分成给自己的上级
					//$personalMoney = round($split_money * $affiliate['config']['level_money_personal']*0.01, 2);
					//$personalPoint = round($point * $affiliate['config']['level_point_personal']*0.01, 0);

					//$info = sprintf($_LANG['separate_info'], $order_sn, $personalMoney, $personalPoint);
					//$personal_id=$personal_data['user_id'];
					//$personal_up_id = $GLOBALS['db']->getOne("SELECT parent_id FROM " . $GLOBALS['ecs']->table('users') .
						//" WHERE user_id = '$personal_id'");
					//$personal_up_name = $GLOBALS['db']->getOne("SELECT user_name FROM " . $GLOBALS['ecs']->table('users') .
						//" WHERE user_id = '$personal_up_id'");
					//if(!empty($personal_up_id)){
						//log_account_change($personal_up_id, $personalMoney, 0, $personalPoint, 0, $info);
						//push_user_msg($personal_up_id,$order_sn,$personalMoney);
						//write_affiliate_log($oid,$personal_up_id,$personal_up_name,$personalMoney, $personalPoint, $separate_by,$separate_personal);
					//}


				//}

				//$sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') .
					//" SET is_separate = 1" .
					//" WHERE order_id = '$oid'";
				//$GLOBALS['db']->query($sql);

			//}



			//$wap_url_sql = "SELECT `wap_url` FROM `ecs_weixin_config` WHERE `id`=1";
			//$wap_url =  $GLOBALS['db'] -> getOne($wap_url_sql);//手机端网址
			//@file_get_contents($wap_url."/weixin/auto_do.php?type=1&is_affiliate=1");
		//}



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
			$pay_money = $order['goods_amount']+$order['tax']+$order['shipping_fee']+$order['insure_fee']+$order['pay_fee']+$order['pack_fee']+$order['card_fee']-$order['integral_money']-$order['bonus']-$order['discount'];
			
			$is_old = $GLOBALS['db']->getOne("SELECT is_old FROM ".$GLOBALS['ecs']->table('users')." WHERE  user_id=".$order['user_id']);
			//如果下单人为店铺老会员
			$affiliate_supplier = unserialize($GLOBALS['_CFG']['affiliate_supplier']);
			if($is_old){
				$rate = floatval($affiliate_supplier['config']['supplier_owner_rate']); 
			}else{
				$rate = floatval($affiliate_supplier['config']['supplier_no_owner_rate']); 
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

//记录店铺佣金变动
/*By Freely*/
function log_supplier_commission($order_id){
    $sql = "select o.order_id,o.supplier_id,o.user_id,o.pay_status,o.shipping_status,o.order_sn,o.goods_amount,o.tax,o.shipping_fee,o.insure_fee,o.pay_fee,o.pack_fee,o.card_fee,o.integral_money,o.bonus,o.discount,u.user_name from ".$GLOBALS['ecs']->table('order_info')." as o LEFT JOIN ".$GLOBALS['ecs']->table('users')." as u ON u.user_id=o.user_id where u.user_id=o.user_id And o.order_id=".$order_id;
    $order = $GLOBALS['db']->getRow($sql);

	if($order['supplier_id']>0){
		//店铺的订单扣除分销发放剩余的全部给店铺 edit by yhy 2019/6/12
		$total_distrib = $GLOBALS['db']->getOne("SELECT SUM(money) FROM ".$GLOBALS['ecs']->table('affiliate_log')." WHERE  order_id=".$order_id);
		
		//实际支付金额
		$pay_money = $order['goods_amount']+$order['tax']+$order['shipping_fee']+$order['insure_fee']+$order['pay_fee']+$order['pack_fee']+$order['card_fee']-$order['integral_money']-$order['bonus']-$order['discount'];
		//echo "<pre>";print_r($order);
		$plat_money = log_plat_commission($order);
		//商家结算金额为总支付减去分销减去平台抽成
		$order_pay_money = $pay_money - $total_distrib -$plat_money;
		$supplier_name = $GLOBALS['db']->getOne("SELECT supplier_name FROM ".$GLOBALS['ecs']->table('supplier')." WHERE  supplier_id=".$order['supplier_id']);


		//避免重复插入数据
		$supplier_commission_count = $GLOBALS['db']->getOne("SELECT COUNT(id) FROM ".$GLOBALS['ecs']->table('supplier_commission_log')." WHERE  order_id=".$order_id);
		if($supplier_commission_count == 0){
			$sql="INSERT INTO ".$GLOBALS['ecs']->table('supplier_commission_log')."(`supplier_id`,`supplier_name`,`order_id`,`order_sn`,`buy_goods_id`,`buy_goods_name`,`buy_supplier_id`,`buy_supplier_name`,`buy_user_id`,`buy_user_name`,`buy_goods_price`,`commission_rate`,`commission_money`,`status`,`createtime`) VALUES('".$order['supplier_id']."','".$supplier_name."','".$order_id."','".$order['order_sn']."','0','','".$order['supplier_id']."','".$supplier_name."','".$order['user_id']."','".$order['user_name']."','".$order['goods_amount']."','100','".$order_pay_money."','0',".gmtime().")";
			$GLOBALS['db']->query($sql);
		}
	}else{
		/*①查询下单人上级如果是店铺管理员，那么该店铺可以获得一级店铺分成
		* ②查询下单人上两级如果是店铺管理员，那么该店铺可以获得二级店铺分成
		*
		*/
		$sql = 'SELECT value FROM ' . $GLOBALS['ecs']->table('ecsmart_shop_config')." WHERE code = 'affiliate_supplier'";
		$affiliate_supplierarr = $GLOBALS['db']->getOne($sql);
		//echo "<pre>";print_r($affiliate_supplierarr);exit;


		if($order&&$order['pay_status'] == PS_PAYED&&$order['shipping_status'] == SS_RECEIVED){//订单付款并且确认收货时处理
			//店铺分成和地推团队分成
			$sql = 'SELECT value FROM ' . $GLOBALS['ecs']->table('ecsmart_shop_config')." WHERE code = 'affiliate_supplier'";
			$affiliate_supplierarr = $GLOBALS['db']->getOne($sql);
			$affiliate_supplier = unserialize($affiliate_supplierarr);
			empty($affiliate_supplier) && $affiliate_supplier = array();
			//购买者的所属店铺信息
			$sql="SELECT s.supplier_id,s.supplier_name,u.user_id,u.parent_id FROM ".$GLOBALS['ecs']->table('supplier')." s LEFT JOIN ".$GLOBALS['ecs']->table('users').' as u ON u.supplierId=s.supplier_id WHERE u.supplierId=s.supplier_id And u.user_id='.$order['user_id'];
			$buy_supplier=$GLOBALS['db']->getRow($sql);
			$level_bendian=0;
			$level_ydian=0;
			$level_fuwufe=0;
			$level=0;
			//它的上级，如果有上级表示此用户是二级用户，否则就是一级用户，对应不同级的店铺分成
			$user_parent_supplier=null;
			if($buy_supplier['parent_id']){
				$sql="SELECT s.supplier_id,s.supplier_name,u.user_id,u.parent_id FROM ".$GLOBALS['ecs']->table('supplier')." s LEFT JOIN ".$GLOBALS['ecs']->table('users').' as u ON u.supplierId=s.supplier_id WHERE u.supplierId=s.supplier_id And u.user_id='.$buy_supplier['parent_id'];
				$user_parent_supplier=$GLOBALS['db']->getRow($sql);
			}
			if($user_parent_supplier&&$user_parent_supplier['supplier_id']==$buy_supplier['supplier_id']){//二级用户
				$level=1;
			}

			if(isset($affiliate_supplier['item'])&&$buy_supplier){
				if(isset($affiliate_supplier['item'][$level])){
					$level_bendian=(float)$affiliate_supplier['item'][$level]['level_bendian'];
					$level_ydian=(float)$affiliate_supplier['item'][$level]['level_ydian'];
					$level_fuwufe=$affiliate_supplier['item'][$level]['level_fuwufe'];
				}
				if($level_bendian>0||$level_ydian>0||$level_fuwufe>0){
					//按商品分成
					$sql_where=' order_id='.$order_id.' ';
					$sql = "SELECT * FROM ".$GLOBALS['ecs']->table('order_goods')."WHERE $sql_where";
					$order_goods = $GLOBALS['db']->getAll($sql);
					foreach($order_goods as $k=>$v){
						$sql = "SELECT g.*,s.supplier_name,s.user_id FROM ".$GLOBALS['ecs']->table('goods')." g LEFT JOIN ".$GLOBALS['ecs']->table('supplier')." as s ON s.supplier_id=g.supplier_id WHERE s.supplier_id=g.supplier_id And g.goods_id=$v[goods_id]";
						$goods = $GLOBALS['db']->getRow($sql);
						//print_r($goods);
						if($goods&&$order['user_id']!=$goods['user_id']){//判断如果下单用户是店铺用户则不做处理
							//店铺分成处理,按单个商品来处理
							if($buy_supplier['supplier_id']==$goods['supplier_id']){//
								$dianpu_fx=$level_bendian;
							}else{
								$dianpu_fx=$level_ydian;
							}
							//print_r($dianpu_fx);die();
							if($dianpu_fx>0){
								$dianpu_money=($v['goods_price']*($dianpu_fx/100))*$v['goods_number'];//店铺可得
								$saveData=[
									'supplier_id'=>$buy_supplier['supplier_id'],
									'supplier_name'=>$buy_supplier['supplier_name'],
									'order_id'=>$order_id,
									'order_sn'=>$order['order_sn'],
									'buy_goods_id'=>$goods['goods_id'],
									'buy_goods_name'=>$goods['goods_name'],
									'buy_supplier_id'=>$goods['supplier_id'],
									'buy_supplier_name'=>$goods['supplier_name'],
									'buy_user_id'=>$order['user_id'],
									'buy_user_name'=>$order['user_name'],
									'buy_goods_price'=>$v['goods_price']*$v['goods_number'],
									'commission_type'=>0,
									'commission_rate'=>$dianpu_fx,
									'commission_money'=>$dianpu_money,
									'status'=>1,
									'createtime'=>time(),
								];
								//print_r($saveData);die();
								$sql="INSERT INTO ".$GLOBALS['ecs']->table('supplier_commission_log')."(`supplier_id`,`supplier_name`,`order_id`,`order_sn`,`buy_goods_id`,`buy_goods_name`,`buy_supplier_id`,`buy_supplier_name`,`buy_user_id`,`buy_user_name`,`buy_goods_price`,`commission_rate`,`commission_money`,`status`,`createtime`) VALUES('".$saveData['supplier_id']."','".$saveData['supplier_name']."','".$saveData['order_id']."','".$saveData['order_sn']."','".$saveData['buy_goods_id']."','".$saveData['buy_goods_name']."','".$saveData['buy_supplier_id']."','".$saveData['buy_supplier_name']."','".$saveData['buy_user_id']."','".$saveData['buy_user_name']."','".$saveData['buy_goods_price']."','".$saveData['commission_rate']."','".$saveData['commission_money']."','".$saveData['status']."',".$saveData['createtime'].")";
								$GLOBALS['db']->query($sql);
								supplier_log_account_change($saveData['supplier_id'], $saveData['commission_money'], '【'.$saveData['buy_user_name'].'】购买【'.$saveData['buy_goods_name'].'】', SUPPLIER_BUY);
							}

							//如果不是本店用户就加
							if($level_fuwufe>0&&$buy_supplier['supplier_id']!=$goods['supplier_id']){
								$commission_money=$level_fuwufe*$v['goods_number'];
								if($v['goods_price']<=0){$commission_money=0;}
								$saveData=[
									'supplier_id'=>$goods['supplier_id'],
									'supplier_name'=>$goods['supplier_name'],
									'order_id'=>$order_id,
									'order_sn'=>$order['order_sn'],
									'buy_goods_id'=>$goods['goods_id'],
									'buy_goods_name'=>$goods['goods_name'],
									'buy_supplier_id'=>$goods['supplier_id'],
									'buy_supplier_name'=>$goods['supplier_name'],
									'buy_user_id'=>$order['user_id'],
									'buy_user_name'=>$order['user_name'],
									'buy_goods_price'=>$v['goods_price']*$v['goods_number'],
									'commission_type'=>1,
									//'commission_rate'=>100,
									'commission_money'=>$commission_money,
									'status'=>1,
									'createtime'=>time(),
								];
								$sql="INSERT INTO ".$GLOBALS['ecs']->table('supplier_commission_log')."(`supplier_id`,`supplier_name`,`order_id`,`order_sn`,`buy_goods_id`,`buy_goods_name`,`buy_supplier_id`,`buy_supplier_name`,`buy_user_id`,`buy_user_name`,`buy_goods_price`,`commission_type`,`commission_rate`,`commission_money`,`status`,`createtime`) VALUES('".$saveData['supplier_id']."','".$saveData['supplier_name']."','".$saveData['order_id']."','".$saveData['order_sn']."','".$saveData['buy_goods_id']."','".$saveData['buy_goods_name']."','".$saveData['buy_supplier_id']."','".$saveData['buy_supplier_name']."','".$saveData['buy_user_id']."','".$saveData['buy_user_name']."','".$saveData['buy_goods_price']."','".$saveData['commission_type']."','".$saveData['commission_rate']."','".$saveData['commission_money']."','".$saveData['status']."',".$saveData['createtime'].")";
								$GLOBALS['db']->query($sql);
								supplier_log_account_change($saveData['supplier_id'], $saveData['commission_money'], '【'.$saveData['buy_user_name'].'】购买【'.$saveData['buy_goods_name'].'】所得服务费', SUPPLIER_SERVICE);
							}
						}
					}
				}
			}

			//地推团队处理
			if($affiliate_supplier&&$buy_supplier){
				//判断一级并且不是指定店铺的一个分成，其它的一个分成
				if($level==0&&$buy_supplier['supplier_id']!=$affiliate_supplier['config']['default_reg_supplier_id']){//
					$commission_rate=(float)$affiliate_supplier['config']['level_push_team_all'];
				}else{
					$commission_rate=(float)$affiliate_supplier['config']['level_not_push_team_all'];
				}
				if($commission_rate>0){
					$order_pay_money=$order['goods_amount']+$order['tax']+$order['shipping_fee']+$order['insure_fee']+$order['pay_fee']+$order['pack_fee']+$order['card_fee'];//订单金额，扣除红包，优惠，积分换等
					$order_pay_money-=$order['integral_money']-$order['bonus']-$order['discount'];
					$commission_money=$order_pay_money*($commission_rate/100);
					$sql="INSERT INTO ".$GLOBALS['ecs']->table('push_team_commission')."(`order_id`,`order_sn`,`order_price`,`buy_user_id`,`buy_user_name`,`commission_rate`,`commission_money`,`status`,`createtime`) VALUES('".$order_id."','".$order['order_sn']."','".$order_pay_money."','".$order['user_id']."','".$order['user_name']."','".$commission_rate."','".$commission_money."',0,".time().")";
					$GLOBALS['db']->query($sql);
					push_team_log_account_change($commission_money, $change_desc = '【'.$order['user_name'].'】下单【'.$order['order_sn'].'】', PT_BUY);
				}
			}
		}
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
