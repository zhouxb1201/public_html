var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var util = require('../../../../utils/util.js');
var header = getApp().header;
var time = require('../../../../utils/time.js');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    //礼品卷列表
    giftvoucherList: '',
    //礼品券状态 1已领用（未使用） 2已使用 3已过期
    state: 1,
    total_count: 1,
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
    that.userGiftvoucher()
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

  onGiftvoucherChange: function (e) {
    const that = this;
    let title = e.detail.title;
    switch (title) {
      case '未使用':
        that.setData({
          state:1,
          giftvoucherList:''
        })
        that.userGiftvoucher();
        break;
      case '已使用':
        that.setData({
          state: 2,
          giftvoucherList: ''
        })
        that.userGiftvoucher();
        break;
      case '已过期':
        that.setData({
          state: 3,
          giftvoucherList: ''
        })
        that.userGiftvoucher();
        break;
    }
  },

  //获取礼品券列表
  userGiftvoucher: function () {
    const that = this;
    let postData = {
      'state': that.data.state
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_userGiftvoucher,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          let giftvoucherList = res.data.data.data;
          for (var i = 0; i < giftvoucherList.length; i++) {
            giftvoucherList[i].start_time = time.js_date_time(giftvoucherList[i].start_time);
            giftvoucherList[i].end_time = time.js_date_time(giftvoucherList[i].end_time);
          }
          that.setData({
            giftvoucherList: giftvoucherList,
            total_count: res.data.data.total_count
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

  //跳转到礼品卷详情
  onGifDetailPage:function(e){
    const that = this;
    let record_id = e.currentTarget.dataset.id;    

    let onPageData = {
      url: '../detail/index',
      num: 4,
      param: '?record_id=' + record_id
    }
    util.jumpPage(onPageData);
  }

})