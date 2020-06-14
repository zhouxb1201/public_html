var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var util = require('../../../../utils/util.js');
var header = getApp().header;
var Base64 = require('../../../../utils/base64.js').Base64;

Page({

  /**
   * 页面的初始数据
   */
  data: {
    accountList:'',
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
    this.getAccount();
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
  //获取账户列表数据
  getAccount: function () {
    const that = this;
    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_bank_account,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          let accountList = res.data.data; 
          let weChat_obj = {
            type:2,
            realname:'微信账号',            
          }
          accountList.unshift(weChat_obj);
          that.setData({
            accountList: accountList
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
  //跳转到账户新增页面
  onPostPage:function(){    
    if (this.checkPhone() == false){
      return;
    }
    let onPageData = {
      url: '../post/index',
      num: 4,
      param: '',
    }
    util.jumpPage(onPageData);
  },
  //删除账户
  delAccount:function(e){
    const that = this;
    let postData = {
      'account_id':e.currentTarget.dataset.id
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_del_account,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          wx.showToast({
            title: res.data.message,
          })
          that.getAccount();
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
  //编辑账户
  editAccount:function(e){
    const that = this;
    let account_id = e.currentTarget.dataset.id;
    for (var i of that.data.accountList){
      if (i.id == account_id) {
        let info = {
          'account_id':i.id,
          'type': i.type,
          'realname': i.realname,
          'account_number': i.account_number,
          'open_bank':i.open_bank
        }
        info = Base64.encode(JSON.stringify(info))        
        let onPageData = {
          url: '../post/index',
          num: 4,
          param: '?fun=edit&&info=' + info,
        }
        util.jumpPage(onPageData);
        break;
      }      
    }
  },

  //是否有电话
  checkPhone: function () {
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
      that.getAccount();
    }
  },

})