import { BASESRC, formatDate, handleErrorSrc } from './util'

// 处理商品价格符号
const yuan = (value = 0) => {
  var p = parseFloat(value);
  var isDot = ((p.toString()).indexOf(".")) != -1;
  var fix = !isDot ? 0 : 2;
  return "¥ " + p.toFixed(fix);
}

// 处理价格转为保留小数点后两位字符串
const priceToFixed = (value = 0, yuan) => {
  return (yuan ? yuan + parseFloat(value).toFixed(2) : parseFloat(value).toFixed(2))
}

// 虚拟币保留小数点四位
const bi = (value = 0, fixedNum = 4, symbol) => {
  return symbol ? symbol + parseFloat(value).toFixed(fixedNum) : parseFloat(value).toFixed(fixedNum)
}

// 处理发布信息的时间
const getTimer = (value) => {
  var minute = 1000 * 60;
  var hour = minute * 60;
  var day = hour * 24;
  var week = day * 7;
  var month = day * 30;
  var year = month * 12;
  var time1 = new Date().getTime(); //当前的时间戳
  var time2 = parseInt(value * 1000); //指定时间的时间戳
  var time = time1 - time2;

  var result = null;
  if (time < 0) {
  } else if (time / year >= 1) {
    result = parseInt(time / year) + "年前";
  } else if (time / month >= 1) {
    result = parseInt(time / month) + "月前";
  } else if (time / week >= 1) {
    result = parseInt(time / week) + "周前";
  } else if (time / day >= 1) {
    result = parseInt(time / day) + "天前";
  } else if (time / hour >= 1) {
    result = parseInt(time / hour) + "小时前";
  } else if (time / minute >= 1) {
    result = parseInt(time / minute) + "分钟前";
  } else {
    result = "刚刚发布";
  }
  return result;
}


// 当数量超过5位数时，已w替代
const praise = (value) => {
  let num = 0;
  if (value > 10000) {
    num = (value - (value % 1000)) / 10000 + "w";
  } else {
    num = value;
  }
  return num;
}


export {
  BASESRC,
  yuan,
  priceToFixed,
  formatDate,
  handleErrorSrc,
  bi,
  getTimer,
  praise
}