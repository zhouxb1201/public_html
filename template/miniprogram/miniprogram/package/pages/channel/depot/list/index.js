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
    //sku库存 1 - 有库存，2-无库存
    stock_status:1,
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    this.cloudStorage();
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
    that.cloudStorage();
  },

  //渠道商云仓库数据
  cloudStorage: function () {
    const that = this;
    wx.showLoading({
      title: '加载中',
    })
    let postData = {
      'page_index': that.data.page_index,
      'page_size': 20,
      'stock_status': that.data.stock_status
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_cloudStorage,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        wx.hideLoading();
        if (res.data.code >= 0) {

          if (that.data.page_index >= 2) {
            let oldData = that.data.cloudData;
            let cloudData = '';
            cloudData = oldData.concat(res.data.data.data);
            that.setData({
              cloudData: cloudData
            })
          } else {
            that.setData({
              cloudData: res.data.data.data
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

  //库存状态
  stockStatusFun:function(e){
    const that = this;
    let index = e.detail.index;
    switch(index){
      case 0:
        that.setData({
          stock_status:1
        })
        break
      case 1:
        that.setData({
          stock_status: 2
        })
        break        
    }
    that.cloudStorage()
  }
})