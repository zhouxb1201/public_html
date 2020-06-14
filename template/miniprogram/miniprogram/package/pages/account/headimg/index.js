var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    //用户头像
    user_img:'',
    //选取的图片
    tempFilePaths:'',
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    this.getMemberBaseInfo()
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
   * 获取用户基本信息
   */
  getMemberBaseInfo: function () {
    const that = this;
    let postData = {};
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getMemberBaseInfo,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          that.setData({
            user_img: res.data.data.avatar
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

  /**
   * 在相册中选择图片
   */
  chooseImage:function(){
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
            formData: {
              "type": 'avatar'
            },
            success: (res) => {
              let image_data = res.data;
              let image_src = JSON.parse(image_data);
              that.setData({
                user_img: image_src.data.src
              })
            }
          })
        }


      }
    })
  },

  

})