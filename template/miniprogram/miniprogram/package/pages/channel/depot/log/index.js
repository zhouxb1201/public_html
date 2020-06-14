var requestSign = require('../../../../../utils/requestData.js');
var util = require('../../../../../utils/util.js');
var api = require('../../../../../utils/api.js').open_api;
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    page_index:1,

  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    this.cloudStorageLog();
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
    const that = this;
    that.data.page_index = that.data.page_index + 1;
    that.cloudStorageLog();
  },

  //渠道商云仓库日志数据
  cloudStorageLog: function () {
    const that = this;
    let postData = {
      'page_index': that.data.page_index,
      'page_size':20
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_cloudStorageLog,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          
          if(that.data.page_index >= 2){
            let oldData = that.data.channel_goods_info;
            let channel_goods_info = '';
            channel_goods_info = oldData.concat(res.data.data.channel_goods_info);
            that.setData({
              channel_goods_info: channel_goods_info
            })
          }else{
            that.setData({
              channel_goods_info: res.data.data.channel_goods_info
            })
          }
          
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