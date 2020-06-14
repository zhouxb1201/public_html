var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var util = require('../../../../utils/util.js');
var WxParse = require('../../../../common/wxParse/wxParse.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    head_bg: getApp().publicUrl + '/wap/static/images/task-head-bg.png',
    //任务id
    general_poster_id:'',
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    const that = this;
    let general_poster_id = options.general_poster_id;
    that.data.general_poster_id = general_poster_id;
    that.data.user_task_id = options.user_task_id;
    that.getTaskDetail();
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

  //任务详情
  getTaskDetail: function () {
    const that = this;
    let postData = {
      'general_poster_id': that.data.general_poster_id,        
    }
    if (that.data.user_task_id != undefined){
      postData.user_task_id = that.data.user_task_id
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getTaskDetail,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 0) {
          let task_detail = res.data.data.general_task_detail;
          that.setData({
            task_detail: task_detail,
          })
          let task_explain = task_detail.task_explain
          WxParse.wxParse('description', 'html', task_explain, that);
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


})