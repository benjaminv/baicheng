<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>video获取第一帧</title>
    <style type="text/css">
        html, body{ width: 100%; height: 100%; text-align: center;}
        li{ position: relative; display: inline-block; list-style: none;}
        canvas, img{ width: 600px; height: 350px; border: 1px solid darkgray;}
        button{ padding: 6px 20px; margin: 6px 3px;}
        h3{color: red;}
    </style>
</head>

<body>
<header>
    <video id="video" src="http://bcshop.lian-mei.com/images/file/20190726/1564139323233696.mp4" poster="video.php?act=get_image&video_img=images/file/20190726/1564139323233696.mp4" controls width="600" height="400" loop >

    </video>
    <p>视频播放器-VIDEO</p>
</header>

<section>
    <ul>
        <li>
            <img id="rendering-img" class="img" crossOrigin="anonymous" />
            <p>获取当前帧到-IMG</p>
        </li>
        <li>
            <canvas id="canvas" class="canvas" ></canvas>
            <p>视频同步渲染到-CANVAS</p>
        </li>
    </ul>
</section>

<footer>
    <button id="video-play-btn">视频播放</button>
    <button id="video-pause-btn">视频暂停</button>
    <button id="video-volumed-btn">音量增大</button>
    <button id="video-volumex-btn">音量减小</button>
    <button id="fullscreen-btn">视频全屏</button>
    <button id="get-current-btn">获取当前视频帧</button>
    <button id="rendering-btn">视频同步渲染</button>
    <button id="body-bg-btn">渲染到body背景</button>
</footer>

<h3>注：请服务器中运行才有效果哦，如用：WampServer64环境将代码放在 www 目录下，如用phpStudy工具将站点目录指到这个文件！</h3>
</body>

<script src="https://cdn.bootcss.com/jquery/3.4.1/jquery.min.js" type="text/javascript" charset="utf-8"></script>

<script type="text/javascript">
    (function() {

        var video = document.getElementById("video");
        var canvas = document.getElementById("canvas");
        var interval1 = interval2 = null;

        //截取视频画面
        var CaptureFirstFrame = function() {

            this.saveImageInfo = function() {
                console.log('base64图片：', canvas.toDataURL("image/png"))
            }

        };

        //打开全屏方法
        CaptureFirstFrame.prototype = {
            CaptureVideo: function (img, rsy, bbg) {
                //canvas 缩放比率
                this.scale = 1;

                //创建canvas元素
                this.cvs = document.createElement("canvas");

                //设置canvas画布大小
                this.cvs.width = canvas.width = video.videoWidth * this.scale;
                this.cvs.height = canvas.height = video.videoHeight * this.scale;

                //设置canvas画布内容、位置
                this.cvs.getContext('2d').drawImage(video, 0, 0, this.cvs.width, this.cvs.height);
                //注：
                /*
                 * canvas.toDataURL("image/png", 1) 方法可能会出错！！！
                 * 因为 【 如果视频文件所在的 域 和 当前index.html页面所在域不同，就会出现跨域传输的问题】，【及便是给img标签加上crossOrigin': 'anonymous' 也没用！】
                 * 所以 请将 视频文件 和 当前index.html页面放在同一个域中，并在服务器中打开（用phpStudy工具等），才能正常运行。
                 */

                if (img) {
                    $('#rendering-img').attr({ 'crossOrigin': 'anonymous', 'src': this.cvs.toDataURL("image/png", 1) });
                };

                if (rsy) {
                    canvas.getContext('2d').drawImage(video, 0, 0, this.cvs.width, this.cvs.height);
                };

                if (bbg) {
                    $(document.body).css('background-image', 'url(' + this.cvs.toDataURL("image/png", 1) + ')');
                };
            },

            //全屏
            openFullscreen: function (element) {
                if (element.requestFullscreen) {
                    element.requestFullscreen();
                } else if (element.mozRequestFullScreen) {
                    element.mozRequestFullScreen();
                } else if (element.msRequestFullscreen) {
                    element.msRequestFullscreen();
                } else if (element.webkitRequestFullscreen) {
                    element.webkitRequestFullScreen();
                }
            }
        };

        var V = new CaptureFirstFrame();

        //监听视频加载完成时 获取第一帧
        video.addEventListener('loadeddata', function() {
            V.CaptureVideo(true);
        }, false);

        //监听视频播放时
        video.addEventListener('play', function() {
            //播放时
        }, false);

        //监听视频暂停时
        video.addEventListener('pause', function() {
            clearInterval(interval1);
            clearInterval(interval2);
        }, false);

        //视频播放
        document.getElementById("video-play-btn").addEventListener('click', function() {
            video.play();
        }, false);

        //视频暂停
        document.getElementById("video-pause-btn").addEventListener('click', function() {
            video.pause();
        }, false);

        //音量增大
        document.getElementById("video-volumed-btn").addEventListener('click', function() {
            (video.volume > 1 || video.volume == 1) ? video.volume = 1 : video.volume = video.volume + 0.1;
        }, false);

        //音量增大
        document.getElementById("video-volumex-btn").addEventListener('click', function() {
            (video.volume < 0.2 || video.volume == 0) ? video.volume = 0 : video.volume = video.volume - 0.1;
        }, false);

        //视频全屏
        document.getElementById("fullscreen-btn").addEventListener('click', function() {
            V.openFullscreen(video);
        }, false);

        //获取当前帧
        document.getElementById("get-current-btn").addEventListener('click', function() {
            V.saveImageInfo();
            V.CaptureVideo(true);
        }, false);

        //渲染到cnavas
        document.getElementById("rendering-btn").addEventListener('click', function() {

            clearInterval(interval1);
            interval1 = window.setInterval(function() {
                V.CaptureVideo(false, true);
            }, 1000/60);
            //window.requestAnimationFrame();
        }, false);

        //渲染到body背景
        document.getElementById("body-bg-btn").addEventListener('click', function() {
            clearInterval(interval2);
            interval2 = window.setInterval(function() {
                V.CaptureVideo(false, false, true);
            }, 1000/60);
        }, false);

    })();

</script>
</html>