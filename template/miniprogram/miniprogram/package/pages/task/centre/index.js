var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var util = require('../../../../utils/util.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    head_bg: getApp().publicUrl + '/wap/static/images/task-head-bg.png',
    page_index:1,
    //任务类型，1-单次任务 2-周期任务
    task_kind:1,
    //用户信息
    user_info:'',
    //任务信息
    task_info:'',
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    const that = this;
    that.getTaskList();
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
    const that = this;
    that.data.page_index += 1;
    that.getTaskList();
  },

  //任务中心页面
  getTaskList: function () {
    const that = this;
    let postData = {
      'page_index': that.data.page_index,
      'page_size':20,
      'task_kind': that.data.task_kind,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getTaskList,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 0) {
          if(that.data.page_index > 1){
            let old_data = that.data.task_info;
            let new_data = old_data.concat(res.data.data.user_task_info.task_info);
            that.setData({
              task_info: new_data
            })
          }else{
            that.setData({
              user_info: res.data.data.user_task_info.user_info,
              task_info: res.data.data.user_task_info.task_info,
            })
          }          
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

  //任务类型切换
  taskKindFun:function(e){
    const that = this;
    let index = e.detail.index;
    if(index == 0){
      that.data.task_kind = 1;
    }else if(index == 1){
      that.data.task_kind = 2;
    }
    that.data.page_index = 1
    that.setData({
      task_info:[]
    })
    that.getTaskList()
  },

  //跳转到任务明细
  taskDetail:function(e){
    let general_poster_id = e.currentTarget.dataset.id;
    wx.navigateTo({
      url: '../detail/index?general_poster_id=' + general_poster_id,
    })
  },

  //跳转到我的任务页面
  myTask:function(){
    wx.navigateTo({
      url: '../list/index',
    })
  },

  //领取任务
  getMyTask: function (e) {
    const that = this;
    let general_poster_id = e.currentTarget.dataset.id;
    let postData = {
      'general_poster_id': general_poster_id,      
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getMyTask,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 0) {
          wx.showToast({
            title: res.data.message,
            icon:'success'
          })
          that.getTaskList();
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