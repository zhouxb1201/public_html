var requestSign = require('../../../utils/requestData.js');
var api = require('../../../utils/api.js').open_api;
var util = require('../../../utils/util.js');
var re = require('../../../utils/request.js');
var header = getApp().header;
var Base64 = require('../../../utils/base64.js').Base64;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    active: 0,
    page_index: 1,
    //订单数据
    orderList: '',
    order_status: '',
    search_text: '',
    pageShow: false,
    no_more: false,
    //消息模板id
    form_id:'',
    //订阅消息的模板id
    templateId: [],
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {
    if (options.status != undefined) {
      let status = options.status;
      this.setData({
        order_status: status
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
    let status = that.data.order_status;
    if (status == '0') {
      that.setData({
        active: 1
      })
    } else if (status == '1') {
      that.setData({
        active: 2
      })
    } else if (status == '2') {
      that.setData({
        active: 3
      })
    } else if (status == '-2') {
      that.setData({
        active: 4
      })
    } else if (status == '-1') {
      that.setData({
        active: 5
      })
    }
    that.data.page_index = 1;
    that.getOrderList().then((res) => {
      if (res.data.code > 0) {
        that.setData({
          orderList: res.data.data.order_list,
          pageShow: true,
        })
      } else {
        wx.showToast({
          title: res.data.message,
          icon: 'none'
        })
      }
    });

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

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function() {

  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function() {
    const that = this;
    that.data.page_index = that.data.page_index + 1;
    that.getOrderList().then((res) => {
      if (res.data.code > 0) {
        let orderlist = that.data.orderList;
        orderlist = orderlist.concat(res.data.data.order_list);
        if (res.data.data.order_list.length == 0) {
          that.setData({
            no_more: true
          })
        } else {
          that.setData({
            orderList: orderlist
          })
        }
      } else {
        wx.showToast({
          title: res.data.message,
          icon: 'none'
        })
      }
    });

  },


  //获取订单列表数据
  getOrderList: function() {
    const that = this;
    wx.showLoading({
      title: '加载中',
    })
    let orderlist = '';
    let order_status = that.data.order_status;
    let postData = {
      'order_status': order_status,
      'page_index': that.data.page_index,
      'search_text': that.data.search_text

    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    return new Promise((resolve, reject) => {
      wx.request({
        url: api.get_orderlist,
        data: postData,
        header: header,
        method: 'POST',
        dataType: 'json',
        responseType: 'text',
        success: (res) => {
          wx.hideLoading();
          resolve(res);
        },
        fail: (res) => {},
      })
    })

  },
  //跳转到商品页
  ongoodpage: function(e) {
    let goodsId = e.currentTarget.dataset.goodsid;
    let orderType = e.currentTarget.dataset.ordertype;
    let onPageData = {};
    if (goodsId && orderType == 10) {
      onPageData = {
        url: '/package/pages/integral/goods/detail/detail',
        num: 4,
        param: '?goodsId=' + goodsId,
      }
    } else {
      onPageData = {
        url: '../../goods/detail/index',
        num: 4,
        param: '?goodsId=' + goodsId,
      }
    }
    util.jumpPage(onPageData);
  },
  //订单的不同状态
  changeStateFun: function(event) {
    const that = this;
    let index = event.detail.index;
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
        // case 4:
        //   that.data.order_status = 3;//已收货 
        //   break;
      case 4:
        that.data.order_status = -2; //待评价
        break;
      case 5:
        that.data.order_status = -1; //售后中
        break;
      default:
        that.data.order_status = '' //全部
        break;
    }
    that.data.page_index = 1;
    that.getOrderList().then((res) => {
      that.setData({
        orderList: res.data.data.order_list
      })
    });
  },
  //顶部查询订单
  searchOrderFun: function(e) {
    const that = this;
    that.data.search_text = e.detail.value;
    that.getOrderList().then((res) => {
      if (res.data.code > 0) {
        that.setData({
          orderList: res.data.data.order_list
        })
      } else {
        wx.showToast({
          title: res.data.message,
          icon: 'none'
        })
      }
    });
  },
  //删除订单
  deleteOrder: function(e) {
    const that = this;
    let orderId = e.currentTarget.dataset.orderid;
    let postData = {
      'order_id': orderId
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_deleteOrder,
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
          that.getOrderList().then((res) => {
            that.setData({
              orderList: res.data.data.order_list
            })
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
  buyAgain: function(e) {
    const that = this;
    let goodlist = e.currentTarget.dataset.goodlist;
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

  //订阅消息
  subscribeMessage: function (e) {
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
  orderCloseFun: function (order_id) {
    const that = this;       
    wx.showModal({
      title: '提示',
      content: '是否确认关闭订单',
      success(res) {
        if (res.confirm) {
          let postData = {
            'order_id': order_id,
            'form_id': that.data.form_id
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
                  that.data.page_index = 1;
                  that.getOrderList().then((res) => {
                    that.setData({
                      orderList: res.data.data.order_list
                    })
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



  //确认收货
  makeSureGood: function(e) {
    const that = this;
    let order_id = e.currentTarget.dataset.orderid;
    let postData = {
      'order_id': order_id
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
          })
          that.data.page_index = 1;
          that.getOrderList().then((res) => {
            that.setData({
              orderList: res.data.data.order_list
            })
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

  //立即付款
  payNowFun: function(e) {
    const that = this;
    let orderno = e.currentTarget.dataset.orderno;
    let onPageData = {
      url: '/pages/payment/pay/index',
      num: 4,
      param: '?orderno=' + orderno + '&payment=now',
    }
    util.jumpPage(onPageData);
  },

  //跳转到评价
  evaluationFun: function(e) {
    const that = this;
    let order_id = e.currentTarget.dataset.orderid;
    let is_evaluate = e.currentTarget.dataset.isevaluate;
    let shop = {};
    for (let value of that.data.orderList) {
      if (order_id == value.order_id) {
        shop['shop_id'] = value.shop_id;
        shop['shop_name'] = value.shop_name;
        shop['order_id'] = value.order_id;
        let goodArray = [];
        for (let item of value.order_item_list) {
          let good = {
            'order_goods_id': item.order_goods_id,
            'img': item.pic_cover,
            'name': item.goods_name,
          };
          goodArray.push(good);
        }
        shop.goods = goodArray;
        break;
      }
    }

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
  onrefundPage: function(e) {
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

  //整个订单退款
  onOrderReturn: function(e) {
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
    let type = e.currentTarget.dataset.type;
    let order_goods_id = '';
    let order_id = '';
    let param = '';
    if (type == 'goods') {
      order_goods_id = e.currentTarget.dataset.ordergoodid;
      param = '?ordergoodid=' + order_goods_id
    } else if (type == 'order') {
      order_id = e.currentTarget.dataset.orderid;
      param = '?orderid=' + order_id
    }

    let onPageData = {
      url: '../customerService/index',
      num: 4,
      param: param,
    }
    util.jumpPage(onPageData);
  },

  //使用消费卡
  useCard: function() {
    let onPageData = {
      url: '/package/pages/consumercard/list/index',
      num: 4,
      param: '',
    }
    util.jumpPage(onPageData);
  },

  //预售付尾款
  presellPay: function(e) {
    const that = this;
    let item = e.currentTarget.dataset.item;
    if (item.can_presell_pay == 0) {
      wx.showModal({        
        content: item.can_presell_pay_reason,
        showCancel: false,
      })
    } else {
      let total_money = 0
      for (let value of item.order_item_list) {
        total_money = parseFloat(total_money + value.price).toFixed(2);
      }
      let last_money = parseFloat(total_money - item.order_money).toFixed(2);

      let postData = {
        'order_id': item.order_id,
        'last_money': last_money
      }
      let datainfo = requestSign.requestSign(postData);
      header.sign = datainfo;
      re.request(api.get_pay_last_money, postData, header).then((res) => {
        if (res.data.code == 0) {
          let onPageData = {
            url: '/pages/payment/pay/index',
            num: 4,
            param: '?orderno=' + res.data.data.out_trade_no + '&payment=now',
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

  //获取消息模板id
  templateSend: function (e) {
    let that = this;
    let form_id = e.detail.formId;
    console.log(form_id);
    that.data.form_id = form_id;
  }

})