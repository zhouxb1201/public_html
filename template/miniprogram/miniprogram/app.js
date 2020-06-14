var appConfig = {}
var requestSign = require('./utils/requestData.js');
let livePlayer = '';
console.log('小程序配置', __wxConfig)
let extConfig = wx.getExtConfigSync ? wx.getExtConfigSync() : {}
console.log('第三方平台数据', extConfig)
if (extConfig.domain) {
  appConfig = extConfig
} else {
  appConfig = require('./config');
}
if (__wxConfig.plugins && __wxConfig.plugins['live-player-plugin']) {
  livePlayer = requirePlugin('live-player-plugin')
}
// 全局mixins
require('./utils/mixins')
// var pages = require('./utils/pages')

// wx.mixin(pages)

App({
  /**
   *当小程序初始化完成时，会触发 onLaunch（全局只触发一次）
   */
  onLaunch: function () {
    const that = this;
    that.mpUpdate();

    console.log('配置信息', appConfig)
    that.publicUrl = appConfig.domain;
    that.globalData.publicUrl = appConfig.domain;
    that.globalData.domain_wap = appConfig.domain_wap;
    that.globalData.website_id = appConfig.website_id;
    that.globalData.auth_id = appConfig.auth_id;
    that.globalData.tab_list = appConfig.pathList

    // 判断设备是否为全面屏
    that.checkFullSucreen();
  },

  /**
   *当小程序启动，或从后台进入前台显示，会触发 onShow
   */
  onShow: function (options) {
    const that = this;
    let user_token = wx.getStorageSync('user_token');
    if (user_token != '') {
      this.getMember();
    }
    that.configFun();

    // 分享卡片入口场景才调用getShareParams接口获取以下参数
    if (livePlayer && options.scene == 1007 || options.scene == 1008 || options.scene == 1044) {
      livePlayer.getShareParams()
        .then(res => {
          console.log('get room id', res.room_id) // 房间号
          console.log('get openid', res.openid) // 用户openid
          console.log('get share openid', res.share_openid) // 分享者openid，分享卡片进入场景才有
          console.log('get custom params', res.custom_params) // 开发者在跳转进入直播间页面时，页面路径上携带的自定义参数，这里传回给开发者
          let extend_code = res.custom_params.extend_code
          wx.setStorageSync('higherExtendCode', extend_code)
        }).catch(err => {
          console.log('err', err)
        })
    }
  },

  globalData: {
    tab_list: [],
    config: '',
    //是否是全面屏
    isFullSucreen: false,
    //搜索标识
    searchSign: '',
    //搜索店铺关键字
    searchShopKey: '',
    //分销的文案数据
    distributionData: '',
    //分红文案数据
    bonusData: '',
    //当账户体系为3，绑定手机为0时，手机将不再需要验证
    no_check_phone: '',
    bonusData: '',
    is_password_set: '',
    //授权证书-证书类型 1-分销中心 2-分红中心 3-微商中心 4-微店
    credential_type: '1',
    //用户会员id
    uid: '',
    //用户头像
    member_img: '',
    //用户名字
    username: '',
    extend_code: '',
    regTime: '',
    headerHeight: 0,
    statusBarHeight: 0

  },

  keyword: '',
  publicUrl: '',
  userToken: '',
  userTokenEvent: '',
  loginStatus: false, //登录状态，true-已登录，false-未登录
  header: {
    'Content-Type': 'application/json; charset=utf-8',
    'X-Requested-With': 'XMLHttpRequest',
    'Program': 'miniProgram',
    'user-token': wx.getStorageSync('user_token'),
    'Cookie': wx.getStorageSync('setCookie'),
    "website-id": appConfig.website_id,
  },

  /**
   * 获取第三个自定义域名地址
   * 不是第三方则获取本地config配置文件
   */
  appExtConfig: function () {
    const that = this
    return new Promise(function (resolve, reject) {
      function setConfig(config) {
        that.header['website-id'] = config.website_id
        that.publicUrl = config.domain;
        that.globalData.publicUrl = config.domain;
        that.globalData.domain_wap = config.domain_wap;
        that.globalData.website_id = config.website_id;
        that.globalData.auth_id = config.auth_id;
        that.globalData.tab_list = config.pathList
      }
      let extConfig = wx.getExtConfigSync ? wx.getExtConfigSync() : {}
      if (!extConfig.domain) {
        extConfig = appConfig
      }
      setConfig(extConfig)
    })
  },

  getMember: function () {
    const that = this;
    let publicUrl = appConfig.domain
    var postData = {};
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
      url: publicUrl + '/wapapi/Member/memberIndex',
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == -1000) {
          wx.redirectTo({
            url: '/pages/logon/index',
          })
        } else {
          that.globalData.is_password_set = res.data.data.is_password_set;
          that.globalData.uid = res.data.data.uid;
          that.globalData.member_img = res.data.data.member_img;
          that.globalData.username = res.data.data.username;
          that.globalData.regTime = res.data.data.reg_time;
          that.globalData.extend_code = res.data.data.extend_code;
          that.globalData.user_tel = res.data.data.user_tel;
        }

      },
      fail: (res) => {},
    })
  },

  //系统配置
  configFun: function () {
    const that = this;
    let publicUrl = appConfig.domain
    var postData = {};
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
      url: publicUrl + '/wapapi/config',
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 1) {
          that.globalData.config = res.data.data
          if (res.data.data.config.account_type == 3 && res.data.data.config.is_bind_phone == 0) {
            that.globalData.no_check_phone = 0
          }
        }


      },
      fail: (res) => {},
    })
  },

  //判断设备是否全屏
  checkFullSucreen: function () {
    const that = this
    wx.getSystemInfo({
      success: function (res) {
        if (res.screenHeight - res.windowHeight - res.statusBarHeight - 32 > 72) {
          that.globalData.isFullSucreen = true
        }
        var headHeight;
        if (/iphone\s{0,}x/i.test(res.model)) {
          headHeight = 88;
        } else if (res.system.indexOf('Android') !== -1) {
          headHeight = 68;
        } else {
          headHeight = 64;
        }
        that.globalData.headerHeight = headHeight;
        that.globalData.statusBarHeight = res.statusBarHeight;
      },
    })
  },


  //小程序更新
  mpUpdate: function () {
    if (wx.canIUse('getUpdateManager')) {
      const updateManager = wx.getUpdateManager()
      updateManager.onCheckForUpdate(function (res) {
        // 请求完新版本信息的回调
        if (res.hasUpdate) {
          updateManager.onUpdateReady(function () {
            wx.showModal({
              title: '更新提示',
              content: '新版本已经准备好，是否重启应用？',
              success: function (res) {
                if (res.confirm) {
                  wx.clearStorageSync()
                  // 新的版本已经下载好，调用 applyUpdate 应用新版本并重启
                  updateManager.applyUpdate()
                }
              }
            })
          })
          updateManager.onUpdateFailed(function () {
            // 新的版本下载失败
            wx.showModal({
              title: '已经有新版本了哟~',
              content: '新版本已经上线啦~，请您删除当前小程序，重新搜索打开哟~',
            })
          })
        }
      })
    } else {
      // 如果希望用户在最新版本的客户端上体验您的小程序，可以这样子提示
      wx.showModal({
        title: '提示',
        content: '当前微信版本过低，无法使用该功能，请升级到最新微信版本后重试。'
      })
    }
  },

  // 监听全局数据变化
  watch: function (key, method) {
    Object.defineProperty(this.globalData, key, {
      configurable: true,
      enumerable: true,
      set: function (value) {
        this['_' + key] = value
        method(value)
      },
      get: function () {
        return this['_' + key]
      }
    })
  },








})