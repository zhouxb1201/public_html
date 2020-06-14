var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    //订单号，创建充值订单时填写
    out_trade_no:'',
    //充值金额
    recharge_money:'',
    //消息模板id
    form_id:'',
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    this.getRechargeOrderNo();
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

  //获取充值订单号
  getRechargeOrderNo:function(){
    const that = this;
    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_recharge,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          that.data.out_trade_no = res.data.data.out_trade_no;
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

  //充值金额
  rechargeMoneyFun:function(e){
    const that = this;
    that.data.recharge_money = e.detail.value 
  },

  //创建充值订单
  createRechargeOrder:function(){
    const that = this;
    let postData = {
      'recharge_money': that.data.recharge_money,
      'out_trade_no': that.data.out_trade_no,
      'form_id': that.data.form_id,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_createRechargeOrder,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          wx.redirectTo({
            url: '/pages/payment/pay/index?orderno=' + that.data.out_trade_no +'&type=6&hash=recharge',
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

  //获取消息模板id
  templateSend: function (e) {
    let that = this;
    let form_id = e.detail.formId;
    that.data.form_id = form_id;
    that.createRechargeOrder();
  }
  
})