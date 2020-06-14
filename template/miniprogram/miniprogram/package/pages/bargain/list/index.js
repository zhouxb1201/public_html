var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    page_index:1,
    page_size:20,
    bargain_list:'',
    shop_id:'',
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    let shop_id = options.shop_id;
    if (shop_id != undefined){
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
    that.getBargainList().then((res)=>{
      if (res.data.code >= 0) {
        that.setData({
          bargain_list: res.data.data.bargain_list
        })

      } else {
        wx.showToast({
          title: res.data.message,
          icon: 'none'
        })
      }
    });
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
    that.getBargainList().then((res)=>{
      if (res.data.code >= 0) {
        let bargain_list = that.data.bargain_list;
        bargain_list = bargain_list.concat(res.data.data.bargain_list);
        that.setData({
          bargain_list: bargain_list
        })

      } else {
        wx.showToast({
          title: res.data.message,
          icon: 'none'
        })
      }
    });
  },

  /**
   * 砍价商品列表
   */
  getBargainList: function () {
    const that = this;
    let postData = {
      'page_index': that.data.page_index,
      'page_size': that.data.page_size,      
    };
    
    if (that.data.shop_id != ''){
      postData.shop_id = that.data.shop_id;
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    return new Promise((resolve,reject) =>{
      wx.request({
        url: api.get_getBargainList,
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