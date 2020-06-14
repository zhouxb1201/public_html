var requestSign = require('../../../../../utils/requestData.js');
var api = require('../../../../../utils/api.js').open_api;
var util = require('../../../../../utils/util.js');
var re = require('../../../../../utils/request.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    mic_logo: null,
    mic_name: null,
    mic_introduce: null,
    goodslist: [],
    items: [],
    shopkeeper_id: null
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {
    const that = this;   
    // 扫码进来
    if (options.scene != undefined) {
      let scene = options.scene;
      let scene_arr = scene.split('_');
      if (scene_arr[0] != -1) {
        wx.setStorageSync('higherExtendCode', scene_arr[0]);
      }
      this.data.shopkeeper_id = scene_arr[1];
      wx.setStorageSync('posterId', scene_arr[2])//获取超级海报id
      wx.setStorageSync('posterType', scene_arr[3])//获取超级海报类型                               
    }
    const value = wx.getStorageSync('user_token');
    if (value) {
      console.log('已登录');
      util.checkReferee();
    } else {
      console.log('未登录')
      that.setData({
        loginShow: true,
      })
    }
   
    if (options.shopkeeper_id) {      
      this.data.shopkeeper_id = options.shopkeeper_id;
    }

    if (options.extend_code != undefined) {
      wx.setStorageSync('higherExtendCode', options.extend_code)
    }

    this.loadData();
  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function() {

  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function() {
    
  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function() {
    let that = this;
    let path_url = "package/pages/microshop/preview/index/index?shopkeeper_id=" + that.data.shopkeeper_id;
    if (wx.getStorageSync('extend_code')) {
      path_url = path_url + '&extend_code=' + wx.getStorageSync('extend_code');
    }
    return {
      path: path_url,
      success: (res) => {
        console.log('转发成功！');
      },
      fail: (res) => {
        console.log('转发失败！');
      }
    }
  },

  //登录结果返回
  requestLogin: function (e) {
    const that = this;
    let result = e.detail.result;
    if (result == true) {
      that.loadData();
    }
  },


  loadData: function() {
    const that = this;
    let postData = {};
    if (that.data.shopkeeper_id) {
      postData.shopkeeper_id = that.data.shopkeeper_id;
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_micCentreInfo, postData, header).then((res) => {
      if (res.data.code >= 0) {
        let result = res.data.data;
        that.setData({
          mic_logo: result.microshop_logo || result.user_headimg ,
          mic_name: result.microshop_name,
          mic_introduce: result.microshop_introduce,          
        })
        that.loadPreviewShop(that.data.shopkeeper_id);
        that.loadShopGoods(that.data.shopkeeper_id);
      }
    })
  },
  loadPreviewShop: function(shopkeeper_id) {
    const that = this;
    let postData = {};
    postData.shopkeeper_id = shopkeeper_id;
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_micPreviewShop, postData, header).then((res) => {
      if (res.data.code >= 1) {
        that.setData({
          goodslist: res.data.data.goods_list
        })
      }
    })
  },
  loadShopGoods: function(shopkeeper_id) {
    const that = this;
    let postData = {};
    postData.shopkeeper_id = shopkeeper_id;
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_micPreviewShopGoods, postData, header).then((res) => {
      if (res.data.code >= 1) {
        that.setData({
          items: res.data.data
        })
      }
    })
  },
  toGoodsList: function() {
    wx.navigateTo({
      url: '../list/list?shopkeeper_id=' + this.data.shopkeeper_id,
    })
  },
  toList: function(e) {
    let target = e.currentTarget.dataset;
    wx.navigateTo({
      url: '../list/list?shopkeeper_id=' + this.data.shopkeeper_id + '&category_id=' + target.id + '&category_name=' + target.text,
    })
  },
  toCategory: function() {
    wx.navigateTo({
      url: '../category/category?shopkeeper_id=' + this.data.shopkeeper_id
    })
  },
  toGoodsDetail: function(e) {
    let target = e.currentTarget.dataset;
    wx.navigateTo({
      url: '../../../../../pages/goods/detail/index?goodsId=' + target.goodsid + '&shopkeeper_id=' + this.data.shopkeeper_id,
    })
  }
})