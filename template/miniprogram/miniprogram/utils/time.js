//时间戳转换成日期时间(年月日)
function js_date_time(unixtime) {
  var dateTime = new Date(parseInt(unixtime) * 1000)
  var year = dateTime.getFullYear();
  var month = dateTime.getMonth() + 1;
  if(month > 0 && month <10){
    month = "0" + month;
  }
  var day = dateTime.getDate();
  if(day > 0 && day < 10){
    day = "0"+day;
  }
  var hour = dateTime.getHours();
  var minute = dateTime.getMinutes();
  var second = dateTime.getSeconds();
  var now = new Date();
  var now_new = Date.parse(now.toDateString());  //typescript转换写法
  var milliseconds = now_new - dateTime;
  var timeSpanStr = year + '-' + month + '-' + day  ;
  return timeSpanStr;
}

function js_date_time_second(time){
  var date = new Date(time * 1000);
  var y = date.getFullYear();
  var m = date.getMonth() + 1;
  m = m < 10 ? ('0' + m) : m;
  var d = date.getDate();
  d = d < 10 ? ('0' + d) : d;
  var h = date.getHours();
  h = h < 10 ? ('0' + h) : h;
  var minute = date.getMinutes();
  var second = date.getSeconds();
  minute = minute < 10 ? ('0' + minute) : minute;
  second = second < 10 ? ('0' + second) : second;
  var timeStr = y + '-' + m + '-' + d + '　' + h + ':' + minute + ':' + second;
  return timeStr;
}



module.exports = {
  js_date_time: js_date_time,
  js_date_time_second: js_date_time_second
}
