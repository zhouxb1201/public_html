var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    anti_code:'',
    bannerImg: getApp().publicUrl + '/wap/static/images/pic_legal.png',
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
    this.isLogin();
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

  //是否需要登录
  isLogin: function () {
    const that = this;
    wx.showLoading({
      title: '加载中',
    })
    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_searchUserCredentialPage,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        wx.hideLoading();        
        if (res.data.code >= 0) {
          if (res.data.code == 1){
            that.loginFun();
          }
          that.setData({
            banner_img: res.data.data.banner_list.img_path,
            logo: res.data.data.logo,
            img_link: res.data.data.banner_list.img_link,
          })
        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
        }
      },
      fail: (res) => { },
    })
  },

  //开启了登录
  loginFun:function(){
    const value = wx.getStorageSync('user_token')
    if (value) {
      console.log('已登录');
    } else {
      console.log('未登录')
      this.setData({
        loginShow: true,
      })
    }   
  },

  //登录以后结果反回
  requestLogin: function (e) {
    const that = this;
    let result = e.detail.result;
    if (result == true) {
      that.isLogin();      
    }
  },

  //扫码
  sweepCode:function(){
    const that = this;
    wx.scanCode({
      success(res){                
        that.data.anti_code = res.result;
        that.searchPage();
      }
    })
  },

  onlinkpage() {
    wx.navigateTo({
      url: this.data.img_link,
    })
  },

  antiCode(e) {
    const that = this;
    that.setData({
      anti_code: e.detail.value
    })
  },

  searchPage: function () {
    if (this.data.anti_code == '') {
      wx.showToast({
        title: '防伪码不能为空',
        icon: 'none'
      })
      return;
    }
    wx.navigateTo({
      url: '../result/index?anti_code=' + this.data.anti_code,
    })
  },


})