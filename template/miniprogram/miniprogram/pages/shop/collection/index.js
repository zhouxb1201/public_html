var requestSign = require('../../../utils/requestData.js');
var api = require('../../../utils/api.js').open_api;
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    page_index:1,
    shop_list:'',
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
    const value = wx.getStorageSync('user_token');
    if(value){
      that.shopCollection().then((res) => {
        if (res.data.code > 0) {
          that.setData({
            shop_list: res.data.data.shop_list
          })
        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
        }
      })
    }else{
      this.setData({
        loginShow: true,
      })
    }
    
  },

  //登录结果返回
  requestLogin: function (e) {
    const that = this;
    let result = e.detail.result;
    if (result == true) {
      that.shopCollection().then((res) => {
        if (res.data.code > 0) {
          that.setData({
            shop_list: res.data.data.shop_list
          })
        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
        }
      })
    }
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
    const that = this;
    that.data.page_index = that.data.page_index + 1
    that.shopCollection().then((res) => {
      if(res.data.code>0){
        let shop_list = that.data.shop_list;
        shop_list = shop_list.concat(res.data.data.shop_list);
        that.setData({
          shop_list:shop_list
        })
      }else{
        wx.showToast({
          title: res.data.message,
          icon:'none'
        })
      }
    })
  },

  /**
   * 店铺收藏
   */
  shopCollection: function () {
    const that = this;
    let postData = {
      'page_index': that.data.page_index,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    return new Promise((resolve, reject) => {
      wx.request({
        url: api.get_myShopCollection,
        data: postData,
        header: header,
        method: 'POST',
        dataType: 'json',
        responseType: 'text',
        success: (res) => {
          resolve(res);
        },
        fail: (res) => { },
      })
    })
  }
})