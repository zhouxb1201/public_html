var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var util = require('../../../../utils/util.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    active:0,
    //消费卡状态 0可使用 1已使用 2已过期
    state:0,
    page_index:1,
    card_list:[],
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    this.consumerCardlist();
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
    that.data.page_index = that.data.page_index + 1;
    that.consumerCardlist();
  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {

  },

  //消费卡状态
  onConsumerCardChange: function (e) {
    const that = this;
    let title = e.detail.title;
    switch (title) {
      case '可使用':
        that.setData({
          state: 0,
          card_list: [],
        })
        that.consumerCardlist();
        break;
      case '已使用':
        that.setData({
          state: 1,
          card_list: [],
        })
        that.consumerCardlist();
        break;
      case '已过期':
        that.setData({
          state: 2,
          card_list: [],
        })
        that.consumerCardlist();
        break;
    }
  },


  //获取消费卡列表
  consumerCardlist: function () {
    const that = this;
    let postData = {
      'state': that.data.state,
      'page_index': that.data.page_index,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_consumerCard,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          if (that.data.page_index > 1){
            let old_card_list = that.data.card_list;
            let new_card_list = res.data.data.data;
            old_card_list = old_card_list.concat(new_card_list);
            that.setData({
              card_list: old_card_list,
            })
          }else{
            that.setData({
              card_list: res.data.data.data,
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

  onDetailPage:function(e){
    const that = this;
    let card_id = e.currentTarget.dataset.cardid;
    wx.navigateTo({
      url: '../detail/index?card_id=' + card_id,     
    })
  }
})