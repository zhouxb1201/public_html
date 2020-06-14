var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    anchor_id:'',
    page_index:1,
    room_no:''//房间号
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    this.data.anchor_id = options.anchor_id
  },
  
  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {
    this.getAnchorInfo();
    this.getAnchorGoodsList();
  }, 
  

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {
    const that = this;
    that.data.page_index = that.data.page_index + 1;
    that.getAnchorGoodsList();
  },

  //获取主播信息
  getAnchorInfo: function () {
    const that = this;
    let postData = {
      'anchor_id': that.data.anchor_id
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getAnchorInfo,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 1) {
          that.setData({
            liver_name: res.data.data.uname,
            user_headimg: res.data.data.user_headimg,
            room_no: res.data.data.room_no
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


  //挑选的商品
  getAnchorGoodsList: function () {
    const that = this;
    let postData = {
      'page_index': that.data.page_index,
      'page_size':10,
      'anchor_id': that.data.anchor_id      
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getAnchorGoodsList,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 1) {
          if(that.data.page_index > 1){
            let goodlist = that.data.goodlist;
            goodlist = goodlist.concat(res.data.data.anchor_goods_list);
            that.setData({
              goodlist: goodlist
            })
          }else{            
            that.setData({
              goodlist: res.data.data.anchor_goods_list
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

  onChooseGoodsPage(){
    wx.navigateTo({
      url: '../chooseGoods/index?anchor_id=' + this.data.anchor_id,
    })
  },
})