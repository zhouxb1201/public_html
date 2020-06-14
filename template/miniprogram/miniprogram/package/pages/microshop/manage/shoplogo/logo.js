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
    //选取的图片
    tempFilePaths: '',

    mic_logo: '' 
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    
  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {
    this.setData({
      mic_logo: wx.getStorageSync("microshop_logo")
    })
  },


  /**
   * 在相册中选择图片
   */
  chooseImage: function () {
    const that = this;
    wx.chooseImage({
      count: 1,
      sizeType: ['original', 'compressed'],
      sourceType: ['album', 'camera'],
      success(res) {
        // tempFilePath可以作为img标签的src属性显示图片
        const tempFilePaths = res.tempFilePaths;
        that.data.tempFilePaths = tempFilePaths;
        for (let path of res.tempFilePaths) {
          wx.uploadFile({
            url: api.get_uploadImage,
            filePath: path,
            name: 'file',
            header: {
              'Content-Type': 'multipart/form-data',
              'X-Requested-With': 'XMLHttpRequest',
              'user-token': wx.getStorageSync('user_token'),
            },
            success: (res) => {
              let image_data = res.data;
              let image_src = JSON.parse(image_data);
              that.setData({
                mic_logo: image_src.data.src
              })
              that.onSave(image_src.data.src);
            }
          })
        }
      }
    })
  },
  onSave: function (logo) {
    const that = this;
    let postData = {
      microshop_logo: logo,
      shopRecruitment_logo: wx.getStorageSync("shopRecruitment_logo"),
      microshop_name: wx.getStorageSync("microshop_name"),
      microshop_introduce: wx.getStorageSync("microshop_introduce")
    };
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_micShopSet, postData, header).then((res) => {
      if (res.data.code >= 0) {
        wx.showToast({
          title: '上传成功',
          icon: 'success',
          duration: 2000
        })
        wx.setStorageSync("microshop_logo", logo);
      }
    })
  }


})