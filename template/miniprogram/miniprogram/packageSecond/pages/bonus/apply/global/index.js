var requestSign = require('../../../../../utils/requestData.js');
var api = require('../../../../../utils/api.js').open_api;
var header = getApp().header;
var WxParse = require('../../../../../common/wxParse/wxParse.js');
var util = require('../../../../../utils/util.js');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    boxShow:false,
    //1全球代理, 2区域代理, 3团队代理
    type:'',
    //申请成为代理商的条件数据
    bonusApplyData:'',
    radioChecked: false,
    //真实姓名
    real_name:'',
    //全球分红1:满足以下所有条件，2:满足条件之一，-1：直接申请
    globalagent_condition:'',
    conditions_array:'',

    //自定义表单数据
    customform: '',
    
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
      'type':that.data.type
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
          let result = res.data.data.global_bonus_agreement.content;
          WxParse.wxParse('content', 'html', result, that);

          //自定义表单数据
          let customform = '';
          let bonus_customform = Object.keys(res.data.data.global_bonus_agreement.customform)
          if (bonus_customform.length != 0){
            customform = res.data.data.global_bonus_agreement.customform;
          }          

          let globalagent_condition = res.data.data.global_bonus.globalagent_condition;
          let globalagent_conditions = res.data.data.global_bonus.globalagent_conditions;
          let conditions_array = globalagent_conditions.split(',');

          that.setData({
            bonusApplyData:res.data.data,
            customform: customform,
            globalagent_condition: globalagent_condition,
            conditions_array: conditions_array
          })

          that.globalBonusTxt();

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

  //申请全球分红文案
  globalBonusTxt:function(){
    const that = this;
    let globalBonusTxtData = that.data.bonusApplyData.global_bonus_agreement;
    wx.setNavigationBarTitle({
      title: globalBonusTxtData.apply_global,
    })
    that.setData({
      txt_global_agreement: globalBonusTxtData.global_agreement
    })
  },

  //图片错误
  errorimg:function(){
    const that = this;
    let bonusApplyData = that.data.bonusApplyData;
    bonusApplyData.global_bonus_agreement.logo = '/images/no-goods.png'
    that.setData({
      bonusApplyData: bonusApplyData
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

  //申请成为全球代理
  globalAgentApply:function(){
    const that = this;
    if (that.data.radioChecked == false) {
      wx.showToast({
        title: '请先阅读协议',
        icon: 'none'
      })
      return;
    }
    let postData = {};
    if (that.data.customform !=  '') {
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
      url: api.get_globalAgentApply,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          wx.showToast({
            title: res.data.message,
            icon:'success'
          })
          wx.navigateBack({
            delta:1
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