var requestSign = require('../../../utils/requestData.js');
var api = require('../../../utils/api.js').open_api;
var util = require('../../../utils/util.js');
var re = require('../../../utils/request.js');
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
    this.getApplyStateByWap();
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

  //申请入驻状态
  getApplyStateByWap: function () {
    const that = this;
    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_getApplyStateByWap, postData, header).then((res) => {
      if (res.data.code == 1) {
        that.setData({
          statusData: res.data.data
        })
      }
    })
  },

  //复制到剪切板
  copyText:function(){
    const that = this;
    if (that.data.statusData.url != ''){
      wx.setClipboardData({
        data: that.data.statusData.url,        
      })
    }    
  },

  onApplyPage:function(){
    const that = this;
    wx.redirectTo({
      url: '../apply/index',
    })
  },
})