var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var util = require('../../../../utils/util.js');
var header = getApp().header;
var re = require('../../../../utils/request.js');
var Base64 = require('../../../../utils/base64.js').Base64;
import { base64src } from '../../../../utils/base64src.js'
Page({

  /**
   * 页面的初始数据
   */
  data: {
    clientHeight: 0,
    category_id:'',
    page_index:1,
    itemIndex: 0,
    goods_list:'',
    cart_goods_num:0,
    //sku弹框显示
    skuShow: false,
    sku: '',
    //购物车是否显示
    cartShow: false,    
    //sku的商品图片
    goodsImg: '',
    //sku的商品名字
    goodsName: '',
    //suk的商品价格
    sku_good_price: '',
    //sku的商品库存数量
    sku_stock_num: '',
    selectObj: {},
    //购买数量
    buyNum: 1,
    //选择的skuId
    skuId: '',
    //是否有购买权限
    is_allow_buy: true,    
    //拼团购买的方式 1-单独购买 ， 2-拼团购买
    group_type:'',
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    let search_obj = JSON.parse(options.obj);
    this.data.search_obj = search_obj;

    //获取滚动条可滚动高度
    wx.getSystemInfo({
      success: (res) => {
        this.setData({
          clientHeight: res.windowHeight - res.windowWidth / 750 * 96
        });
      }
    });
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
    this.setData({
      skuShow: false,
    })
    this.getStoreIndex();
    this.getStoreGoodsCategoryList();
    this.getStoreCart();    
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

  //获取门店首页
  getStoreIndex: function () {
    const that = this;
    let postData = {
      'lng': that.data.search_obj.lng,
      'lat': that.data.search_obj.lat,
      'store_id': that.data.search_obj.store_id,
    }

    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_storeIndex,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          let storeData = res.data.data;
          that.setData({
            storeData: storeData
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


  openLocation: function (e) {
    const that = this;
    let latitude = parseFloat(e.currentTarget.dataset.lat);
    let longitude = parseFloat(e.currentTarget.dataset.lng);
    wx.openLocation({
      latitude,
      longitude,
      scale: 18
    })
  },

  //获取门店对应的所有商品分类
  getStoreGoodsCategoryList: function () {
    const that = this;
    let postData = {      
      'store_id': that.data.search_obj.store_id,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getStoreGoodsCategoryList,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          let category_list = res.data.data;
          that.setData({
            category_list: category_list,
            category_id: res.data.data[0].category_id
          })
          that.getStoreGoods();
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

  navChange: function (e) {
    const that = this;
    that.data.page_index = 1;
    that.setData({
      category_id: e.currentTarget.dataset.id,
      itemIndex: e.currentTarget.dataset.index,
      goods_list: '',
    });
    that.getStoreGoods();
  },

  //获取门店分类商品
  getStoreGoods: function () {
    const that = this;
    let postData = {
      'category_id': that.data.category_id,
      'page_index': that.data.page_index,
      'page_size':20,
      'store_id': that.data.search_obj.store_id,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getStoreGoods,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          if (that.data.page_index >= 2) {
            let old_goods_list = that.data.goods_list;
            let new_goods_list = old_goods_list.concat(res.data.data.goods_list);
            that.setData({
              goods_list: new_goods_list
            })
          } else {
            that.setData({
              goods_list: res.data.data.goods_list
            })
          }
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

  //获取门店购物车商品
  getStoreCart: function () {
    const that = this;
    let postData = {      
      'store_id': that.data.search_obj.store_id,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_storeCart,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          let required = util.isEmpty(res.data.data.cart_list);
          if (required){
            that.setData({
              cart_goods_num:0,
              cart_list:[],
              cart_total_money:0
            })
          }else{
            let cart_list = res.data.data.cart_list;
            for (let item of cart_list) {
              item.num = parseInt(item.num);
              if (item.promotion_type == 3 || item.promotion_type == 1 || item.promotion_type == 2 || item.promotion_type == 4) {
                item.selected = false
              } else {
                item.selected = true
              }
            }
            that.setData({
              cart_goods_num: res.data.data.total_count,
              cart_list: cart_list,
              cart_total_money: parseFloat(res.data.data.total_money).toFixed(2)
            })
          }
          
          
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

  //购物车弹框隐藏
  cartOnclose: function () {
    this.setData({
      cartShow: false,
    })
  },

  //购物车弹框显示
  cartOnShow: function () {
    this.setData({
      cartShow: true,
    })
  },

  //修改购物车商品数量
  getEditCartNum: function (e) {
    const that = this;
    let cart_id = e.currentTarget.dataset.cartid;
    let num = e.detail;
    let postData = {
      'cart_id': cart_id,
      'num':num
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_editCartNum,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          that.getStoreCart();
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

  //删除购物车商品
  deleteCartGoods: function (e) {
    const that = this;
    let cart_id = e.currentTarget.dataset.cartid;
    wx.showModal({
      content: '请确认是否删除该商品？',
      success(res){
        if(res.confirm){
          let postData = {
            'cart_id': cart_id,
          }
          let datainfo = requestSign.requestSign(postData);
          header.sign = datainfo
          wx.request({
            url: api.get_deleteCartGoods,
            data: postData,
            header: header,
            method: 'POST',
            dataType: 'json',
            responseType: 'text',
            success: (res) => {
              if (res.data.code >= 0) {
                that.getStoreCart();
              } else {
                wx.showToast({
                  title: res.data.message,
                  icon: 'none'
                })
              }
            },
            fail: (res) => { },
          })
        }
      }
    })
    
  },

  //选择的购物车商品
  selectCartGood:function(e){
    const that = this;
    let cart_id = e.currentTarget.dataset.cartid;
    let cart_list = that.data.cart_list;
    let cart_total_money = that.data.cart_total_money;
    for(let i=0;i<cart_list.length;i++){
      if(cart_list[i].cart_id == cart_id){
        if (cart_list[i].selected == true){
          cart_list[i].selected = false;
          cart_total_money -= cart_list[i].price;
        }else{
          cart_list[i].selected = true;
          cart_total_money += cart_list[i].price
        }
        break;        
      }
    }
    that.setData({
      cart_list: cart_list,
      cart_total_money: cart_total_money
    })
  },

  //sku弹出层开启
  skuShow: function () {
    this.setData({
      skuShow: true,
    })
  },
  //sku弹出层关闭
  skuOnclose: function () {
    this.setData({
      skuShow: false,
    })
  },

  //商品sku显示函数
  goodsSkuShowFun: function (e) {
    const that = this;
    let goodItemData = e.currentTarget.dataset.gooditem;
    let goodsImg = goodItemData.goods_detail.goods_img;
    let goodsName = goodItemData.goods_detail.goods_name;
    let goods_id = goodItemData.goods_detail.goods_id;
    let sku = goodItemData.goods_detail.sku;
    let sku_good_price = goodItemData.goods_detail.min_price;
    let sku_stock_num = goodItemData.goods_detail.sku.list[0].stock_num;
    let is_allow_buy = goodItemData.is_allow_buy;//是否有权限购买

    let sku_list_array = [];
    for (let item of sku.list) {
      sku_list_array = sku_list_array.concat(item.s);
    }
    let result = []
    let obj = {}
    //js高性能数组去重 
    //创建一个空对象，然后用 for 循环遍历，利用对象的属性不会重复这一特性，校验数组元素是否重复    
    for (let i of sku_list_array) {
      if (!obj[i]) {
        result.push(i)
        obj[i] = 1
      }
    }

    //给sku得tree添加一个未选中的属性
    for (var i = 0; i < sku.tree.length; i++) {
      for (var e = 0; e < sku.tree[i].v.length; e++) {
        sku.tree[i].v[e].isSelect = "false";
        sku.tree[i].v[e].isDefault = that.isInArray(result, sku.tree[i].v[e].id);
      }
    }

    //判断是否有秒杀
    let seckill_list = goodItemData.seckill_list;
    if (seckill_list.seckill_id != ''){
      that.setData({
        seckill_list: seckill_list
      })
    }

    let group_list = goodItemData.group_list;
    let group_status = '';//拼团状态
    if (group_list.hasOwnProperty('group_id')){
      group_status = 'groupStart';
      let groupPrice = sku.list[0].group_price || '0'
      that.setData({
        groupPrice: groupPrice,
        group_list: group_list,
        groupStatus: group_status,
      })
    }else{
      group_status = 'groupUnStart';
      that.setData({
        groupStatus: group_status
      })
    }  

    console.log(goodItemData.goods_detail.goods_type)

    that.setData({
      goodsImg: goodsImg,
      sku: sku,
      goodsName: goodsName,
      sku_good_price: sku_good_price,
      sku_stock_num:sku_stock_num,
      is_allow_buy: is_allow_buy,
      goods_id: goods_id,
      goods_type: goodItemData.goods_detail.goods_type
    })
    that.skuShow();
  },

  //判断变量是否在数组中
  isInArray: function (result, id) {
    var testStr = ',' + result.join(",") + ",";
    return testStr.indexOf("," + id + ",") != -1;
  },

  //规格属性的选择
  clickMenu: function (e) {
    const that = this;
    var selectIndex = e.currentTarget.dataset.selectIndex;//组的index
    var attrIndex = e.currentTarget.dataset.attrIndex;//当前的index
    var sku = that.data.sku;
    var spec = sku.tree;

    for (var i = 0; i < spec.length; i++) {
      for (var n = 0; n < spec[i].v.length; n++) {
        if (selectIndex == i) {
          spec[selectIndex].v[n].isSelect = "false";
        }
      }
    }
    spec[selectIndex].v[attrIndex].isSelect = "true";

    that.setData({
      sku: sku
    })

    let attrId = e.currentTarget.dataset.attrId;
    that.data.selectObj[selectIndex] = attrId.toString();


    for (var m = 0; m < sku.list.length; m++) {
      let selectArray = [];
      for (let i in that.data.selectObj) {
        selectArray.push(that.data.selectObj[i]); //属性
      }

      let bool = this.arrayIsEqual(selectArray.sort(), sku.list[m].s.sort());
      let maxBuy = '';
      if (bool == true) {

        // 最大购买数量
        if (sku.list[m].hasOwnProperty('max_buy') && sku.list[m].max_buy != 0) {
          maxBuy = sku.list[m].max_buy
        } else if (sku.list[m].hasOwnProperty('group_limit_buy') && sku.list[m].group_limit_buy != 0) {
          maxBuy = sku.list[m].group_limit_buy
        } else {
          maxBuy = sku.list[m].stock_num
        }


        // 购买数量
        let buyNum = '';
        if (that.data.buyNum != 1) {
          buyNum = that.data.buyNum
        } else {
          buyNum = 1
        }

        that.setData({
          skuId: sku.list[m].id,
          buyNum: buyNum,
          sku_good_price: sku.list[m].price,
          sku_stock_num: maxBuy,
        })
        if (sku.list[m].stock_num == 0) {
          wx.showToast({
            title: '没有库存',
            icon: 'none'
          })
        }
        break;
      }
    }
  },

  //判断2个数组是否相等
  arrayIsEqual: function (arr1, arr2) {
    if (arr1 === arr2) {//如果2个数组对应的指针相同，那么肯定相等，同时也对比一下类型
      return true;
    } else {
      if (arr1.length != arr2.length) {
        return false;
      } else {//长度相同
        for (let i in arr1) {//循环遍历对比每个位置的元素
          if (arr1[i] != arr2[i]) {//只要出现一次不相等，那么2个数组就不相等
            return false;
          }
        }//for循环完成，没有出现不相等的情况，那么2个数组相等
        return true;
      }
    }
  },

  //购买数量
  changeBuynum: function (e) {
    let sku_stock_num = this.data.sku_stock_num;
    if (e.detail == sku_stock_num) {
      wx.showToast({
        title: '库存不足',
        icon: 'none'
      })
      setTimeout(function () {
        wx.hideToast()
      }, 1000)
    }
    this.setData({
      buyNum: e.detail
    })
  },

  //添加到购物车
  addCart: function () {
    const that = this;

    let skuId = '';
    if (that.data.sku.tree.length == 0) {
      skuId = that.data.sku.list[0].id;
    } else {
      if (that.data.skuId != '') {
        skuId = that.data.skuId;
      } else {
        wx.showToast({
          title: '请选择商品规格！',
          icon: 'loading'
        })
        return;
      }
    }

    let postData = {
      'sku_id': skuId,
      'num': that.data.buyNum,
      'store_id': that.data.search_obj.store_id,
      'goods_id': that.data.goods_id
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_storeAddCart,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          wx.showToast({
            title: '添加成功',
          })
          that.skuOnclose();
          that.getStoreCart();

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

  onGroupbuybtn:function(e){
    const that = this;
    let group_type = e.currentTarget.dataset.grouptype;
    that.setData({
      group_type: group_type
    })
    that.buyNowOrder();
  },

  //立即下单
  buyNowOrder:function(){
    const that = this;

    let skuId = '';
    if (that.data.sku.tree.length == 0) {
      skuId = that.data.sku.list[0].id;
    } else {
      if (that.data.skuId != '') {
        skuId = that.data.skuId;
      } else {
        wx.showToast({
          title: '请选择商品规格！',
          icon: 'loading'
        })
        return;
      }
    }
    let skuObj = {
      sku_id: skuId,
      num: that.data.buyNum,
      store_id: that.data.search_obj.store_id,
      store_name: that.data.storeData.store_name
    };
    let sku_list = [];
    sku_list.push(skuObj);
    let params = {
      sku_list: sku_list,
      order_tag: 'buy_now',
      shipping_type:2,
      lat: that.data.search_obj.lat,
      lng: that.data.search_obj.lng,
    }
    //秒杀
    if (that.data.seckill_list.seckill_id != '') {
      params["seckill_id"] = that.data.seckill_list.seckill_id;
    }
    //拼团
    if (that.data.groupStatus == 'groupStart' && that.data.group_type == 2) {
      params["group_id"] = that.data.group_list.group_id;      
    }
    params = Base64.encode(JSON.stringify(params));    
    let onPageData = {
      url: '/pages/orderInfo/index',
      num: 4,
      param: '?params=' + params
    }
    util.jumpPage(onPageData);
  },


  //购物车结算
  orderConfirm:function(){
    const that = this;
    let cart_list = that.data.cart_list;
    let cart_id_list = [];
    let sku_list = [];
    for (let value of cart_list){
      if (value.selected == true){
        cart_id_list.push(value.cart_id);
        let skuObj = {
          sku_id: value.sku_id,
          coupon_id: 0,
        };
        sku_list.push(skuObj);
      }      
    }
    
    let params = {
      sku_list: sku_list,
      order_tag: 'cart',
      cart_from: 2,
      cart_id_list: cart_id_list,      
    }
    params = Base64.encode(JSON.stringify(params));
    let onPageData = {
      url: '/pages/orderInfo/index',
      num: 4,
      param: '?params=' + params
    }
    util.jumpPage(onPageData);
  },

  

})