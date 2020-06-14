const app = getApp();
var requestSign = require('../../../utils/requestData.js');
var api = require('../../../utils/api.js').open_api;
var util = require('../../../utils/util.js');
var re = require('../../../utils/request.js');
var header = getApp().header;
Component({
  /**
   * 组件的属性列表
   */
  properties: {
    loginShow: {
      type: Boolean,
      value:false,
      
    },  
  },

  /**
   * 组件的初始数据
   */
  data: {
  },

  lifetimes: {
    attached() {
      // 在组件实例进入页面节点树时执行
      const that = this;
      that.getMpBaseInfo();
    },
    
    detached() {
      // 在组件实例被从页面节点树移除时执行
    },
  },

  /**
   * 组件的方法列表
   */
  methods: {
    
    onLoginClose: function () {
      const that = this;
      wx.showTabBar();
      that.setData({
        loginShow: false,
      })
    },


    bindGetUserInfo: function (res) {
      const that = this;
      console.log(res.detail);
      if (res.detail.userInfo) {
        console.log("点击了同意授权");
        wx.setStorageSync("nickName", res.detail.userInfo.nickName);
        wx.setStorageSync("avatarUrl", res.detail.userInfo.avatarUrl);
        wx.setStorageSync("gender ", res.detail.userInfo.gender );
        wx.setStorageSync("encrypted_data", res.detail.encryptedData);
        wx.setStorageSync("iv", res.detail.iv);
        wx.showLoading({
          title: '登录中',
        })
        that.onLoginClose();                
        util.onlogin().then(function (res) {
          wx.hideLoading();
          if (res.data.code > 0){
            setTimeout(() => {
              that.triggerEvent('request', { result: true })
            },1000)            
          }
        })

      } else {
        console.log('授权失败！');
        wx.showToast({
          title: res.detail.errMsg,
          icon: 'none',
        })
      }
    },

    //获取小程序头像和平台名称
    getMpBaseInfo:function(){
      const that = this;
      const appConfig = getApp().globalData;
      var postData = {
        'website_id': appConfig.website_id,
        'auth_id': appConfig.auth_id
      };
      let datainfo = requestSign.requestSign(postData);
      header.sign = datainfo;
      wx.request({
        url: api.get_getMpBaseInfo,
        data: postData,
        header: header,
        method: 'POST',
        dataType: 'json',
        responseType: 'text',
        success: (res) => {
          if(res.data.code == 1){
            if (res.data.data.logo != ''){
              that.setData({
                head_img: res.data.data.logo
              })
            }
            if (res.data.data.name != '') {
              that.setData({
                mall_name: res.data.data.name
              })
            }            
          }
        },
        fail: (res) => { },
      })
    },
    
  }
})
