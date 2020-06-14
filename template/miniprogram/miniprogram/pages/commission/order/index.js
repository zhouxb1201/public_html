var requestSign = require('../../../utils/requestData.js');
var api = require('../../../utils/api.js').open_api;
var re = require('../../../utils/request.js');
var util = require('../../../utils/util.js');
var header = getApp().header;
var time = require('../../../utils/time.js');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    pageShow: false,
    //
    active: 0,
    //订单状态
    order_status: '',
    page_index: 1,
    goods_list_show: '',
    orderData: '',
    url: getApp().publicUrl,

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
    const that = this;
    const value = wx.getStorageSync('user_token');
    if(value){
      that.distributionCenter();
      that.distributionOrder().then((res) => {
        if (res.data.code >= 0) {
          let orderData = res.data.data.data
          for (let value of orderData) {
            value.create_time = time.js_date_time(value.create_time);
          }
          that.setData({
            orderData: orderData
          })

        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
        }
      });
    }else{
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
      that.distributionCenter();
      that.distributionOrder().then((res) => {
        if (res.data.code >= 0) {
          let orderData = res.data.data.data
          for (let value of orderData) {
            value.create_time = time.js_date_time(value.create_time);
          }
          that.setData({
            orderData: orderData
          })

        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
        }
      });
    }
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
    that.distributionOrder().then((res) => {
      if (res.data.code >= 0) {
        let orderData = that.data.orderData;
        let orderPageData = res.data.data.data;
        for (let value of orderPageData) {
          value.create_time = time.js_date_time(value.create_time);
        }
        orderData = orderData.concat(orderPageData);
        that.setData({
          orderData: orderData
        })

      } else {
        wx.showToast({
          title: res.data.message,
          icon: 'none'
        })
      }
    });
  },

  //分销中心，判断是否申请成为分销商
  distributionCenter: function () {
    const that = this;
    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    wx.showLoading({
      title: '加载中',
    })
    re.request(api.get_distributionCenter, postData, header).then((res) => {
      if (res.data.code >= 0) {
        wx.hideLoading();
        let isdistributor = res.data.data.isdistributor
        if (isdistributor != 2) {
          wx.showModal({
            title: '提示',
            content: '你还不是分销商，请先申请！',
            success(res) {
              if (res.confirm) {
                let onPageData = {
                  url: '../apply/index',
                  num: 4,
                  param: '?isdistributor=' + isdistributor,
                }
                util.jumpPage(onPageData);
              } else if (res.cancel) {
                wx.navigateBack({
                  delta: 1,
                })
              }
            }
          })
        } else {
          that.setData({
            pageShow: true
          })
        }
      } else {
        wx.showToast({
          title: res.data.message,
          icon: 'none'
        })
      }
    })
  },

  //订单状态改变
  onTabsChange(event) {
    const that = this;
    if (event.detail.title == '已付款') {
      that.data.order_status = 1;
    } else if (event.detail.title == '已发货') {
      that.data.order_status = 2;
    } else if (event.detail.title == '已收货') {
      that.data.order_status = 3;
    } else if (event.detail.title == '已完成') {
      that.data.order_status = 4;
    } else if (event.detail.title == '已关闭') {
      that.data.order_status = 5;
    } else if (event.detail.title == '未支付') {
      that.data.order_status = -1;
    } else {
      that.data.order_status = '';
    }

    that.distributionOrder().then((res) => {
      if (res.data.code >= 0) {
        let orderData = res.data.data.data
        for (let value of orderData) {
          value.create_time = time.js_date_time(value.create_time);
          value.good_show = false;
        }
        that.setData({
          orderData: orderData,
          page_index: 1,
        })

      } else {
        wx.showToast({
          title: res.data.message,
          icon: 'none'
        })
      }
    });
  },


  //获取分销订单数据  
  distributionOrder: function () {
    const that = this;
    that.setData({
      boole: false
    })
    let postData = {
      'order_status': that.data.order_status,
      'page_index': that.data.page_index,
      'page_size': 10,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    return new Promise((resolve, reject) => {
      wx.request({
        url: api.get_distributionOrder,
        data: postData,
        header: header,
        method: 'POST',
        dataType: 'json',
        responseType: 'text',
        success: (res) => {
          resolve(res);
        },
        fail: (res) => { },
      })
    })

  },

  //展开订单商品
  goodChangeShow: function (e) {
    const that = this;
    let good_index = e.currentTarget.dataset.goodindex;
    let good_show = e.currentTarget.dataset.goodshow;
    let orderData = that.data.orderData;

    that.setData({
      goods_list_show: good_index,
    })
    for (let i = 0; i < orderData.length; i++) {
      orderData[i].good_show = false;
      if (i == good_index) {
        orderData[i].good_show = true
      }
    }
    that.setData({
      orderData: orderData
    })




  },


})