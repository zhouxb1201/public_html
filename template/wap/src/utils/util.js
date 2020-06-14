import md5 from 'js-md5';

const authKey = process.env.AUTH_KEY

/**
 * 获取当前域名
 */
const getDomain = () => {
  let domain = ''
  if (!window.location.origin) {
    domain = window.location.protocol + "//" + window.location.hostname + (window.location.port ? ':' + window.location.port : '');
  } else {
    domain = window.location.origin
  }
  return domain
}
/**
 * 判断是否在微信环境
 */
const isWeixin = () => {
  let ua = navigator.userAgent.toLowerCase();
  return ua.match(/MicroMessenger/i) == "micromessenger" ? true : false
}

/**
 * 解析多种情况的path： ==> '/a/b'
 * 0，'/a/b/'
 * 1，'/a/b?mid=1' 
 * 2，'/a/b/2'
 * 3，'/a/b/2?mid=1'
 * 4，'外链'
 */
const analysPath = (path='') => {
  let newPath = ''

  if (path == '/') return '/'

  if (path.indexOf('http://') != -1 || path.indexOf('https://') != -1) {
    let baseUrl = getDomain() + '/wap'
    if (path.indexOf(baseUrl) != -1) {
      var splitPath = path.split(baseUrl)[1]
      newPath = splitPath.split('?')[0]
    } else {
      newPath = path
    }
  } else {
    if (path.charAt(path.length - 1) == '/') {
      newPath = path.substring(0, path.length - 1);
    } else {
      newPath = path.split('?')[0];
    }
  }

  return newPath;
}

/**
 * 验证请求接口签名
 * @param obj  请求参数 按顺序拼接成字符串
 * @returns string
 */
const authSign = function (obj) {
  let api_key = authKey;
  let newObj = '';
  if (obj && typeof obj == 'object') {
    for (const key in obj) {
      if (obj.hasOwnProperty(key)) {
        obj[key] = obj[key] === undefined ? '': obj[key]
      }
    }
    newObj = Object.keys(obj).join('')
  }
  return md5(api_key + newObj)
}

/**
 * 判断是否为空对象或空数组
 * @param obj
 */
const isEmpty = function (obj) {
  if (!obj && obj !== 0 && obj !== '') {
    return true;
  }
  if (Array.prototype.isPrototypeOf(obj) && obj.length === 0) {
    return true;
  }
  if (Object.prototype.isPrototypeOf(obj) && Object.keys(obj).length === 0) {
    return true;
  }
  return false;
}

/**
 * 处理图片src方法
 * @param src 
 * @returns string
 */
const BASESRC = (src) => {
  const BASE = process.env.NODE_ENV === 'development' ? window.location.protocol + "//" + window.location.hostname : getDomain()
  let newSrc = ''
  if (typeof src != 'string') return ''

  if (src.indexOf('http://') == 0 || src.indexOf('https://') == 0) return src

  src.substr(0, 1) !== '/' ? newSrc = BASE + '/' + src : newSrc = BASE + src

  return newSrc;
}


// const formatDate1 = (timeStamp, isSecond) => {
//   if (!timeStamp) return timeStamp
//   var date = new Date();
//   date.setTime((timeStamp + "").length === 10 ? parseInt(timeStamp) * 1000 : parseInt(timeStamp));
//   var y = date.getFullYear();
//   var m = date.getMonth() + 1;
//   m = m < 10 ? ('0' + m) : m;
//   var d = date.getDate();
//   d = d < 10 ? ('0' + d) : d;
//   var h = date.getHours();
//   h = h < 10 ? ('0' + h) : h;
//   var minute = date.getMinutes();
//   var second = date.getSeconds();
//   minute = minute < 10 ? ('0' + minute) : minute;
//   second = second < 10 ? ('0' + second) : second;
//   return isSecond ? y + '-' + m + '-' + d + ' ' + h + ':' + minute + ':' + second : y + '-' + m + '-' + d;
// };
// 处理时间戳
const formatDate = (timeStamp, fmt, noShowCy) => {
  if (!timeStamp) return timeStamp;
  const stamp = (timeStamp + "").length === 10 ? parseInt(timeStamp) * 1000 : parseInt(timeStamp)
  var date = new Date(stamp);
  // date.setTime((timeStamp + "").length === 10 ? parseInt(timeStamp) * 1000 : parseInt(timeStamp));
  var isCy = new Date().getFullYear() == date.getFullYear();//本年
  if (!fmt || fmt === 's') {
    var yFmt = isCy && noShowCy ? '' : 'YYYY-';
    fmt = yFmt + (fmt === 's' ? 'mm-dd HH:MM:SS' : 'mm-dd HH:MM');
  }
  if (fmt.includes('YYYY') && isCy && noShowCy) {
    var yTxt = 'YYYY' + fmt.charAt(4);
    fmt = fmt.replace(yTxt, '');//删除显示本年
  }
  let ret;
  let opt = {
    "Y+": date.getFullYear().toString(),        // 年
    "m+": (date.getMonth() + 1).toString(),     // 月
    "d+": date.getDate().toString(),            // 日
    "H+": date.getHours().toString(),           // 时
    "M+": date.getMinutes().toString(),         // 分
    "S+": date.getSeconds().toString()          // 秒
    // 有其他格式化字符需求可以继续添加，必须转化成字符串
  };

  for (let k in opt) {
    ret = new RegExp("(" + k + ")").exec(fmt);
    if (ret) {
      var tkey = ret[1];
      var val = (tkey.length == 1) ? (opt[k]) : (opt[k].padStart(tkey.length, "0"));
      fmt = fmt.replace(tkey, val);
    };
  };
  return fmt;
};


// 处理图片出错显示默认图
const handleErrorSrc = (src) => {
  console.log(src)
};

// 判断客户端系统
const isIos = () => {
  var u = navigator.userAgent;
  var isAndroid = u.indexOf('Android') > -1 || u.indexOf('Adr') > -1; //android终端
  var isiOS = !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/); //ios终端
  return isiOS
}

// 过滤微信自带参数
const filterWxParams = (fullPath) => {
  var names = ['from', 'isappinstalled']
  var loca = window.location;
  var obj = {}
  var baseUrl = fullPath.split("?")[0]
  var url = ''
  if (fullPath.split("?")[1]) {
    var arr = fullPath.split("?")[1].split("&");
    for (var i = 0; i < arr.length; i++) {
      arr[i] = arr[i].split("=");
      obj[arr[i][0]] = arr[i][1];
    };
    for (var i = 0; i < names.length; i++) {
      delete obj[names[i]];
    }
    url = baseUrl + '?' + JSON.stringify(obj).replace(/[\"\{\}]/g, "").replace(/\:/g, "=").replace(/\,/g, "&")
  } else {
    url = fullPath
  }
  return url;
}

// 获取服务端时间
const getServerTime = () => {
  return new Promise((resolve, reject) => {
    var xhr = null;
    if (window.XMLHttpRequest) {
      xhr = new window.XMLHttpRequest();
    } else { // ie
      xhr = new ActiveObject("Microsoft")
    }
    xhr.open("GET", '?t=' + new Date().getTime(), false)//false不可变
    xhr.send(null);
    var date = xhr.getResponseHeader("Date");
    resolve(new Date(date));
  })
}

/**
 * 拼接对象为请求字符串
 * @param {Object} obj - 待拼接的对象
 * @returns {string} - 拼接成的请求字符串
 */
const encodeUriParams = (obj, noEncode) => {
  const params = []

  Object.keys(obj).forEach((key) => {
    let value = obj[key]
    // 如果值为undefined将其置空
    if (typeof value === 'undefined') {
      value = ''
    }
    // 对于需要编码的文本（比如中文）进行编码
    let uriValue = noEncode ? value : encodeURIComponent(value)
    params.push([key, uriValue].join('='))
  })

  return params.join('&')
}

/**
 * url中的参数解析成对象
 * @param {*} url 
 */
const decodeUriParams = (url) => {
  let obj = {};
  let reg = /[?&][^?&]+=[^?&]+/g;
  let arr = url.match(reg);
  if (arr) {
    arr.forEach((item) => {
      let tempArr = item.substr(1).split('=');
      let key = decodeURIComponent(tempArr[0]);
      let val = decodeURIComponent(tempArr[1]);
      obj[key] = val;
    })
  }
  return obj
}

/**
 * 过滤指定uri参数 ，拼接对象为请求字符串（通常用于过滤路由参数）
 * @param {*} obj 
 * @param {*} key 指定key
 * 返回新的uri字符串
 */
const filterUriParams = (obj = {}, key) => {
  let paramStr = '';
  let query = Object.assign({}, obj);
  query[key] && delete query[key];
  let uriParam = encodeUriParams(query, true)
  paramStr = uriParam ? '?' + uriParam : uriParam;
  return paramStr;
}

/**
 * 解析浏览器链接 
 * @param {string} path 当前路由
 * 处理成跟当前fullPath一致
 */
const analysLink = (path) => {
  return getDomain() + (path == '/' ? '/wap/' : location.pathname) + location.search
}

/**
 * 图片资源转换base64路径
 * @param {string} src 图片路径
 */
const buildBase64ImageSrc = (src) => {
  return new Promise((resolve, reject) => {
    var image = new Image();
    image.src = src + '?v=' + Math.random(); // 处理缓存
    image.crossOrigin = "";  // 支持跨域图片
    image.onload = function () {
      var canvas = document.createElement("canvas");
      canvas.width = image.width;
      canvas.height = image.height;
      var ctx = canvas.getContext("2d");
      ctx.drawImage(image, 0, 0, image.width, image.height);
      var dataURL = canvas.toDataURL("image/jpeg");  // 可选其他值 image/png
      resolve(dataURL)
    }
    image.onerror = function () {
      resolve()
    }
  })
}

/**
 * 获取图片实际尺寸
 * @param {string} src 图片路径
 */
const getImageSize = (src) => {
  return new Promise((resolve, reject) => {
    var img_url = src
    var img = new Image()
    img.src = img_url
    img.onload = function () {
      resolve({
        width: img.width,
        height: img.height
      })
    }
  })
}

/**
 * base64图片链接转换文件格式
 * @param {string} dataurl  url
 * @param {string} filename 返回的图片文件名称
 */
const base64toFile = (dataurl, filename) => {
  var arr = dataurl.split(','), mime = arr[0].match(/:(.*?);/)[1],
    bstr = atob(arr[1]), n = bstr.length, u8arr = new Uint8Array(n);
  while (n--) {
    u8arr[n] = bstr.charCodeAt(n);
  }
  return new File([u8arr], filename, { type: mime });
}

/**
 * 限制输入框小数点后保留四位小数（仅限于input  keydown方法）
 */
const handleInput = (e, n = 4) => {
  // 通过正则过滤小数点后四位
  if (n === 6) {
    e.target.value = e.target.value.match(/^\d*(\.?\d{0,5})/g)[0] || null
  } else {
    e.target.value = e.target.value.match(/^\d*(\.?\d{0,3})/g)[0] || null
  }
}

/**
 * 
 * 限制输入正整数，不能小数点
 */
const handleInt = (e) => {
  e.target.value = e.target.value.replace(/\D/g, '');
}

/**
 * 手机号中间数字以星号表示
 */
const encryptMobile = (str) => {
  var reg = /^(\d{3})\d+(\d{4})$/;
  str = str.replace(reg, "$1 **** $2");
  return str;
}

/**
 * 银行卡中间数字以星号表示
 */
const encryptBankCard = (str) => {
  var reg = /^(\d{4})\d+(\d{4})$/;
  str = str.replace(reg, "$1 **** **** $2");
  return str;
}

/**
 * 格式化输入的银行卡号（仅限于input  keydown方法）
 */
const formatBankNo = (value) => {
  if (value == "") return;
  var account = new String(value);
  account = account.substring(0, 22); /*帐号的总数, 包括空格在内 */
  if (account.match(".[0-9]{4}-[0-9]{4}-[0-9]{4}-[0-9]{7}") == null) {
    /* 对照格式 */
    if (account.match(".[0-9]{4}-[0-9]{4}-[0-9]{4}-[0-9]{7}|" + ".[0-9]{4}-[0-9]{4}-[0-9]{4}-[0-9]{7}|" +
      ".[0-9]{4}-[0-9]{4}-[0-9]{4}-[0-9]{7}|" + ".[0-9]{4}-[0-9]{4}-[0-9]{4}-[0-9]{7}") == null) {
      var accountNumeric = accountChar = "", i;
      for (i = 0; i < account.length; i++) {
        accountChar = account.substr(i, 1);
        if (!isNaN(accountChar) && (accountChar != " ")) accountNumeric = accountNumeric + accountChar;
      }
      account = "";
      for (i = 0; i < accountNumeric.length; i++) {    /* 可将以下空格改为- */
        if (i == 4) account = account + " "; /* 帐号第四位数后加空格 */
        if (i == 8) account = account + " "; /* 帐号第八位数后加空格 */
        if (i == 12) account = account + " ";/* 帐号第十二位后数后加空格 */
        account = account + accountNumeric.substr(i, 1)
      }
    }
  }
  else {
    account = " " + account.substring(1, 5) + " " + account.substring(6, 10) + " " + account.substring(14, 18) + "-" + account.substring(18, 25);
  }
  if (account != value) value = account;
}

// 将腾讯/高德地图经纬度转换为百度地图经纬度
const txMapTransBMap = (lng, lat) => {
  let x_pi = 3.14159265358979324 * 3000.0 / 180.0;
  let x = lng;
  let y = lat;
  let z = Math.sqrt(x * x + y * y) + 0.00002 * Math.sin(y * x_pi);
  let theta = Math.atan2(y, x) + 0.000003 * Math.cos(x * x_pi);
  let lngs = z * Math.cos(theta) + 0.0065;
  let lats = z * Math.sin(theta) + 0.006;
  return {
    lng: lngs,
    lat: lats
  }
}

// 将百度地图经纬度转换为腾讯/高德地图经纬度
const bMapTransTxMap = (lng, lat) => {
  let x_pi = 3.14159265358979324 * 3000.0 / 180.0;
  let x = lng - 0.0065;
  let y = lat - 0.006;
  let z = Math.sqrt(x * x + y * y) + 0.00002 * Math.sin(y * x_pi);
  let theta = Math.atan2(y, x) + 0.000003 * Math.cos(x * x_pi);
  let lngs = z * Math.cos(theta);
  let lats = z * Math.sin(theta);
  return {
    lng: lngs,
    lat: lats
  }
}

export {
  getDomain,
  isWeixin,
  analysPath,
  authSign,
  isEmpty,
  BASESRC,
  formatDate,
  handleErrorSrc,
  isIos,
  filterWxParams,
  getServerTime,
  encodeUriParams,
  decodeUriParams,
  filterUriParams,
  analysLink,
  buildBase64ImageSrc,
  getImageSize,
  base64toFile,
  handleInput,
  handleInt,
  encryptMobile,
  encryptBankCard,
  formatBankNo,
  txMapTransBMap,
  bMapTransTxMap
}
