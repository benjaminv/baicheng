<?php
define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(dirname(__FILE__) . '/includes/cls_json.php');
require(dirname(__FILE__) . '/includes/lib_order.php');
require(dirname(__FILE__) . '/includes/lib_v_user.php');
require(dirname(__FILE__) . '/weixin/wechat.class.php');
error_reporting(E_ALL & ~E_NOTICE);
if ((DEBUG_MODE & 2) != 2)
{
    $smarty->caching = true;
}

$user_id = $_SESSION['user_id'];
//if($user_id==0){sys_msg("请先登录后再来分享！", "返回会员登录页面" ,"user.php");}
$oid=intval($_GET['oid']);
$order=order_info($oid);
if(!$order){sys_msg('订单不存在，无法处理！');}
if($order['pay_status']!=2){sys_msg('此订单还未付款，暂时不能分享！');}
$act=!empty($_REQUEST['act'])?$_REQUEST['act']:'';
$is_weixin = is_weixin();
if($act=='upload'){
    if($order['user_id']==$user_id){
        $media_id = $_REQUEST['serverId'];
        $filename='';
        $path_img='';
        $thumb='';
        if(strlen($media_id)>=10){//表示有图片，需要做保存
            $a_token = prince_access_token($db);
            if(strlen($a_token)>=64){
                //sys_msg($a_token);
                $wxdownUrl = "http://file.api.weixin.qq.com/cgi-bin/media/get?access_token=" . $a_token . "&media_id=" . $media_id . "";
                $resData = get_http($wxdownUrl);
                //sys_msg($resData);
                if (strlen($resData)>110) {
                    $path_a = "images/Image/" . date('Ym') . "/";
                    $path = "./../" . $path_a;
                    if (!file_exists($path)) {
                        mkdir($path);
                    }
                    $filename = $media_id . '.jpg';
                    //sys_msg($path . $filename);
                    $ret = file_put_contents($path . $filename, $resData, true);
                    if ($ret) {
                        //include_once(ROOT_PATH . 'includes/cls_image.php');
                        //$image = new cls_image($_CFG['bgcolor']);
                        //ini_set("memory_limit", -1);
                        //$thumb = $image->make_thumb($path . $filename, 100, 100);
                        $path_img = $path_a . $filename;
                        $path_img=$ecs->get_domain().'/'.$path_img;
                    }
                }
            }
        }

        $json   = new JSON;
        if($path_img==""){sys_msg('上传失败，请重新选择上传！');}
        $result=[
            'ok'=>true,
            'path_img'=>$path_img
        ];
        die($json->encode($result));
    }
}elseif($act=='wxupload') {//上传保存图片并自动发表评价和晒图
    //判断是否是自已的订单，如果不是将不能评价和晒单
    $zd_fx_wx_money=0;
    if($order['user_id']==$user_id){
        $media_id = $_REQUEST['serverId'];
        //sys_msg($media_id);
        $filename='';
        $path_img='';
        $thumb='';
        if($media_id!=''){
            $path_a = "images/Image/" . date('Ym') . "/";
            $path = "./../" . $path_a;
            if (!file_exists($path)) {
                mkdir($path);
            }
            $filename = $media_id . '.jpg';
            include_once(ROOT_PATH . 'includes/cls_image.php');
            $image = new cls_image($_CFG['bgcolor']);
            ini_set("memory_limit", -1);
            $thumb = $image->make_thumb($path . $filename, 100, 100);
            $path_img = $path_a . $filename;
        }

        /*if(strlen($media_id)>=10){//表示有图片，需要做保存
            $a_token = prince_access_token($db);
            if(strlen($a_token)>=64){
                //sys_msg($a_token);
                $wxdownUrl = "http://file.api.weixin.qq.com/cgi-bin/media/get?access_token=" . $a_token . "&media_id=" . $media_id . "";
                $resData = get_http($wxdownUrl);
                //sys_msg($a_token);
                if (strlen($resData)>110) {
                    $path_a = "images/Image/" . date('Ym') . "/";
                    $path = "./../" . $path_a;
                    if (!file_exists($path)) {
                        mkdir($path);
                    }
                    $filename = time() . '_' . $k . '.jpg';
                    //sys_msg($path . $filename);
                    $ret = file_put_contents($path . $filename, $resData, true);
                    if ($ret) {
                        include_once(ROOT_PATH . 'includes/cls_image.php');
                        $image = new cls_image($_CFG['bgcolor']);
                        ini_set("memory_limit", -1);
                        $thumb = $image->make_thumb($path . $filename, 100, 100);
                        $path_img = $path_a . $filename;
                    }
                }
            }
        }*/
        //晒单处理
        $title = '百城大计';//默认标题
        $message = '好评';//默认内容
        $add_time = gmtime();//发表时间
        $status = 1;//是否需要审核(0需要审核，1不需要审核)
        $hide_username = 0;//是否匿名晒单

        $user_info = $db->getRow("SELECT * FROM " . $ecs->table('users') . " WHERE user_id = '$user_id'");
        $comment_type=0;
        $email = $user_info['email'];
        $user_name = $user_info['user_name'];
        $content='分享自动好评';
        $comment_rank=5;
        $server = 5;
        $send = 5;
        $shipping = 5;
        $ip_address = real_ip();
        $o_sn=$order['order_sn'];
        $buy_time=$order['add_time'];
        //sys_msg($o_sn);
        //找出还没晒单的记录处理自动晒单
        $order_goods = $db->getAll("SELECT o.rec_id,g.*,(select shaidan_id from " . $ecs->table('shaidan') . " where rec_id=o.rec_id) as shaidan_id FROM " . $ecs->table('order_goods') . " as o left join " . $ecs->table('goods') . " as g on o.goods_id = g.goods_id WHERE  order_id = '$oid' having (select count(shaidan_id) from " . $ecs->table('shaidan') . " where rec_id=o.rec_id)<=0");
        //sys_msg($o_sn);
        if ($order_goods) {
            foreach ($order_goods as $k => $v) {
                $id_value=$v['goods_id'];
                $rec_id=$v['rec_id'];
                $sql = "INSERT INTO " . $ecs->table('comment') . "(comment_type, id_value, email, user_name, content, comment_rank, add_time, ip_address, user_id, status, rec_id, comment_tag, buy_time, hide_username, order_id)" . "VALUES ('$comment_type', '$id_value', '$email', '$user_name', '$content', '$comment_rank', '$add_time', '$ip_address', '$user_id', '$status', '$rec_id', '$comment_tag', '$buy_time', '$hide_username', '$oid')";
                $db->query($sql);

                $sql = "INSERT INTO " . $ecs->table('shop_grade') . "(user_id, user_name, add_time,  server, send, shipping, order_id, order_sn)" . "VALUES ('$user_id', '$user_name', '$add_time', '$server', '$send', '$shipping', '$oid', '$o_sn')";
                $db->query($sql);

                $sql = "INSERT INTO " . $ecs->table('shaidan') . "(rec_id, goods_id, user_id, title, message, add_time, status, hide_username)" . "VALUES ('$v[rec_id]', '$v[goods_id]', '$user_id', '$title', '$message', '$add_time', '$status', '$hide_username')";
                $db->query($sql);
                $shaidan_id = $db->insert_id();
                if($path_img!=''&&$shaidan_id){
                    $sql = "INSERT INTO " . $ecs->table('shaidan_img') . "(`shaidan_id`, `desc`, `image`, `thumb`)" . "VALUES ('$shaidan_id', '" . $filename . "', '$path_img', '$thumb')";
                    $db->query($sql);
                }
                $db->query("UPDATE " . $ecs->table('order_goods') . " SET comment_state = 1 WHERE rec_id = '$rec_id'");
            }
            //增加钱
            $ret = $GLOBALS['db']->getRow("SELECT * FROM ".$GLOBALS['ecs']->table('weixin_config')." WHERE `id` = 1");
            $zd_fx_wx_money=$ret['zd_fx_wx_money'];
            log_account_change($order['user_id'], $zd_fx_wx_money, 0, 0, 0, '订单号【'.$order['order_sn'].'】分享佣金', ACT_OTHER);
        }
    }
    $json   = new JSON;
    $result=[
        'fxno'=>true,
        'msg'=>'分享成功 ',
        'gotono'=>true,
        'gotourl'=>'fx.php?u='.$order['user_id'].'&oid='.$oid.'&fx_money='.$zd_fx_wx_money.'&fxno=true',
    ];
    die($json->encode($result));
}else{
    if($_REQUEST['fxno']==true){
        $fx_money=intval($_REQUEST['fx_money']);
        $msg='您已分享成功';
        if($fx_money>0){$msg='您分享成功，获得'.$fx_money.'元账户余额，详情请在账户明细中查看';}
        show_message($msg, '返回订单详细页面', 'user.php?act=order_detail&order_id='.$oid, 'info');
    }
    //是否生成过二维码
    if(is_erweima($order['user_id']) == 0)
    {
        //获取用户对应的所属店铺
        $myuid = $order['user_id'];
        $mysupllierId=$GLOBALS['db']->getOne("SELECT supplierId FROM ".$GLOBALS['ecs']->table('users')." WHERE user_id=".$myuid);
        if($mysupllierId==0){
            $config = unserialize($GLOBALS['_CFG']['affiliate_supplier']);
            empty($config) && $config = array();
            $mysupllierId=$config['config']['default_reg_supplier_id'];
        }
        require('includes/phpqrcode.php');
        $selfUrl='http://' . $_SERVER ['HTTP_HOST'].'/mobile/supplier_qrcode.php?u='.$order['user_id'].'&suppId='.$mysupllierId.'&erweima_type=1';
        $qrcode = new QRcode();
        $imagesname = time().$myuid.".png";
        $qcode = $qrcode->png($selfUrl,"images/qrcode/".$imagesname,"L",6,2);
        $sql = "INSERT INTO ".$GLOBALS['ecs']->table('user_qrcode')."(user_id, code) value ('$myuid', 'images/qrcode/$imagesname')";
        $GLOBALS['db']->query($sql);
    }


    $shaidan_info=$db->getRow("SELECT simg.image,s.message FROM " . $ecs->table('shaidan_img') . " as simg LEFT JOIN " . $ecs->table('shaidan') . " as s ON s.shaidan_id=simg.shaidan_id WHERE s.shaidan_id=simg.shaidan_id And s.rec_id in(select rec_id from " . $ecs->table('order_goods') . " where order_id=".$oid.") Order By s.add_time Desc limit 1");
    $shaidan_img = $shaidan_info['image'];
	if($shaidan_img!=''){
        $shaidan_img=$ecs->get_domain().'/'.$shaidan_img;
    }
	$shaidan_content = $shaidan_info['message'];


    $weixin_info = $db->getRow("select * from ".$ecs->table('weixin_config')." where id='1'");
    //print_r('$is_weixin='.$is_weixin);
    //print_r($weixin_info);
    if($is_weixin&&$weixin_info['appid'] && $weixin_info['appsecret']){
        require_once "wxjs/jssdk.php";
        $jssdk = new JSSDK($weixin_info['appid'], $weixin_info['appsecret']);
        $signPackage = $jssdk->GetSignPackage();
        //print_r($signPackage);
        $smarty->assign('weixin_info', $weixin_info);
        $smarty->assign('signPackage', $signPackage);

    }



     assign_template();
    assign_dynamic('fx');
        $position = assign_ur_here();
        $smarty->assign('page_title',      $position['title']);    // 页面标题
        $smarty->assign('ur_here',         $position['ur_here']);  // 当前位置

        /* meta information */
        $smarty->assign('keywords',        htmlspecialchars($_CFG['shop_keywords']));
        $smarty->assign('description',     htmlspecialchars($_CFG['shop_desc']));
        //zhouhui 如果是获取的uid 则读取它.

        $smarty->assign('user_info',	   get_user_info_by_user_id($order['user_id']));
        $smarty->assign('erweima',get_erweima_by_user_id($order['user_id']));
        $smarty->assign('user_id',$user_id);

    $smarty->assign('order',$order);
    $smarty->assign('shaidan_img',$shaidan_img);
    //$smarty->assign('user',$user);
    $smarty->display('fx.dwt');
}


function prince_access_token($db) {
    $ret = $GLOBALS['db']->getRow("SELECT * FROM ".$GLOBALS['ecs']->table('weixin_config')." WHERE `id` = 1");
    $appid = $ret['appid'];
    $appsecret = $ret['appsecret'];
    $access_token = $ret['access_token'];
    $dateline = $ret['expire_in'];
    $time = time();

    //if(($time - $dateline) >= 3000) {
    if(1) {
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$appsecret";
        $ret_json = prince_curl_get_contents($url);
        //print_r('$ret_json='.$ret_json);
        //print_r('<br>');
        $ret = json_decode($ret_json);
        if($ret->access_token){
            $GLOBALS['db']->query("UPDATE `ecs_weixin_config` SET `access_token` = '$ret->access_token',`expire_in` = '$time' WHERE `id` =1;");
            return $ret->access_token;
        }
    }elseif(empty($access_token)) {
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$appsecret";
        $ret_json = prince_curl_get_contents($url);
        $ret = json_decode($ret_json);
        if($ret->access_token){
            $GLOBALS['db']->query("UPDATE `ecs_weixin_config` SET `access_token` = '$ret->access_token',`expire_in` = '$time' WHERE `id` =1;");
            return $ret->access_token;
        }
    }else {
        return $access_token;
    }
}


function https_request($url, $data = null){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    if (class_exists('\\CURLFile')) {
        curl_setopt($curl, CURLOPT_SAFE_UPLOAD, true);
    }
    else if (defined('CURLOPT_SAFE_UPLOAD')) {
        curl_setopt($curl, CURLOPT_SAFE_UPLOAD, false);
    }

    if (!empty($data)){
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($curl);
    curl_close($curl);

    dm299_log($output);
    return $output;
}

function get_http($url)
{
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_NOBODY, 0);    //只取body头
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $package = curl_exec($ch);
        curl_close($ch);
        return $package;
}

function prince_curl_get_contents($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]);
    curl_setopt($ch,CURLOPT_FOLLOWLOCATION,1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $r = curl_exec($ch);
    curl_close($ch);
    return $r;
}

function prince_curl_grab_page($url,$data,$proxy='',$proxystatus='',$ref_url='') {
    $header = array('Expect:');
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
    curl_setopt($ch, CURLOPT_TIMEOUT, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($proxystatus == 'true') {
        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, TRUE);
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
    }
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_URL, $url);
    if(!empty($ref_url)){
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_REFERER, $ref_url);
    }
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    ob_start();
    return curl_exec ($ch);
    ob_end_clean();
    curl_close ($ch);
    unset($ch);
}

//输出JSON串
function sys_msg($msg='',$links='',$hrefs=''){
    $is_aiax = isset($_REQUEST['is_ajax']) ? $_REQUEST['is_ajax'] : 0;
    if($is_aiax==1){
        $json   = new JSON;
        $result=[
            'errno'=>true,
            'msg'=>$msg,
            'gotono'=>($hrefs!=''?true:false),
            'gotourl'=>$hrefs,
        ];
        die($json->encode($result));
    }else{
        show_message($msg, $links, $hrefs);
    }
}
?>