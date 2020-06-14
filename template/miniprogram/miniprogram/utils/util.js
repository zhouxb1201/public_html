var requestSign = require('./requestData.js');
var api = require('./api.js').open_api;
var re = require('./request.js');
var header = getApp().header;
var app = getApp();

export function getTemData() {

}

/**
 * url page地址
 * num 选用的跳转方式1-switchTab，2-reLaunch，3-redirectTo，4-navigateTo，5-navigateBack
 * param 参数
 */
export function jumpPage(data) {
  let url = data.url;
  let num = data.num;
  let param = data.param;
  for (let item of app.globalData.tab_list) {
    item = '/' + item;
    if (url == item) {
      num = 1
      break
    }
  }

  if (param != '') {
    url = url + param;
  }

  switchPage(url, num);

}

function switchPage(url, num) {
  switch (num) {
    case 1:
      switchTab(url);
      break;
    case 2:
      reLaunch(url);
      break;
    case 3:
      redirectTo(url);
      break;
    case 4:
      navigateTo(url);
      break;
    case 5:
      navigateBack(url);
      break;
  }
}

function switchTab(url) {
  wx.switchTab({
    url: url,
  })
}

function reLaunch(url) {
  wx.reLaunch({
    url: url
  })
}

function redirectTo(url) {
  wx.redirectTo({
    url: url
  })
}

function navigateTo(url) {
  wx.navigateTo({
    url: url
  })
}

function navigateBack(url) {
  wx.navigateBack({
    delta: url
  })
}


/**
 * 邀请码
 */
export function extend_code() {
  let postData = {}
  let datainfo = requestSign.requestSign(postData);
  header.sign = datainfo
  re.request(api.get_qrcode, postData, header).then((res) => {
    if (res.data.code == 0) {
      wx.setStorageSync("extend_code", res.data.data.extend_code);
    }
  })
}

/**
 * 绑定上下级关系
 */
export function checkReferee() {
  let higherExtendCode = wx.getStorageSync('higherExtendCode');
  let posterId = wx.getStorageSync('posterId');
  let posterType = wx.getStorageSync('posterType');
  let postData = {
    'extend_code': higherExtendCode,
    'poster_id': posterId,
    'poster_type': posterType
  }
  let datainfo = requestSign.requestSign(postData);
  header.sign = datainfo
  re.request(api.get_checkReferee, postData, header).then((res) => {
    console.log(res.data.message)
    wx.removeStorageSync('higherExtendCode') // 成为下线成功删除推广码
  })
}

/**
 * 检查手机号是否正确 
 * */
export function checkPhone(phone) {
  var telStr = /^[1](([3][0-9])|([4][5-9])|([5][0-3,5-9])|([6][5,6])|([7][0-8])|([8][0-9])|([9][1,8,9]))[0-9]{8}$/;
  if (!(telStr.test(phone))) {
    console.log("手机号码有误，请重填");
    return false;
  }
}


export function checkIdCardNo(idCardNo) {
  // 身份证号码为15位或者18位，15位时全为数字，18位前17位为数字，最后一位是校验位，可能为数字或字符X 
  var reg = /(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/;
  if (reg.test(idCardNo) === false) {
    wx.showToast({
      title: '身份证号码不正确！',
      icon: 'none',
    })
    return false;
  }
}

/**获取订阅消息的模板ID
 * @ type 模板类型 1:付款成功 2:订单关闭 3:余额变动 4:售后情况
 */
export function getMpTemplateId(type) {
  let postData = {
    'type': type
  }
  let datainfo = requestSign.requestSign(postData);
  header.sign = datainfo
  return re.request(api.get_getMpTemplateId, postData, header);
}

/**获取客户同意订阅消息的模板信息
 *@ info
 */
export function postUserMpTemplateInfo(info) {
  let obj = {},
    list = [];
  for (let key in info) {
    console.log(key);
    if (key != 'errMsg') {
      obj = {
        'template_id': key,
        'action': info[key],
      }
      list.push(obj);
    }
  }
  let postData = {
    'list': list,
    'uid': getApp().globalData.uid,
  }
  let datainfo = requestSign.requestSign(postData);
  header.sign = datainfo
  re.request(api.get_postUserMpTemplateInfo, postData, header).then((res) => {
    if (res.data.code == -1) {
      wx.showToast({
        title: res.data.message,
        icon: 'none'
      })
    }
  })
}

/**
 * 判断是否为空对象或空数组
 * @ obj
 */
export function isEmpty(obj) {
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
 * 判断自定义表单里的必填项是否为空
 * @ customform
 */
export function isRequired(customform) {
  for (let item of customform) {
    if (item.required == true && item.value == '') {
      console.log(item.label + ':' + item.value);
      wx.showToast({
        title: item.label + '不能为空',
        icon: 'none',
      })
      return false;
    }
  }
  return true;
}


/**
 * 检查手机号是否存在 
 */
export function hasPhone() {
  const that = this;
  const have_mobile = wx.getStorageSync('have_mobile');
  if (have_mobile != true) {
    return false
  }
}
/**
 * 分销字段设置
 */
export function distributionSet() {
  const that = this;
  let postData = {}
  let datainfo = requestSign.requestSign(postData);
  header.sign = datainfo;
  return new Promise((resolve, reject) => {
    wx.request({
      url: api.get_distributionSet,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 0) {
          let distributionData = res.data.data;
          getApp().globalData.distributionData = distributionData;
          resolve(res);
        }
      },
      fail: (res) => {},
    })
  })
}

/**
 * 分红字段设置
 */
export function bonusSet() {
  const that = this;
  let postData = {}
  let datainfo = requestSign.requestSign(postData);
  header.sign = datainfo;
  return new Promise((resolve, reject) => {
    wx.request({
      url: api.get_bonusSet,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 0) {
          let bonusData = res.data.data;
          getApp().globalData.bonusData = bonusData;
          resolve(res);
        }
      },
      fail: (res) => {},
    })
  })
}


/**
 * 登录
 */
export function onlogin() {
  let _this = this;
  return new Promise((resolve, reject) => {
    const appConfig = getApp().globalData;
    let header = {
      'Content-Type': 'application/json; charset=utf-8',
      'X-Requested-With': 'XMLHttpRequest',
      'Program': 'miniProgram',
      "website-id": appConfig.website_id,
    }
    wx.login({
      success(res) {
        var code = res.code;
        let postData = {
          'type': 'MP',
          "extend_code": wx.getStorageSync('higherExtendCode'),
          "encrypted_data": wx.getStorageSync('encrypted_data'),
          "iv": wx.getStorageSync('iv'),
          "code": code
        }
        if (res.code) {
          wx.request({
            url: api.get_oauthLogin_new,
            header: header,
            data: postData,
            method: 'POST',
            success: function (res) {
              if (res.data.code == 1) {
                if (getApp().userTokenEvent == '') {
                  wx.setStorageSync("user_token", res.data.data.user_token)
                  getApp().userToken = res.data.data.user_token;
                } else {
                  getApp().userToken = getApp().userTokenEvent;
                }
                let token = 'user-token';
                getApp().header[token] = getApp().userToken

                wx.setStorageSync("have_mobile", res.data.data.have_mobile);
                wx.setStorageSync("openid", res.data.data.openid);
                var setCookie = res.header['Set-Cookie'];                                                                
                var cookie = setCookie.match(/(PHPSESSID=\S*)/)[1];
                cookie = cookie.replace(",","");
                cookie = cookie.replace(";", "");                
                wx.setStorageSync("setCookie", cookie)
                getApp().header.Cookie = cookie;                             
                
                getApp().loginStatus = true;
                _this.checkReferee();
                getApp().getMember()
              } else if (res.data.code == 2) {
                wx.showModal({
                  title: '提示',
                  content: res.data.message,
                  success(res) {
                    if (res.confirm) {
                      wx.switchTab({
                        url: '/pages/member/index',
                      })
                    }
                  }
                })
              } else if (res.data.code == 3) {
                wx.showModal({
                  title: '提示',
                  content: res.data.message,
                  showCancel: true,
                })
              } else if (res.data.code == 5) {
                var setCookie = res.header['Set-Cookie'];
                if (setCookie == undefined) {
                  setCookie = res.header['set-cookie'];
                }
                wx.setStorageSync("setCookie", setCookie.split(";")[0])
                getApp().header.Cookie = setCookie.split(";")[0];
                // loginAgain(res.data.data);
              } else {
                if (res.data.code == -40003) {
                  wx.showModal({
                    title: '提示',
                    content: 'sha加密生成签名失败',
                    showCancel: false,
                  })
                } else {
                  wx.showModal({
                    title: '提示',
                    content: res.data.message,
                    showCancel: false,
                  })
                }

              }
              resolve(res);
            },
          })
        } else {
          console.log('登录失败！' + res.errMsg);
        }
      }
    })
  })
}

// function loginAgain(rData) {
//   const that = this;
//  const appConfig = getApp().globalData;
//   let postData = {
//     'type': 'MP',
//     "extend_code": wx.getStorageSync('higherExtendCode'),
//     "encrypted_data": wx.getStorageSync('encrypted_data'),
//     "iv": wx.getStorageSync('iv'),
//   }
//   let header = {
//     'Content-Type': 'application/json; charset=utf-8',
//     'X-Requested-With': 'XMLHttpRequest',
//     'Program': 'miniProgram',
//     'Cookie': wx.getStorageSync('setCookie'),
//     "website-id": appConfig.website_id,
//   }
//   let datainfo = requestSign.requestSign(postData);
//   header.sign = datainfo;
//   wx.request({
//     url: api.get_oauthLogin,
//     data: postData,
//     header: header,
//     method: 'POST',
//     dataType: 'json',
//     responseType: 'text',
//     success: (res) => {
//       if (res.data.code == 1) {
//         if (getApp().userTokenEvent == '') {
//           wx.setStorageSync("user_token", res.data.data.user_token)
//           getApp().userToken = res.data.data.user_token;
//         } else {
//           getApp().userToken = getApp().userTokenEvent;
//         }
//         let token = 'user-token';
//         getApp().header[token] = getApp().userToken;

//         let configData = getApp().globalData.config;
//         if (getApp().globalData.no_check_phone === 0) {
//           wx.setStorageSync("have_mobile", true);
//         } else {
//           wx.setStorageSync("have_mobile", res.data.data.have_mobile);
//         }

//         if (getApp().globalData.uid == '') {
//           getApp().getMember();
//         }
//         qlkefuInfoFun()

//         wx.setStorageSync("openid", res.data.data.openid);
//         getApp().loginStatus = true;
//       } else if (res.data.code == 2) {
//         wx.showModal({
//           title: '提示',
//           content: res.data.message,
//           success(res) {
//             if (res.confirm) {
//               wx.switchTab({
//                 url: '/pages/member/index',
//               })
//             }
//           }
//         })
//       } else if (res.data.code == 3) {
//         wx.showModal({
//           title: '提示',
//           content: res.data.message,
//           showCancel: true,
//         })
//       } else {
//         wx.showModal({
//           title: '提示',
//           content: '接口错误，授权登录失败',
//           showCancel: false,
//         })
//       }
//     },
//     fail: (res) => {},
//   })
// }

//获取客服信息
export function qlkefuInfoFun(shop_id, goods_id) {
  const that = this;
  const appConfig = getApp().globalData;
  let publicUrl = appConfig.domain;
  let postData = {};
  if (shop_id) {
    postData.shop_id = shop_id;
  }
  if (goods_id) {
    postData.goods_id = goods_id;
  }
  let datainfo = requestSign.requestSign(postData);
  let header = {
    'Content-Type': 'application/json; charset=utf-8',
    'X-Requested-With': 'XMLHttpRequest',
    'Program': 'miniProgram',
    'user-token': wx.getStorageSync('user_token'),
    'Cookie': wx.getStorageSync('setCookie'),
    "website-id": appConfig.website_id,
  }
  header.sign = datainfo;
  wx.request({
    url: publicUrl + '/wapapi/addons/qlkefu/qlkefu/qlkefuInfo',
    data: postData,
    header: header,
    method: 'POST',
    dataType: 'json',
    responseType: 'text',
    success: (res) => {
      if (res.data.code == 1) {
        wx.setStorageSync("domain", res.data.data.domain);
        wx.setStorageSync("port", res.data.data.port);
        if (res.data.data.seller_code != undefined) {
          wx.setStorageSync("seller_code", res.data.data.seller_code);
        }

      }

    },
    fail: (res) => {},
  })

}

/**
 * 拼接对象为请求字符串
 * @param {Object} obj - 待拼接的对象
 * @returns {string} - 拼接成的请求字符串
 */
export function encodeUriParams(obj = {}, encode) {
  const params = [];
  Object.keys(obj).forEach(key => {
    let value = obj[key];
    // 如果值为undefined将其置空
    if (typeof value === "undefined") {
      value = "";
    }
    // 对于需要编码的文本（比如中文）进行编码
    let uriValue = encode ? encodeURIComponent(value) : value;
    params.push([key, uriValue].join("="));
  });

  return params.join("&");
}

// 将腾讯/高德地图经纬度转换为百度地图经纬度
export function txMapTransBMap(lng, lat) {
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