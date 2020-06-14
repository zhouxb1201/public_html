var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var util = require('../../../../utils/util.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {

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
    this.getMyFocus();
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

  //获取关注列表
  getMyFocus: function () {
    const that = this;
    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getMyFocus,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code < 0) {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
        } else {
          that.setData({
            focus_list: res.data.data.focus_list
          })
        }

      },
      fail: (res) => { },
    })
  },

  addOrCancleFocus: function (e) {
    const that = this;
    let isfocus = e.currentTarget.dataset.isfocus;
    let follow_uid = e.currentTarget.dataset.followuid;
    let url = '';
    if (isfocus == true) {
      url = api.get_cancleFocus
    } else {
      url = api.get_addFocus
    }
    let postData = {
      'follow_uid': follow_uid
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: url,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code < 0) {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
        } else {
          that.getMyFocus();
        }

      },
      fail: (res) => { },
    })
  },

})