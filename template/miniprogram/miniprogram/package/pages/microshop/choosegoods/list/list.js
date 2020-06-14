var requestSign = require('../../../../../utils/requestData.js');
var api = require('../../../../../utils/api.js').open_api;
var util = require('../../../../../utils/util.js');
var re = require('../../../../../utils/request.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    //页面标题
    titleName: '商品列表',
    //分类id
    categoryId: '',
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
    shop_id: '',

    selected: [],
    selectDisabled: false,
    delDisabled: false
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {
    wx.showLoading({
      title: '加载中'
    })
    let categoryId = '';
    let titleName = '';
    if (options.category_id != undefined) {
      categoryId = options.category_id
    }

    if (options.key != undefined) {
      titleName = options.key
      console.log(options.key);
    }
    if (options.shop_id != undefined) {
      this.data.shop_id = options.shop_id;
    }

    this.setData({
      categoryId: categoryId,
      titleName: titleName
    })
    this.getGoodsList();
    wx.hideLoading();

    if (options.category_name != undefined) {
      wx.setNavigationBarTitle({
        title: options.category_name
      });
    } else {
      wx.setNavigationBarTitle({
        title: this.data.titleName
      });
    }

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

  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function() {
    this.data.page = this.data.page + 1;
    if (this.data.noMore == 'false') {
      this.getGoodsList();
    }
  },


  //请求商品列表数据  
  getGoodsList: function() {

    const that = this;
    let order = that.data.orderActive;
    let sort = that.data.sort;
    let searchText = '';
    if (that.data.titleName != '') {
      searchText = that.data.titleName;
    }
    let postData = {
      'page_index': that.data.page,
      'page_size': 20,
      'order': order,
      'sort': sort,
      'min_price': that.data.minPrice,
      'max_price': that.data.maxPrice,
      'search_text': searchText,
      'is_shipping_free': that.data.free_shipping,
      'is_new': that.data.new_goods,
      'is_recommend': that.data.recommend_goods,
      'is_hot': that.data.hot_goods,
      'is_promotion': that.data.promotion_goods,
      'shop_id': that.data.shop_id,
      'microshop_type': 1
    }

    if (that.data.categoryId != '') {
      postData.category_id = that.data.categoryId
    }
    let datainfo = requestSign.requestSign(postData);

    header.sign = datainfo
    wx.request({
      url: api.get_goodsList,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          var goodslist = that.data.goodlist;
          if (goodslist.length == 0) {
            goodslist = res.data.data.goods_list;
          } else {
            goodslist = goodslist.concat(res.data.data.goods_list); //滚动到底部把数据添加到原数组
          }
          let selected = [];
          for (let i = 0; i < goodslist.length; i++) {
            selected.push(goodslist[i].mic_selectedgoods);
          }
          this.setData({
            goodlist: goodslist,
            selected: selected
          })

          if (res.data.data.total_count == that.data.goodlist.length) {
            this.setData({
              noMore: 'true'
            })
          }
          this.pupupRightClose();
        } else {
          wx.showModal({
            title: '提示',
            content: res.data.message,
            showCancel: false,
          })
        }

      },
      fail: (res) => {},
    })
  },



  //顶部选项卡
  changeSort: function(e) {
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
      } else(
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
      } else(
        this.setData({
          priceSort: 'ASC',
          sort: 'ASC',
          page: 1
        })
      )
    }
    this.data.page = 1;
    this.data.goodlist = [];
    this.getGoodsList();
    this.backTop();
  },

  // 滚动到顶部
  backTop: function() {
    // 控制滚动
    wx.pageScrollTo({
      scrollTop: 0
    })
  },

  //右边弹框显示
  pupupRightShow: function() {
    this.setData({
      rightShow: true
    })
  },
  //右边弹框关闭
  pupupRightClose: function() {
    this.setData({
      rightShow: false
    })
  },
  //新品/包邮
  checkedBool: function(e) {
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
  minPrice: function(e) {
    this.setData({
      minPrice: e.detail.value
    })
  },
  //最高价
  maxPrice: function(e) {
    this.setData({
      maxPrice: e.detail.value
    })
  },



  //重置
  resetData: function() {
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

  //筛选
  conditionGoods: function() {
    const that = this;
    that.data.page = 1;
    that.data.goodlist = [];
    that.getGoodsList();
    that.backTop();
  },
  //挑选
  selectGoods: function(e) {
    let event = e.currentTarget.dataset;
    const that = this;
    that.setData({
      selectDisabled: true
    })
    let postData = {
      goods_id: event.goodsid
    };
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_micSelectGoods, postData, header).then(res => {
      if (res.data.code >= 0) {
        let selectedIndex = "selected[" + event.index + "]";
        that.setData({
          [selectedIndex]: 1,
          selectDisabled: false
        })
      }
    })
  },
  //取消
  delGoods: function(e) {
    let event = e.currentTarget.dataset;
    const that = this;
    that.setData({
      delDisabled: true
    })
    let postData = {
      goods_id: event.goodsid
    };
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_micDelGoods, postData, header).then(res => {
      if (res.data.code >= 0) {
        let selectedIndex = "selected[" + event.index + "]";
        that.setData({
          [selectedIndex]: 0,
          delDisabled: false
        })
      }
    })
  }


})