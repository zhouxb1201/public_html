var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    //购买类型 purchase：采购操作，pickupgoods：自提操作
    buy_type:'',
    clientHeight: 0,
    itemIndex: 0,
    //分类列表
    category_list: '',
    //分类id
    category_id: '',
    page_index: 1,
    search_text: '',
    //商品列表
    goods_list: '',
    //sku弹框显示
    skuShow: false,
    sku: '',
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
    //商品归属类型
    channel_goods_type: '',
    //购物车商品
    cart_list: [],
    //购物车商品数量
    cart_goods_num: 0,
    //购物车商品总价格
    cart_total_money: 0.00,
    //最小采购金额
    lowest_purchase_money:0,
    //购物车是否显示
    cartShow: false,
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    const that = this;
    that.setData({
      buy_type: options.buy_type
    })
    if (options.buy_type == 'purchase'){
      wx.setNavigationBarTitle({
        title: '采购商品',
      })
    } else if (options.buy_type == 'pickupgoods' ){
      wx.setNavigationBarTitle({
        title: '提货商品',
      })
    }
    
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
    const that = this;
    that.getChannelGoodsCategoryList();
    that.getChannelCartGoodsInfo();
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
    that.data.page_index = that.data.page_index + 1;
    that.getChannelGradeGoods();
  },

  //获取分类
  getChannelGoodsCategoryList: function () {
    const that = this;
    let postData = {
      'buy_type': that.data.buy_type
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getChannelGoodsCategoryList,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          that.setData({
            category_list: res.data.data.category_list,
            category_id: res.data.data.category_list[0].category_id
          })

          that.getChannelGradeGoods();
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

  //根据分类id获取商品列表
  getChannelGradeGoods: function () {
    const that = this;
    let postData = {
      'page_index': that.data.page_index,
      'page_size': 20,
      'buy_type': that.data.buy_type,
      'category_id': that.data.category_id,
      'search_text': that.data.search_text,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getChannelGradeGoods,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          if (that.data.page_index >= 2) {
            let old_goods_list = that.data.goods_list;
            let new_goods_list = old_goods_list.concat(that.goodStockNumFun(res.data.data.goods_list));
            that.setData({
              goods_list: new_goods_list
            })
          } else {
            that.setData({
              goods_list: that.goodStockNumFun(res.data.data.goods_list)
            })
          }

          if (that.data.page_index < 2) {
            that.data.page_index = that.data.page_index + 1;
            that.getChannelGradeGoods()
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

  //计算商品库存数（库存数为每个sku库存数量相加）
  goodStockNumFun: function (goods_list) {
    const that = this;
    for (let item of goods_list) {
      let total_stock = 0;
      for (let value of item.sku.list) {
        total_stock = total_stock + value.stock_num
      }
      item.total_stock = total_stock
    }

    return goods_list
  },

  //查询商品
  searchFun: function (e) {
    const that = this;
    that.data.page_index = 1;
    that.setData({
      search_text: e.detail.value,
    })
    that.getChannelGradeGoods();
  },

  navChange: function (e) {
    const that = this;
    that.data.page_index = 1;
    that.setData({
      category_id: e.currentTarget.dataset.id,
      itemIndex: e.currentTarget.dataset.index,
      goods_list: '',
    });
    that.getChannelGradeGoods();
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
    let goodsImg = goodItemData.img_list[0];
    let goodsName = goodItemData.goods_name;
    let sku = goodItemData.sku;
    let sku_good_price = goodItemData.min_price;
    let sku_stock_num = goodItemData.total_stock;
    let channel_goods_type = goodItemData.channel_info;

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
    

    that.setData({
      goodsImg: goodsImg,
      sku: sku,
      goodsName: goodsName,
      sku_good_price: sku_good_price,
      sku_stock_num: sku_stock_num,
      channel_goods_type: channel_goods_type,
    })
    that.skuShow();
  },

  //判断变量是否在数组中
  isInArray: function (result,id){    
    var testStr = ',' + result.join(",") + ",";
    return testStr.indexOf("," + id + ",") != -1; 
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
    }
    this.setData({
      buyNum: e.detail
    })
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

  //添加购物车
  addChannelCart: function () {
    const that = this;
    if (that.data.sku.tree.length == 0) {
      that.data.skuId = that.data.sku.list[0].id;
    } else {
      if (that.data.skuId == '') {
        wx.showToast({
          title: '请选择规格！',
          icon: 'none',
        })
        return;
      }
    }

    let postData = {
      'sku_id': that.data.skuId,
      'num': that.data.buyNum,
      'channel_goods_type': that.data.channel_goods_type,
      'buy_type': that.data.buy_type,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_addChannelCart,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 0) {
          wx.showToast({
            title: '加入购物车成功！',
            icon: 'success',
          })
          that.skuOnclose();
          that.getChannelCartGoodsInfo();
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

  //获取购物车商品列表
  getChannelCartGoodsInfo: function () {
    const that = this;
    wx.showLoading({
      title: '加载中',
    })
    let postData = {
      'page_index': 1,
      'page_size': 20,
      'buy_type': that.data.buy_type,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getChannelCartGoodsInfo,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        wx.hideLoading();
        if (res.data.code == 0) {
          let cart_list = res.data.data.cart_list;
          let cart_goods_num = 0;
          for (let item of cart_list) {
            cart_goods_num += parseInt(item.num);
            item.num = parseInt(item.num);
          }
          that.setData({
            cart_goods_num: cart_goods_num,
            cart_list: cart_list,
            cart_total_money: parseFloat(res.data.data.total_money).toFixed(2),
            lowest_purchase_money: res.data.data.lowest_purchase_money,
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

  //修改渠道商购物车商品数量
  channelCartAdjustNum: function (e) {
    const that = this;
    let channel_info = e.currentTarget.dataset.channelinfo;
    let sku_id = e.currentTarget.dataset.skuid;
    let num = e.detail;
    let postData = {
      'sku_id': sku_id,
      'num': num,
      'channel_info': channel_info,
      'buy_type': that.data.buy_type,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_channelCartAdjustNum,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 0) {
          that.getChannelCartGoodsInfo();
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

  //删除渠道商购物车商品
  deleteChannelCart: function (e) {
    const that = this;
    let sku_id = e.currentTarget.dataset.skuid;

    wx.showModal({
      title: '提示',
      content: '确定删除该商品？',
      success(res) {
        if (res.confirm) {
          let postData = {
            'sku_id': sku_id,
            'buy_type': that.data.buy_type,
          }
          let datainfo = requestSign.requestSign(postData);
          header.sign = datainfo;
          wx.request({
            url: api.get_deleteChannelCart,
            data: postData,
            header: header,
            method: 'POST',
            dataType: 'json',
            responseType: 'text',
            success: (res) => {
              if (res.data.code == 0) {
                that.getChannelCartGoodsInfo();
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

  //跳转到确认订单页
  orderConfirm(){
    const that = this;    
    if (that.data.lowest_purchase_money > that.data.cart_total_money){
      wx.showToast({
        title: '最小采购金额为:' + that.data.lowest_purchase_money + '元',
        icon:'none',
      })
      return;
    }
    wx.navigateTo({
      url: '../order/confirm/index?buy_type=' + that.data.buy_type ,
    })
  }



})