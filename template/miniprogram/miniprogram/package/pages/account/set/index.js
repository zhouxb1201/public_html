var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var util = require('../../../../utils/util.js');
var header = getApp().header;
const app = getApp();
Page({

  /**
   * 页面的初始数据
   */
  data: {
    //用户基本信息
    userBaseInfo:'',
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    const that = this;
    that.getMemberBaseInfo();
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
   * 获取用户基本信息
   */
  getMemberBaseInfo: function () {
    const that = this;   
    let postData = {};
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getMemberBaseInfo,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          that.setData({
            userBaseInfo:res.data.data
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

  /**
   * 退出账号的模拟框
   */
  loginOutShowModal:function(){
    const that = this;
    wx.showModal({
      title: '确认退出？',
      content: '退出后将无法查看订单，重新登录即可查看',
      success(res){
        if(res.confirm){
          console.log('成功')
        }else if(res.cancel){
          console.log('失败')
        }
      }
    })
  },

  /**
   * 退出登录
   */
  loginOut:function(){
    const that = this;
    let postData = {};
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_logout,
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
          wx.clearStorage();
          app.loginStatus = false;
          setTimeout(()=>{
            
            let onPageData = {
              url: '/pages/index/index',
              num: 4,
              param: '',
            }
            util.jumpPage(onPageData);
          },2000)
        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
        }
      },
      fail: (res) => { },
    })
  }
})