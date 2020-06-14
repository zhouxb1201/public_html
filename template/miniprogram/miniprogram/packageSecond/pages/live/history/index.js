var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
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
    this.getWatchHistory();
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

  //历史数据列表
  getWatchHistory: function () {
    const that = this;
    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getWatchHistory,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code === 1) {
          let history_list = that.statusType(res.data.data.history_list)
          that.setData({
            history_list: history_list
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

  statusType(live_list) {
    console.log(live_list)
    const list = live_list.filter(e => {
      if (e.status == -1) {
        e.status_name = '拒绝'
      }
      if (e.status == 0) {
        e.status_name = '待审核'
      }
      if (e.status == 1) {
        e.status_name = '直播预告'
      }
      if (e.status == 2) {
        e.status_name = '直播中'
      }
      if (e.status == 3) {
        e.status_name = '已审核'
      }
      if (e.status == 4) {
        e.status_name = '已下播'
      }
      return e;
    })
    return list;
  },
})