var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var header = getApp().header;
var time = require('../../../../utils/time.js');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    //礼品卷详情
    giftvoucherdetail:'',
    record_id:'',
    //状态 1已领用（未使用） 2已使用 3已过期
    state:'',
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    const that = this;
    that.data.record_id = options.record_id;
    that.userGiftvoucherInfo()
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

  //获取礼品券详情
  userGiftvoucherInfo: function () {
    const that = this;
    let postData = {
      'record_id': that.data.record_id
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_userGiftvoucherInfo,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          let giftvoucherdetail = res.data.data;
          giftvoucherdetail.start_time = time.js_date_time(giftvoucherdetail.start_time);
          giftvoucherdetail.end_time = time.js_date_time(giftvoucherdetail.end_time);
          that.setData({
            giftvoucherdetail: giftvoucherdetail,
            state: giftvoucherdetail.state,
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
})