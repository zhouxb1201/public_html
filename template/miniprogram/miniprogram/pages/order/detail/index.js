var requestSign = require('../../../utils/requestData.js');
var api = require('../../../utils/api.js').open_api;
var util = require('../../../utils/util.js');
var time = require('../../../utils/time.js');
var re = require('../../../utils/request.js');
var header = getApp().header;
var Base64 = require('../../../utils/base64.js').Base64;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    //订单状态
    orderState: -2,
    //进度条
    stateActive: 0,
    //订单id
    order_id: '',
    steps: [{
        text: '已付款',
      },
      {
        text: '已发货',
      },
      {
        text: '已签收',
      },
    ],
    //订单详情数据
    detailData: '',
    //拼团记录id
    record_id: '',
    //拼团数据
    groupData: '',
    //物流展示
    goods_packet_show: false,
    //消息模板id
    form_id: '',
    //小的导航
    minNavShow: false,
    //订阅消息的模板id
    templateId: [],
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {
    const that = this;
    let order_id = options.orderId;
    that.setData({
      order_id: order_id
    })

    let minnav = options.minnav;
    if (minnav != undefined) {
      that.setData({
        minNavShow: minnav
      })
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
    const that = this;
    that.getOrderInfo();

    //获取订阅模板的模板id
    let type = 2;
    const tId = util.getMpTemplateId(type);
    tId.then((res) => {
      if (res.data.code == 1 && res.data.data.length > 0) {
        let tem_array = [];
        for (let item of res.data.data) {
          if (item.status == 1) {
            tem_array.push(item.template_id)
          }
        }
        that.data.templateId = tem_array
      }
    })
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

  //获取订单详情
  getOrderInfo: function() {
    const that = this;
    wx.showLoading({
      title: '加载中',
    })
    let postData = {
      'order_id': that.data.order_id
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_orderDetail,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        wx.hideLoading()
        if (res.data.code > 0) {
          let orderDetail = res.data.data;
          let order_status = orderDetail.order_status;
          let stateActive = '';
          if (order_status == 1) {
            stateActive = 0
          } else if (order_status == 2) {
            stateActive = 1
          } else if (order_status == 3) {
            stateActive = 2
          };
          if (orderDetail.group_record_id != 0) {
            that.setData({
              record_id: orderDetail.group_record_id
            })
            that.getGroupMemberListForWap();
          }

          let goods_packet_show = '';
          if (orderDetail.goods_packet_list.length == 0 || orderDetail.goods_packet_list[0].shipping_info == null) {
            goods_packet_show = false;
          } else {
            goods_packet_show = true;
          }
          if (orderDetail.consign_time != 0) {
            orderDetail.consign_time = time.js_date_time_second(orderDetail.consign_time);
          }
          if (orderDetail.create_time != 0) {
            orderDetail.create_time = time.js_date_time_second(orderDetail.create_time);
          }
          if (orderDetail.finish_time != 0) {
            orderDetail.finish_time = time.js_date_time_second(orderDetail.finish_time);
          }
          if (orderDetail.pay_time != 0) {
            orderDetail.pay_time = time.js_date_time_second(orderDetail.pay_time);
          }

          that.setData({
            detailData: orderDetail,
            orderState: order_status,
            stateActive: stateActive,
            goods_packet_show: goods_packet_show,
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

  //再次购买
  buyAgain: function() {
    const that = this;
    let goodlist = that.data.detailData.order_goods;
    let cart = [];
    for (var value of goodlist) {
      let goods = {};
      goods['sku_id'] = value.sku_id;
      goods.num = value.num;
      cart.push(goods);
    }
    let postData = {
      'cart': cart
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_buyAgain,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code > 0) {
          wx.showToast({
            title: res.data.message,
          })
          let onPageData = {
            url: '/pages/shopcart/index',
            num: 4,
            param: '',
          }
          util.jumpPage(onPageData);
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



  //跳转到评价
  evaluationFun: function() {
    const that = this;
    let order_detail = that.data.detailData;
    let order_id = order_detail.order_id;
    let is_evaluate = order_detail.is_evaluate;
    let shop = {};
    shop['shop_id'] = order_detail.shop_id;
    shop['shop_name'] = order_detail.shop_name;
    shop['order_id'] = order_detail.order_id;
    let goodArray = [];
    for (let item of order_detail.order_goods) {
      let good = {
        'order_goods_id': item.order_goods_id,
        'img': item.pic_cover,
        'name': item.goods_name,
      };
      goodArray.push(good);
    }
    shop.goods = goodArray;
    let postData = {
      "shop": shop,
    };
    postData = Base64.encode(JSON.stringify(postData));
    let sign = '';
    if (is_evaluate == 0) {
      sign = 'begin'
    } else if (is_evaluate == 1) {
      sign = 'again'
    }
    let onPageData = {
      url: '../evaluate/index',
      num: 4,
      param: '?order_info=' + postData + '&sign=' + sign,
    }
    util.jumpPage(onPageData);

  },

  //单个商品的退款
  returnGood: function(e) {
    const that = this;
    let order_goods_id = e.currentTarget.dataset.ordergoodid;
    let unrefund = e.currentTarget.dataset.unrefund;
    let unrefund_reason = e.currentTarget.dataset.unrefundreason;
    if (unrefund == 1) {
      wx.showModal({
        title: '提示',
        content: unrefund_reason,
        showCancel: false
      })
      return;
    }
    let onPageData = {
      url: '../refund/index',
      num: 4,
      param: '?ordergoodid=' + order_goods_id + '&sign=goods',
    }
    util.jumpPage(onPageData);
  },

  //退款退货
  returnOrder: function(e) {
    const that = this;
    let order_id = e.currentTarget.dataset.orderid;
    let onPageData = {
      url: '../refund/index',
      num: 4,
      param: '?orderid=' + order_id + '&sign=order',
    }
    util.jumpPage(onPageData);
  },

  //售后情况
  customerService: function(e) {
    const that = this;
    let order_goods_id = e.currentTarget.dataset.ordergoodid;
    let onPageData = {
      url: '../customerService/index',
      num: 4,
      param: '?ordergoodid=' + order_goods_id,
    }
    util.jumpPage(onPageData);
  },



  /**
   * 确认收货
   */
  makeSureGood: function() {
    const that = this;
    let postData = {
      'order_id': that.data.order_id
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_orderTakeDelivery,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code > 0) {
          wx.showToast({
            title: res.data.message,
            icon: 'success'
          })
          setTimeout(function() {
            wx.redirectTo({
              url: '../list/index',
            })
          }, 2000)
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

  //预售付尾款
  presellPay: function() {
    const that = this;
    let detailData = that.data.detailData;
    if (detailData.can_presell_pay == 0) {
      wx.showModal({
        title: '提示',
        content: detailData.can_presell_pay_reason,
        showCancel: false,
      })
    } else {
      let postData = {
        'order_id': detailData.order_id,
        'last_money': detailData.final_money
      }
      let datainfo = requestSign.requestSign(postData);
      header.sign = datainfo;
      re.request(api.get_pay_last_money, postData, header).then((res) => {
        if (res.data.code == 0) {
          let onPageData = {
            url: '/pages/payment/pay/index',
            num: 4,
            param: '?orderno=' + res.data.data.out_trade_no,
          }
          util.jumpPage(onPageData);
        }
      })

    }
  },

  //提取码
  pickUpQrcode: function(e) {
    const that = this;
    let verification_code = e.currentTarget.dataset.verificationcode;
    let verification_qrcode = e.currentTarget.dataset.verificationqrcode;
    that.setData({
      verification_code: verification_code,
      verification_qrcode: verification_qrcode,
      boxshow: true,
    })
  },

  //立即付款
  payNow: function(e) {
    const that = this;
    let order_id = e.currentTarget.dataset.orderid;
    let onPageData = {
      url: '/pages/payment/pay/index',
      num: 4,
      param: '?orderid=' + order_id + '&payment=now' + '&hash=orderPay',
    }
    util.jumpPage(onPageData);
  },

  //订阅消息
  subscribeMessage: function(e) {
    const that = this;
    let order_id = e.currentTarget.dataset.orderid;
    if (that.data.templateId.length == 0) {
      that.orderCloseFun(order_id);
    } else {
      //订阅消息模板
      wx.requestSubscribeMessage({
        tmplIds: that.data.templateId,
        success(res) {
          console.log(res);
          that.orderCloseFun(order_id);
          util.postUserMpTemplateInfo(res);
        },
        fail(res) {
          console.log(res);
          that.orderCloseFun(order_id);
        }
      })
    }
  },

  //关闭订单
  orderCloseFun: function(order_id) {
    const that = this;
    wx.showModal({
      title: '提示',
      content: '是否确认关闭订单',
      success(res) {
        if (res.confirm) {
          let postData = {
            'order_id': order_id,
            'form_id': that.data.form_id,
          }
          let datainfo = requestSign.requestSign(postData);
          header.sign = datainfo;
          wx.request({
            url: api.get_orderClose,
            data: postData,
            header: header,
            method: 'POST',
            dataType: 'json',
            responseType: 'text',
            success: (res) => {
              if (res.data.code > 0) {
                wx.showToast({
                  title: res.data.message,
                })
                setTimeout(function() {
                  wx.navigateBack({
                    delta: 1
                  })
                }, 1000)

              } else {
                wx.showToast({
                  title: res.data.message,
                  icon: 'none'
                })
              }
            },
            fail: (res) => {},
          })
        }
      }
    })

  },

  //获取消息模板id
  templateSend: function(e) {
    let that = this;
    let form_id = e.detail.formId;
    that.data.form_id = form_id;
  }


})