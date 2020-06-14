var requestSign = require('../../utils/requestData.js');
var api = require('../../utils/api.js').open_api;
var header = getApp().header;
var util = require('../../utils/util.js');
const app = getApp();
Page({

  /**
   * 页面的初始数据
   */
  data: {
    head_img:'',
    mall_name:'',
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {

  },

  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function () {

  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {
    const that = this;    
    that.getMpBaseInfo();
  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function () {

  },

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function () {

  },

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function () {

  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {

  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {

  },

  bindGetUserInfo:function(res){    
   console.log(res);
    if (res.detail.userInfo) {
      console.log("点击了同意授权");
      wx.setStorageSync("nickName", res.detail.userInfo.nickName);
      wx.setStorageSync("avatarUrl", res.detail.userInfo.avatarUrl);
      wx.setStorageSync("encrypted_data", res.detail.encryptedData);
      wx.setStorageSync("iv", res.detail.iv);
      wx.showLoading({
        title: '登录中',
      })        
      util.onlogin().then(function (res) {
        wx.hideLoading();        
        if(res.data.code > 0){
          setTimeout(function () {
            // let onPageData = {
            //   url: '/pages/index/index',
            //   num: 4,
            //   param: '',
            // }
            // util.jumpPage(onPageData);
            wx.navigateBack({
              delta:1
            })
          },1000)                
        }
        
      })
    } else {
      console.log("点击了拒绝授权");     
    }
  },

  onloginPage:function(){  
    let onPageData = {
      url: '/pages/login/login',
      num: 4,
      param: '',
    }
    util.jumpPage(onPageData);
  },

  onIndexPage:function(){   
    let onPageData = {
      url: '/pages/index/index',
      num: 4,
      param: '',
    }
    util.jumpPage(onPageData);
  },

  //获取小程序头像和平台名称
  getMpBaseInfo: function () {
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
        if (res.data.code == 1) {
          if (res.data.data.logo != '') {
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

  
})