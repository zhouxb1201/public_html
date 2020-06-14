var requestSign = require('../../../../../utils/requestData.js');
var util = require('../../../../../utils/util.js');
var api = require('../../../../../utils/api.js').open_api;
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
     //购买类型 purchase：采购操作，pickupgoods：自提操作
    buy_type:'',
    orderType:'',
    //地址类别弹框显示
    addressListShow: false,
    //地址列表  
    address_list: '',
    //地址数据
    addressData:'',
    //运费
    shipping_fee:'',
    //留言
    buyer_message:'',
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    const that = this;
    that.data.buy_type = options.buy_type;
    that.channelSettlement();
    if (options.buy_type == 'purchase'){
      //purchase：采购操作，
      that.setData({
        orderType : 1,
      })
    } else if (options.buy_type == 'pickupgoods'){
      //pickupgoods：自提操作
      that.setData({
        orderType: 2,
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

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {

  },


  //渠道商结算页数据
  channelSettlement: function () {
    const that = this;
    let postData = {
      'buy_type': that.data.buy_type
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_channelSettlement,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          let orderData = res.data.data;
          orderData.total_money = parseFloat(orderData.total_money).toFixed(2);
          let addressData = {
            address_id: orderData.address_id,
            consigner: orderData.consigner,
            mobile: orderData.mobile,
            address_info: orderData.address_info
          }
          let shipping_fee = orderData.total_shipping_fee;
          that.setData({
            orderData: orderData,
            addressData: addressData,
            shipping_fee: shipping_fee,
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

  // 渠道商提交订单
  channelOrderCreate: function () {
    const that = this;
    let postData = {
      buy_type: that.data.buy_type
    };
    if (that.data.buy_type == 'pickupgoods'){
      postData.address_id = that.data.addressData.address_id;
      postData.buyer_message = that.data.buyer_message;
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    wx.request({
      url: api.get_channelOrderCreate,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 0) {
          that.data.out_trade_no = res.data.data.out_trade_no;
          that.payOrderFun();
        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
        }
      }
    })
  },

  //提交订单
  payOrderFun:function(){
    const that = this;
    wx.navigateTo({
      url: '/pages/payment/pay/index?orderno=' + that.data.out_trade_no  +'&hash=' +that.data.buy_type,
    })
  },

  //卖家留言
  buyerMessage:function(e){
    const that = this;
    that.data.buyer_message = e.detail.value;    
  },

  //打开地址列表弹框
  addressListShow: function () {
    const that = this;
    that.setData({
      addressListShow: true,
    })
    that.receiverAddressList();
  },

  //关闭地址列表弹框
  addressListClose: function () {
    const that = this;
    that.setData({
      addressListShow: false,
    })
  },

  //收货地址列表
  receiverAddressList: function () {
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
      fail: (res) => { },
    })
  },

  //默认地址选择
  addressSelect: function (e) {
    const that = this;
    let address_id = e.currentTarget.dataset.addressid;
    that.setData({
      address_id: address_id
    })
    for (let i of that.data.address_list){
      if (i.id == address_id){
        let addressData = {
          address_id: i.id,
          consigner: i.consigner,
          mobile: i.mobile,
          address_info: i.province_name + '' + i.address
        }
        that.setData({
          addressData: addressData
        })
        that.countChannelFree(); 
      }
    }

    that.addressListClose();
  },

  onAddressPage: function () {
    const that = this;
    let onPageData = {
      url: '/package/pages/address/addAddress/index',
      num: 4,
      param: '',
    }
    util.jumpPage(onPageData);
    that.addressListClose();
  },

  //渠道商计算运费
  countChannelFree: function () {
    const that = this;
    let goods_list = that.data.orderData.shop_list;
    let goods_info = [];
    for (let i of goods_list){
      let good_item = i.goods_id + ':' + i.num;
      goods_info.push(good_item);
    }
    goods_info = goods_info.join(',');
    var postData = {
      address_id: that.data.address_id,
      goods_info: goods_info,
    };
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_countChannelFree,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
         that.setData({
           shipping_fee: res.data.data.free_money
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
  
})