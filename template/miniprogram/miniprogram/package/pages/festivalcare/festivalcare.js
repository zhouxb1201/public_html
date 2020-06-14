var requestSign = require('../../../utils/requestData.js');
var api = require('../../../utils/api.js').open_api;
var re = require('../../../utils/request.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    info: {},
    publicUrl: getApp().publicUrl
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    const that = this;
    let postData = {
      prize_id: options.prizeid
    };
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_acceptFestivalcare, postData, header).then(res => {
      if (res.data.code == 1) {
        that.setData({
          info:res.data.data
        })
      }else{
        wx.showModal({
          title: '提示',
          content: res.data.message,
          showCancel: false,
          success(res) {
            if (res.confirm) {
              wx.navigateBack();
            }
          }
        })
      }
    })
  },


  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {

  }


})