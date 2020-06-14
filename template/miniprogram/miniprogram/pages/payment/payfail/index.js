var requestSign = require('../../../utils/requestData.js');
var api = require('../../../utils/api.js').open_api;
var util = require('../../../utils/util.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    //外部交易号
    out_trade_no: '',
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    const that = this;
    let out_trade_no = options.out_trade_no;
    that.data.out_trade_no = out_trade_no;
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

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {

  },

  onPageOrder:function(){
    let onPageData = {
      url: '/pages/order/list/index',
      num: 4,
      param: ''
    }
    util.jumpPage(onPageData); 
  }


})