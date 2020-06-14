// miniprogram/package/pages/integral/orderConfirm/confirm.js
var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var re = require('../../../../utils/request.js');
var util = require('../../../../utils/util.js');
var Base64 = require('../../../../utils/base64.js').Base64;
import {
  base64src
} from '../../../../utils/base64src.js'
var header = getApp().header;
var params = {};
Page({

  /**
   * 页面的初始数据
   */
  data: {
    publicUrl: getApp().publicUrl,
    items: {},
    addressShow: true,
    //地址类别弹框显示
    addressListShow: false,
    //地址列表  
    address_list: '',
    flag: true, //防止重复点击立即领取

    goodsType: 0, // 商品类型 0==> 普通商品  1 ==> 优惠券 2 ==> 礼品券 3 ==> 余额
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
    re.request(api.get_integralOrderInfo, postData, header).then(res => {
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
          addressListShow: false,
          goodsType: resData.shop[0].goods_list[0].goods_exchange_type
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
  //改变数量
  onChange: function(event) {
    const that = this;
    that.setData({
      num: parseFloat(event.detail)
    })
    params.sku_list[0].num  = that.data.num;
    that.loadData();
  },
  //立即支付
  onPay: function() {
    const items = this.data.items;
    const obj = {};
    obj.order_data = {};
    let orderData = obj.order_data;
    orderData.custom_order = "";
    orderData.type = 6;
    orderData.goods_type = this.data.goodsType;
    orderData.pay_type = 0;
    if (this.data.goodsType == 0) {
      orderData.address_id = this.data.addressid;
    }
    orderData.leave_message = "";
    orderData.shipping_type = 1;
    if (items.shop[0].total_point && items.shop[0].total_amount > 0) {
      orderData.point_exchange_type = 2; //兑换方式 1-只能积分兑换 2-积分和金钱兑换
    } else {
      orderData.point_exchange_type = 1;
    }

    orderData.goods_list = {};
    orderData.goods_list.exchange_point = items.shop[0].total_point ?
      items.shop[0].total_point :
      "";
    if (!items) return {};

    orderData.goods_list.goods_id = items.shop[0].goods_list[0].goods_id;
    orderData.goods_list.sku_id = items.shop[0].goods_list[0].sku_id ? items.shop[0].goods_list[0].sku_id : "";
    orderData.goods_list.num = items.shop[0].goods_list[0].num;
    orderData.goods_list.price = items.shop[0].goods_list[0].price ? items.shop[0].goods_list[0].price : "";


    if (items.shop[0].total_point && items.shop[0].total_amount > 0) {
      let params =  Base64.encode(JSON.stringify(obj));
      let onPageData = {
        url: '/pages/payment/pay/index',
        num: 4,
        param: '?order_data=' + params + '&pay_money=' + items.shop[0].total_amount + '&hash=integral' 
      }
      util.jumpPage(onPageData);
    }else{
      this.createPayOrder(obj);
    }
    
  },
  createPayOrder:function(obj){
    let postData  = obj;
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_integralPay, postData, header).then(res => {
      if(res.data.code == 0){
        wx.reLaunch({
          url: '/pages/payment/paysuccess/index?out_trade_no=' + res.data.data.out_trade_no,
        })
      }else{
        wx.showToast({
          title: res.data.message,
          icon: 'none'
        })
      }
    })
  }
})