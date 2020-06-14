var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var re = require('../../../../utils/request.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    clientHeight: 0,
    items:[],
    itemIndex: 0,
    //当前的明细数据
    now_detail: '',


  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    //获取滚动条可滚动高度
    wx.getSystemInfo({
      success: (res) => {
        this.setData({
          clientHeight: res.windowHeight - res.windowWidth / 750 * 96
        });
      }
    });
    this.getCategoryList();
  },

  //请求商品分类数据  
  getCategoryList: function () {
    wx.showLoading({
      title: '加载中',
    })
    const that = this;
    let order = that.data.orderActive;
    let sort = that.data.sort;
    let postData = {}

    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_categorylist, postData, header).then((res) => {
      wx.hideLoading();
      that.setData({
        items: res.data.data
      })
    })
  }

})