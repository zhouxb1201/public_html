var requestSign = require('../../utils/requestData.js');
var api = require('../../utils/api.js').open_api;
var util = require('../../utils/util.js');
var time = require('../../utils/time.js');
var header = getApp().header;
var Base64 = require('../../utils/base64.js').Base64;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    boxShow: false,
    type_list: [{
        name: '1',
        value: '快递配送',
        checked: true
      },
      {
        name: '2',
        value: '线下自提',
        checked: false
      },
    ],
    orderTag: '',
    //sku信息, 立即购买传参
    sku_list: '',
    //购物车id, 购物车传参
    cart_id_list: '',
    //参加预售则填写活动ID，没有则填写0
    presell_id: 0,
    //订单数据
    orderData: '',
    //订单总价
    totalPrice: 0,
    //地址类别弹框显示
    addressListShow: false,
    //地址列表  
    address_list: '',
    //优惠券
    couponData: '',
    //优惠券框显示
    couponShow: false,
    //已选的优惠券id
    coupon_id: '',
    //已选优惠券的名字
    coupon_name: '',
    //店铺名
    shopName: '',
    //店铺id
    shop_id: '',
    addressShow: true,
    //地址id
    addressid: '',
    //参与哪个团购id
    recordid: '',
    //团购活动id
    groupid: '',
    //立即购买标识
    skillGroundSign: false,
    //购买数量   
    num: 1,
    //Textarea层级太高，在有弹框出现时先隐藏
    isTextareaShow: true,
    //总的优惠金额
    promotion_amount: 0,

    // 预售总金额
    presell_allmoney: '',
    //预售最大购买量
    presell_max_buy: 0,
    //预售支付开始时间
    pay_start_time: '',

    //满减送的规则id 可为空 或者不传
    rule_id: '',

    //自定义表单数据
    customform: '',
    //积分选择状态
    pointStatus: false,
    //积分开启：0未开启，1开启
    is_point: '',
    //积分可兑换金额
    total_deduction_money: '0.00',
    //是否勾选积分0未，1勾
    is_deduction: '',
    //配送方式 1->快递配送，2->门店自提
    shipping_type: 1,
    coupon_id_list: [],

    shipping_show: false,
    shopStoreShow: false,
    store_id: '',
    store_name: '',
    //计时计次标识0-普通商品，1-该商品为计时计次商品
    timing_tag: 0,

    shopkeeper_id: '',
    //线下自提数据
    underLineParams: '',
    cart_from: '',
    shippingTipText: '',
    //选中的数据
    currentData: [],
    stepper_disabled: false,
    //买家留言
    leave_message: '',
    sum_ing: 1
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {
    const that = this
    let params = JSON.parse(Base64.decode(options.params));
    console.log(params)
    let orderTag = params.order_tag;
    //购物车过来的数据
    if (orderTag == "cart") {
      that.data.cart_id_list = params.cart_id_list;
      that.data.sku_list = params.sku_list;
      if (params.cart_from != undefined) {
        that.data.cart_from = params.cart_from;
      }

    } else if (orderTag == "buy_now") {
      //商品详情过来的数据
      var sku_list = params.sku_list;
      that.setData({
        skillGroundSign: true
      })
      //判断是否有group_id这个属性
      if (params.hasOwnProperty('group_id')) {
        that.setData({
          groupid: params.group_id
        })
      }
      //判断是否有record_id这个属性
      if (params.hasOwnProperty('record_id')) {
        that.setData({
          recordid: params.record_id
        })
      }
      //判断是否有presell_id这个预售属性
      if (params.hasOwnProperty('presell_id')) {
        that.setData({
          presell_id: params.presell_id
        })
      }

      //判断是否有shipping_type这个线下自提id
      if (params.hasOwnProperty('shipping_type')) {
        let type_list = that.data.type_list;
        let underLineParams = params;
        for (let item of type_list) {
          item.checked = false;
          if (item.name == params.shipping_type) {
            item.checked = true
          }
        }
        // if (sku_list[0].store_id != '') {
        //   that.setData({
        //     store_name: sku_list[0].store_name,
        //     store_id: sku_list[0].store_id,
        //   })
        // }
        that.setData({
          shipping_type: params.shipping_type,
          underLineParams: underLineParams,

          type_list: type_list
        })
      }

      //判断是否有shopkeeper_id这个微店id
      if (params.hasOwnProperty('shopkeeper_id')) {
        that.setData({
          shopkeeper_id: params.shopkeeper_id
        })
      }

      that.data.num = parseFloat(sku_list[0].num);
      that.data.sku_list = sku_list;
    }

    that.setData({
      orderTag: orderTag,
    })

    wx.showLoading({
      title: '加载中',
    })
    that.getOrderInfo();


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
    that.receiverAddressList();
  },



  //改变数量
  onChange: function(event) {
    const that = this;
    console.log(event.detail)
    that.setData({
      num: parseFloat(event.detail),
      stepper_disabled: true,
    })
    that.getOrderInfo();
  },

  //请求订单数据  
  getOrderInfo: function() {
    const that = this;
    let orderTag = that.data.orderTag;

    if (orderTag == 'cart') {
      var postData = {
        order_tag: 'cart',
        cart_id_list: that.data.cart_id_list,
        sku_list: that.data.sku_list
      }

      if (that.data.addressid != '') {
        postData['address_id'] = that.data.addressid;
      }
      if (that.data.cart_from != '') {
        postData['cart_from'] = that.data.cart_from;
      }
    } else if (orderTag == 'buy_now') {
      that.data.sku_list[0].num = that.data.num;
      var postData = {
        order_tag: 'buy_now',
        sku_list: that.data.sku_list,
      }

      // 预售开启
      if (that.data.presell_id != 0) {
        postData['presell_id'] = that.data.presell_id;
      } else {
        //团购
        if (that.data.groupid != '') {
          postData['group_id'] = that.data.groupid;
          postData['record_id'] = that.data.recordid;
        }
      }

      if (that.data.addressid != '' && that.data.addressid != undefined) {
        postData['address_id'] = that.data.addressid;
      }
    }

    if (that.data.is_deduction != '') {
      postData['is_deduction'] = that.data.is_deduction
    }

    if (that.data.shipping_type == 2) {
      postData['shipping_type'] = that.data.shipping_type
    }

    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_orderInfo,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        wx.hideLoading();
        if (res.data.code < 0) {
          wx.showModal({
            title: '提示',
            content: res.data.message,
            showCancel: false,
          })
        } else {
          let order = res.data.data;
          let totalprice = 0;
          let full_cut = '';
          for (var i = 0; i < order.shop.length; i++) {

            totalprice += order.shop[i].total_amount;
            // 判断满减是否有数据
            if (Object.keys(order.shop[i].full_cut).length == 0) {
              order.shop[i].full_cut_state = 'false';
            } else {
              order.shop[i].full_cut_state = 'true';
            }
            //返回的数量为字符串类型，转为浮点型
            for (let item of order.shop[i].goods_list) {
              item.num = parseFloat(item.num);
              //当预售开启时给good_list添加presell_id
              if (that.data.presell_id != 0) {
                item.presell_id = that.data.presell_id;
              }
            }



            //满减的时间戳转换
            if (order.shop[i].coupon_list.length > 0) {
              for (var n = 0; n < order.shop[i].coupon_list.length; n++) {
                order.shop[i].coupon_list[n].end_time = time.js_date_time(order.shop[i].coupon_list[n].end_time); //时间戳转日期
                order.shop[i].coupon_list[n].start_time = time.js_date_time(order.shop[i].coupon_list[n].start_time);
                order.shop[i].coupon_list[n].discount = parseInt(order.shop[i].coupon_list[n].discount);
              }
            }
          }

          //判断是否有团购
          if (order.group_id != '' && order.group_id != undefined && order.group_id != null) {
            that.setData({
              recordid: order.record_id,
              groupid: order.group_id,
            })
          }

          //预售
          if (that.data.presell_id != 0) {
            let pay_start_time = time.js_date_time(order.shop[0].presell_info.pay_start_time); //时间戳转日期
            let presell_allmoney = order.shop[0].presell_info.allmoney;
            let tail_money = parseFloat(presell_allmoney * that.data.num - totalprice).toFixed(2); //尾款
            let presell_max_buy = 0;
            if (order.shop[0].presell_info.maxbuy != 0) {
              presell_max_buy = order.shop[0].presell_info.maxbuy;
            } else {
              presell_max_buy = order.shop[0].presell_info.presellnum
            }
            that.setData({
              presell_allmoney: presell_allmoney,
              presell_max_buy: presell_max_buy,
              pay_start_time: pay_start_time,
              tail_money: tail_money,
            })
          }

          let addressShow = (JSON.stringify(order.address) == "{}");
          let addressid = '';
          if (addressShow == false) {
            addressid = order.address.address_id
          }



          //o2o
          that.shippingType(res.data.data);

          if (orderTag == 'buy_now') {
            //goods_type等于0计时计次商品
            if (order.shop[0].goods_list[0].goods_type == 0) {
              that.setData({
                timing_tag: 1,
                shipping_type: 2,
                has_store: 2,
                userAddressShow: false,
              })
            }
            //goods_type等于3虚拟商品
            if (order.shop[0].goods_list[0].goods_type == 3) {
              that.setData({
                userAddressShow: false,
              })
            }
          }

          that.setData({
            orderData: order,
            totalPrice: parseFloat(totalprice).toFixed(2),
            addressShow: addressShow,
            addressid: addressid,
            addressListShow: false,
            promotion_amount: order.promotion_amount,
            is_point: order.is_point,
            boxShow: true,
            stepper_disabled: false,
          })

          //自定义表单数据
          if (that.data.customform == '') {
            if (res.data.data.customform.length != 0) {
              let customform = res.data.data.customform;
              that.setData({
                customform: customform,
              })
              that.selectComponent('#getForm').customformDataSet();
            }

          }

        }


      },
      fail: (res) => {},
    })
  },

  //o2o配送方式
  shippingType: function(order) {
    const that = this;
    let config_store = getApp().globalData.config.addons.store;
    //1-o2o开启，0未开启
    let has_store;
    //判断是否有o2o应用
    if (config_store == 1) {
      has_store = order.has_store
    } else {
      has_store = 0
    }

    let shipping_show = false;
    let text = ''
    //o2o开启
    if (has_store == 1) {
      //快递配送
      if (that.data.shipping_type == 1) {
        that.setData({
          userAddressShow: true,
        })
      } else {
        //线下自提
        for (let value of order.shop) {
          if (value.has_store != '1') {
            shipping_show = true;
            text = '由于部分商家不支持线下自提，请为不支持线下自提的订单商品选择收货地址'
          }
        }
        //有部分店铺没有开启线下自提，所以显示提示框和地址框
        if (shipping_show == true) {
          that.setData({
            userAddressShow: true,
          })
        } else {
          that.setData({
            userAddressShow: false,
          })
        }
      }
    } else if (has_store == 0) { //2未开启
      that.setData({
        userAddressShow: true,
      })
    }

    that.setData({
      has_store: has_store,
      shipping_show: shipping_show,
      shippingTipText: text,
    })
  },

  //优惠券数据
  couponPopup: function(e) {
    const that = this;
    let shopId = e.currentTarget.dataset.shopid;
    let goodslist = e.currentTarget.dataset.goodslist;
    let shopData = that.data.orderData.shop;
    let couponData = '';
    let shopName = '';
    for (var i = 0; i < shopData.length; i++) {
      if (shopId == shopData[i].shop_id) {
        let coupon_list = shopData[i].coupon_list;
        shopName = shopData[i].shop_name;
        couponData = coupon_list;
        break;
      }
    }

    let shop_sku_list = []
    for (let value of goodslist) {
      shop_sku_list.push(value.sku_id);
    }

    that.setData({
      couponData: couponData,
      shopName: shopName,
      shop_id: shopId,
      shop_sku_list: shop_sku_list
    })
    that.couponShow();
  },

  //优惠券弹出层开启
  couponShow: function() {
    const that = this;
    that.setData({
      couponShow: true,
      isTextareaShow: false,
    })
  },

  //优惠券弹出层关闭
  couponOnclose: function() {
    const that = this;
    that.setData({
      couponShow: false,
      isTextareaShow: true,
    })
  },

  //使用优惠券
  useCoupon: function(e) {
    const that = this;
    let coupon_id = e.currentTarget.dataset.couponid;
    let couponIndex = e.currentTarget.dataset.index;
    let couponData = that.data.couponData;
    let orderData = that.data.orderData;
    let shop_sku_list = that.data.shop_sku_list;
    let sku_list = that.data.sku_list;
    for (let value of shop_sku_list) {
      for (let item of sku_list) {
        if (value == item.sku_id) {
          item.coupon_id = coupon_id
        }
      }
    }

    that.data.sku_list = sku_list

    let coupon_id_Obj = {
      'shop_id': that.data.shop_id,
      'coupon_id': coupon_id
    }
    that.data.coupon_id_list.push(coupon_id_Obj)


    for (let value of couponData) {
      value.using = false
    }
    couponData[couponIndex].using = true;


    that.setData({
      couponData: couponData,
      coupon_name: couponData[couponIndex].coupon_name
    })
    that.getOrderInfo();
    that.couponOnclose();
  },



  //打开地址列表弹框
  addressListShow: function() {
    const that = this;
    that.setData({
      addressListShow: true,
      isTextareaShow: false,
    })
    that.receiverAddressList();

  },

  //关闭地址列表弹框
  addressListClose: function() {
    const that = this;
    that.setData({
      addressListShow: false,
      isTextareaShow: true,
    })
  },


  //收货地址列表
  receiverAddressList: function() {
    const that = this;
    var postData = {
      page_index: 1,
      page_size: 20,
    };
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_receiverAddressList,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          that.setData({
            address_list: res.data.data.address_list
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

  //默认地址选择
  addressSelect: function(e) {
    const that = this;
    let address_id = e.currentTarget.dataset.addressid;
    that.data.addressid = address_id
    that.getOrderInfo();
    that.addressListClose();
  },

  onAddressPage: function() {
    const that = this;
    let onPageData = {
      url: '/package/pages/address/addAddress/index',
      num: 4,
      param: '',
    }
    util.jumpPage(onPageData);
  },

  //积分
  pointChange: function(e) {
    const that = this;
    let pointStatus = e.currentTarget.dataset.point
    if (pointStatus == true) {
      that.setData({
        pointStatus: false,
        total_deduction_money: '0.00',
        is_deduction: 0
      })
    } else {
      that.setData({
        pointStatus: true,
        total_deduction_money: that.data.orderData.deduction_point.total_deduction_money,
        is_deduction: 1
      })
    }
    that.setData({
      has_store: 2,
      store_id: '',
    })
    that.getOrderInfo();

  },

  //提交订单
  orderCreate: function() {
    const that = this;
    if (that.data.shipping_type == 1) {
      if (that.data.addressid == ''&&that.data.userAddressShow) {
        wx.showToast({
          title: '请选择地址',
          icon: 'none',
        })
        return
      }
    }
    let shopData = that.data.orderData.shop;
    let shop_list = [];
    for (var i = 0; i < shopData.length; i++) {
      let shop_amount = '';
      if (that.data.coupon_id != '') {
        shop_amount = shopData[i].total_amount_money
      } else {
        shop_amount = shopData[i].total_amount;
      }

      //满减卷id
      let rule_id = ''
      if (shopData[i].full_cut_state == 'true') {
        rule_id = shopData[i].full_cut.rule_id
      }

      let goods_list = [];
      for (let item of shopData[i].goods_list) {
        let good_Obj = {
          "goods_id": item.goods_id, //商品id
          "sku_id": item.sku_id, //sku_id
          "price": item.price, //单价
          "num": item.num, // 购买数目
          "discount_price": item.discount_price, //结算单价
          "seckill_id": item.seckill_id, //秒杀活动id 可为空 或者不传
          "presell_id": item.presell_id, //预售ID 可为空 或者不传
          "channel_id": item.channel_id, //渠道商id 可为空
          "bargain_id": item.bargain_id, //砍价活动id 可为空
          "discount_id": item.discount_id, // 限时折扣活动id 可为空 或者不传
        }
        goods_list.push(good_Obj);
      }

      let coupon_id = '';
      for (let v of that.data.coupon_id_list) {
        if (shopData[i].shop_id == v.shop_id) {
          coupon_id = v.coupon_id
        }
      }

      let shop_item = {
        shop_id: shopData[i].shop_id,
        shop_amount: shop_amount,
        coupon_id: coupon_id,
        goods_list: goods_list,
        rule_id: rule_id,    
        leave_message: that.data.leave_message,    
      };

      if (that.data.timing_tag == 1 && that.data.currentData.length > 0) {
        shop_item.card_store_id = that.data.currentData[0].card_store_id;
      }

      if (that.data.shipping_type == 2 && that.data.currentData.length > 0) {
        shop_item.has_store = shopData[i].has_store, 
        that.data.currentData.forEach(c => {
          if (c.shop_id == shop_item.shop_id) {
            shop_item.store_id = c.store_id
          }
        })
      }

      shop_list.push(shop_item);
    }
    let customform = '';
    if (that.data.customform != '') {
      let required = util.isRequired(this.data.customform);
      if (required) {
        customform = JSON.stringify(this.data.customform);
      } else {
        return;
      }
    }
    let order_data = {
      order_from: 6,
      custom_order: customform,
      total_amount: that.data.orderData.amount,
      address_id: that.data.addressid,
      record_id: that.data.recordid,
      group_id: that.data.groupid,
      shop_list: shop_list,
      is_deduction: that.data.is_deduction,
      shipping_type: that.data.shipping_type,
    }
    if (that.data.orderTag == 'cart') {
      order_data.cart_from = that.data.cart_from
    }
    if ((that.data.shopkeeper_id || wx.getStorageSync("shopkeeper_id")) && wx.getStorageSync("isshopkeeper") < 1) {
      order_data.shopkeeper_id = that.data.shopkeeper_id || wx.getStorageSync("shopkeeper_id");
    }

    //计时计次
    if (that.data.timing_tag == 1) {
      if (!order_data.shop_list.every(({
        card_store_id
      }) => card_store_id)) {
        wx.showToast({
          title: '请选择门店！',
          icon: 'none',
        })
        return
      }
    }

    if (that.data.shipping_type == 2) {
      let flag = true
      order_data.shop_list.forEach((s, i) => {
        if (s.has_store == 1 && !s.store_id) {
          wx.showToast({
            title: '需要选择自提门店！',
            icon: 'none'
          })
          return flag = false
        }
        if (s.has_store == 0 && order_data.address_id == 0) {
          wx.showToast({
            title: '需要选择收货地址',
            icon: 'none',
          })
          return flag = false
        }
      })
      if (!flag) return
    }    
    // return console.log(order_data)
    var postData = {
      order_data: order_data
    };
    that.setData({
      sum_ing: 0
    })
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_orderCreate,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        that.setData({
          sum_ing: 1
        })
        if (res.data.code >= 0) {

          let onPageData = {
            url: '../payment/pay/index',
            num: 4,
            param: '?orderno=' + res.data.data.out_trade_no
          }
          util.jumpPage(onPageData);
        } else {
          wx.showModal({
            title: '提示',
            content: res.data.message,
            showCancel: false,
          })
        }
      },
      fail: (res) => {},
    })
  },


  //提交门店线下订单
  storeOrderCreate: function() {
    const that = this;
    let shopData = that.data.orderData.shop;
    let shop_list = [];
    for (var i = 0; i < shopData.length; i++) {
      let shop_amount = '';
      if (that.data.coupon_id != '') {
        shop_amount = shopData[i].total_amount_money
      } else {
        shop_amount = shopData[i].total_amount;
      }

      //满减卷id
      let rule_id = ''
      if (shopData[i].full_cut_state == 'true') {
        rule_id = shopData[i].full_cut.rule_id
      }

      let goods_list = [];
      for (let item of shopData[i].goods_list) {
        let good_Obj = {
          "goods_id": item.goods_id, //商品id
          "sku_id": item.sku_id, //sku_id
          "price": item.price, //单价
          "num": item.num, // 购买数目
          "discount_price": item.discount_price, //结算单价
          "seckill_id": item.seckill_id, //秒杀活动id 可为空 或者不传
          "presell_id": item.presell_id, //预售ID 可为空 或者不传
          "channel_id": item.channel_id, //渠道商id 可为空
          "bargain_id": item.bargain_id, //砍价活动id 可为空
          "discount_id": item.discount_id, // 限时折扣活动id 可为空 或者不传
        }
        goods_list.push(good_Obj);
      }

      let coupon_id = '';
      for (let v of that.data.coupon_id_list) {
        if (shopData[i].shop_id == v.shop_id) {
          coupon_id = v.coupon_id
        }
      }

      let shop_item = {
        shop_id: shopData[i].shop_id,
        shop_amount: shop_amount,
        coupon_id: coupon_id,
        goods_list: goods_list,
        rule_id: rule_id,
        has_store: shopData[i].has_store, 
        leave_message: that.data.leave_message,       
      };

      if (shop_item.has_store == 1){
        if (that.data.timing_tag == 1 && that.data.currentData.length > 0) {
          shop_item.card_store_id = that.data.currentData[0].card_store_id;
        }else{
          that.data.currentData.forEach(c => {
            if (c.shop_id == shop_item.shop_id) {
              shop_item.store_id = c.store_id
            }
          })
        }
      }

      shop_list.push(shop_item);
    }
    let customform = '';
    if (that.data.customform != '') {
      customform = JSON.stringify(this.data.customform);
    }
    let order_data = {
      order_from: 6,
      custom_order: customform,
      total_amount: that.data.orderData.amount,
      address_id: that.data.addressid,
      record_id: that.data.recordid,
      group_id: that.data.groupid,
      shop_list: shop_list,
      is_deduction: that.data.is_deduction,
      shipping_type: that.data.shipping_type,
    }
    if (that.data.orderTag == 'cart') {
      order_data.cart_from = that.data.cart_from
    }
    if ((that.data.shopkeeper_id || wx.getStorageSync("shopkeeper_id")) && wx.getStorageSync("isshopkeeper") < 1) {
      order_data.shopkeeper_id = that.data.shopkeeper_id || wx.getStorageSync("shopkeeper_id");
    }


    //计时计次
    if (that.data.timing_tag == 1) {
      if (!order_data.shop_list.every(({
          card_store_id
        }) => card_store_id)) {
        wx.showToast({
          title: '请选择门店！',
          icon: 'none',
        })
        return
      }
    }

    if (that.data.shipping_type == 2 && that.data.timing_tag != 1) {
      let flag = true
      order_data.shop_list.forEach((s,i) =>{
        if (s.has_store == 1 && !s.store_id) {
          wx.showToast({
            title: '需要选择自提门店！',
            icon: 'none'
          })
          return flag = false
        }
        if (s.has_store == 0 && order_data.address_id == 0) {
          wx.showToast({
            title: '需要选择收货地址',
            icon: 'none',
          })
          return flag = false
        }
      })      
      if (!flag) return
    }    

    var postData = {
      order_data: order_data
    };
    that.setData({
      sum_ing: 0
    })
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_StoreOrderCreate,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        that.setData({
          sum_ing: 1
        })
        if (res.data.code >= 0) {

          let onPageData = {
            url: '../payment/pay/index',
            num: 4,
            param: '?orderno=' + res.data.data.out_trade_no
          }
          util.jumpPage(onPageData);
        } else {
          wx.showModal({
            title: '提示',
            content: res.data.message,
            showCancel: false,
          })
        }
      },
      fail: (res) => {},
    })
  },



  //自定义表单
  customformData: function(e) {
    console.log(e.detail);
    const that = this;
    that.setData({
      customform: e.detail.customform
    })
  },

  //配送方式
  typeRadioChange: function(e) {
    const that = this;
    let type = parseInt(e.detail.value);
    let type_list = that.data.type_list;
    for (let item of type_list) {
      item.checked = false;
      if (item.name == type) {
        item.checked = true
      }
    }
    that.setData({
      shipping_type: type,
      type_list: type_list
    })
    that.getOrderInfo();
  },

  //店铺门店列表显示
  storeListShow: function() {
    const that = this;
    that.setData({
      shopStoreShow: true
    })
  },
  //店铺门店列表不显示
  storeListClose: function() {
    const that = this;
    that.setData({
      shopStoreShow: false
    })
  },

  //计时计次时选择的门店
  getStoreList: function(e) {
    const that = this;
    let shop_id = e.currentTarget.dataset.shopid;
    if (that.data.timing_tag == 1) {
      that.setData({
        store_list: that.data.orderData.shop[0].store_list
      })
      that.storeListShow();
    }
  },

  //获取当前位置的经纬度
  getUserLocation: function(e) {
    const that = this;
    let shop_id = e.currentTarget.dataset.shopid;
    that.data.shop_id = shop_id;
    let shop = that.data.orderData.shop;
    let store_list = '';
    for (let i = 0; i < shop.length; i++) {
      if (shop[i].shop_id == shop_id) {
        store_list = shop[i].store_list;
      }
    }
    that.setData({
      store_list: store_list
    })
    that.storeListShow();
  },



  //获取门店店铺地址
  // getStoreListForWap: function(latitude, longitude) {
  //   const that = this;
  //   that.storeListShow();
  //   var postData = {
  //     shop_id: that.data.shop_id,
  //     page_index: 1,
  //     page_size: 20,
  //     lng: longitude,
  //     lat: latitude,
  //   };
  //   let datainfo = requestSign.requestSign(postData);
  //   header.sign = datainfo
  //   wx.request({
  //     url: api.get_getStoreListForWap,
  //     data: postData,
  //     header: header,
  //     method: 'POST',
  //     dataType: 'json',
  //     responseType: 'text',
  //     success: (res) => {
  //       if (res.data.code >= 0) {
  //         let store_list = res.data.data.store_list
  //         that.setData({
  //           store_list: store_list
  //         })

  //       } else {
  //         wx.showModal({
  //           title: '提示',
  //           content: res.data.message,
  //           showCancel: false,
  //         })
  //       }
  //     },
  //     fail: (res) => {},
  //   })
  // },

  //店铺选择
  storeSelect: function(e) {
    const that = this;
    let store_id = e.currentTarget.dataset.storeid;
    let store_name = e.currentTarget.dataset.storename;
    let shop_id = e.currentTarget.dataset.shopid;
    let orderData = that.data.orderData;
    let currentData = [];
    let currentObj = {};
    let shopData = orderData.shop;
    let sku_list = that.data.sku_list;
    shopData.forEach(shopItem => {
      if (shopItem.shop_id == shop_id) {
        currentObj.shop_id = shop_id;
        //计时计次开启
        if (that.data.timing_tag == 1) {
          shopItem.card_store_id = store_id;
          currentObj.card_store_id = store_id;
        } else {
          shopItem.store_id = store_id;
          currentObj.store_id = store_id;
        }
        shopItem.store_name = store_name;
        currentObj.store_name = store_name;
        sku_list.forEach((s, i) => {
          let goods_item = shopItem.goods_list;
          goods_item.forEach((g, i) => {
            if (g.sku_id == s.sku_id) {
              s.store_id = store_id
              s.store_name = store_name
            }
          })
        })
      }
      currentData.push(currentObj);
    })

    that.setData({
      store_id: store_id,
      store_name: store_name,
      orderData: orderData,
      sku_list: sku_list,
      currentData: currentData
    })
    that.getOrderInfo();
    that.storeListClose();
  },

  //买家留言
  leaveMessage(e) {
    const that = this;
    let leave_message = e.detail.value;
    that.setData({
      leave_message: leave_message
    })
  },





})