// miniprogram/package/pages/microshop/manage/set/set.js
Page({

  /**
   * 页面的初始数据
   */
  data: {
    microshop_logo: '',
    shopRecruitment_logo: ''
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {
    
  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function() {
    this.setData({
      microshop_logo: wx.getStorageSync("microshop_logo"),
      shopRecruitment_logo: wx.getStorageSync("shopRecruitment_logo")
    })
  },
  toInfo: function() {
    wx.navigateTo({
      url: '../info/info',
    })
  },
  toShopLogo:function(){
    wx.navigateTo({
      url: '../shoplogo/logo',
    })
  },
  toRecLogo:function(){
    wx.navigateTo({
      url: '../reclogo/logo',
    })
  }
})