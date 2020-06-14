var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var util = require('../../../../utils/util.js');
var header = getApp().header;
var time = require('../../../../utils/time.js');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    //优惠券列表
    couponList: '',
    state: 1,
    total_count: 1,
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    this.getCoupon();
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




  onCouponChange: function (e) {
    const that = this;
    let index = e.detail.index;
    let title = e.detail.title;
    switch (title) {
      case '未使用':
        that.setData({
          state:1,
          couponList:''
        })        
        that.getCoupon();
        break;
      case '已使用':
        that.setData({
          state: 2,
          couponList: ''
        })
        that.getCoupon();
        break;
      case '已过期':
        that.setData({
          state: 3,
          couponList: ''
        })
        that.getCoupon();
        break;
    }
  },

  //获取优惠券
  getCoupon: function () {
    const that = this;
    let postData = {
      'state': that.data.state
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getcouplist,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          let couponList = res.data.data.list;
          for (var i = 0; i < couponList.length; i++) {
            couponList[i].start_time = time.js_date_time(couponList[i].start_time);
            couponList[i].end_time = time.js_date_time(couponList[i].end_time);
            couponList[i].discount = parseInt(couponList[i].discount);
            couponList[i].money = parseInt(couponList[i].money);
            couponList[i].at_least = parseInt(couponList[i].at_least);
          }
          that.setData({
            couponList: couponList,
            total_count: res.data.data.total_count
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

  //跳转到领劵中心
  onCentrePage:function(){    
    let onPageData = {
      url: '../centre/index',
      num: 4,
      param: ''
    }
    util.jumpPage(onPageData);
  },
  //跳转到优惠券详情
  onDetailPage:function(e){
    let coupon_type_id = e.currentTarget.dataset.coupontypeid;
    let onPageData = {
      url: '../detail/index',
      num: 4,
      param: '?coupon_type_id=' + coupon_type_id
    }
    util.jumpPage(onPageData);
  }




})