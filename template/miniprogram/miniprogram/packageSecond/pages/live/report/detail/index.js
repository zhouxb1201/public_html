var requestSign = require('../../../../../utils/requestData.js');
var api = require('../../../../../utils/api.js').open_api;
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    report_arr: '',
    img_list: [],
    violation_id:'',
    content:'',
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {
    this.data.anchor_id = options.anchor_id;
    this.getViolationType();
  },

  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function() {

  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function() {

  },

  //获取违规类型
  getViolationType: function() {
    const that = this;
    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getViolationType,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code === 1) {
          let report_arr = res.data.data;
          for (let value of report_arr) {
            value.selected = 0
          };
          that.setData({
            report_arr: report_arr
          })

        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
        }
      },
      fail: (res) => {},
    })
  },

  selectId(e) {
    const that = this;
    let id = e.currentTarget.dataset.id;
    let report_arr = that.data.report_arr;
    for (let value of report_arr) {
      value.selected = 0;
      if (value.violation_id == id) {
        if (value.selected == 0) {
          value.selected = 1
        } else {
          value.selected = 0
        }
      }
    }
    that.setData({
      report_arr: report_arr,
      violation_id:id,
    })
  },

  //从手机获取图片
  getImagesFun: function(e) {
    const that = this;
    if (that.data.img_list.length > 2) {
      wx.showToast({
        title: '最多上传3张',
        icon: 'none',
      })
      return
    }
    wx.chooseImage({
      count: 1,
      sizeType: ['original', 'compressed'],
      sourceType: ['album', 'camera'],
      success: function(res) {
        let tempFilePaths = res.tempFilePaths
        that.uploadImg(tempFilePaths)
      },
    })
  },

  //上传图片到服务器
  uploadImg: function(tempFilePaths) {
    const that = this;
    let path = tempFilePaths[0];
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
        'type': 'live_report'
      },
      success: (res) => {
        let image_data = res.data;
        let image_src = JSON.parse(image_data);
        let img_list = that.data.img_list;
        img_list.push(image_src.data.src)
        that.setData({
          img_list: img_list,
        })

      }
    })
  },

  deleteImg(e) {
    const that = this;
    let index = e.currentTarget.dataset.index;    
    let img_list = that.data.img_list;
    img_list.splice(index, 1);    
    that.setData({
      img_list: img_list
    })
  },

  //举报内容
  reportContent(e){
    let value = e.detail.value;
    this.data.content = value;    
  },


  addLiveReport(){
    const that = this;
    let img_list = that.data.img_list;    
    let postData = {
      'anchor_id': that.data.anchor_id,
      'content': that.data.content,      
      'violation_id': that.data.violation_id,
    }
    if (img_list.length > 0) {
      postData.report_imgs = img_list.toString();
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_addLiveReport,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code === 1) {          
          wx.redirectTo({
            url: '../result/index',
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




})