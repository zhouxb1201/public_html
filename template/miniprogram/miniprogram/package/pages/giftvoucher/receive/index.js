var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var util = require('../../../../utils/util.js');
var re = require('../../../../utils/request.js');
var time = require('../../../../utils/time.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    //礼品卷id
    gift_voucher_id:'',
    //礼品卷详情
    giftvoucherdetail: '',
    //可用店铺
    store_list:'',
    page_index:1,
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    const that = this;    
    //跳转进来
    if (options.gift_voucher_id != undefined){
      let gift_voucher_id = options.gift_voucher_id;
      that.data.gift_voucher_id = gift_voucher_id;
    }

    // 扫码进来
    if (options.scene != undefined) {
      let scene = options.scene;
      let value_index = scene.indexOf('_');
      that.data.gift_voucher_id = scene.substring(value_index + 1)//获取id值 
    }   

    that.giftvoucherDetail();
    that.getUserLocation();   
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

  //获取礼品券详情
  giftvoucherDetail: function () {
    const that = this;
    let postData = {
      'gift_voucher_id': that.data.gift_voucher_id
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_giftvoucherDetail,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          let giftvoucherdetail = res.data.data;
          giftvoucherdetail.start_time = time.js_date_time(giftvoucherdetail.start_time);
          giftvoucherdetail.end_time = time.js_date_time(giftvoucherdetail.end_time);
          that.setData({
            giftvoucherdetail: giftvoucherdetail,
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

  //获取当前位置的经纬度
  getUserLocation: function () {
    const that = this;
    wx.getLocation({
      type: 'gcj02',
      success: function (res) {
        //纬度，范围为 -90~90，负数表示南纬
        const latitude = res.latitude
        //经度，范围为 -180~180，负数表示西经
        const longitude = res.longitude
        that.setData({
          lng: longitude,
          lat: latitude
        })
        that.giftvoucherStore();
      },
    })
  },

  //获取礼品券可用门店
  giftvoucherStore: function () {
    const that = this;
    let postData = {
      'gift_voucher_id': that.data.gift_voucher_id,
      'lng': that.data.lng,
      'lat': that.data.lat,
      'page_index': that.data.page_index,
      'page_size':20,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_giftvoucherStore,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          let store_list = res.data.data.store_list;
          that.setData({
            store_list: store_list
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

  //领取礼品卷
  giftvoucherReceive: function () {
    const that = this;
    if (that.ifLogin() == false) {
      return
    };
    if (that.hasPhoneFun() == false) {
      return
    }
    let postData = {
      'gift_voucher_id': that.data.gift_voucher_id,
      'get_type': 1,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_giftvoucherReceive,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code > 0) {
          wx.showToast({
            title: '领取礼品卷成功！',
          })
        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
          that.giftvoucherDetail();
        }
      },
      fail: (res) => { },
    })
  },

  //判断是否登录
  //是否登录
  ifLogin: function () {
    const that = this;
    var userToken = wx.getStorageSync('user_token');
    var ifLogin = true;
    if (userToken == '') {
      wx.showToast({
        title: '您还未授权登录',
        icon: 'loading'
      })
      setTimeout(function () {
        wx.hideToast()
        that.setData({
          loginShow: true,
        })

      }, 2000)
      ifLogin = false;
      return ifLogin
    }
    return ifLogin
  },

  //判断是否有手机号
  hasPhoneFun: function () {
    const that = this;
    const have_mobile = wx.getStorageSync('have_mobile');
    if (have_mobile != true) {
      that.setData({
        phoneShow: true
      })
      
      return false
    }
  },

  //绑定手机结果返回
  phonereResult: function (e) {
    const that = this
    let result = e.detail.result;
    if (result == 'success') {
      that.giftvoucherDetail();
      that.getUserLocation();
    }
  },
})