var requestSign = require('../../../utils/requestData.js');
var api = require('../../../utils/api.js').open_api;
var util = require('../../../utils/util.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    isShowThingcircle: 0,
    items: [{
        imgUrl: "../../../images/pic_message.png",
        text: "消息通知",
        badge: 0
      },
      {
        imgUrl: "../../../images/pic_give_up.png",
        text: "赞和收藏",
        badge: 0
      },
      {
        imgUrl: "../../../images/pic_at.png",
        text: "评论和@",
        badge: 0
      }
    ]
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
    const that = this
    that.chatList();
    if (getApp().globalData.config) {
      that.setData({
        isShowThingcircle: getApp().globalData.config.addons.thingcircle
      })
    } else {
      getApp().watch('config', function (e) {
        that.setData({
          isShowThingcircle: e.addons.thingcircle
        })
      })
    }
    that.getThingcircleMessage()
  },

  //会员客服消息列表，
  chatList: function () {
    const that = this;
    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_chatList,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          that.setData({
            messageList: res.data.data
          })
          if (res.data.data.length > 0) {
            wx.setStorageSync("seller_code", res.data.data[0].seller_code);
          }
        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
        }
      },
      fail: (res) => {},
    })
  },

  getThingcircleMessage: function () {
    const that = this;
    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_thingcircleMessageInfo,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          that.data.items[0].badge = res.data.data.message_count;
          that.data.items[1].badge = res.data.data.lac_count;
          that.data.items[2].badge = res.data.data.comment_count;
          that.setData({
            items: that.data.items
          })
        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
        }
      },
      fail: (res) => {},
    })
  },

  toPath:function(e){
    const that = this
    let {index} = e.currentTarget.dataset;
    let url = ''
    if (index == 0) {
      url = '../goodthingcircle/notice/index'
    } else {
      let hash;
      if (index == 1) {
        hash = "collect";
      } else if (index == 2) {
        hash = "at";
      }
      url = '../goodthingcircle/message/index?hash='+hash
    }
    that.data.items[index].badge = 0;
    that.setData({
      items: that.data.items
    })
    wx.navigateTo({
      url: url,
    })
  },

  onChatPage: function (e) {
    const that = this
    let kefuCode = e.currentTarget.dataset.kefucode;

    wx.navigateTo({
      url: '/packageSecond/pages/chat/index?kefuCode=' + kefuCode,
    })
  }
})