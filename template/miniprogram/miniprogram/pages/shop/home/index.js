var requestSign = require('../../../utils/requestData.js');
var api = require('../../../utils/api.js').open_api;
var re = require('../../../utils/request.js');
var WxParse = require('../../../common/wxParse/wxParse.js');
var Base64 = require('../../../utils/base64.js').Base64;
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    //店铺id
    shopId: '',
    //店铺基础数据
    shopData: '',
    //自定义模板数据
    temData: '',
    dataUrl: getApp().publicUrl,
    goodsList: '',
    
    //店铺是否被收藏
    isCollection: '',
    stars:'',
    isOpen: 0,
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    this.setData({
      shopId: options.shopId,
      stars: options.stars
    })    
    this.getTemData();
    if (options.extend_code != undefined) {
      wx.setStorageSync('higherExtendCode', options.extend_code)
    }
  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {
    this.setData({
      isOpen: getApp().globalData.config.addons.shop
    })
    this.getMember();
  },

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function () {

  },


  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {

  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {
    let that = this;
    let path_url = "/pages/shop/home/index";
    console.log(that.data.extend_code)
    if (that.data.extend_code != '') {
      path_url = path_url + '?extend_code=' + that.data.extend_code + '&shopId=' + that.data.shopId;
      console.log(path_url);
    }

    return {
      path: path_url,
      success: (res) => {
        console.log('转发成功！');
      },
      fail: (res) => {
        console.log('转发失败！');
      }
    }
  
  },

  

  //获取自定义数据  
  getTemData: function () {
    const that = this;    
    let postData = {
      'shop_id': that.data.shopId,
      'type': 2,
      'is_mini':1
    }

    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_custom,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {

          //判断图片地址是本地图片还是网络图片
          let template_data = res.data.data.template_data
          for (var item in template_data.items) {
            let item_data = template_data.items[item].data;
            if (template_data.items[item].id == 'goods'){
              template_data.items[item].shop_id = that.data.shopId
            }
            if (template_data.items[item].id == 'shop_head') {
              template_data.items[item].shop_id = that.data.shopId;
              template_data.items[item].stars = that.data.stars;
            }
            if (template_data.items[item].id == 'search') {
              template_data.items[item].shop_id = that.data.shopId
            }
            for (var index in item_data) {
              if (item_data[index].imgurl != undefined) {
                if (item_data[index].imgurl.substring(0, 1) == 'h') {
                } else {
                  item_data[index].imgurl = that.data.dataUrl + item_data[index].imgurl
                }
              }
            }
          }          
          wx.setNavigationBarTitle({
            title: template_data.page.title,
          })
          that.setData({
            temData: template_data
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

  //会员中心
  getMember: function () {
    const that = this;
    var postData = {};
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_memberIndex, postData, header).then((res) => {
      if (res.data.code >= 0) {
        that.data.extend_code = res.data.data.extend_code;
      }
    })
  },

  
  

})