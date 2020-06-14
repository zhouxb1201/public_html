var requestSign = require('../../utils/requestData.js');
var api = require('../../utils/api.js').open_api;
var re = require('../../utils/request.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    clientHeight:0,
    //一级分类
    first_category:'',
    //二级分类
    second_category:'',
    //三级分类
    third_category:'',
    itemIndex:0,
    //当前的明细数据
    now_detail:'',
    
    
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    //获取滚动条可滚动高度
    wx.getSystemInfo({
      success: (res) => {
        this.setData({
          clientHeight: res.windowHeight - res.windowWidth / 750 * 96
        });
      }
    });

    this.getCategoryList();
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

  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {

  },

  //请求商品分类数据  
  getCategoryList: function () {
    wx.showLoading({
      title: '加载中',
    })
    const that = this;
    let order = that.data.orderActive;
    let sort = that.data.sort;
    let postData = {}
    
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_categoryInfo, postData, header).then((res) => {
      wx.hideLoading();
      that.setData({
        first_category: res.data.data,
      }) 
    })    
  },

  //第一分类切换
  navFirstChange:function(e){
    const that = this;
    let itemId = e.currentTarget.dataset.id;
    that.setData({
      itemIndex: e.currentTarget.dataset.index,
    })    
  },

  onGoodsListPage:function(e){
    const that = this;
    let category_id = e.currentTarget.dataset.categoryid;
    let category_name = e.currentTarget.dataset.categoryname;
    wx.navigateTo({
      url: '/pages/goodlist/index?category_id=' + category_id + '&category_name=' + category_name,
    })
  }

  
})