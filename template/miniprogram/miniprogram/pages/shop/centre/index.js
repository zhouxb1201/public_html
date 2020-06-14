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
    publicUrl: getApp().publicUrl,
    //申请入驻的状态
    status:'',
    //协议
    shop_protocol:'',
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    const that = this;
    that.getApplyStateByWap();
    that.getShopProtocolByWap();
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
  getApplyStateByWap:function(){
    const that = this;    
    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_getApplyStateByWap, postData, header).then((res) =>{
      if(res.data.code == 1){
        that.setData({
          status: res.data.data.status
        })
      }
    })
  },

  //获取店铺入驻协议
  getShopProtocolByWap:function(){
    const that = this;
    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_getShopProtocolByWap, postData, header).then((res) => {
      if (res.data.code == 1) {
        let shop_protocol = res.data.data.shop_protocol;
        if (shop_protocol[0].content != ''){
          WxParse.wxParse('description', 'html', shop_protocol[0].content, that);
        }
        if (shop_protocol[1].content != '') {
          WxParse.wxParse('standard', 'html', shop_protocol[1].content, that);
        }
        if (shop_protocol[2].content != '') {
          WxParse.wxParse('require', 'html', shop_protocol[2].content, that);
        }
        if (shop_protocol[3].content != '') {
          WxParse.wxParse('postage', 'html', shop_protocol[3].content, that);
        }
        
        that.setData({
          shop_protocol: shop_protocol
        })
      }
    })
  },

  //跳转到入驻协议页面
  onAgreementPage:function(){
    wx.navigateTo({
      url: '../agreement/index',
    })
  },

  //跳转到申请结果页面
  onResultPage:function(){
    wx.navigateTo({
      url: '../result/index',
    })
  }

})