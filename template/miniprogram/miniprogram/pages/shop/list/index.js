var requestSign = require('../../../utils/requestData.js');
var api = require('../../../utils/api.js').open_api;
var util = require('../../../utils/util.js');
var re = require('../../../utils/request.js');
var header = getApp().header;
const app = getApp();
Page({

  /**
   * 页面的初始数据
   */
  data: {
    shopList: '',
    //页码
    page: 1,
    sort: '',
    //排序字段
    navActive: '',
    //没有更多的标识
    noMore: false,
    //搜索的店铺
    searchShop: '',
    //排序字段-销量
    saleNum: 'DESC',
    //排序字段-人气（收藏数量）
    shopCollect: 'DESC',
    //排序字段-评分
    shopCredit: "DESC",
    //排序（ASC,DESC）
    sort: '',
    //系统报错显示
    errorFail: false,

    //店铺收搜关键字
    shop_value:'',
    //系统配置中
    config_store: 0,
    //店铺类别弹框显示
    shopTypeShow:false,
    //店铺类别id
    shop_group_id:'',
    shop_group_name:'全部'

  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
        
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
    if (getApp().globalData.searchSign == 'shop') {
      that.data.page = 1;
      that.setData({
        shop_value: getApp().globalData.searchShopKey
      })
    }
    that.getShopsList();
    that.shopgroup();
    that.setData({
      config_store: app.globalData.config.addons.store
    })
  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function () {
    getApp().globalData.searchShopKey = ''
  },

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function () {
    getApp().globalData.searchShopKey = ''
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
    this.data.page = this.data.page + 1
    this.getShopsList();
  },

  //请求店铺列表数据  
  getShopsList: function () {
    wx.showLoading({
      title: '加载中',
    })
    const that = this;    
    let order = that.data.navActive;
    let sort = that.data.sort;
    let searchText = '';
    if (that.data.shop_value != '') {
      searchText = that.data.shop_value;
    }
    let postData = {
      'page_index': that.data.page,
      'search_text': searchText,
    }
    if (order != '') {
      postData.order = order;
      postData.sort = that.data.sort;
    }

    if (that.data.shop_group_id != ''){
      postData.shop_group_id = that.data.shop_group_id 
    }

    let datainfo = requestSign.requestSign(postData);

    header.sign = datainfo;
    re.request(api.get_shopSearch, postData, header).then((res) =>{
      wx.hideLoading();
      if (res.data.code >= 0) {
        let shopList = res.data.data.shop_list;
        if (that.data.page > 1) {
          let oldShopList = that.data.shopList;
          let newShopList = oldShopList.concat(shopList);
          that.setData({
            shopList: newShopList
          })
        } else if (that.data.page == 1) {
          that.setData({
            shopList: shopList
          })
        }
        if (shopList.length == 0) {
          that.setData({
            noMore: true
          })
        }
        that.setData({
          errorFail: false
        })
      } else {
        wx.showToast({
          title: res.data.message,
          icon: 'none'
        })
        that.setData({
          errorFail: true,
          shopList:[],
          shop_value:'',
          page:1,
        })
      }
    })
   
  },
  //跳转到搜索页
  onSearchPage: function () {    
    let onPageData = {
      url: '../../search/index',
      num: 4,
      param: '?searchKey=shop',
    }
    util.jumpPage(onPageData);
  },
  //切换顶部的导航
  changeSort: function (e) {
    const that = this;
    let order = e.currentTarget.dataset.order;
    let sort = e.currentTarget.dataset.sort;
    that.setData({
      navActive: order
    });
    if (order == 'sale_num') {
      if (sort == 'DESC') {
        that.setData({
          saleNum: 'ASC',
          sort: 'ASC'
        })
      } else {
        that.setData({
          saleNum: 'DESC',
          sort: 'DESC'
        })
      }
    }
    if (order == 'shop_collect') {
      if (sort == 'DESC') {
        that.setData({
          shopCollect: 'ASC',
          sort: 'ASC'
        })
      } else {
        that.setData({
          shopCollect: 'DESC',
          sort: 'DESC'
        })
      }
    }
    if (order == 'comprehensive') {
      if (sort == 'DESC') {
        that.setData({
          shopCredit: 'ASC',
          sort: 'ASC'
        })
      } else {
        that.setData({
          shopCredit: 'DESC',
          sort: 'DESC'
        })
      }
    }
    that.data.page = 1;
    that.data.shopList = [];
    that.getShopsList();
    that.backTop();
    that.shopTypeOnclose();
  },

  // 滚动到顶部
  backTop: function () {
    // 控制滚动
    wx.pageScrollTo({
      scrollTop: 0
    })
  },
  //跳转到商品页
  ongoodDetail: function (e) {
    const that = this;
    let goodId = e.currentTarget.dataset.goodid;
    
    let onPageData = {
      url: '../../goods/detail/index',
      num: 4,
      param: '?goodsId=' + goodId,
    }
    util.jumpPage(onPageData);
  },

  //收搜店铺
  searchShop:function(e){
    const that = this;
    let shop_value = e.detail.value;
    that.data.shop_value = shop_value;
    that.data.page = 1
    that.getShopsList();
  },

  //重置搜索关键字
  resetSearchkey:function(){
    const that = this;
    that.setData({
      shop_value:'',
      shop_group_id:'',
      shop_group_name:'全部'
    })    
    that.data.page = 1
    that.getShopsList();
  },

  //附近门店
  allStoreFun:function(){
    const that = this;
    let onPageData = {
      url: '/package/pages/store/list/index',
      num: 4,
      param: '',
    }
    util.jumpPage(onPageData);
  },

  //店铺类型弹框关闭
  shopTypeOnclose:function(){
    const that = this;
    that.setData({
      shopTypeShow:false
    })
  },

  //店铺类型弹框开启
  shopTypeOnshow: function (e) {
    const that = this;
    let order = e.currentTarget.dataset.order;    
    that.setData({
      navActive: order,
      shopTypeShow: true
    })
  },

  //店铺分类选择
  selectShopType:function(e){
    const that = this;
    let shop_group_id = e.currentTarget.dataset.shopgroupid;
    let shop_group_name = e.currentTarget.dataset.shopgroupname;
    that.setData({
      shop_group_id: shop_group_id,
      shop_group_name: shop_group_name
    })
    that.data.page = 1;
    that.getShopsList();
    that.shopTypeOnclose();
    that.backTop();
  },

  //获取店铺类型列表
  shopgroup: function () {
    const that = this;
    var postData = {};
    let header = getApp().header;
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    wx.request({
      url: api.get_shopgroup,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 1) {
          that.setData({
            shopTypeArray: res.data.data.shop_group_list
          })
        } else {
          wx.showModal({
            title: '提示',
            content: res.data.message,
            showCancel: false,
          })
        }
      },
      fail: (res) => { },
    })
  },

})