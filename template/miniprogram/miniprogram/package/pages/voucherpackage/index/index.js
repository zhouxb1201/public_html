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
    publicUrl: getApp().publicUrl,
    detail: {},
    couponList: [],
    giftList: [],
    id: '',
    isSuccess: false
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {
    const value = wx.getStorageSync('user_token')
    if (options.id) {
      this.setData({
        id: options.id
      })     
    }

    // 扫码进来
    if (options.scene != undefined) {
      let scene = options.scene;
      let value_index = scene.indexOf('_');
      this.setData({
        id: scene.substring(value_index + 1)//获取id值 
      })       
    }

    if (value) {
      console.log('已登录');
      this.loadData();
    } else {
      console.log('未登录');
      this.setData({
        loginShow: true,
      })
    }

  },

  //登录结果返回
  requestLogin: function (e) {
    const that = this;
    let result = e.detail.result;
    if (result == true) {
      this.loadData();
    }
  },
  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function() {

  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function() {

  },
  /**
   * 初始化数据
   */
  loadData: function() {
    const that = this;
    let postData = {
      voucher_package_id: that.data.id
    };
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_voucherpackageDetail, postData, header).then((res) => {
      if (res.data.code > 0) {
        that.setData({
          detail: res.data.data
        })
      }
    })
  },
  /**
   * 领取
   */
  onReceive: function() {
    const that = this;
    if (that.checkPhone() == false) {
      return;
    }
    let postData = {
      voucher_package_id: that.data.id
    };
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_voucherpackage, postData, header).then((res) => {
      if (res.data.code > 0) {
        that.setData({
          couponList: res.data.data.coupon_type_list,
          giftList: res.data.data.gift_voucher_list
        })
        wx.showToast({
          title: '领取成功',
          icon: 'success',
          duration: 2000
        })
        setTimeout(() => {
          that.setData({
            isSuccess: true
          })
        }, 500);
      } else {
        wx.showToast({
          title: res.data.message,
          icon: 'none',
          duration: 2000
        })
      }
    })
  },
  //是否有电话
  checkPhone: function () {
    let that = this;
    let have_mobile = wx.getStorageSync('have_mobile');
    if (have_mobile != true) {
      that.setData({
        phoneShow: true
      })
    }
    return have_mobile
  },

  //绑定手机结果返回
  phonereResult: function (e) {
    const that = this
    let result = e.detail.result;
    if (result == 'success') {
      that.loadData();
    }
  }

})