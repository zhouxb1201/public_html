// miniprogram/package/pages/prize/result/result.js
Page({

  /**
   * 页面的初始数据
   */
  data: {
    publicUrl: getApp().publicUrl
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {

  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function (res) {
    if (res.from === 'button') {
      // 来自页面内转发按钮
      console.log(res.target)
    }
    
    return {
      title: '领取成功',
      path: 'package/pages/prize/result/result'
    }
  },
  onBack:function(){
    wx.navigateBack({
      url: '../list/list',
    })
  }
})