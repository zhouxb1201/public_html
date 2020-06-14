var WxParse = require('../../../../../common/wxParse/wxParse.js');
var requestSign = require('../../../../../utils/requestData.js');
var api = require('../../../../../utils/api.js').open_api;
var util = require('../../../../../utils/util.js');
var re = require('../../../../../utils/request.js');
var header = getApp().header;
import regeneratorRuntime from '../../../../../common/regenerator-runtime/runtime.js';
var Base64 = require('../../../../../utils/base64.js').Base64;
import {
  base64src
} from '../../../../../utils/base64src.js'
var goodsParams = {
  goods_id: null,
  mic_goods: 1
}
Component({
  /**
   * 组件的属性列表
   */
  properties: {
    pageType: {
      type: Number,
      value: 2
    },
    info: Object,
    goods: Array
  },

  /**
   * 组件的初始数据
   */
  data: {
    publicUrl: getApp().publicUrl,

    indicatorDots: false, //是否显示画板指示点

    indicatorcolor: "#000", //选中点的颜色 

    vertical: false, //是否竖直

    autoplay: true, //是否自动切换 

    interval: 2500, //自动切换的间隔

    duration: 100, //滑动动画时长毫秒 

    imgheights: [], //所有图片的高度 

    imgwidth: 750, //图片宽度 

    imgcurrent: 0, //默认  

    imgList: [''], //图片地址


    cols: [{
        icon: "shop2",
        text: "专属微店"
      },
      {
        icon: "balance2",
        text: "自购返利"
      },
      {
        icon: "balance1",
        text: "销售返利"
      },
      {
        icon: "money",
        text: "开店返利"
      }
    ],

    // 商品明细
    goods_detail: {},

    gradeInfo: {}, //等级信息
    active: 0,
    gradeFlag: true,

    goods_list: [],


    stockNum: '', //库存数量
    goodsPrice: '', //商品价格

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
  lifetimes: {
    attached: function() {
      if (this.properties.pageType == 3) {
        this.getRenew();
      } else if (this.properties.pageType == 4) {
        this.getUpGrade();
      } else {
        let g = [];
        for (let i = 0; i < this.properties.goods.length; i++) {
          g.push(this.properties.goods[i].goods_id);
        }
        this.getGoodsInfo(g);
      }

    }
  },
  /**
   * 组件的方法列表
   */
  methods: {
    imageLoad(e) { //获取图片真实宽度  
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
    imgError(e) {
      var errorImgIndex = e.target.dataset.id
      let imgList = this.data.imgList;
      imgList[errorImgIndex] = "/images/no-goods.png"
      this.setData({
        imgList: imgList
      })
    },
    getRenew() { //立即续费
      const that = this;
      let g = [];
      let postData = {};
      let datainfo = requestSign.requestSign(postData);
      header.sign = datainfo;
      re.request(api.get_micRenew, postData, header).then((res) => {
        if (res.data.code == 0) {
          that.setData({
            gradeInfo: res.data.data
          })
          g = res.data.data.goods_id;
          that.getGoodsInfo(g);
        }
      })
    },
    getUpGrade() { //立即升级
      const that = this;
      let g = [];
      let postData = {};
      let datainfo = requestSign.requestSign(postData);
      header.sign = datainfo;
      re.request(api.get_micUpGrade, postData, header).then((res) => {
        if (res.data.code == 0) {
          that.setData({
            gradeInfo: res.data.data
          })
          g = res.data.data[0].goods_id;
          that.getGoodsInfo(g);
        }

      })
    },
    getItem(e) { //切换等级
      const that = this;
      const target = e.currentTarget.dataset;
      if (that.data.active === target.index) {
        //防止重复点击等级重复提交请求接口
        return false;
      }
      console.log(that.data.active);
      if (!that.data.gradeFlag) return;
      that.setData({
        gradeFlag: false,
        active: target.index,
      })
      let g = that.data.gradeInfo[target.index].goods_id;
      that.getGoodsInfo(g);
    },
    getGoodsInfo(g) {
      let goods_id = null;
      let goodslist = [];
      const that = this;
      for (let i = 0; i < g.length; i++) {
        goods_id = g[i];
        goodsParams.goods_id = goods_id;
        try {
          that.getGoodsDetail(goodsParams, goodslist);
        } catch (e) {
          console.log(e);
        }
      }
    },
    async getGoodsDetail(params, goodslist) {
      const that = this;
      let postData = params || {};
      let datainfo = requestSign.requestSign(postData);
      header.sign = datainfo;
      await re.request(api.get_goodsDetail, postData, header).then((res) => {
        if (res.data.code == 0) {
          goodslist.push(res.data.data.goods_detail);
          that.setData({
            goods_list: goodslist
          })
        }
      })
      that.setData({
        goods_detail: goodslist[0],
        imgList: goodslist[0].goods_images,
        gradeFlag: true
      })
      goodsParams.goods_id = null;
      that.goodsSku();
    },
    onGoods(e) {
      const that = this;
      const target = e.currentTarget.dataset;
      if (goodsParams.goods_id === target.goodsid) {
        //防止重复点击等级商品重复提交请求接口
        return false;
      }
      goodsParams.goods_id = target.goodsid;
      that.setData({
        goods_detail: that.data.goods_list[target.index],
        imgList: that.data.goods_list[target.index].goods_images
      })
      that.goodsSku();
    },
    goodsSku() {
      const that = this;
      let min_price = that.data.goods_detail.min_price; //商品最小价格
      let good_Price;
      good_Price = min_price;
      let sku = that.data.goods_detail.sku;
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
        maxBuy: maxbuy
      })
    },
    //sku弹出层关闭
    skuOnclose() {
      this.setData({
        skuShow: false
      })
    },
    //点击选择规格，出现加入购物车和立即购买
    skuBtnShow() {
      this.setData({
        skuShow: true
      })
    },
    //规格属性的选择
    clickMenu(e) {
      const that = this;
      var selectIndex = e.currentTarget.dataset.selectIndex; //组的index
      var attrIndex = e.currentTarget.dataset.attrIndex; //当前的index
      var sku = that.data.sku;
      var spec = sku.tree;

      for (let i = 0; i < spec.length; i++) {
        for (let n = 0; n < spec[i].v.length; n++) {
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


      for (let m = 0; m < sku.list.length; m++) {
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
    changeBuynum(e) {
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
    buyNowOrder(){
      const that = this;
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
        sku_list: sku_list,
        order_type: that.properties.pageType,
      }
      params = Base64.encode(JSON.stringify(params));
      
      let onPageData = {
        url: '../orderConfirm/confirm',
        num: 4,
        param: '?params=' + params
      }
      util.jumpPage(onPageData);
      
    }
  }

})