var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var re = require('../../../../utils/request.js');
var util = require('../../../../utils/util.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    page: 1,
    list: [],
    noMore: 'false'
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    this.loadData();
  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {
    
  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {
    this.data.page = this.data.page + 1;
    this.loadData();
  },
  loadData: function () {
    const that = this;
    let postData = {
      'page_index': that.data.page,
      'page_size': 8
    };
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_signinLog, postData, header).then((res) => {
      if (res.data.code > 0) {
        var list = that.data.list;
        if (list.length == 0) {
          list = res.data.data.data;
        } else {
          list = list.concat(res.data.data.data); //滚动到底部把数据添加到原数组
        }
        this.setData({
          list: list
        })

        if (res.data.data.total_count == that.data.list.length) {
          this.setData({
            noMore: 'true'
          })
        }
      } else {
        wx.showModal({
          title: '提示',
          content: res.data.message,
          showCancel: false,
        })
      }
    })
  }
})