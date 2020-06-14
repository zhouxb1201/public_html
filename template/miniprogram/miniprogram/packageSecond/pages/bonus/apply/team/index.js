var requestSign = require('../../../../../utils/requestData.js');
var api = require('../../../../../utils/api.js').open_api;
var header = getApp().header;
var WxParse = require('../../../../../common/wxParse/wxParse.js');
var time = require('../../../../../utils/time.js');
var util = require('../../../../../utils/util.js');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    boxShow:false,
    //1全球代理, 2区域代理, 3团队代理
    type: '',
    //申请成为代理商的条件数据
    bonusApplyData: '',
    radioChecked: false,
    //真实姓名
    real_name: '',
    //全球分红1:满足以下所有条件，2:满足条件之一，-1：直接申请
    teamagent_condition: '',
    conditions_array: '',
    //自定义表单
    customform:'',
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    const that = this;
    let type = options.type;
    that.data.type = type;
    //判断手机是否存在
    if (util.hasPhone() == false) {
      that.setData({
        phoneShow: true
      })
    } else {
      that.setData({
        boxShow: true
      })
      that.applyagent()
    }
   
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

  //申请成为代理商，所需条件，情况多种，
  applyagent: function () {
    const that = this;
    let postData = {
      'type': that.data.type
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_applyagent,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          let result = res.data.data.team_bonus_agreement.content;
          WxParse.wxParse('content', 'html', result, that);

          //自定义表单数据
          let customform = res.data.data.team_bonus_agreement.customform;
          let customform_array = Object.keys(customform);//判断对象是否为空，返回值是数组
          if (customform_array.length != 0) {
            that.setData({
              customform: customform
            })
          }
          
          let teamagent_condition = res.data.data.team_bonus.teamagent_condition;
          let teamagent_conditions = res.data.data.team_bonus.teamagent_conditions;
          let conditions_array = teamagent_conditions.split(',');

          that.setData({
            bonusApplyData: res.data.data,
            teamagent_condition: teamagent_condition,
            conditions_array: conditions_array
          })
          that.teamTxt();
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

  //图片错误
  errorimg: function () {
    const that = this;
    let bonusApplyData = that.data.bonusApplyData;
    bonusApplyData.global_bonus_agreement.logo = '/images/rectangle-error.png'
    that.setData({
      bonusApplyData: bonusApplyData
    })
  },

  //申请团队分红文案
  teamTxt: function () {
    const that = this;
    let teamTxtData = that.data.bonusApplyData.team_bonus_agreement;
    wx.setNavigationBarTitle({
      title: teamTxtData.apply_team,
    })
    that.setData({
      txt_team_agreement: teamTxtData.team_agreement
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


  //申请成为团队代理
  teamAgentApply: function () {
    const that = this;
    if (that.data.radioChecked == false) {
      wx.showToast({
        title: '请先阅读协议',
        icon: 'none'
      })
      return;
    }
    let postData = {};
    if (that.data.customform != '') {
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
      url: api.get_teamAgentApply,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          wx.showToast({
            title: res.data.message,
            icon: 'success'
          })
          wx.navigateBack({
            delta: 1
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


  //自定义表单
  customformData: function (e) {
    console.log(e.detail);
    const that = this;
    that.setData({
      customform: e.detail.customform
    })
  },

  //绑定手机结果返回
  phonereResult: function (e) {
    const that = this
    let result = e.detail.result;
    if (result == 'success') {
      that.setData({
        boxShow: true
      })
      that.applyagent()
    }
  },
  
})