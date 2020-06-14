// miniprogram/package/pages/microshop/profit/detail/detail.js
var requestSign = require('../../../../../utils/requestData.js');
var api = require('../../../../../utils/api.js').open_api;
var util = require('../../../../../utils/util.js');
var re = require('../../../../../utils/request.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    profit: 0,
    total_money: 0,
    withdrawals: 0,
    apply_withdraw: 0,
    freezing_profit: 0
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {
    this.loadData();
  },
  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function() {

  },
  loadData: function() {
    const that = this;
    let postData = {};
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_micShopDetail, postData, header).then((res) => {
      if (res.data.code == 0) {
        that.setData({
          profit: res.data.data.profit,
          total_money: res.data.data.total_money,
          withdrawals: res.data.data.withdrawals,
          apply_withdraw: res.data.data.apply_withdraw,
          freezing_profit: res.data.data.freezing_profit
        })
      } else {
        wx.showModal({
          title: '提示',
          content: res.data.message,
          showCancel: false,
        })
      }
    })
  },
  toLog() {
    wx.navigateTo({
      url: '../log/log'
    })
  },
  toWithdraw() {
    const that = this;
    if (that.data.profit > 0) {
      wx.navigateTo({
        url: '../withdraw/withdraw'
      })
    }
  }
})