// miniprogram/package/pages/integral/goods/list/list.js
var requestSign = require('../../../../../utils/requestData.js');
var api = require('../../../../../utils/api.js').open_api;
var re = require('../../../../../utils/request.js');
var header = getApp().header;
var sortFlag = true; //防止重复点击
Page({
  data: {
    titleName: '商品列表',
    //页码
    page: 1,
    //分类id
    categoryId: '',
    orderActive: '',
    //兑换量排序
    saleSort: 'ASC',
    //积分排序
    pointSort: 'ASC',
    sort: '',
    shop_id: '',

    //商品列数据
    goodlist: [],
    noMore: 'false'
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {
    if (options.category_name) {
      wx.setNavigationBarTitle({
        title: options.category_name
      });
    } else if (options.search_text) {
      wx.setNavigationBarTitle({
        title: options.search_text
      });
    } else {
      wx.setNavigationBarTitle({
        title: this.data.titleName
      });
    }
    if (options.category_id == undefined && options.search_text == undefined){
      this.setData({
        categoryId: '' ,
        titleName:''
      })
    } else if (options.category_id){
      this.setData({
        categoryId: options.category_id,
        titleName: ''
      })
    } else if (options.search_text){
      this.setData({
        categoryId: '',
        titleName: options.search_text
      })
    }
    
    this.loadList();
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
    this.data.page = this.data.page + 1
    this.loadList();
  },
  loadList: function() {
    wx.showLoading({
      title: '加载中'
    }) 
    const that = this;
    let order = that.data.orderActive;
    let sort = that.data.sort;
    let postData = {
      'page_index': that.data.page,
      'page_size': 8,
      'order': order,
      'sort': sort ? sort : '',
      'search_text': that.data.titleName ? that.data.titleName : '',
      'shop_id': that.data.shop_id ? that.data.shop_id : '',
      'category_id': that.data.categoryId ? that.data.categoryId : ''
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_integralGoodsList, postData, header).then(res => {
      wx.hideLoading();
      sortFlag = true;
      if (res.data.code == 1) {
        var goodslist = that.data.goodlist;
        if (goodslist.length == 0) {
          goodslist = res.data.data.goods_list;
        } else {
          goodslist = goodslist.concat(res.data.data.goods_list); //滚动到底部把数据添加到原数组
        }

        this.setData({
          goodlist: goodslist,
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
    } else if (onOrder == 'point_exchange') {
      if (sort == 'ASC') {
        this.setData({
          pointSort: 'DESC',
          sort: 'DESC',
          page: 1
        })
      } else(
        this.setData({
          pointSort: 'ASC',
          sort: 'ASC',
          page: 1
        })
      )
    }
    this.data.page = 1;
    this.setData({
      noMore: 'false',
      goodlist:[]
    })
    if (!sortFlag) return;
    sortFlag = false;
    this.loadList();
    this.backTop();
  },
  // 滚动到顶部
  backTop: function () {
    // 控制滚动
    wx.pageScrollTo({
      scrollTop: 0
    })
  }

})