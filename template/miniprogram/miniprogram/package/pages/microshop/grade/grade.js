var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var util = require('../../../../utils/util.js');
var re = require('../../../../utils/request.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    items: [],
    styles: "",
    shopkeeper_level_name:"",
    shopkeeper_level_time:"",
    is_default_shopkeeper:0
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {
    if (options.shopkeeper_level_name){
      this.setData({
        shopkeeper_level_name: options.shopkeeper_level_name,
        shopkeeper_level_time: options.shopkeeper_level_time,
        is_default_shopkeeper: options.is_default_shopkeeper
      })
    } 
    this.loadData();
  },
  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function() {

  },
  loadData: function() {
    const that = this;
    let postData = {};
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_micGradeInfo, postData, header).then((res) => {
      if (res.data.code == 0) {
        that.setData({
          items: res.data.data,
        })
        if (that.data.items.length > 3) {
          that.setData({
            styles: "flex-shrink:0;width:180rpx;"
          })
        } else {
          that.setData({
            styles: "flex: 1 1 auto;"
          })
        }
      }
    })
  },
  onUpGrade:function(){//提升等级
    const that = this;
    let postData = {};
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_micUpGrade, postData, header).then((res) => {
      if (res.data.code >= 0) {
        if (res.data.data.length == 0){
          wx.showToast({
            title: '您当前已是最高等级',
            icon: 'none',
            duration: 2000
          })
        }else{
          wx.navigateTo({
            url: '../centre/centre?order_type=4',
          })
        }
      }
    })
  },
  onRenew: function () {//立即续费
    wx.navigateTo({
      url: '../centre/centre?order_type=3',
    })
  }

})