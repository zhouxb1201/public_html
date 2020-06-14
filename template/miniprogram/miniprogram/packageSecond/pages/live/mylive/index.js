var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    anchor_txt:'',
    //主播状态0-设置了后台添加主播，关闭入口 1-申请主播 2-查看主播信息 3-完善主播信息
    page_status:'',
  },
  

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {
    var userToken = wx.getStorageSync('user_token');    
    if (userToken) {
      this.getAnchorUserInfo();
    }else{
      wx.navigateTo({
        url: '/pages/logon/index',
      })
    }
    
  },

  

  //获取我的
  getAnchorUserInfo: function () {
    const that = this;
    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getAnchorUserInfo,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code < 0) {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })     
        }else{
          that.setData({
            userInfo: res.data,
            page_status: res.data.page_status,
            anchor_id :res.data.anchor_id
          })
          that.anchorStatus(res.data.page_status);
        } 
        
      },
      fail: (res) => { },
    })
  },

  //主播状态1-申请主播 2-查看主播信息 3-完善主播信息
  anchorStatus(status){
    const that = this;
    switch (status){
      case 1:
        that.setData({
          anchor_txt:'申请成为主播'
        })
        break;
      case 2:
        that.setData({
          anchor_txt: '主播信息'
        })
        break;
      case 3:
        that.setData({
          anchor_txt: '完善主播信息'
        })
        break;
    }
  },

  //分成提示
  profitTips(){
    wx.showModal({
      title: '提示',
      content: '带货获得分成将直接发放至余额账户，订单完成后方可提现',
      showCancel:false,
    })
  },

  onFansPage(){
    wx.navigateTo({
      url: '../fans/index',
    })
  },

  onFocusPage() {
    wx.navigateTo({
      url: '../focus/index',
    })
  },

  onHistory(){
    wx.navigateTo({
      url: '../history/index',
    })
  },

  onApplyAnchor(){
    wx.navigateTo({
      url: '../applyAnchor/index?page_status=' + this.data.page_status,
    })
  },

  onLiveShop(){
    wx.navigateTo({
      url: '../liveShop/index?anchor_id=' + this.data.anchor_id,
    })
  }
})