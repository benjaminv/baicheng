/*
时间倒计时插件
TimeDown.js
endDateStr格式：2019-11-25 8:00:45
*/
function TimeDown(id, endDateStr) {
    //结束时间
    var endDate = new Date(endDateStr);
    //当前时间
    var nowDate = new Date();
    //相差的总秒数
    var totalSeconds = parseInt((endDate - nowDate) / 1000);
    //天数
    var days = Math.floor(totalSeconds / (60 * 60 * 24));
    //取模（余数）
    var modulo = totalSeconds % (60 * 60 * 24);
    //小时数
    var hours = Math.floor(modulo / (60 * 60));
    modulo = modulo % (60 * 60);
    //分钟
    var minutes = Math.floor(modulo / 60);
    //秒
    var seconds = modulo % 60;
    var strTime = '';
    if(days > 0){
        strTime += days + "天" + hours + "小时" + minutes + "分钟" + seconds + "秒";
    }
    else if(hours > 0){
        strTime += hours + "小时" + minutes + "分钟" + seconds + "秒";
    }else if(minutes > 0){
        strTime += minutes + "分钟" + seconds + "秒";
    }else if(seconds > 0){
        strTime += seconds + "秒";
    }
    //输出到页面
    // document.getElementById(id).innerHTML = days + "天" + hours + "小时" + minutes + "分钟" + seconds + "秒";
    document.getElementById(id).innerHTML = strTime;
    //延迟一秒执行自己
    setTimeout(function () {
        TimeDown(id, endDateStr);
    }, 1000)
}