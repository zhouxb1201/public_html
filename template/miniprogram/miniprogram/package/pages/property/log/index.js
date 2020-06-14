var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    //余额明细列表
    balancelist:'',
    page_index:1,
    noMore:'hiddren',
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    this.getBalanceLog();
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
    this.data.page_index = this.data.page_index + 1;
    this.getBalanceLog();
  },
  
  getBalanceLog:function(){
    const that = this;
    let postData = {
      'page_index': that.data.page_index
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_balancewater,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {     
          let balancelist = that.data.balancelist;
          if (balancelist.length == 0){            
            balancelist = res.data.data.data;
          }else{
            balancelist = balancelist.concat(res.data.data.data);
          }
          that.setData({
            balancelist: balancelist,
          })
          if (res.data.data.data.length == 0){
            that.setData({
              noMore:'',
            })
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

  //跳到余额详情
  onlogDetailPage:function(e){
    let id = e.currentTarget.dataset.id;    
    wx.navigateTo({
      url: '../logdetail/index?id='+id,
    })
  }
  
})