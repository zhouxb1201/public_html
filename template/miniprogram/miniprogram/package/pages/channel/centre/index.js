var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var util = require('../../../../utils/util.js');
var header = getApp().header;
var time = require('../../../../utils/time.js');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    pageShow:false,
    //是否是渠道商 true-是 false-不是
    is_channel:'',
    //渠道商数据
    channelData:'',
    //成为渠道商时间
    channel_time:'',
    
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
    const that = this;
    const value = wx.getStorageSync('user_token');
    if(value){
      that.checkApplay();
    }else{
      this.setData({
        loginShow: true,
      })
    }
    
    getApp().globalData.credential_type = 3
  },

  //登录结果返回
  requestLogin: function (e) {
    const that = this;
    let result = e.detail.result;
    if (result == true) {
      that.checkApplay();
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

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {

  },

  checkApplay:function(){
    const that = this;
    that.applayChannelForm().then((res) => {
      console.log(res.statusCode);
      if (res.statusCode == 500) {
        wx.showModal({
          title: '提示',
          content: '接口数据错误',
          showCancel: false,
        })
      }
      if (res.data.code == 1) {
        that.setData({
          is_channel: res.data.data.is_channel,
          channel_time: time.js_date_time(res.data.data.to_channel_time),
        })
        if (res.data.data.is_channel == false) {

          wx.showModal({
            title: '提示',
            content: '您还不是渠道商，请先申请',
            success(res) {
              if (res.confirm) {
                let onPageData = {
                  url: '/package/pages/channel/apply/index',
                  num: 4,
                  param: '',
                }
                util.jumpPage(onPageData);
              } else if (res.cancel) {
                let onPageData = {
                  url: 1,
                  num: 5,
                  param: '',
                }
                util.jumpPage(onPageData);
              }
            },
          })
        } else {
          that.setData({
            pageShow: true,
            channelData: res.data.data,
          })
        }
      } else if (res.data.code == 0) {//code=0,成为分销商才能申请渠道商
        wx.showModal({
          title: '提示',
          content: res.data.message,
          showCancel: true,
          success(res) {
            if (res.confirm) {
              wx.navigateBack({
                delta: 1
              })
            }
          }
        })
      } else {
        wx.showToast({
          title: res.data.message,
        })
      }
    })
  },




  //渠道商入口
  applayChannelForm:function(){
    const that = this;
    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    return new Promise((resolve, reject) => {
      wx.request({
        url: api.get_channelIndex,
        data: postData,
        header: header,
        method: 'POST',
        dataType: 'json',
        responseType: 'text',
        success: (res) => {
          resolve(res);
        },
        fail: (res) => {
          reject(res);
         },
      })
    })
  },
})