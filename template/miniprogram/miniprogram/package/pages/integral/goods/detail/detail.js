// miniprogram/package/pages/integral/goods/detail/detail.js
var requestSign = require('../../../../../utils/requestData.js');
var api = require('../../../../../utils/api.js').open_api;
var re = require('../../../../../utils/request.js');
var util = require('../../../../../utils/util.js');
var WxParse = require('../../../../../common/wxParse/wxParse.js');
var time = require('../../../../../utils/time.js');
var Base64 = require('../../../../../utils/base64.js').Base64;
import {
  base64src
} from '../../../../../utils/base64src.js'
var header = getApp().header;
Page({
  data: {
    //当前商品id
    goodsId: '',
    //图片地址
    imgList: [],
    //是否显示画板指示点  
    indicatorDots: false,
    //选中点的颜色  
    indicatorcolor: "#000",
    //是否竖直  
    vertical: false,
    //是否自动切换  
    autoplay: true,
    //自动切换的间隔
    interval: 2500,
    //滑动动画时长毫秒  
    duration: 100,
    //所有图片的高度  
    imgheights: [],
    //图片宽度 
    imgwidth: 750,
    //默认  
    imgcurrent: 0,

    //商品价格
    goodsPrice: '',
    //积分
    goodsPoint: '',
    // 商品明细
    goods_detail: '',
    //库存数量
    stockNum: '',
    //选择的规格
    specName: '',
    skuId: '',
    //sku弹出框
    skuShow: false,
    sku: '',
    //购买数量
    buyNum: 1,
    //限购数量
    maxBuy: '',
    currentnum: 0,
    selectObj: {}
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {
    const value = wx.getStorageSync('user_token');
    if (value) {
      console.log('已登录');
    } else {
      console.log('未登录')
      that.setData({
        loginShow: true,
      })
    }
    this.setData({
      goodsId: options.goodsId
    })

  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function() {
    this.loadData();
  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function(res) {
    if (res.from === 'button') {
      // 来自页面内转发按钮
      console.log(res.target)
    }
    return {
      title: '商品详情',
      path: 'package/pages/integral/goods/detail/detail?goodsId=' + this.data.goodsId
    }
  },
  loadData: function() {
    const that = this;
    console.log(that.data.goodsId);
    let postData = {
      'goods_id': that.data.goodsId
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_integralGoodsDetail, postData, header).then(res => {
      if (res.data.code == 1) {
        let min_price = res.data.data.goods_detail.min_price; //商品最小价格

        let good_Price;

        good_Price = min_price;

        let sku = res.data.data.goods_detail.sku;
        for (var i = 0; i < sku.tree.length; i++) { //给sku得tree添加一个未选中的属性
          for (var e = 0; e < sku.tree[i].v.length; e++) {
            sku.tree[i].v[e].isSelect = "false";
          }
        }

        let stock_num = 0; //库存数量
        let maxbuy = 0;
        for (var n = 0; n < sku.list.length; n++) {
          stock_num = stock_num + sku.list[n].stock_num;
          if (sku.list[n].hasOwnProperty('max_buy')) {
            maxbuy = sku.list[n].max_buy;
          }

        };

        that.setData({
          stockNum: stock_num,
          sku: sku,
          goodsPrice: parseFloat(good_Price).toFixed(2),
          goodsPoint: res.data.data.goods_detail.point_exchange,
          goods_detail: res.data.data.goods_detail,
          imgList: res.data.data.goods_detail.goods_images
        })
        var goodinfo = res.data.data.goods_detail.description;
        WxParse.wxParse('description', 'html', goodinfo, that);
      } else {
        wx.showModal({
          title: '提示',
          content: res.data.message,
          showCancel:false,
          success(res) {
            if (res.confirm) {
              wx.navigateBack();
            } 
          }
        })
      }
    })
  },
  imageLoad: function(e) { //获取图片真实宽度  
    var imgwidth = e.detail.width,
      imgheight = e.detail.height,
      //宽高比  
      ratio = imgwidth / imgheight;
    console.log(imgwidth, imgheight)
    //计算的高度值  
    var viewHeight = 750 / ratio;
    var imgheight = viewHeight;
    var imgheights = this.data.imgheights;
    //把每一张图片的对应的高度记录到数组里  
    imgheights[e.target.dataset.id] = imgheight;
    this.setData({
      imgheights: imgheights
    })
  },
  //商品图片加载出错，替换为默认图片
  imgError: function(e) {
    var errorImgIndex = e.target.dataset.id
    let imgList = this.data.imgList;
    imgList[errorImgIndex] = "/images/no-goods.png"
    this.setData({
      imgList: imgList
    })
  },
  //sku弹出层关闭
  skuOnclose: function() {
    this.setData({
      skuShow: false
    })
  },
  //点击选择规格，出现加入购物车和立即购买
  skuBtnShow: function() {
    this.setData({
      skuShow: true
    })
  },
  //规格属性的选择
  clickMenu: function(e) {
    const that = this;
    var selectIndex = e.currentTarget.dataset.selectIndex; //组的index
    var attrIndex = e.currentTarget.dataset.attrIndex; //当前的index
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

        if (sku.list[m].hasOwnProperty('max_buy')) {
          maxBuy = sku.list[m].max_buy
        } else {
          maxBuy = sku.list[m].stock_num
        }

        that.setData({
          stockNum: sku.list[m].stock_num,
        })

        let buyNum = '';
        if (that.data.buyNum != 1) {
          buyNum = that.data.buyNum
        } else {
          buyNum = 1
        }

        that.setData({
          specName: sku.list[m].sku_name,
          skuId: sku.list[m].id,
          buyNum: buyNum,
          goodsPrice: sku.list[m].price,
          goodsPonit: sku.list[m].point_exchange,
          maxBuy: maxBuy
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
  changeBuynum: function(e) {
    let stockNum = this.data.stockNum;
    if (e.detail == stockNum) {
      wx.showToast({
        title: '库存不足',
        icon: 'none'
      })
      setTimeout(function() {
        wx.hideToast()
      }, 1000)
    }
    this.setData({
      buyNum: e.detail
    })
  },
  //商品详情的选项卡
  checkCurrent: function(e) {
    const that = this;
    that.setData({
      currentnum: e.detail.index
    });

  },
  //底部下单按键
  onOrderBtn: function() {
    const that = this;
    if (that.ifLogin() == false) {
      return
    };
    if (that.hasPhoneFun() == false) {
      return
    }
    if (that.data.skuId == '') {
      that.setData({
        skuShow: true,
        sureBtn: 'order'
      })
    } else {
      that.buyNowOrder();
    }
  },
  //是否登录
  ifLogin: function() {
    const that = this;
    var userToken = wx.getStorageSync('user_token');
    var ifLogin = true;
    if (userToken == '') {
      wx.showToast({
        title: '您还未授权登录',
        icon: 'loading'
      })
      setTimeout(function() {
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
  hasPhoneFun: function() {
    const that = this;
    const have_mobile = wx.getStorageSync('have_mobile');
    if (have_mobile != true) {
      that.setData({
        phoneShow: true
      })
      return false
    }

  },
  //立即下单
  buyNowOrder: function() {
    const that = this;
    if (that.hasPhoneFun() == false) {
      return
    }
    let sku_id = '';
    // 商品没有规格
    if (that.data.sku.tree.length == 0) {
      sku_id = that.data.sku.list[0].id;
    } else {
      sku_id = that.data.skuId;
    }
    let skuObj = {
      sku_id: sku_id,
      num: that.data.buyNum,
    };
    if (sku_id == '') {
      wx.showToast({
        title: '请选择规格数量！',
        icon: 'loading'
      })
      return
    }

    let sku_list = [];
    sku_list.push(skuObj);
    let params = {
      sku_list: sku_list
    }



    params = Base64.encode(JSON.stringify(params));

    let onPageData = {
      url: '../../orderConfirm/confirm',
      num: 4,
      param: '?params=' + params
    }
    util.jumpPage(onPageData);
  },
  //跳转到积分商城首页
  onIntegralIndex: function() {
    let onPageData = {
      url: '../../index/index',
      num: 4,
      param: '',
    }
    util.jumpPage(onPageData);
  }
})