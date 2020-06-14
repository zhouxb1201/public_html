var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var WxParse = require('../../../../common/wxParse/wxParse.js');
var time = require('../../../../utils/time.js');
var util = require('../../../../utils/util.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    //申请成为分销商条件
    applayChannelData: '',
    //-2-未达成条件 -1-申请已拒绝 0-未申请 1-申请未审核 2-申请已审核
    is_checked:'',
    //none-没有开启条件，all-满足所有条件，single-满足一个条件
    channel_condition:'',
    //需要满足的条件
    condition:'',    
    radioChecked: false,
    //真实姓名
    real_name: '',


    //自定义表单数据
    customform: '',
    


  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    const that = this;
    that.applayChannelForm()
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

  //申请成为分销商条件
  applayChannelForm: function () {
    const that = this;
    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_applayChannelForm,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          let result = res.data.data.channel_agreement.condition;
          WxParse.wxParse('content', 'html', result, that);

          //自定义表单数据
          let customform = [];
          if (util.isEmpty(res.data.data.customform)){
            if (res.data.data.customform.channel != undefined){
              customform = res.data.data.customform.channel
            }                        
          }
          
          that.setData({
            applayChannelData: res.data.data,
            is_checked: res.data.data.is_checked,
            condition: res.data.data.condition,
            channel_condition: res.data.data.channel_condition,
            real_name: res.data.data.real_name,
            customform: customform,
          })
        } else {
          wx.showModal({
            title: '提示',
            content: res.data.message,
            showCancel:false,
          })
        }
      },
      fail: (res) => {

      },
    })
  },

  //阅读协议勾选
  radioApplyChange: function (e) {
    const that = this;
    let radioChecked = e.currentTarget.dataset.checked;
    if (radioChecked == true) {
      that.setData({
        radioChecked: false
      })
    } else {
      that.setData({
        radioChecked: true
      })
    }
  },

  //真实姓名
  realNameFun: function (e) {
    const that = this;
    that.setData({
      real_name: e.detail.value
    })
  },


  //申请成为分销商，提交表单
  applayChannel: function () {
    const that = this;
    if (that.data.radioChecked == false) {
      wx.showToast({
        title: '请先阅读协议',
        icon: 'none'
      })
      return;
    }
    let postData = {};
    if (that.data.customform.length != 0) {
      let required = util.isRequired(that.data.customform);
      if (required) {
        postData['post_data'] = JSON.stringify(that.data.customform);
      } else {
        return;
      }       
    } else {
      postData['real_name'] = that.data.real_name;
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_applayChannel,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
          setTimeout(() => {
            wx.navigateBack({
              delta: 1
            })
          }, 1500)
        } else {
          wx.showModal({
            title: '提示',
            content: res.data.message,
            showCancel: false,
          })
        }
      },
      fail: (res) => { },
    })
  },


  //自定义表单
  customformData: function (e) {
    const that = this;
    that.setData({
      customform: e.detail.customform
    })
  },


})