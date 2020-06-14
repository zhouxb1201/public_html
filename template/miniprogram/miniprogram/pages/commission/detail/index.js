var requestSign = require('../../../utils/requestData.js');
var api = require('../../../utils/api.js').open_api;
var util = require('../../../utils/util.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    //分销佣金
    myCommissionData:'',
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
    const value = wx.getStorageSync('user_token');
    if(value){
      this.setDistributionData();
      this.myCommission();
    }else{
      this.setData({
        loginShow: true,
      })
    }
    
  },

  //登录结果返回
  requestLogin: function (e) {
    const that = this;
    let result = e.detail.result;
    if (result == true) {
      this.setDistributionData();
      this.myCommission();
    }
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

  //分销佣金
  myCommission: function () {
    const that = this;

    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    wx.request({
      url: api.get_myCommissiona,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if(res.data.code >= 0){
          that.setData({
            myCommissionData:res.data.data
          })
        }else{
          wx.showToast({
            title: res.data.message,
            icon:'none'
          })
        }

      },
      fail: (res) => { },
    })

  },

  //跳转到佣金提现页面 
  onWithdrawPage:function(){    
    wx.redirectTo({
      url: '../withdraw/index',
    })
  },

  //设置分销的文案字段
  setDistributionData: function () {
    const that = this;
    let distributionData = getApp().globalData.distributionData;
    if (distributionData == '') {
      util.distributionSet().then((res) => {
        let resultData = res.data.data;
        that.setData({
          txt_withdrawable_commission: resultData.withdrawable_commission,
          txt_total_commission: resultData.total_commission,
          txt_commission: resultData.commission,
          txt_withdrawals_commission: resultData.withdrawals_commission,
          txt_frozen_commission: resultData.frozen_commission,
          txt_withdrawal: resultData.withdrawal,
         
        })
      });

    } else {
      wx.setNavigationBarTitle({
        title: distributionData.distribution_commission,
      })
      that.setData({
        txt_withdrawable_commission: distributionData.withdrawable_commission,
        txt_total_commission: distributionData.total_commission,
        txt_commission: distributionData.commission,
        txt_withdrawals_commission: distributionData.withdrawals_commission,
        txt_frozen_commission: distributionData.frozen_commission,
        txt_withdrawal: distributionData.withdrawal,

      })
    }
  },
})