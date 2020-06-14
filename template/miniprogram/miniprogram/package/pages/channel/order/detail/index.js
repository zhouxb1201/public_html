var requestSign = require('../../../../../utils/requestData.js');
var util = require('../../../../../utils/util.js');
var api = require('../../../../../utils/api.js').open_api;
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    order_id:'',
    //订单类型：采购(需要支付) ：purchase 出货：output 自提（需要支付）：pickupgoods 零售：retail
    order_type:'',
    // 是否有物流
    goods_packet_show:false,
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    const that =this;
    that.data.order_id = options.orderId;
    that.setData({
      order_type: options.orderType
    })
    that.getPurchaseOrderDetail();

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

  

  //渠道商订单详情
  getPurchaseOrderDetail:function(){
    const that = this;
    let postData = {
      'order_id': that.data.order_id,
      'order_type': that.data.order_type,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getPurchaseOrderDetail,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          let goods_packet_show = '';
          let detailData = res.data.data;

          if (that.data.order_type == 'pickupgoods' || that.data.order_type == 'retail'){
            if (detailData.goods_packet_list.length == 0 || detailData.goods_packet_list[0].shipping_info == null) {
              goods_packet_show = false;
            } else {
              goods_packet_show = true;
            }
            that.setData({
              goods_packet_show: goods_packet_show
            })
          }
          
          that.setData({
            detailData: detailData,            
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


  //确认收货
  orderTakeDelivery: function (e) {
    const that = this;
    let order_id = e.currentTarget.dataset.orderid;
    let postData = {
      order_id: order_id,
    };
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    wx.request({
      url: api.get_orderTakeDelivery,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 1) {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
          that.getChannelOrderDetailList();
        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
        }
      }
    })
  },
  
})