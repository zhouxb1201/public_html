const app = getApp();
var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var util = require('../../../../utils/util.js');
var re = require('../../../../utils/request.js');
var header = getApp().header;

Page({

  /**
   * 页面的初始数据
   */
  data: {
    boxShow: false,
    //自定义模板数据
    copyData: "",
    temData: "",
    //所有图片的高度  
    imgheights: [],
    //默认  
    current: 0,
    //商品数据
    goodsList: "",
    idarray: [],

    //精选店铺
    shopsList: '',
    recommendnum: ""
  },

  bindViewTap: function (event) {
    let v = event.currentTarget.dataset.recommendnum
    this.setData({
      recommendnum: v
    })
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    const that = this;
    if (options.scene != undefined) {
      wx.setStorageSync('higherExtendCode', options.scene)
    }
    wx.showLoading({
      title: '加载中',
    })
    const value = wx.getStorageSync('user_token')
    if (value) {
      console.log('已登录');
    } else {
      console.log('未登录')
      wx.hideTabBar();
      that.setData({
        loginShow: true,
      })
    }

    //that.configFun();


  },



  requestLogin: function (e) {
    const that = this;
    let result = e.detail.result;
    if (result == true) {
      that.getTemData();
      util.extend_code();
    }
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
    const that = this;
    that.getTemData();
    util.extend_code();
    console.log(app.globalData.config);
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

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {

  },

  //获取自定义数据 
  getTemData: function () {
    const that = this;
    let postData = {
      "type": 9,
      "is_mini": 1
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_custom, postData, header).then((res) => {
      wx.hideLoading();
      if (res.data.code < 0) {
        wx.showModal({
          title: '提示',
          content: res.data.message,
          showCancel: true,
        })
      } else {
        //判断图片地址是本地图片还是网络图片
        let template_data = res.data.data.template_data
        for (var item in template_data.items) {
          let item_data = template_data.items[item].data;
          for (var index in item_data) {
            if (item_data[index].imgurl != undefined) {
              if (item_data[index].imgurl.substring(0, 1) == 'h') {
              } else {
                item_data[index].imgurl = getApp().publicUrl + item_data[index].imgurl
              }
            }
          }
        }

        let copyright = '';
        if (res.data.data.copyright != undefined) {
          copyright = res.data.data.copyright
        }

        that.setData({
          copyData: copyright,
          temData: template_data,
          boxShow: true,
        });
      }
    })

  }









})