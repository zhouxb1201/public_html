var requestSign = require('../../../utils/requestData.js');
var api = require('../../../utils/api.js').open_api;
var util = require('../../../utils/util.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    //外部交易号
    out_trade_no: '',
    //拼团记录id
    record_id: '',
    //拼团数据
    groupData: '',
    resultData: {},
    //从积分商城订单过来 0 => 不是 1 => 是
    is_integral_order: 0,
    //各种订单支付成功类型
    success_type: 0,
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {
    const that = this;
    if (options.out_trade_no) {
      let out_trade_no = options.out_trade_no;
      that.data.out_trade_no = out_trade_no;
      that.get_pay_result_info();
    }


  },

  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function() {

  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function() {
    
  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function() {

  },

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function() {

  },

  //支付成功类型
  paySuccessType: function() {
    const that = this;
    let success_type = that.data.success_type
    if (success_type == 'groud_order') {
      //从拼团过来的订单支付成功
      that.setData({
        pay_success_type: 1
      })
    } else if (success_type == 'card_order') {
      //从消费卡过来的订单支付成功
      that.setData({
        pay_success_type: 2
      })
    } else if (success_type == 'integral_order') {
      //从积分商城过来的订单支付成功
      that.setData({
        pay_success_type: 3
      })
    } else if (success_type == 'channel_order') {
      //从微商中心过来的采购订单支付成功
      that.setData({
        pay_success_type: 4
      })
    } else if (success_type == 'microshop_order') {
      //从微店过来的支付成功
      that.setData({
        pay_success_type: 5
      })
    } else if (success_type == "paygift"){
      //从支付
      that.setData({
        pay_success_type: 6
      })
    }else {
      that.setData({
        pay_success_type: 0
      })
    }

  },

  //跳转到商品列表页
  onOrderDetail: function(e) {
    const that = this;
    let order_id = e.currentTarget.dataset.orderid;
    if (this.data.pay_success_type == 4){
      let channel_type = e.currentTarget.dataset.channeltype;
      that.onChannelOrderListPage(channel_type);
    }else{
      that.onOrderList(order_id)
    }
    
  },

  //跳转到平台的订单列表
  onOrderList: function (order_id){
    if (order_id == 0 || order_id == null || order_id == '') {
      let onPageData = {
        url: '/pages/order/list/index',
        num: 4,
        param: '',
      }
      util.jumpPage(onPageData);
    } else {
      let onPageData = {
        url: '/pages/order/detail/index',
        num: 4,
        param: '?orderId=' + order_id,
      }
      util.jumpPage(onPageData);
    }
  },

  //跳转到渠道商的订单列表
  onChannelOrderListPage: function (channel_type){
    let onPageData = {
      url: '/package/pages/channel/order/list/index',
      num: 4,
      param: '?buy_type=' + channel_type,
    }
    util.jumpPage(onPageData);
  },

  //跳转到首页
  onIndexPage: function() {        
    let onPageData = {
      url: '/pages/index/index',
      num: 4,
      param: '',
    }
    util.jumpPage(onPageData);
  },
  //跳转到积分商城首页
  onintegralPage: function() {
    let onPageData = {
      url: '/package/pages/integral/index/index',
      num: 2,
      param: '',
    }
    util.jumpPage(onPageData);
  },
  //跳转到拼团详情
  onGroupDetailPage: function() {
    const that = this;
    let onPageData = {
      url: '/package/pages/assemble/detail/index',
      num: 4,
      param: '?record_id=' + that.data.record_id,
    }
    util.jumpPage(onPageData);
  },
  //获取订单结果
  get_pay_result_info: function() {
    const that = this;
    let postData = {
      'out_trade_no': that.data.out_trade_no
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_get_pay_result_info,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          that.setData({
            resultData: res.data.data,

          })

          //积分商城
          if (res.data.data.is_integral_order == 1 && res.data.data.is_integral_order != null) {
            that.setData({
              success_type: 'integral_order'
            })
          }

          //拼团
          if (res.data.data.group_record_id != 0 && res.data.data.group_record_id != null) {
            that.setData({
              record_id: res.data.data.group_record_id,
              success_type: 'groud_order',
            })
            that.getGroupMemberListForWap();
          }

          //消费卡
          if (res.data.data.card_ids != '' && res.data.data.card_ids != null) {
            that.setData({
              success_type: 'card_order',
            })
          }

          //微商中心
          if (res.data.data.is_channel != '' && res.data.data.is_channel != null) {
            that.setData({
              success_type: 'channel_order',
            })
          }

          //微店
          if (res.data.data.order_type == 2 || res.data.data.order_type == 3 || res.data.data.order_type == 4) {
            if (wx.getStorageSync("shopkeeper_id")) {
              wx.removeStorageSync("shopkeeper_id");
            }
            that.setData({
              success_type: 'microshop_order',
            })
          }

          //支付有礼
          if (res.data.data.pay_gift_status && res.data.data.pay_gift_status == 1){
            that.setData({
              success_type: 'paygift',
            })
          }
          that.paySuccessType();


        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
        }
      },
      fail: (res) => {},
    })
  },

  //获取拼团数据
  getGroupMemberListForWap: function() {
    const that = this;
    let postData = {
      'record_id': that.data.record_id
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getGroupMemberListForWap,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          that.setData({
            groupData: res.data.data
          })
        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
        }
      },
      fail: (res) => {},
    })
  },

  //跳转到消费卡页面
  onCardPage: function() {
    wx.navigateTo({
      url: '/package/pages/consumercard/list/index',
    })
  },
  //跳转到微商中心页面
  onchannelPage: function() {
    let onPageData = {
      url: '/package/pages/channel/centre/index',
      num: 2,
      param: '',
    }
    util.jumpPage(onPageData);
  },
  //跳转到微店中心页面
  onMicroshop: function() {
    let onPageData = {
      url: '/package/pages/microshop/centre/centre',
      num: 2,
      param: '',
    }
    util.jumpPage(onPageData);
  },
  //跳转到奖品页面
  onGiftPage:function(){
    wx.navigateTo({
      url: '/package/pages/prize/list/list',
    })
  }


})