var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var re = require('../../../../utils/request.js');
var util = require('../../../../utils/util.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    pageShow: false,
    code_img: '',
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {

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
    this.getKindPoster();
  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function () {

  },
  
  
  getLimitMpCode: function () {
    const that = this;
    const appConfig = getApp().globalData;
    var postData = {
      'website_id': appConfig.website_id,
      'auth_id': appConfig.auth_id,
      'poster_type':4, 
      'shopkeeperId': wx.getStorageSync("shopkeeper_id"),  
      'page': 'package/pages/microshop/preview/index/index',
    };
    if (wx.getStorageSync('extend_code')) {
      postData.code = wx.getStorageSync('extend_code')
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_getUnLimitMpCode, postData, header).then((res) => {
      if (res.data.code == 1) {
        that.setData({
          code_img: res.data.data
        })
      } else {
        that.setData({
          code_img: '/images/no-goods.png'
        })
      }
    })
  },

  //获取超级海报
  getKindPoster: function () {
    const that = this;
    var postData = {
      "poster_type": 4,
      "is_mp": 1,
      "mp_page": 'package/pages/microshop/preview/index/index'
    };

    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_getKindPoster, postData, header).then((res) => {
      if (res.data.data == undefined){
        that.getLimitMpCode();
      }else{
        that.setData({
          code_img: res.data.data.poster
        })
      }      
      
    })
  },

  saveCode: function () {
    const that = this;
    let img_list = [];
    img_list.push(that.data.code_img);
    wx.previewImage({
      urls: img_list,
    })
  }

})