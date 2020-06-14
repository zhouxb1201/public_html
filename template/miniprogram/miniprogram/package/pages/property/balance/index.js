var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var util = require('../../../../utils/util.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    //总金额
    balance:'',
    //可用余额
    can_use_money:'',
    //冻结金额
    freezing_balance:'',
    //提现是否开启，1：开启，0：关闭，开启则显示页面提现按钮，否则则关闭
    is_use:'',
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
    this.getBalance();
  },

  //获取余额数据
  getBalance:function(){
    const that = this;

    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_balance,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {          
          that.setData({
            balance: res.data.data.balance,
            freezing_balance: res.data.data.freezing_balance,
            can_use_money: res.data.data.can_use_money,
            is_use: res.data.data.is_use,
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
  //跳转到充值页
  onRechargePage:function(){
    if (this.checkPhone() == false){
      return
    }
    let onPageData = {
      url: '../recharge/index',
      num: 4,
      param: '',
    }
    util.jumpPage(onPageData);
  },
  //跳转到提现页面
  onWithdrawPage:function(){
    if (this.checkPhone() == false) {
      return
    }
    let onPageData = {
      url: '../withdraw/index',
      num: 4,
      param: '',
    }
    util.jumpPage(onPageData);
  },

  //是否有电话
  checkPhone:function(){
    let that = this;
    let have_mobile = wx.getStorageSync('have_mobile');
    if (have_mobile != true) {
      that.setData({
        phoneShow: true
      })
    }
    return have_mobile
  },

  //绑定手机结果返回
  phonereResult: function (e) {
    const that = this
    let result = e.detail.result;
    if (result == 'success') {      
      that.getBalance();
    }
  },
  

  
})