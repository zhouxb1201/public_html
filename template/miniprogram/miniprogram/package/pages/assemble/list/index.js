var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    page_index: 1,
    //拼团列表
    group_shopping_list:'',
    shop_id:'',
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    let shop_id = options.shop_id;
    if (shop_id != undefined) {
      this.data.shop_id = shop_id;
    }
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
    that.groupShoppingList().then((res) =>{
      if(res.data.code > 0){
        that.setData({
          group_shopping_list: res.data.data.group_shopping_list
        })
      }else{
        wx.showToast({
          title: res.data.message,
          icon:'none'
        })
      }
    })
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
    that.data.page_index = that.data.page_index +1;
    that.groupShoppingList().then((res) =>{
      if(res.data.code >=0){
        let group_shopping_list = that.data.group_shopping_list;
        group_shopping_list = group_shopping_list.concat(res.data.data.group_shopping_list);
        that.setData({
          group_shopping_list: group_shopping_list
        })
      }else{
        wx.showToast({
          title: res.data.message,
          icon:'none',
        })
      }
    })
  },

  /**
   * 拼团商品列表
   */
  groupShoppingList: function () {
    const that = this;
    let postData = {
      'page_index': that.data.page_index,
      'page_size': 20,
    }
    if (that.data.shop_id != '') {
      postData.shop_id = that.data.shop_id;
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    return new Promise((resolve, reject) => {
      wx.request({
        url: api.get_groupShoppingListForWap,
        data: postData,
        header: header,
        method: 'POST',
        dataType: 'json',
        responseType: 'text',
        success: (res) => {
          resolve(res);
        },
        fail: (res) => { },
      })
    })
  },

})