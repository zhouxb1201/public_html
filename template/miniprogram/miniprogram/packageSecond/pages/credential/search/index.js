var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    pageShow:false,
    banner_img:'',
    logo:'',
    img_link:'',
    cred_no:'',
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    this.searchUserCredentialPage();
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

  //查询证书页
  searchUserCredentialPage: function () {
    const that = this;
    wx.showLoading({
      title: '加载中',
    })
    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_searchUserCredentialPage,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        wx.hideLoading();
        that.setData({
          pageShow:true,
        })        
        if (res.data.code === 0) {
          that.setData({
            banner_img:res.data.data.banner_list.img_path,
            logo: res.data.data.logo,
            img_link: res.data.data.banner_list.img_link,
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

  onlinkpage(){
    wx.navigateTo({
      url: this.data.img_link,      
    })
  },

  credNo(e){
    const that = this;
    that.setData({
      cred_no: e.detail.value
    })
  },

  searchPage:function(){
    if (this.data.cred_no == ''){
      wx.showToast({
        title: '证书编号不能为空',
        icon:'none'
      })
      return;
    }
    wx.navigateTo({
      url: '../detail/index?cred_no=' + this.data.cred_no,
    })
  },

  
})