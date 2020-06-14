var requestSign = require('../../../utils/requestData.js');
var api = require('../../../utils/api.js').open_api;
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    //订单id
    order_id:'',
    order_no:'',
    goods_packet_list:'',
    index:0,

  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    const that = this;
    let orderId = options.orderId;
    if(orderId != undefined){
      that.setData({
        order_id: orderId
      })
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
    this.getOrderlogistics();
  },

  getOrderlogistics:function(){
    const that = this;
    let postData = {
      'order_id': that.data.order_id
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_orderShippingInfo,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code > 0) {
          let goods_packet_list = res.data.data.goods_packet_list;
          for(var list of goods_packet_list){
            let arr = Object.keys(list.shipping_info);
            if (arr.length != 0){
              for (var item of list.shipping_info.data) {
                list.shipping_info.data = JSON.parse(JSON.stringify(list.shipping_info.data).replace(/context/g, "text"));
                list.shipping_info.data = JSON.parse(JSON.stringify(list.shipping_info.data).replace(/time/g, "desc"));
              }
            }
            
          } 
          that.setData({
            order_no: res.data.data.order_no,
            goods_packet_list: goods_packet_list
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
  packetIndex:function(e){
    const that = this;
    let index = e.currentTarget.dataset.index;
    that.setData({
      index:index
    })
  }

})