// miniprogram/package/pages/microshop/orderConfirm/confirm.js
var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var util = require('../../../../utils/util.js');
var re = require('../../../../utils/request.js');
var header = getApp().header;
var Base64 = require('../../../../utils/base64.js').Base64;
import {
  base64src
} from '../../../../utils/base64src.js';
var params = {};
Page({

  /**
   * 页面的初始数据
   */
  data: {
    items: {},
    addressShow: true,
    //地址类别弹框显示
    addressListShow: false,
    //地址列表  
    address_list: '',
    flag: false, //防止重复点击

    //购买数量   
    num: 1,

    //地址id
    addressid: ''
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {
    params = JSON.parse(Base64.decode(options.params));
  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function() {
    const that = this;
    wx.showLoading({
      title: '加载中',
    })
    that.loadData();
  },
  loadData: function() {
    const that = this;
    let postData = params;
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_micShopInfo, postData, header).then(res => {
      wx.hideLoading();
      if (res.data.code == 1) {
        let resData = res.data.data;
        let addressShow = JSON.stringify(resData.address) ? true : false;
        let addressid = '';
        if (addressShow == true) {
          addressid = resData.address.address_id;
        }
        //返回的数量为字符串类型，转为浮点型
        for (var i = 0; i < resData.shop.length; i++) {
          for (let item of resData.shop[i].goods_list) {
            item.num = parseFloat(item.num);
          }
        }
        that.setData({
          items: resData,
          addressShow: addressShow,
          addressid: addressid,
          addressListShow: false
        })
      } else {
        wx.showToast({
          title: res.data.message,
          icon: 'none'
        })
      }
    })
  },
  //默认地址选择
  addressSelect: function(e) {
    const that = this;
    let address_id = e.currentTarget.dataset.addressid;
    that.data.addressid = address_id;
    params.address_id = address_id;
    that.loadData();
    that.addressListClose();
  },
  //打开地址列表弹框
  addressListShow: function() {
    const that = this;
    that.setData({
      addressListShow: true
    })
    that.receiverAddressList();
  },

  //关闭地址列表弹框
  addressListClose: function() {
    const that = this;
    that.setData({
      addressListShow: false
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
        wx.hideLoading();
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
  /**
   * 新增地址
   */
  onAddressPage: function() {
    const that = this;
    let onPageData = {
      url: '../../address/addAddress/index',
      num: 4,
      param: '',
    }
    util.jumpPage(onPageData);
  },
  onSubmit: function() {
    const that = this;
    that.setData({
      flag: true
    })
    const order_data = {};
    order_data.custom_order = "";
    order_data.order_from = 6;
    order_data.total_amount = that.data.items.amount;
    order_data.shipping_type = 1;
    if (wx.getStorageSync("isshopkeeper")) {
      //是否是微店店主
      order_data.shopkeeper_id = wx.getStorageSync("shopkeeper_id");
    }
    if (params.order_type) {
      order_data.order_type = params.order_type;
    }
    order_data.address_id = that.data.addressid;
    if (!that.data.items.shop) {
      return false;
    }
    let shop = that.data.items.shop[0];
    order_data.shop_list = [];
    let shop_obj = {};
    shop_obj.leave_message = shop.leave_message ? shop.leave_message : "";
    shop_obj.shop_id = shop.shop_id;
    shop_obj.rule_id = shop.full_cut.rule_id ? shop.full_cut.rule_id : ""; //满减id
    shop_obj.coupon_id = shop.coupon_id ? shop.coupon_id : ""; //优惠券id
    shop_obj.shop_amount = shop.total_amount <= 0 ? (shop.total_amount = 0) : shop.total_amount;
    if (order_data.shipping_type == 2) {
      shop_obj.store_id = shop.store_id;
    }
    if (order_data.shipping_type == 0) {
      shop_obj.card_store_id = shop.card_store_id;
    }
    shop_obj.goods_list = [];
    let goods_obj = {};
    let g = shop.goods_list;
    for (let i = 0; i < g.length; i++) {
      goods_obj.goods_id = g[i].goods_id;
      goods_obj.sku_id = g[i].sku_id;
      goods_obj.price = g[i].price;
      goods_obj.num = g[i].num;
      goods_obj.discount_price = g[i].discount_price;
      goods_obj.seckill_id = g[i].seckill_id ? g[i].seckill_id : "";
      goods_obj.channel_id = g[i].channel_id ? g[i].channel_id : "";
      goods_obj.discount_id = g[i].discount_id ? g[i].discount_id : "";
      goods_obj.bargain_id = g[i].bargain_id ? g[i].bargain_id : "";
      goods_obj.presell_id = g[i].presell_id ? g[i].presell_id : "";
    }
    shop_obj.goods_list.push(goods_obj);
    order_data.shop_list.push(shop_obj);

    let postData = {
      order_data: order_data
    };
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_orderCreate, postData, header).then(res => {
      if (res.data.code == 0) {
        let onPageData = {
          url: '/pages/payment/pay/index',
          num: 4,
          param: '?orderno=' + res.data.data.out_trade_no
        }
        util.jumpPage(onPageData);
      }
      that.setData({
        flag: false
      })
    })

  }
})