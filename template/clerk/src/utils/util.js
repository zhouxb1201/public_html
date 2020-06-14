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
 * 4，'第三方链接'
 */
const analysPath = (path) => {
  let newPath = ''
  if (path.indexOf('http://') === 0 || path.indexOf('https://') === 0) {
    console.log(path, '第三方链接')
    return path;
  } else {
    if (path.charAt(path.length - 1) == '/') {
      newPath = path.substring(0, path.length - 1);
    } else {
      newPath = path.split('?')[0];
    }
    return newPath;
  }
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
  const BASE = getDomain()
  let newSrc = ''
  if (typeof src != 'string') return ''

  if (src.indexOf('http://') == 0 || src.indexOf('https://') == 0) return src

  src.substr(0, 1) !== '/' ? newSrc = BASE + '/' + src : newSrc = BASE + src

  return newSrc;
}

// 处理时间戳
const formatDate = (timeStamp, isSecond) => {
  if (!timeStamp) return timeStamp
  var date = new Date();
  date.setTime((timeStamp + "").length === 10 ? parseInt(timeStamp) * 1000 : parseInt(timeStamp));
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
  return isSecond ? y + '-' + m + '-' + d + ' ' + h + ':' + minute + ':' + second : y + '-' + m + '-' + d;
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
    xhr.open("GET", location.href, false)//false不可变
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
const encodeUriParams = (obj) => {
  const params = []

  Object.keys(obj).forEach((key) => {
    let value = obj[key]
    // 如果值为undefined将其置空
    if (typeof value === 'undefined') {
      value = ''
    }
    // 对于需要编码的文本（比如中文）进行编码
    params.push([key, encodeURIComponent(value)].join('='))
  })

  return params.join('&')
}

/**
 * 解析浏览器链接 
 * @param {string} path 当前路由
 * 处理成跟当前fullPath一致
 */
const analysLink = (path) => {
  return getDomain() + (path == '/' ? '/wap/' : location.pathname) + location.search
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
  analysLink
}
