// miniprogram/packageSecond/pages/goodthingcircle/follow/index.js
var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    page_index: 1,
    page_size: 20,
    listData: []
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {

  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {
    this.loadList();
  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {
    const that = this;
    that.data.page_index += 1;
    that.loadList();
  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {

  },
  loadList: function () {
    const that = this;
    let postData = {
      'page_index': that.data.page_index,
      'page_size': that.data.page_size
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_attentionUserList,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code === 1) {
          let listData = that.data.listData;
          let listPageData = res.data.data.data;
          if (that.data.page_index > 1) {
            listData = listData.concat(listPageData);
            that.setData({
              listData: listData
            })
          } else {
            if (listPageData.length == 0) {
              that.data.reachBottomflag = false;
              return false;
            }
            that.setData({
              listData: listPageData
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
  onOthers:function(e){
    const that = this;
    const event = e.currentTarget.dataset;
    let postData = {
      'thing_auid': event.id
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_attentionThingcircle,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code === 1) {
          if(res.data.message == "关注成功"){
            that.data.listData[event.index].mutual = 1;
          }else{
            that.data.listData[event.index].mutual = 0;
          }
          that.setData({
            listData:that.data.listData
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
  }
})