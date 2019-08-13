<?php
//同步商城会员，订单，商品到erp系统
//商城店铺的商品和订单，同步到对应的erp公司中，平台商品和订单同步到默认公司(平台公司)
////执行方法
//会员 订单 退款单数据加入queue通道，定时任务执行
require_once($_SERVER['DOCUMENT_ROOT']. '/api/library/ripcord.php');
class OdooErp{
    //保存例实例在此属性中 
	protected  $odooUid;
	protected  $erpObj;
    //构造函数声明为private,防止直接创建对象 
	public function __construct($odoo_erp_db){
		$this->odoo_erp_db = $odoo_erp_db;
        $common = ripcord::client(ODOO_ERP_URL."/xmlrpc/2/common");
		$this->odooUid = $common->authenticate($this->odoo_erp_db, ODOO_ERP_USER, ODOO_ERP_PSD, array());
		$this->erpObj = ripcord::client(ODOO_ERP_URL."/xmlrpc/2/object");
    }

	/*通用同步接口,参数为模型，方法以及变量*/
	public function executeErp($models,$action,$position=array(),$keyword=array()){
		$res = $this->erpObj->execute_kw($this->odoo_erp_db, $this->odooUid, ODOO_ERP_PSD,$models,$action,$position,$keyword);
		return $res;
	}
	
	//构造订单同步数据(新版的同步订单数据结构，只能同步一个订单,多个订单循环)
	public function getOrderSyncData($orderInfo){
		$data =array();
		$data['external_id'] = $orderInfo['order_id'];
		$data['name'] = $orderInfo['order_sn'];
		$data['date_order'] = local_date("Y-m-d",$orderInfo['add_time']);
		$data['state'] = 'done';
		$data['order_type'] = 'tech_order';
		$data['return_process'] = 'False';
		$data['amount_paid'] = $orderInfo['goods_amount'];
		$data['amount_return'] = 0;
		$data['branch_name'] = $orderInfo['supplier_name'];
		$data['branch_phone'] = $orderInfo['contacts_phone'];
		$data['technican_name'] = $orderInfo['technican_name'];
		$data['technican_sn'] = $orderInfo['technican_sn'];

		//查询订单商品详情
		$goodssql = "SELECT rec_id,goods_name,goods_id,goods_attr,product_id,goods_number,goods_price FROM ". $GLOBALS['ecs']->table('order_goods') ."  WHERE  order_id=".$orderInfo['order_id'];
		$goodsInfo = $GLOBALS['db']->getAll($goodssql);
		foreach($goodsInfo as $gv){
			$order_line_detail = array();
			$order_line_detail = array(
					'external_id'=>$gv['rec_id'],
					'name'=>$gv['goods_name'],
					'attribute'=>trim($gv['goods_attr']),
					'price_unit'=>$gv['goods_price'],
					'line_note'=>'技师端订单'
			);
			$data['lines'][]=array(
				0,$gv['rec_id'],$order_line_detail
			);
		}
		return $data;
	}

	
	//参数 需要同步的订单ordersn  同步单个订单到gooderp
	public function syncOrderByOrdersnsFromQueue($ordersn){
		$sql = "SELECT a.order_id,a.order_sn,a.from_supplier_id,a.user_id,a.add_time,a.goods_amount,b.sync_erp,b.erp_id,a.mobile,b.contacts_phone,b.supplier_name,a.technican_name,a.technican_sn FROM ". $GLOBALS['ecs']->table('order_info') ."  a LEFT JOIN ". $GLOBALS['ecs']->table('supplier') ." b ON a.from_supplier_id=b.supplier_id WHERE  a.order_sn ='".$ordersn."'";
		$orderInfo = $GLOBALS['db']->getRow($sql);
		$sync_data = $this->getOrderSyncData($orderInfo);
		
		if(isset($sync_data) && !empty($sync_data)){
			@file_put_contents("sync_order.txt", "同步的订单信息:".json_encode($sync_data).PHP_EOL, FILE_APPEND);
			$res = $this->erpObj->execute_kw($this->odoo_erp_db, $this->odooUid, ODOO_ERP_PSD,"pos.order","sync_pos_order",array($sync_data),array());
            @file_put_contents("sync_order.txt", "同步的订单结果:".json_encode($res).PHP_EOL, FILE_APPEND);
			
			if(isset($res['SuccessCode']) && $res['SuccessCode'] == 1){
				$sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') . " SET sync_erp = 1,sync_result = '同步成功' WHERE order_id=".$sync_data['external_id'];
				$GLOBALS['db']->query($sql);
				return array('SuccessCode'=>1);
			}else{
				return array('SuccessCode'=>0);
			}
		}else{
			return array('SuccessCode'=>0);
		}
	}

	public function syncSupplierBySupplierid($supplier_id){
		
	}




	/*
	 *功能：传输审核通过的店铺信息
	 *参数：array
	 *返回：
	 */
	public  function transmit_data_to_shang_pu($data){
		
		$res = $this->erpObj->execute_kw($this->odoo_erp_db, $this->odooUid, ODOO_ERP_PSD,"spa.branch","sync_spa_branch",array($data),array());

		return $res;
	}
	
}
