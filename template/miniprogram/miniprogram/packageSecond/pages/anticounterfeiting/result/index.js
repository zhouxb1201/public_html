var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    resultData:'',
    steps:'',
    upSrc:'',
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    this.data.anti_code = options.anti_code
    this.searchAnticounterfeiting();
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

  //查询防伪码
  searchAnticounterfeiting: function () {
    const that = this;
    wx.showLoading({
      title: '加载中',
    })
    let postData = {
      'anti_code': that.data.anti_code,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_searchAnticounterfeiting,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        wx.hideLoading();
        if (res.data.code >= 0) {
          that.setData({
            resultData:res.data.data,
            steps: res.data.data.batch_trace,
            upSrc: res.data.data.chain_url
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


  onGoodPage(){
    wx.navigateTo({
      url: '/pages/goods/detail/index?goodsId=' + this.data.resultData.goods_id,
    })
  },

  //跳转到上链结果页面
  onOtherPage(){
    wx.navigateTo({
      url: '../otherresult/index?upSrc=' + this.data.upSrc,
    })    
  },
})