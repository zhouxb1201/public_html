var requestSign = require('../../../utils/requestData.js');
var api = require('../../../utils/api.js').open_api;
var util = require('../../../utils/util.js');
var re = require('../../../utils/request.js');
var WxParse = require('../../../common/wxParse/wxParse.js');
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
    this.getShopProtocolByWap();
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

  //获取店铺入驻协议
  getShopProtocolByWap: function () {
    const that = this;
    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_getShopProtocolByWap, postData, header).then((res) => {
      if (res.data.code == 1) {
        let shop_protocol = res.data.data.shop_protocol;
        if (shop_protocol[4].content != '') {
          WxParse.wxParse('agreement', 'html', shop_protocol[4].content, that);
        }       
        wx.setNavigationBarTitle({
          title: shop_protocol[4].title,
        })
        that.setData({
          shop_protocol: shop_protocol
        })
      }
    })
  },

  //跳转到填写资料页面
  onApplyFormPage:function(){
    wx.redirectTo({
      url: '../apply/index',
    })
  },
})