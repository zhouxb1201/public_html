var requestSign = require('../../utils/requestData.js');
var time = require('../../utils/time.js');
var api = require('../../utils/api.js').open_api;
var util = require('../../utils/util.js');
var header = getApp().header;
const app = getApp();
var Base64 = require('../../utils/base64.js').Base64;
Page({

  /**
   * 页面的初始数据
   */
  data: {

    // 购物车列表
    carts: '',
    // 列表是否有数据
    hasList: false,
    // 总价，初始为0     
    totalPrice: 0,
    // 全选状态，默认不全选
    selectAllStatus: false,
    //结算按钮显示隐藏 
    countBtnDisabled: true,
    //合计
    totalMoney: '0.00',
    // 选中商品数组
    cartslist: [],
    //优惠券
    couponData: '',
    //优惠券框显示
    couponShow: false,
    //所有店铺的优惠券列表
    couponListData: [],

    //绑定手机弹框
    phoneShow: false,
    //手机验证码倒计时设置
    phoneCode: '获取验证码',
    codeDis: false,
    //手机号码
    user_tel: '',
    //手机验证码
    verification_code: '',
    //数量变化
    change_num: false,
    //门店列表请求参数
    current_store: {},
    //门店列表
    store_list: [],
    //门店弹框显示
    storeListShow: false,
    

  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {


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
    const value = wx.getStorageSync('user_token')
    if (value) {
      console.log('已登录');
      that.getCartList();
    } else {
      console.log('未登录')
    }

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

  },
  

  //请求购物车商品列表数据  
  getCartList: function() {
    const that = this;
    let postData = {
      page_index: 1
    };
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_cart,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          if (Object.keys(res.data.data).length != 0) {
            let carts = res.data.data.shop_info
            that.setShopCartData(carts);
          } else {
            that.setData({
              hasList: false,
              selectAllStatus: false,
              countBtnDisabled: true,
              totalMoney: '0.00'
            })
          }


        } else {
          wx.showToast({
            title: res.data.message,
          })
        }

      },
      fail: (res) => {
        wx.showToast({
          title: '接口请求出错！',
        })
      },
    })
  },

  //整理购物车数据
  setShopCartData(carts){
    const that = this;
    let totalMoney = 0;
    let cartslist = [];
    let sku_list = [];

    for (var i = 0; i < carts.length; i++) {
      carts[i].selected = true
      let goodsList = carts[i].goods_list;
      let mansong_info = '';
      let goodsIdArray = [];
      if (Object.keys(carts[i].mansong_info).length == 0) {
        carts[i].mansong_info = 'false';
      }
      for (var n = 0; n < goodsList.length; n++) {
        //判断是否为网络图片
        if (goodsList[n].picture_info.substring(0, 1) != 'h') {
          goodsList[n].picture_info = getApp().publicUrl + '/' + goodsList[n].picture_info
        }
        //预售（3）/秒杀（1）/拼团（2）/砍价（4）商品只能单独结算，所以不能选中
        if (goodsList[n].promotion_type == 3 || goodsList[n].promotion_type == 1 || goodsList[n].promotion_type == 2 || goodsList[n].promotion_type == 4) {
          goodsList[n].selected = false;
        } else {
          goodsList[n].selected = true;
          totalMoney += goodsList[n].price * goodsList[n].num
          cartslist.push(goodsList[n].cart_id);
        }

        let item_Obj = {
          sku_id: goodsList[n].sku_id,
          coupon_id: 0,
        }
        sku_list.push(item_Obj);

        goodsIdArray.push(goodsList[n].goods_id);

      }

      that.goodsCouponList(goodsIdArray, carts[i].shop_id);
    }

    let countBtnDisabled = '';
    if (totalMoney == 0) {
      countBtnDisabled = true
    }
    that.setData({
      carts: carts,
      totalMoney: totalMoney.toFixed(2),
      cartslist: cartslist,
      sku_list: sku_list,
      hasList: true,
      selectAllStatus: true,
      countBtnDisabled: countBtnDisabled
    })
  },

  // 判断对象中key对应value是否为空
  objectValueNotNone: function(obj) {
    for (var objKey in obj) {
      if (!obj[objKey]) {
        return false;
      }
    }
    return true;
  },

  //删除购物车商品
  deleteGoods: function(e) {
    const that = this;
    let cartId = e.currentTarget.dataset.cartid;

    wx.showModal({
      title: '提示',
      content: '确认删除该商品',
      success: function(res) {
        if (res.confirm) {
          deteleCartGoods(cartId)
        } else if (res.cancel) {
          console.log('用户点击取消')
        }
      }
    })

    let deteleCartGoods = function(cartId) {
      let postData = {
        'cart_id': cartId
      };
      let datainfo = requestSign.requestSign(postData);
      header.sign = datainfo
      wx.request({
        url: api.get_delete_car_goods,
        data: postData,
        header: header,
        method: 'POST',
        dataType: 'json',
        responseType: 'text',
        success: (res) => {
          if (res.data.code == 0) {
            that.getCartList();
          } else {
            wx.showToast({
              title: res.data.message,
              icon: 'loading'
            })
            setTimeout(function() {
              wx.hideToast();
            }, 2000)
          }
        },
        fail: (res) => {},
      })
    }
  },

  //数量改变
  onChange: function(event) {
    wx.showToast({
      title: '加载中',
      icon: 'loading'
    })
    const that = this;
    let postData = {
      'cartid': event.currentTarget.dataset.cartid,
      'num': event.detail
    };
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_cartAdjustNum,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        wx.hideToast();
        if (res.data.code == 0) {

          that.getCartList();
        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'loading'
          })
          setTimeout(function() {
            wx.hideToast();
          }, 2000)
        }
      },
      fail: (res) => {},
    })
  },

  //全选按钮
  selectAll: function() {
    const that = this;
    let status = that.data.selectAllStatus;
    let carts = that.data.carts;
    let totalMoney = 0;
    if (status == true) {
      for (var i = 0; i < carts.length; i++) {
        carts[i].selected = false;
        let goodsList = carts[i].goods_list;
        for (var n = 0; n < goodsList.length; n++) {
          goodsList[n].selected = false;
        }
      }
      that.setData({
        selectAllStatus: false,
        carts: carts
      })
    } else if (status == false) {
      for (var i = 0; i < carts.length; i++) {
        carts[i].selected = true;
        let goodsList = carts[i].goods_list;
        for (var n = 0; n < goodsList.length; n++) {
          //预售（3）/秒杀（1）/拼团（2）/砍价（4）商品只能单独结算，所以不能选中
          if (goodsList[n].promotion_type == 3 || goodsList[n].promotion_type == 1 || goodsList[n].promotion_type == 2 || goodsList[n].promotion_type == 4) {
            goodsList[n].selected = false;
          } else {
            goodsList[n].selected = true;
            totalMoney += goodsList[n].price * goodsList[n].num
          }
        }
      }
      that.setData({
        selectAllStatus: true,
        carts: carts
      })
    }

    that.totalPrice();

  },
  //店铺选择
  selectShop: function(e) {
    const that = this;
    let shopid = e.currentTarget.dataset.shopid;
    let carts = that.data.carts;
    let totalMoney = 0;
    for (var i = 0; i < carts.length; i++) {
      if (carts[i].shop_id == shopid) {
        if (carts[i].selected == true) {
          carts[i].selected = false;
          let goodsList = carts[i].goods_list;
          for (var n = 0; n < goodsList.length; n++) {
            goodsList[n].selected = false;
          }
          that.setData({
            totalMoney: totalMoney.toFixed(2),
            carts: carts
          })
          break
        } else if (carts[i].selected == false) {
          carts[i].selected = true;
          let goodsList = carts[i].goods_list;

          for (var n = 0; n < goodsList.length; n++) {
            //预售（3）/秒杀（1）/拼团（2）/砍价（4）商品只能单独结算，所以不能选中
            if (goodsList[n].promotion_type == 3 || goodsList[n].promotion_type == 1 || goodsList[n].promotion_type == 2 || goodsList[n].promotion_type == 4) {
              goodsList[n].selected = false;
            } else {
              goodsList[n].selected = true;
              totalMoney += goodsList[n].price * goodsList[n].num
            }

          }
          that.setData({
            totalMoney: totalMoney.toFixed(2),
            carts: carts
          })
          break
        }

      }
    }
    that.totalPrice();
  },

  //选择商品
  selectGood: function(e) {
    const that = this;
    let cartid = e.currentTarget.dataset.cartid;
    let carts = that.data.carts;
    let totalMoney = 0;

    for (var i = 0; i < carts.length; i++) {
      let goodsList = carts[i].goods_list;
      for (var n = 0; n < goodsList.length; n++) {
        if (goodsList[n].cart_id == cartid) {
          if (goodsList[n].selected == true) {
            goodsList[n].selected = false;
            carts[i].selected = false;
            that.setData({
              selectAllStatus: false,
              carts: carts
            })
            break;
          } else {
            goodsList[n].selected = true;
            that.setData({
              carts: carts
            })
            break;
          }
        }
      }
    }

    that.totalPrice();
  },

  //选中商品的总价
  totalPrice: function() {
    const that = this;
    let carts = that.data.carts;
    let totalMoney = 0;
    let cartslist = [];
    let sku_list = [];
    for (var i = 0; i < carts.length; i++) {
      let goodsList = carts[i].goods_list;
      for (var n = 0; n < goodsList.length; n++) {
        if (goodsList[n].selected == true) {
          totalMoney += goodsList[n].price * goodsList[n].num
          cartslist.push(goodsList[n].cart_id);
          let item_Obj = {
            sku_id: goodsList[n].sku_id,
            coupon_id: 0,
          }
          sku_list.push(item_Obj);
        }
      }
    }
    if (totalMoney == 0) {
      var orderBtn = true;
    } else {
      var orderBtn = '';
    }

    that.setData({
      totalMoney: totalMoney,
      cartslist: cartslist,
      sku_list: sku_list,
      countBtnDisabled: orderBtn
    })
  },

  //跳转到确认订单页面
  onOrderPage: function() {
    const that = this;
    let cartslist = that.data.cartslist
    let have_mobile = wx.getStorageSync('have_mobile');
    if (have_mobile == false) {
      that.setData({
        phoneShow: true,
      })
    } else {
      let params = {
        order_tag: 'cart',
        cart_id_list: cartslist,
        sku_list: that.data.sku_list,
        cart_from: 1,
      }
      params = Base64.encode(JSON.stringify(params));

      let onPageData = {
        url: '../orderInfo/index',
        num: 4,
        param: '?params=' + params,
      }
      util.jumpPage(onPageData);
    }
  },

  // 商品优惠券
  goodsCouponList: function(goodsIdArray, shopId) {
    const that = this;
    let postData = {
      'goods_id_array': goodsIdArray
    };
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_goodsCouponList,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {

        let couponData = ''; //优惠券数据
        let carts = that.data.carts;
        if (res.data.data.length != 0) {
          couponData = res.data.data;
          for (var i = 0; i < couponData.length; i++) {
            couponData[i].discount = parseInt(couponData[i].discount);
            couponData[i].end_time = time.js_date_time(couponData[i].end_time); //时间戳转日期
            couponData[i].start_time = time.js_date_time(couponData[i].start_time);
          }
          let couponObj = {
            'shop_id': shopId,
            'couponData': couponData
          }
          let couponListData = that.data.couponListData;
          couponListData.push(couponObj);

          for (var i = 0; i < carts.length; i++) {
            if (shopId == carts[i].shop_id) {
              carts[i].coupon_result = true
            }
          }

          that.setData({
            carts: carts,
            couponListData: couponListData
          })
        }

      },
      fail: (res) => {},
    })
  },

  //获取店铺优惠券
  getShopCoupon: function(e) {
    const that = this;
    let shopId = e.currentTarget.dataset.shopid;
    for (let i of that.data.couponListData) {
      if (shopId == i.shop_id) {
        that.setData({
          couponData: i.couponData
        })
        that.couponShow();
      }
    }

  },

  //优惠券弹出层开启
  couponShow: function() {
    this.setData({
      couponShow: true
    })
  },

  //优惠券弹出层关闭
  couponOnclose: function() {
    this.setData({
      couponShow: false
    })
  },

  //领取优惠券
  receiveCoupon: function(e) {
    const that = this;
    let coupontypeid = e.currentTarget.dataset.coupontypeid;
    let postData = {
      'coupon_type_id': coupontypeid,
      'get_type': 4 //1->订单,2->首页领取,3->注册营销获取,4->购物车获取,5->商品详情获取
    };
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_userArchiveCoupon,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        wx.showToast({
          title: '领取成功',
          icon: 'success'
        })
        setTimeout(function() {
          wx.hideToast();
          that.couponOnclose();
        }, 1000)

      },
      fail: (res) => {},
    })
  },

  //门店列表弹框开启
  storeListShow: function() {
    this.setData({
      storeListShow: true
    })
  },

  //门店列表弹框关闭
  storeListClose: function() {
    this.setData({
      storeListShow: false
    })
  },

  //配送方式
  shippingType: function(e) {
    const that = this;
    let shop_id = e.currentTarget.dataset.shopid;
    let carts = that.data.carts;
    let sku_list_store = [];
    for (let shopItem of carts) {
      if (shopItem.shop_id == shop_id) {
        for (let goodItem of shopItem.goods_list) {
          sku_list_store.push(goodItem.sku_id);
        }
      }
    }
    let current_store = {
      shop_id: shop_id,
      sku_list: sku_list_store
    }
    that.data.current_store = current_store;
    that.getLocation();
  },

  //获取经纬度
  getLocation: function() {
    const that = this;
    wx.getLocation({
      type: 'wgs84',
      success: function(res) {
        let lat = res.latitude;
        let lng = res.longitude;
        that.data.current_store.lat = lat;
        that.data.current_store.lng = lng;
        that.getStoreList();
      },
      fail: function(res) {},
      complete: function(res) {},
    })
  },

  //门店列表
  getStoreList: function() {
    const that = this;
    let postData = {
      'lng': that.data.current_store.lng,
      'lat': that.data.current_store.lat,
      'shop_id': that.data.current_store.shop_id,
      'sku_list': that.data.current_store.sku_list,
    };
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getStoreList,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 1) {
          let store_list = [{
            shop_id:0,
            store_id:0,            
          }]
          store_list = store_list.concat(res.data.data);
          for(let s of store_list){
            s.selected = false;
            if(s.shop_id == 0 && s.store_id == 0){
              s.selected = true
            }
          }
         
          that.setData({
            store_list: store_list,
          })
          that.storeListShow()
        }
      },
      fail: (res) => {},
    })
  },

  //选择门店
  selectStore: function(e) {
    const that = this;
    let shop_id = e.currentTarget.dataset.shopid;
    let store_id = e.currentTarget.dataset.storeid;
    let store_name = e.currentTarget.dataset.storename;
    let store_list = that.data.store_list;
    for (let s of store_list){
      s.selected = false;
      if(shop_id == s.shop_id && store_id == s.store_id){
        s.selected = true
      }
    }
    that.setData({
      store_list: store_list
    })
    that.cartGetGoodsList(shop_id, store_id, store_name);
    that.storeListClose();
  },

  //获取购物车中属于此店铺的所有商品
  cartGetGoodsList: function (shop_id, store_id, store_name) {
    const that = this;
    let postData = {
      'shop_id': shop_id,
      'store_id': store_id,
    };
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_cartGetGoodsList,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if(res.data.code == 1){
          let carts = that.data.carts;
          let shop_item = res.data.data; 
          shop_item.store_name = store_name;        
          carts[shop_item.shop_id] = shop_item;
          that.setShopCartData(carts);
        }
      },
      fail: (res) => {},
    })
  },

  onIndexPage: function() {
    let onPageData = {
      url: '/pages/index/index',
      num: 4,
      param: '',
    }
    util.jumpPage(onPageData);
  },

  onGoodDetailPage: function(e) {
    const that = this;
    let goods_id = e.currentTarget.dataset.goodsid;
    let onPageData = {
      url: '/pages/goods/detail/index',
      num: 4,
      param: '?goodsId=' + goods_id,
    }
    util.jumpPage(onPageData);
  },

  onShopPage: function(e) {
    const that = this;
    let shop_id = e.currentTarget.dataset.shopid;
    let onPageData = {
      url: '/pages/shop/home/index',
      num: 4,
      param: '?shopId=' + shop_id,
    }
    util.jumpPage(onPageData);
  },





})