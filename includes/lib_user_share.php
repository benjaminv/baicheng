<?php
function erweima_share($url,$filename)
{

    require_once '../includes/phpqrcode.php';
    //$goods_id = isset($_REQUEST['id'])  ? intval($_REQUEST['id']) : 0;
    $data = ($url);
    $img = $_REQUEST['img'];
    $logo = str_replace("..", ".", $img);	// 中间那logo图
    $errorCorrectionLevel = 'L';//容错级别
    $matrixPointSize = 6;//生成图片大小
    //生成二维码图片
    QRcode::png($data, '../qrcode.png', $errorCorrectionLevel, $matrixPointSize, 2);
    $QR = '../qrcode.png';//已经生成的原始二维码图
    if ($logo !== FALSE) {
        $QR = imagecreatefromstring(file_get_contents($QR));
        $logo = imagecreatefromstring(file_get_contents($logo));
        $QR_width = imagesx($QR);//二维码图片宽度
        $QR_height = imagesy($QR);//二维码图片高度
        $logo_width = imagesx($logo);//logo图片宽度
        $logo_height = imagesy($logo);//logo图片高度
        $logo_qr_width = $QR_width / 5;
        $scale = $logo_width/$logo_qr_width;
        $logo_qr_height = $logo_height/$scale;
        $from_width = ($QR_width - $logo_qr_width) / 2;
        //重新组合图片并调整大小
        imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,
            $logo_qr_height, $logo_width, $logo_height);
    }
    if (!file_exists($_SERVER['DOCUMENT_ROOT'] ."/data/users/")) {
        mkdir($_SERVER['DOCUMENT_ROOT'] ."/data/users/");
    }
    imagepng($QR, '../data/users/'.$filename.'.png');
    $img='/data/users/'.$filename.'.png';
    return $img;
}
/**
 * 生成海报
 * @param $background_w  海报地图宽度
 * @param $background_h 海报地图高度
 * @param $background  海报地图路径
 * @param $erweima_w  二维码宽度
 * @param $erweima_h 二维码高度
 * @param $erweima_x  二维码离左侧距离
 * @param $erweima_y  二维码离顶部高度
 * @param $erweima_url 二维码链接
 * @param $headimg_file 头像路径
 * @param $headimg_status  是否显示头像  0：不显示  1：显示
 * @param $headimg_w  头像宽度
 * @param $headimg_h 头像高度
 * @param $headimg_x  头像离左侧距离
 * @param $headimg_y  头像离右侧距离
 * @param $user_name_status  是否显示用户名  1：显示
 * @param $user_name_x  用户名离左侧距离
 * @param $user_name_y  用户名离顶部距离
 * @param $user_name_font  用户名字体大小
 * @param $user_name_color  用户名字体颜色
 * @param $bill_text  文本副本数组  array（text_x,text_y,text_font,text_color,text)
 * @param $bill_img  图片数组  array（img_w,img_h,img_x,img_y,img)
 * @param $sharepng_name  生成海报名称，唯一
 *@param $erweima_name  二维码名称，唯一
 **/
function createSharePng($sharepng_name,$background_w,$background_h,$background,$erweima_name,$erweima_url,$erweima_w,$erweima_h,$erweima_x,$erweima_y,$headimg_file,$headimg_status=0,$headimg_w,$headimg_h,$headimg_x,$headimg_y,$user_name,$user_name_status=0,$user_name_x,$user_name_y,$user_name_font,$user_name_color,$bill_text,$bill_img)
{

    $erweima_share=erweima_share($erweima_url,$erweima_name);

    //创建画布
    $im = imagecreatetruecolor($background_w, $background_h);

    //填充画布背景色
    $color = imagecolorallocate($im, 255, 241, 242);
    imagefill($im, 0, 0, $color);

    //字体文件
    $font_file = "../includes/code_png/msyh.TTF";
    $font_file_bold =$font_file;// "code_png/msyh_bold.ttf";

    //设定字体的颜色
    $font_color_1 = ImageColorAllocate ($im, 148, 106, 78);

    list($g_w,$g_h) = getimagesize($_SERVER['DOCUMENT_ROOT'] .$background);
    $backgroundImg = @imagecreatefromstring(file_get_contents($_SERVER['DOCUMENT_ROOT'] .$background));
    imagecopyresized($im, $backgroundImg, 0, 0, 0, 0, $background_w, $background_h, $g_w, $g_h);


    //二维码图片
    list($g_w,$g_h) = getimagesize($_SERVER['DOCUMENT_ROOT'] .$erweima_share);
    $goodImg = @imagecreatefromstring(file_get_contents($_SERVER['DOCUMENT_ROOT'] .$erweima_share));//createImageFromFile($codeName);
    imagecopyresized($im, $goodImg, $erweima_x, $erweima_y, 0, 0, $erweima_w, $erweima_h, $g_w, $g_h);

    //头像
    if($headimg_status==1)
    {

        if(strpos($headimg_file,'http') !== false){
            $myGetImageSize=myGetImageSize($headimg_file, 'curl');
            $g_w=$myGetImageSize['width'];
            $g_h=$myGetImageSize['height'];
            if(empty($myGetImageSize))
            {
                $g_w=132;
                $g_h=132;
            }

        }else{
            list($g_w,$g_h) = getimagesize($headimg_file);

        }

       /* list($g_w,$g_h) = getimagesize($headimg_file);
        if(empty($g_w))
        {
            $myGetImageSize=myGetImageSize($headimg_file, 'curl');
            $g_w=$myGetImageSize['width'];
            $g_h=$myGetImageSize['height'];
        }*/

        $headimg = @imagecreatefromstring(file_get_contents($headimg_file));//createImageFromFile($codeName);

        imagecopyresized($im, $headimg, $headimg_x, $headimg_y, 0, 0, $headimg_w, $headimg_h, $g_w, $g_h);
    }

    //用户名
    if($user_name_status==1)
    {
        $rgb = hex2rgba($user_name_color, false, true);
        $font_color_1 = ImageColorAllocate ($im, $rgb[0], $rgb[1], $rgb[2]);
        imagettftext($im, $user_name_font,0, $user_name_x, $user_name_y, $font_color_1 ,$font_file, $user_name);
    }

    //新增文本
    foreach($bill_text as $key=>$val){
        $rgb = hex2rgba($val['text_color'], false, true);
        $font_color_1 = ImageColorAllocate ($im, $rgb[0], $rgb[1], $rgb[2]);
        imagettftext($im, $val['text_font'],0, $val['text_x'], $val['text_y'], $font_color_1 ,$font_file, $val['text']);
    }
    //新增图像
    foreach($bill_img as $key=>$val){
		//echo "<pre>"; print_r($_SERVER['DOCUMENT_ROOT'] .$val['img']);
        list($g_w,$g_h) = getimagesize($_SERVER['DOCUMENT_ROOT'] .$val['img']);
        $bill_img_d = @imagecreatefromstring(file_get_contents($_SERVER['DOCUMENT_ROOT'] .$val['img']));//createImageFromFile($codeName);
        imagecopyresized($im, $bill_img_d, $val['img_x'], $val['img_y'], 0, 0, $val['img_w'], $val['img_h'], $g_w, $g_h);
    }
    $file_name=$_SERVER['DOCUMENT_ROOT'] ."/data/users/".$sharepng_name.".jpg";

    //输出图片
    if($file_name){
        imagepng ($im,$file_name);
    }else{
        Header("Content-Type: image/png");
        imagepng ($im);
    }

    //释放空间
    imagedestroy($im);
    imagedestroy($backgroundImg);
    imagedestroy($goodImg);
    if($headimg_status==1)
    {
        imagedestroy($headimg);
    }
    if(!empty($bill_img))
    {
        imagedestroy($bill_img_d);
    }
}
/**
 * 分享图片生成
 * @param $gData  商品数据，array
 * @param $codeName 二维码图片
 * @param $fileName string 保存文件名,默认空则直接输入图片
 */
function createSharePng_user($codeName,$fileName = '',$user_detail){


    //创建画布
    $im = imagecreatetruecolor(596, 963);

    //填充画布背景色
    $color = imagecolorallocate($im, 255, 241, 242);
    imagefill($im, 0, 0, $color);

    //字体文件
    $font_file = "../includes/code_png/msyh.ttf";
    $font_file_bold =$font_file;// "code_png/msyh_bold.ttf";

    //设定字体的颜色
    $font_color_1 = ImageColorAllocate ($im, 148, 106, 78);

    list($g_w,$g_h) = getimagesize('../includes/code_png/daili.jpg');
    $backgroundImg = @imagecreatefromstring(file_get_contents('../includes/code_png/daili.jpg'));
    imagecopyresized($im, $backgroundImg, 0, 0, 0, 0, 596, 963, 596, 963);


    //商品图片
    list($g_w,$g_h) = getimagesize($codeName);
    $goodImg = @imagecreatefromstring(file_get_contents($codeName));//createImageFromFile($codeName);
    imagecopyresized($im, $goodImg, 450, 520, 0, 0, 80, 80, $g_w, $g_h);

    //商品描述
    imagettftext($im, 22,0, 265, 375, $font_color_1 ,$font_file, $user_detail['realname']);
    imagettftext($im, 13,0, 65, 450, $font_color_1 ,$font_file_bold, "授权方：江苏美奥纳米生物科技有限公司");
    //imagettftext($im, 13,0, 65, 480, $font_color_1 ,$font_file_bold, "代理商：".$user_detail['pu_realname']."-".$user_detail['pu_rank_name']);
    imagettftext($im, 13,0, 65, 480, $font_color_1 ,$font_file_bold, "被授权方：".$user_detail['realname']);
    imagettftext($im, 13,0, 65, 510, $font_color_1 ,$font_file_bold, "微信号：".$user_detail['nickname']);
    imagettftext($im, 13,0, 65, 540, $font_color_1 ,$font_file_bold, "手机号：".$user_detail['mobile_phone']);
    imagettftext($im, 13,0, 65, 570, $font_color_1 ,$font_file_bold, "代理商级别：".$user_detail['rank_name']);
    imagettftext($im, 13,0, 65, 600, $font_color_1 ,$font_file_bold, "代理商编码：".$user_detail['sn']);
    imagettftext($im, 13,0, 65, 630, $font_color_1 ,$font_file_bold, "授权范围：负责本公司旗下小肤君品牌的销售、推广和宣传");
    //输出图片
    if($fileName){
        imagepng ($im,$fileName);
    }else{
        Header("Content-Type: image/png");
        imagepng ($im);
    }

    //释放空间
    imagedestroy($im);
    imagedestroy($backgroundImg);
}
/**
 * 从图片文件创建Image资源
 * @param $file 图片文件，支持url
 * @return bool|resource    成功返回图片image资源，失败返回false
 */
function createImageFromFile($file){
    if(preg_match('/http(s)?:\/\//',$file)){
        $fileSuffix = getNetworkImgType($file);
    }else{
        $fileSuffix = pathinfo($file, PATHINFO_EXTENSION);
    }
    if(!$fileSuffix) return false;

    switch ($fileSuffix){
        case 'jpeg':
            $theImage = @imagecreatefromjpeg($file);
            break;
        case 'jpg':
            $theImage = @imagecreatefromjpeg($file);
            break;
        case 'png':
            $theImage = @imagecreatefrompng($file);
            break;
        case 'gif':
            $theImage = @imagecreatefromgif($file);
            break;
        default:
            $theImage = @imagecreatefromstring(file_get_contents($file));
            break;
    }

    return $theImage;
}

/**
 * 获取网络图片类型
 * @param $url  网络图片url,支持不带后缀名url
 * @return bool
 */
function getNetworkImgType($url){
    $ch = curl_init(); //初始化curl
    curl_setopt($ch, CURLOPT_URL, $url); //设置需要获取的URL
    curl_setopt($ch, CURLOPT_NOBODY, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);//设置超时
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //支持https
    curl_exec($ch);//执行curl会话
    $http_code = curl_getinfo($ch);//获取curl连接资源句柄信息
    curl_close($ch);//关闭资源连接

    if ($http_code['http_code'] == 200) {
        $theImgType = explode('/',$http_code['content_type']);

        if($theImgType[0] == 'image'){
            return $theImgType[1];
        }else{
            return false;
        }
    }else{
        return false;
    }
}

/**
 * 分行连续截取字符串
 * @param $str  需要截取的字符串,UTF-8
 * @param int $row  截取的行数
 * @param int $number   每行截取的字数，中文长度
 * @param bool $suffix  最后行是否添加‘...’后缀
 * @return array    返回数组共$row个元素，下标1到$row
 */
function cn_row_substr($str,$row = 1,$number = 10,$suffix = true){
    $result = array();
    for ($r=1;$r<=$row;$r++){
        $result[$r] = '';
    }

    $str = trim($str);
    if(!$str) return $result;

    $theStrlen = strlen($str);

    //每行实际字节长度
    $oneRowNum = $number * 3;
    for($r=1;$r<=$row;$r++){
        if($r == $row and $theStrlen > $r * $oneRowNum and $suffix){
            $result[$r] = mg_cn_substr($str,$oneRowNum-6,($r-1)* $oneRowNum).'...';
        }else{
            $result[$r] = mg_cn_substr($str,$oneRowNum,($r-1)* $oneRowNum);
        }
        if($theStrlen < $r * $oneRowNum) break;
    }

    return $result;
}

/**
 * 按字节截取utf-8字符串
 * 识别汉字全角符号，全角中文3个字节，半角英文1个字节
 * @param $str  需要切取的字符串
 * @param $len  截取长度[字节]
 * @param int $start    截取开始位置，默认0
 * @return string
 */
function mg_cn_substr($str,$len,$start = 0){
    $q_str = '';
    $q_strlen = ($start + $len)>strlen($str) ? strlen($str) : ($start + $len);

    //如果start不为起始位置，若起始位置为乱码就按照UTF-8编码获取新start
    if($start and json_encode(substr($str,$start,1)) === false){
        for($a=0;$a<3;$a++){
            $new_start = $start + $a;
            $m_str = substr($str,$new_start,3);
            if(json_encode($m_str) !== false) {
                $start = $new_start;
                break;
            }
        }
    }

    //切取内容
    for($i=$start;$i<$q_strlen;$i++){
        //ord()函数取得substr()的第一个字符的ASCII码，如果大于0xa0的话则是中文字符
        if(ord(substr($str,$i,1))>0xa0){
            $q_str .= substr($str,$i,3);
            $i+=2;
        }else{
            $q_str .= substr($str,$i,1);
        }
    }
    return $q_str;
}

function hex2rgba($color, $opacity = false, $raw = false) {
    $default = 'rgb(0,0,0)';
    //Return default if no color provided
    if(empty($color))
        return $default;
    //Sanitize $color if "#" is provided
    if ($color[0] == '#' ) {
        $color = substr( $color, 1 );
    }
    //Check if color has 6 or 3 characters and get values
    if (strlen($color) == 6) {
        $hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
    } elseif ( strlen( $color ) == 3 ) {
        $hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
    } else {
        return $default;
    }

    //Convert hexadec to rgb
    $rgb =  array_map('hexdec', $hex);

    if($raw){
        if($opacity){
            if(abs($opacity) > 1) $opacity = 1.0;
            array_push($rgb, $opacity);
        }
        $output = $rgb;
    }else{
        //Check if opacity is set(rgba or rgb)
        if($opacity){
            if(abs($opacity) > 1)
                $opacity = 1.0;
            $output = 'rgba('.implode(",",$rgb).','.$opacity.')';
        } else {
            $output = 'rgb('.implode(",",$rgb).')';
        }
    }

    //Return rgb(a) color string
    return $output;
}
/**
 * 获取远程图片的宽高和体积大小
 *
 * @param string $url 远程图片的链接
 * @param string $type 获取远程图片资源的方式, 默认为 curl 可选 fread
 * @param boolean $isGetFilesize 是否获取远程图片的体积大小, 默认false不获取, 设置为 true 时 $type 将强制为 fread
 * @return false|array
 */
function myGetImageSize($url, $type = 'curl', $isGetFilesize = false)
{
    // 若需要获取图片体积大小则默认使用 fread 方式
    $type = $isGetFilesize ? 'fread' : $type;

    if ($type == 'fread') {
        // 或者使用 socket 二进制方式读取, 需要获取图片体积大小最好使用此方法
        $handle = fopen($url, 'rb');

        if (! $handle) return false;

        // 只取头部固定长度168字节数据
        $dataBlock = fread($handle, 168);
    }
    else {
        // 据说 CURL 能缓存DNS 效率比 socket 高
        $ch = curl_init($url);
        // 超时设置
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        // 取前面 168 个字符 通过四张测试图读取宽高结果都没有问题,若获取不到数据可适当加大数值
        curl_setopt($ch, CURLOPT_RANGE, '0-167');
        // 跟踪301跳转
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        // 返回结果
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $dataBlock = curl_exec($ch);

        curl_close($ch);

        if (! $dataBlock) return false;
    }

    // 将读取的图片信息转化为图片路径并获取图片信息,经测试,这里的转化设置 jpeg 对获取png,gif的信息没有影响,无须分别设置
    // 有些图片虽然可以在浏览器查看但实际已被损坏可能无法解析信息
    $size = getimagesize('data://image/jpeg;base64,'. base64_encode($dataBlock));
    if (empty($size)) {
        return false;
    }

    $result['width'] = $size[0];
    $result['height'] = $size[1];

    // 是否获取图片体积大小
    if ($isGetFilesize) {
        // 获取文件数据流信息
        $meta = stream_get_meta_data($handle);
        // nginx 的信息保存在 headers 里，apache 则直接在 wrapper_data
        $dataInfo = isset($meta['wrapper_data']['headers']) ? $meta['wrapper_data']['headers'] : $meta['wrapper_data'];

        foreach ($dataInfo as $va) {
            if ( preg_match('/length/iU', $va)) {
                $ts = explode(':', $va);
                $result['size'] = trim(array_pop($ts));
                break;
            }
        }
    }

    if ($type == 'fread') fclose($handle);

    return $result;
}

//获取会员某商品的推广海报
function getPosterByGoodsId($goods_id,$user_id){
	
	$ecs = $GLOBALS['ecs'];
	$db = $GLOBALS['db'];
	if(empty($user_id) || !$db->getRow("SELECT user_id FROM ".$ecs->table('users') ." WHERE user_id=".intval($user_id))){
		return array('code'=>0,'msg'=>'会员不存在!');
	}
	$affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
	$poster_id = $affiliate['config']['poster_id'];
	$goods_info = $db->getRow("SELECT goods_name,goods_img,poster_id,shop_price,supplier_id,goods_thumb FROM ".$ecs->table('goods') ." WHERE goods_id=".intval($goods_id)); 
	$goods_attr = $db->getAll("SELECT attr_price FROM ".$ecs->table('goods_attr') ." WHERE goods_id=".intval($goods_id));
	if(!empty($goods_attr) && count($goods_attr)>1){
		$attr_prices = array_column($goods_attr,'attr_price');
		$max_price = $goods_info['shop_price'] + max($attr_prices);
		$min_price = $goods_info['shop_price'] + min($attr_prices);
		$goods_info['shop_price'] = $min_price."-".$max_price;
	}
	$goods_info['goods_img'] = '/'.$goods_info['goods_img'];
	
	if($goods_info['poster_id']){
		$poster_id = $goods_info['poster_id'];
	}
	if(empty($poster_id)){
		return array('code'=>0,'msg'=>'系统没有配置海报模板,请联系管理员!');
	}
	//查询店铺logo没有的话不进行logo变量替换
	if($goods_info['supplier_id']>0){
		$goods_info['logo'] = $GLOBALS['db']->getOne('SELECT value FROM ' . $GLOBALS['ecs']->table('supplier_shop_config') . ' WHERE code="shop_logo" AND  parent_id > 0 AND supplier_id='.$goods_info['supplier_id']);
		if(!empty($goods_info['logo'])){
			$goods_info['logo'] = "/".$goods_info['logo'];
		}
	}

	$sql=" SELECT * FROM " . $ecs->table('bill_template') . " WHERE id='" . $poster_id . "'";
    $bill_template=$db->getRow($sql);
    $sql=" SELECT * FROM " . $ecs->table('bill_text') . " WHERE bill_id='" . $poster_id . "'";
    $bill_text=$db->getAll($sql);
    $sql=" SELECT * FROM " . $ecs->table('bill_img') . " WHERE bill_id='" . $poster_id . "'";
    $bill_img=$db->getAll($sql);
	
	//进行别名替换{{goods_name}}{{goods_price}}{{goods_thumb}}
	$bill_text = posterAliasReplace($bill_text,$goods_info);
	$bill_img = posterAliasReplace($bill_img,$goods_info,1);
	//echo "<pre>";print_r($bill_img);exit;
    $sharepng_name="bill_template_goods_".$user_id."_".$goods_id."_".$poster_id."_".$bill_template['add_time'];
    $sharepng_file="/data/users/".$sharepng_name.".jpg";
    if(!file_exists('../'.$sharepng_file))
    {
        $erweima_name="bill_user_".$user_id."_".$poster_id;
		$erweima_url = 'http://'.$_SERVER['HTTP_HOST'].'/mobile/goods.php?id='.$goods_id."&parent_id=".$user_id;
		$sql=" SELECT nickname,headimgurl FROM " . $ecs->table('weixin_user') . " WHERE ecuid='" . $user_id . "'";
		$weixin_user=$db->getRow($sql);
        $user_name=$weixin_user['nickname'];
        $headimg_file=$weixin_user['headimgurl'];
        createSharePng($sharepng_name,$bill_template['background_w'],$bill_template['background_h'],$bill_template['background'],$erweima_name,$erweima_url,$bill_template['erweima_w'],$bill_template['erweima_h'],$bill_template['erweima_x'],$bill_template['erweima_y'],$headimg_file,$bill_template['headimg_status'],$bill_template['headimg_w'],$bill_template['headimg_h'],$bill_template['headimg_x'],$bill_template['headimg_y'],$user_name,$bill_template['user_name_status'],$bill_template['user_name_x'],$bill_template['user_name_y'],$bill_template['user_name_font'],$bill_template['user_name_color'],$bill_text,$bill_img);
    }

	return array('code'=>1,'post'=>$sharepng_file);
}

/*
**param data  海报图片或者文字数组
**param goods 商品信息
**param type  0为文字 1为图片
**
*/
function posterAliasReplace($data,$info,$type = 0){
	switch($type){
		case "0"://替换文字中的变量
			foreach($data as $k=>$v){
				if(strpos($v['text'],'{{goods_name}}') !== false){
					$data[$k]['text'] = str_replace("{{goods_name}}",$info['goods_name'],$v['text']);
				}

				if(strpos($v['text'],'{{goods_price}}') !== false){
					$data[$k]['text'] = str_replace("{{goods_price}}",$info['shop_price'],$v['text']);
				}
			}
			break;
		case "1":
			foreach($data as $k=>$v){
				if($v['alias'] == '{{goods_img}}'  && !empty($info['goods_img'])){
					//判断原图是否存在，存在则使用原图
					
					if(!file_exists($_SERVER['DOCUMENT_ROOT'].$info['goods_img'])){
						$data[$k]['img'] = '/'.$info['goods_thumb'];
					}else{
						$data[$k]['img'] = '/'.$info['goods_img'];
					}
				}
				if($v['alias'] == '{{supplier_logo}}' && !empty($info['logo'])){
					$data[$k]['img'] = $info['logo'];
				}
			}
			break;
		default:
			
	}
	return $data;
}


?>