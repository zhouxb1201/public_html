var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var header = getApp().header;
var time = require('../../../../utils/time.js');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    //
    active: 0,
    //订单状态 0:全部 1:已付款 3:已收货 4:已完成
    order_status: 0,
    page_index: 1,
    goods_list_show: false,
    boole: false,
    orderData: '',
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
      that.bonusOrder().then((res) => {
        if (res.data.code >= 0) {
          let orderData = res.data.data.data;
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
      wx.navigateTo({
        url: '/pages/logon/index',
      })
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
    that.bonusOrder().then((res) => {
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

  //订单状态改变
  onTabsChange(event) {
    const that = this;
    that.setData({
      orderData: []
    })
    switch (event.detail.title)
    {
      case '所有订单':
        that.setData({
          order_status: 0,
        });
        break;
      case '已付款':
        that.setData({
          order_status: 1,
        });
        break;
      case '已收货':
        that.setData({
          order_status: 3,
        });
        break;
      case '已完成':
        that.setData({
          order_status: 4,
        });
        break;
    }
    
    
    that.bonusOrder().then((res) => {
      if (res.data.code >= 0) {
        let bonusData = res.data.data;
        let orderData = '';
        if(bonusData.length == 0){
          orderData = ''
        }else{
          orderData = res.data.data.data;
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
  bonusOrder: function () {
    const that = this;
    wx.showLoading({
      title: '加载中',
    })
    that.setData({
      boole: false
    })
    let postData = {
      'status': that.data.order_status,
      'page_index': that.data.page_index,
      'page_size':10,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    return new Promise((resolve, reject) => {
      wx.request({
        url: api.get_bonus_order,
        data: postData,
        header: header,
        method: 'POST',
        dataType: 'json',
        responseType: 'text',
        success: (res) => {
          wx.hideLoading();
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
    let boole = e.currentTarget.dataset.boole;
    that.setData({
      goods_list_show: good_index,
    })

    if (boole == false) {
      that.setData({
        boole: true
      })
    } else {
      that.setData({
        boole: false
      })
    }
  }
})