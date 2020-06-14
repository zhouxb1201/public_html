var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var re = require('../../../../utils/request.js');
var header = getApp().header;
var postData = {}; //接口参数
var prizeDefaultImg = [];
Page({
  data: {
    active: 0,
    state: 1,
    page_index: 1,
    listData: [],
    publicUrl: getApp().publicUrl,
    defaultImg: [],
    reachBottomflag: true
  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function() {
    const that = this;
    that.loadList();
  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function() {
    const that = this;
    if (that.data.reachBottomflag == false) {
      return false;
    }
    that.data.page_index = that.data.page_index + 1;
    postData.state = that.data.state;
    postData.page_index = that.data.page_index;
    postData.page_size = 10;
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_prizeList, postData, header).then(res => {
      if (res.data.code == 1) {
        let listData = that.data.listData;
        let listPageData = res.data.data.data;
        if (listPageData.length == 0) {
          that.data.reachBottomflag = false;
          return false;
        }
        listData = listData.concat(listPageData);
        that.setData({
          listData: listData
        })
        that.defaultPirzeImg(listData);
      }
    })
  },
  onTabsChange: function(event) {
    const that = this;
    if (event.detail.title == "未领奖") {
      that.data.state = 1;
    } else if (event.detail.title == "已领奖") {
      that.data.state = 2;
    } else if (event.detail.title == "已过期") {
      that.data.state = 3;
    }
    that.data.page_index = 1
    postData.page_index = that.data.page_index;
    that.data.reachBottomflag = true;
    that.loadList();
  },
  loadList: function() {
    wx.showLoading({
      title: '加载中',
    })
    const that = this;
    postData.state = that.data.state;
    postData.page_index = that.data.page_index;
    postData.page_size = 10;
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_prizeList, postData, header).then(res => {
      wx.hideLoading();
      if (res.data.code == 1) {
        that.setData({
          listData: res.data.data.data
        })
        that.defaultPirzeImg(res.data.data.data);
      } else {
        wx.showToast({
          title: res.data.data.message,
          icon: 'none'
        })
      }
    })
  },
  onConfirm: function(e) {
    wx.navigateTo({
      url: '../confirm/confirm?member_prize_id=' + e.currentTarget.dataset.memberprizeid,
    })
  },
  defaultPirzeImg: function(listData) {
    const that = this;
    let imgSrc = '';
    let type = null;

    for (let i = 0; i < listData.length; i++) {
      type = listData[i].type;
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
      prizeDefaultImg.push(imgSrc);
    }
    prizeDefaultImg.concat(prizeDefaultImg);
    that.setData({
      defaultImg: prizeDefaultImg
    })
  },
  onLogistics: function(e) {
    wx.navigateTo({
      url: '/pages/order/logistics/index?orderId=' + e.currentTarget.dataset.orderid,
    })
  }
})