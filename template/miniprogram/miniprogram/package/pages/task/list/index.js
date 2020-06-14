var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var util = require('../../../../utils/util.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    page_index:1,
    //任务类型，1-进行中 2-已完成 3-已失效
    task_status:1,
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    this.getMyTaskList();
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

  //我的任务列表
  getMyTaskList: function () {
    const that = this;
    let postData = {
      'page_index': that.data.page_index,
      'page_size': 20,
      'task_status': that.data.task_status,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getMyTaskList,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 0) {
          let task_info = res.data.data.user_task_info.task_info;
          that.setData({
            task_info:task_info,            
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

  //我的任务状态改变
  taskStatusFun:function(e){
    const that = this;
    let index = e.detail.index;
    switch(index){
      case 0:
        that.setData({
          task_status:1,
        })        
        break
      case 1:
        that.setData({
          task_status: 2,
        })
        break
      case 2:
        that.setData({
          task_status: 3,
        })
        break
    }
    that.data.page_index = 1;
    that.setData({
      task_info:[],
    })
    that.getMyTaskList();
  },

  //跳转到任务明细
  taskDetail: function (e) {
    let general_poster_id = e.currentTarget.dataset.id;
    let user_task_id = e.currentTarget.dataset.userid;    
    wx.navigateTo({
      url: '../detail/index?general_poster_id=' + general_poster_id + '&user_task_id=' + user_task_id,
    })
  },
})