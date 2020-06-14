// miniprogram/package/pages/prize/confirm/confirm.js
var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var re = require('../../../../utils/request.js');
var util = require('../../../../utils/util.js');
var header = getApp().header;
var postData = {}; //接口参数
var member_prize_id = null;
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
    defaultImg: null,
    //地址id
    addressid: '',


    shopStoreShow: false,
    storeInfo: {},
    store_id: '',
    store_name: '',
    store_list: {},
    //店铺id
    shop_id: ''
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {
    member_prize_id = options.member_prize_id;
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
  loadData() {
    const that = this;
    postData.member_prize_id = member_prize_id;
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_prizeDetail, postData, header).then(res => {
      wx.hideLoading();
      if (res.data.code == 1) {
        let resData = res.data.data;
        let addressShow = JSON.stringify(resData.address) ? true : false;
        let addressid = '';
        if (addressShow == true) {
          addressid = resData.address.address_id;
        }
        let storeInfo = {};
        if (resData.store_list) {
          storeInfo = resData.store_list;
        }
        let imgSrc = '';
        let type = resData.type;
        if (type == 1) {
          // 1 => 余额
          imgSrc = that.data.publicUrl + '/wap/static/images/default-balance.png';
        } else if (type == 2) {
          // 2 => 积分
          imgSrc = that.data.publicUrl + '/wap/static/images/default-integral.png';
        } else if (type == 3) {
          // 3 => 优惠券
          imgSrc = that.data.publicUrl + '/wap/static/images/default-coupon.png';
        } else if (type == 4) {
          // 4 => 礼品券
          imgSrc = that.data.publicUrl + '/wap/static/images/default-giftvoucher.png';
        } else if (type == 5) {
          // 5 => 商品
          imgSrc = that.data.publicUrl + '/wap/static/images/default-goods.png';
        } else if (type == 6) {
          // 6 => 赠品
          imgSrc = that.data.publicUrl + '/wap/static/images/default-gift.png';
        }
        that.setData({
          items: resData,
          addressShow: addressShow,
          addressid: addressid,
          addressListShow: false,
          defaultImg: imgSrc,
          store_list: storeInfo
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
    postData.address_id = address_id;
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
  //立即领取
  onReceive: function() {
    const that = this;
    if (that.checkPhone() == false) {
      return;
    }
    if (!that.data.flag) {
      return false;
    }
    that.data.flag = false; //防止重复点击
    let param = {
      member_prize_id: member_prize_id,
      order_from: 6
    }
    if ((that.data.items.type == 5 && that.data.items.goods_type == 1) || that.data.items.type == 6) {
      param.address_id = that.data.items.address.address_id;
      if (!param.address_id) {
        that.data.flag = true;
        wx.showToast({
          title: "请选择收货地址",
          icon: 'none'
        })
      }
    } else if (that.data.items.type == 5 && that.data.items.goods_type == 1) {
      param.card_store_id = that.data.store_id;
      if (!param.card_store_id) {
        that.data.flag = true;
        wx.showToast({
          title: "请选择门店",
          icon: 'none'
        })
      }
    }

    let datainfo = requestSign.requestSign(param);
    header.sign = datainfo;
    re.request(api.get_acceptPrize, param, header).then(res => {
      that.data.flag = true;
      if (res.data.code == 1) {
        wx.redirectTo({
          url: '../result/result'
        })
      } else {
        wx.showToast({
          title: res.data.message,
          icon: 'none'
        })
      }
    })

  },

  //计时计次时选择的门店
  getStoreList: function(e) {
    const that = this;
    let shop_id = e.currentTarget.dataset.shopid;
    that.storeListShow();

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
  //店铺选择
  storeSelect: function(e) {
    const that = this;
    let store_id = e.currentTarget.dataset.storeid;
    let store_name = e.currentTarget.dataset.storename;
    that.setData({
      store_id: store_id,
      store_name: store_name
    })
    that.storeListClose();
  },
  //是否有电话
  checkPhone: function () {
    let that = this;
    let have_mobile = wx.getStorageSync('have_mobile');
    if (have_mobile != true) {
      that.setData({
        phoneShow: true
      })
    }
    return have_mobile
  },

  //绑定手机结果返回
  phonereResult: function (e) {
    const that = this
    let result = e.detail.result;
    if (result == 'success') {
      that.loadData();
    }
  }
})