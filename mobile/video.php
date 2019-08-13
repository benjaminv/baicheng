<?php
/**
 * Created by PhpStorm.
 * User: user010
 * Date: 2019/8/6
 * Time: 14:07
 */

define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');

if(!empty($_REQUEST['act']) && $_REQUEST['act'] == 'get_image')
{
    $video = $_REQUEST['video_img'] ? $_REQUEST['video_img'] : '';
    $debug = $_REQUEST['debug'] ? $_REQUEST['debug'] : '';


    if($video){
        //...

        $file = ROOT_PATH_WAP.$video;

        $pos = strrpos($file,'.');

        $destFilePath = substr($file,0,$pos).'.jpg';

        getVideoImg($file,$destFilePath);

        if($debug){
            echo '<img src="/'.str_replace(ROOT_PATH_WAP, '', $destFilePath).'">';
        }else {
            echo str_replace(ROOT_PATH_WAP, '', $destFilePath);
        }
    }
    echo '';
    die;

}

//获取视频图片
function getVideoImg($file,$destFilePath){
    //$command = "/usr/bin/ffmpeg -i {$file} -y -f image2 -ss 1 -vframes 1 -s 640x600 {$destFilePath}";

    if(PHP_OS == 'Darwin'){ //mac
        $testCmd = "/usr/local/bin/ffmpeg";
    }else{
//        $testCmd = "/usr/bin/ffmpeg";
        $testCmd = "/usr/local/bin/ffmpeg/bin/ffmpeg";  //linux path
    }


    $command = $testCmd." -i {$file} -y -f image2 -ss 1 -vframes 1 -s 640x600 {$destFilePath}";
    //$str = "/sbin/ffmpeg/bin/ffmpeg -i ".$file." -y -f mjpeg -ss 3 -t 1 -s 200x200 ffmpeg.jpg";

    ob_start();
    passthru($command);
    $info = ob_get_contents();
    ob_end_clean();
}