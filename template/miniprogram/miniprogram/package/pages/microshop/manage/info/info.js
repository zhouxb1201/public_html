// miniprogram/package/pages/microshop/manage/info/info.js
var requestSign = require('../../../../../utils/requestData.js');
var api = require('../../../../../utils/api.js').open_api;
var util = require('../../../../../utils/util.js');
var re = require('../../../../../utils/request.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    mic_name: '',
    mic_introduce: ''
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
      mic_name: wx.getStorageSync("microshop_name"),
      mic_introduce: wx.getStorageSync("microshop_introduce")
    })
  },
  onSave: function() {
    const that = this;
    let postData = {
      microshop_logo: wx.getStorageSync("microshop_logo"),
      shopRecruitment_logo: wx.getStorageSync("shopRecruitment_logo"),
      microshop_name: that.data.mic_name,
      microshop_introduce: that.data.mic_introduce
    };
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_micShopSet, postData, header).then((res) => {
      if(res.data.code >= 0){
        wx.showToast({
          title: '保存成功',
          icon: 'success',
          duration: 2000
        })
        wx.setStorageSync("microshop_name", that.data.mic_name);
        wx.setStorageSync("microshop_introduce", that.data.mic_introduce);
        wx.navigateBack();
      }
    })
  },
  onInputName:function(e){
    this.setData({
      mic_name: e.detail.value
    })
  },
  onInputIntroduce:function(e){
    this.setData({
      mic_introduce: e.detail.value
    })
  }


})