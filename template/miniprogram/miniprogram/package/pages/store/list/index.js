var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var util = require('../../../../utils/util.js');
var header = getApp().header;
var re = require('../../../../utils/request.js');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    store_list:'',
    page_index:1,
    page_size:20,
    sales:'DESC',
    navActive:'',
    sort:'',
    search_text:'',
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    this.getUserLocation();
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
    that.data.page_index += 1;
    that.getAllStoreListForWap();
  },

  //切换顶部的导航
  changeSort: function (e) {
    const that = this;
    let order = e.currentTarget.dataset.order;
    let sort = e.currentTarget.dataset.sort;
    that.setData({
      navActive: order
    });
    if (order == 'sales') {
      if (sort == 'DESC') {
        that.setData({
          sales: 'ASC',
          sort: 'ASC'
        })
      } else {
        that.setData({
          sales: 'DESC',
          sort: 'DESC'
        })
      }
    }
    if (order == 'distance') {
      if (sort == 'DESC') {
        that.setData({
          distance: 'ASC',
          sort: 'ASC'
        })
      } else {
        that.setData({
          distance: 'DESC',
          sort: 'DESC'
        })
      }
    }
    if (order == 'score') {
      if (sort == 'DESC') {
        that.setData({
          score: 'ASC',
          sort: 'ASC'
        })
      } else {
        that.setData({
          score: 'DESC',
          sort: 'DESC'
        })
      }
    }
    that.data.page_index = 1;
    that.data.store_list = [];
    that.getAllStoreListForWap();
    that.backTop();    
  },

  backTop(){
    wx.pageScrollTo({
      scrollTop: 0,
    })
  },

  searchKeyFun:function(e){
    const that = this;
    that.data.page_index = 1;
    that.data.search_text = e.detail.value;
    that.getAllStoreListForWap();
  },

  // 获取平台下所有门店列表
  getAllStoreListForWap:function(){
    const that = this;
    let postData = {
      'page_index': that.data.page_index,
      'page_size': that.data.page_size,
      'lng': that.data.lng,
      'lat': that.data.lat,
      'sort': that.data.sort,
      'order': that.data.navActive,
      'search_text': that.data.search_text,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_getAllStoreListForWap, postData, header).then((res) => {      
      if(res.data.code == 1){
        if(that.data.page_index > 1){
          let oldData = that.data.store_list;
          let newData = oldData.concat(res.data.data.store_list);
          that.setData({
            store_list: newData
          })
        }else{
          that.setData({
            store_list: res.data.data.store_list
          })
        }
        
      }else{
        wx.showModal({
          title: '提示',
          content: res.data.message,
        })
      }
    })
  },

  //获取当前位置的经纬度
  getUserLocation: function () {
    const that = this;    
    wx.getLocation({
      type: 'gcj02',
      success: function (res) {
        //纬度，范围为 -90~90，负数表示南纬
        const latitude = res.latitude
        //经度，范围为 -180~180，负数表示西经
        const longitude = res.longitude
        that.setData({
          lng: longitude,
          lat: latitude
        })
        that.getAllStoreListForWap();
      },
    })
  },

  

  //跳转到门店首页
  onstoreHomePage:function(e){
    const that = this;
    let store_id = e.currentTarget.dataset.storeid;
    let obj = {
      'lat': that.data.lat,
      'lng': that.data.lng,
      'store_id': store_id,
    }
    obj = JSON.stringify(obj);
    let onPageData = {
      url: '../home/index',
      num: 4,
      param: '?obj=' + obj,
    }
    util.jumpPage(onPageData);
  },

  //跳转到商品详情页
  onGoodDetailPage:function(e){
    const that = this;
    let goods_id = e.currentTarget.dataset.goodid;
    let onPageData = {
      url: '/pages/goods/detail/index',
      num: 4,
      param: '?goodsId=' + goods_id,
    }
    util.jumpPage(onPageData);
  },
})