var requestSign = require('../../../../../utils/requestData.js');
var util = require('../../../../../utils/util.js');
var api = require('../../../../../utils/api.js').open_api;
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    //购买类型:purchase：采购，output：出货，pickupgoods：自提，retail：零售
    buy_type:'',
    //订单类型
    order_type:'',
    //订单状态 0：待付款，1：待发货，2：已发货，3：已收货，4：已完成，5：已关闭，- 1：售后中
    order_status:'',
    //搜索关键字
    search_text:'',
    page_index:1,
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    const that = this;
    that.setData({
      buy_type: options.buy_type
    })
    that.orderTypeFun();
    that.getChannelOrderDetailList();
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
    const that = this;    
    that.data.page_index = that.data.page_index + 1;
    that.getChannelOrderDetailList();
  },

  //判断是来自哪个订单
  orderTypeFun(){
    const that = this;
    let buy_type = that.data.buy_type;
    if (buy_type == 'purchase') {
      that.setData({
        order_type: 1
      })
      wx.setNavigationBarTitle({
        title: '采购订单',
      })
    } else if (buy_type == 'pickupgoods') {
      that.setData({
        order_type: 2
      })
      wx.setNavigationBarTitle({
        title: '提货订单',
      })
    } else if (buy_type == 'output') {
      that.setData({
        order_type: 3
      })
      wx.setNavigationBarTitle({
        title: '出货订单',
      })
    } else if (buy_type == 'retail') {
      that.setData({
        order_type: 4
      })
      wx.setNavigationBarTitle({
        title: '零售订单',
      })
    }
  },



  //获取订单列表数据
  getChannelOrderDetailList: function () {
    const that = this;
    wx.showLoading({
      title: '加载中',
    })
    let orderlist = '';
    let order_status = that.data.order_status;
    let postData = {
      'buy_type': that.data.buy_type,
      'order_status': that.data.order_status,
      'search_text': that.data.search_text,
      'page_index':that.data.page_index,
      'page_size':20,

    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getChannelOrderDetailList,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        wx.hideLoading();
        if(res.data.code == 0){
          let orderList = '';
          if(that.data.page_index >= 2){
            let oldOrderList = that.data.orderList;
            orderList = oldOrderList.concat(res.data.data.data);            
          }else{
            orderList = res.data.data.data
          }
          that.setData({
            orderList: orderList
          })
        }else{
          wx.showToast({
            title: res.data.message,
            icon:'none',
          })
        }
      },
      fail: (res) => { },
    })

  },

  

  //订单的不同状态
  changeStateFun: function (event) {
    const that = this;
    let index = event.detail.index;
    if(that.data.order_type == 1){
      switch (index) {
        case 1:
          that.data.order_status = 0; //待付款
          break;        
        case 2:
          that.data.order_status = 4;//已完成 
          break;        
        default:
          that.data.order_status = '' //全部
          break;
      }
    } else if (that.data.order_type == 2 || that.data.order_type == 4){
      switch (index) {
        case 1:
          that.data.order_status = 0; //待付款
          break;
        case 2:
          that.data.order_status = 1; //待发货
          break;
        case 3:
          that.data.order_status = 2; //已发货
          break;
        case 4:
          that.data.order_status = 3;//已收货 
          break;
        case 5:
          that.data.order_status = 4;//已完成 
          break;
        case 6:
          that.data.order_status = -1;//售后中
          break;       
        default:
          that.data.order_status = '' //全部
          break;
      }
    } else if (that.data.order_type == 3 ){
      switch (index) {
        case 1:
          that.data.order_status = 0; //待付款
          break;
        case 2:
          that.data.order_status = 2;//已发货 
          break;
        default:
          that.data.order_status = '' //全部
          break;
      }
    }
    
    that.data.page_index = 1;
    that.getChannelOrderDetailList();
  },

  //立即付款
  payNowFun: function (orderno){
    const that = this;
    let onPageData = {
      url: '/pages/payment/pay/index',
      num: 4,
      param: '?orderno=' + orderno + '&payment=now' + '&buy_type=' + that.data.buy_type,
    }
    util.jumpPage(onPageData);
  },

  //微商中心订单列表（详情）支付信息
  channelOrderPay: function (order_id) {
    const that = this;
    let postData = {
      order_id: order_id,
    };
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    wx.request({
      url: api.get_channelOrderPay,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 1) {
          that.payNowFun(res.data.data.out_trade_no);
        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
        }
      }
    })
  },

  //立即支付
  payOrderNowFun:function(e){
    const that = this;
    let order_id = e.currentTarget.dataset.orderid;
    if (that.data.buy_type == 'purchase'){
      that.channelOrderPay(order_id);
    }
  },

  //关闭订单
  channelOrderClose:function(e){
    const that = this;
    let order_id = e.currentTarget.dataset.orderid;
    let postData = {
      order_id: order_id,
      order_type:that.data.buy_type,
    };
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    wx.request({
      url: api.get_channelOrderClose,
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

  //确认收货
  orderTakeDelivery:function(e){
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

  //查询订单
  searchChannelOrderFun:function(e){
    const that = this;
    that.data.search_text = e.detail.value;
    that.data.orderList = [];
    that.getChannelOrderDetailList();

  }




})