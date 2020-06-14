var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var util = require('../../../../utils/util.js');
var re = require('../../../../utils/request.js');
var time = require('../../../../utils/time.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    //优惠券id
    couponId:'',
    //优惠券详情
    couponDetail:'',
    //商品列数据
    goodlist: [],
    //页码
    page: 1,
    noMore: 'false',
    //
    orderActive: '',
    //销量排序
    saleSort: 'ASC',
    //价格排序
    priceSort: 'ASC',
    sort: '',
    //右边弹出框
    rightShow: false,
    //是否包邮
    free_shipping: 0,
    //是否新品
    new_goods: 0,
    //是否推荐
    recommend_goods: 0,
    //是否热卖
    hot_goods: 0,
    //是否促销
    promotion_goods: 0,
    //最低价
    minPrice: '',
    //最高价
    maxPrice: '',
    //商品菜单顶部固定
    topNavShow: false,
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    const that = this;
    //跳转进来
    if (options.couponId != undefined){
      let couponId = options.couponId;
      that.data.couponId = couponId;
    }

    // 扫码进来
    if (options.scene != undefined) {
      let scene = options.scene;
      let value_index = scene.indexOf('_');
      that.data.couponId = scene.substring(value_index + 1)//获取id值 
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
    const that = this;
    that.data.goodlist = [];
    that.couponDetail();
    that.couponGoodsList();
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
    that.data.page = that.data.page + 1;
    that.couponGoodsList();
  },

  
  
  //优惠券详情
  couponDetail: function () {
    const that = this;
    let postData = {
      'coupon_type_id': that.data.couponId
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_couponDetail, postData, header).then((res) => {
      if (res.data.code == 1) {
        let couponDetail = res.data.data;
        couponDetail.start_time = time.js_date_time(couponDetail.start_time);
        couponDetail.end_time = time.js_date_time(couponDetail.end_time);
        couponDetail.money = parseInt(couponDetail.money);
        couponDetail.discount = parseInt(couponDetail.discount);
        couponDetail.at_least = parseInt(couponDetail.at_least);
        that.setData({
          couponDetail: couponDetail
        })
      } else {
        wx.showModal({
          title: '提示',
          content: res.data.message,
          showCancel: false,
        })
      }
    })
  },

  //领取优惠券
  receiveCoupon:function(){
    const that = this;
    if (that.ifLogin() == false) {
      return
    };
    if (that.hasPhoneFun() == false) {
      return
    }
    let postData = {
      'coupon_type_id': that.data.couponId,
      'get_type':6,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_userArchiveCoupon, postData, header).then((res) => {
      if (res.data.code > 0) {
        wx.showToast({
          title: '领取优惠券成功！',
        })
      } else {
        wx.showModal({
          title: '提示',
          content: res.data.message,
          showCancel: false,
        })
        if (res.data.code == -2011){
          that.couponDetail();
        }
      }
    })
  },

  //优惠券适用商品列表
  couponGoodsList: function () {
    const that = this;
    let order = that.data.orderActive;
    let sort = that.data.sort;
    let postData = {
      'coupon_type_id': that.data.couponId,
      'page_index': that.data.page,
      'page_size': 8,
      'order': order,
      'sort': sort,
      'min_price': that.data.minPrice,
      'max_price': that.data.maxPrice,
      'is_shipping_free': that.data.free_shipping,
      'is_new': that.data.new_goods,
      'is_recommend': that.data.recommend_goods,
      'is_hot': that.data.hot_goods,
      'is_promotion': that.data.promotion_goods,
    }

    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_couponGoodsList, postData, header).then((res) => {
      if (res.data.code == 1) {
        var goodslist = that.data.goodlist;
        if (goodslist.length == 0) {
          goodslist = res.data.data.goods_list;
        } else {
          goodslist = goodslist.concat(res.data.data.goods_list);//滚动到底部把数据添加到原数组
        }

        this.setData({
          goodlist: goodslist,
          rightShow: false,
        })

        if (res.data.data.total_count == that.data.goodlist.length) {
          this.setData({
            noMore: 'true'
          })
        }
      } else {
        wx.showModal({
          title: '提示',
          content: res.data.message,
          showCancel: false,
        })
      }
    })
  },

  //顶部选项卡
  changeSort: function (e) {
    let onOrder = e.currentTarget.dataset.order;
    this.setData({
      orderActive: onOrder
    })

    let sort = e.currentTarget.dataset.sort;
    if (onOrder == 'sales') {
      if (sort == 'ASC') {
        this.setData({
          saleSort: 'DESC',
          sort: 'DESC',
          page: 1
        })
      } else (
        this.setData({
          saleSort: 'ASC',
          sort: 'ASC',
          page: 1
        })
      )
    } else if (onOrder == 'price') {
      if (sort == 'ASC') {
        this.setData({
          priceSort: 'DESC',
          sort: 'DESC',
          page: 1
        })
      } else (
        this.setData({
          priceSort: 'ASC',
          sort: 'ASC',
          page: 1
        })
      )
    }
    this.data.page = 1;
    this.data.goodlist = [];
    this.couponGoodsList();
    this.backTop();
  },

  // 滚动到顶部
  backTop: function () {
    // 控制滚动
    wx.pageScrollTo({
      scrollTop: 0
    })
  },

  //右边弹框显示
  pupupRightShow: function () {
    this.setData({
      rightShow: true
    })
  },
  //右边弹框关闭
  pupupRightClose: function () {
    this.setData({
      rightShow: false
    })
  },
  //新品/包邮
  checkedBool: function (e) {
    const that = this;
    let checkOption = e.currentTarget.dataset.checked;
    if (checkOption == 'freeShipping') {
      if (that.data.free_shipping == 0) {
        that.setData({
          free_shipping: 1
        })
      } else {
        that.setData({
          free_shipping: 0
        })
      }
    } else if (checkOption == 'newGoods') {
      if (that.data.new_goods == 0) {
        that.setData({
          new_goods: 1
        })
      } else {
        that.setData({
          new_goods: 0
        })
      }
    } else if (checkOption == 'recommend') {
      if (that.data.recommend_goods == 0) {
        that.setData({
          recommend_goods: 1
        })
      } else {
        that.setData({
          recommend_goods: 0
        })
      }
    } else if (checkOption == 'hotGoods') {
      if (that.data.hot_goods == 0) {
        that.setData({
          hot_goods: 1
        })
      } else {
        that.setData({
          hot_goods: 0
        })
      }
    } else if (checkOption == 'promotion') {
      if (that.data.promotion_goods == 0) {
        that.setData({
          promotion_goods: 1
        })
      } else {
        that.setData({
          promotion_goods: 0
        })
      }
    }
  },

  //最低价
  minPrice: function (e) {
    this.setData({
      minPrice: e.detail.value
    })
  },
  //最高价
  maxPrice: function (e) {
    this.setData({
      maxPrice: e.detail.value
    })
  },

  //重置
  resetData: function () {
    const that = this;
    that.setData({
      free_shipping: 0,
      new_goods: 0,
      recommend_goods: 0,
      hot_goods: 0,
      promotion_goods: 0,
      minPrice: '',
      maxPrice: '',
    })
  },

  //筛选商品
  chooseGoods: function () {
    const that = this;
    that.data.page = 1;
    that.data.goodlist = [];
    that.couponGoodsList();
  },

  //跳转到店铺首页
  onShopPage: function (e) {
    const that = this;
    let shop_id = e.currentTarget.dataset.shopid;
    wx.navigateTo({
      url: '/pages/shop/home/index?shopId=' + shop_id,
    })
  },

  //跳转到商品详情
  ongoodsPage: function (e) {
    const that = this;
    let goods_id = e.currentTarget.dataset.goodsid;
    wx.navigateTo({
      url: '/pages/goods/detail/index?goodsId=' + goods_id,
    })
  },

  //页面滚动事件
  onPageScroll: function (e) {
    const that = this;
    let scrollTopValue = e.scrollTop;
    let topNavShow = scrollTopValue > 200 ? true : false;
    that.setData({
      topNavShow: topNavShow
    })
  },


  //判断是否登录
  //是否登录
  ifLogin: function () {
    const that = this;
    var userToken = wx.getStorageSync('user_token');
    var ifLogin = true;
    if (userToken == '') {
      wx.showToast({
        title: '您还未授权登录',
        icon: 'loading'
      })
      setTimeout(function () {
        wx.hideToast()
        that.setData({
          loginShow: true,
        })

      }, 2000)
      ifLogin = false;
      return ifLogin
    }
    return ifLogin
  },

  //判断是否有手机号
  hasPhoneFun: function () {
    const that = this;
    const have_mobile = wx.getStorageSync('have_mobile');
    if (have_mobile != true) {
      that.setData({
        phoneShow: true
      })
      
      return false
    }
  },

  //绑定手机结果返回
  phonereResult: function (e) {
    const that = this
    let result = e.detail.result;
    if (result == 'success') {
      that.data.goodlist = [];
      that.couponDetail();
      that.couponGoodsList();
    }
  },

  
})