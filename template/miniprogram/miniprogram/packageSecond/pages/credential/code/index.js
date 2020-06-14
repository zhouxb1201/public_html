var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    //是否显示
    wechat_show:false,
    //微信号
    wchat_name:'',
    txt_tips:'授权证书用于展示身份，会员可扫码证书上面的二维码查看证书是否真伪',
    code_img:'',
    active:0,
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    this.setData({
      type: getApp().globalData.credential_type
    })
    let role_team_show = '';
    if (options.role_type_array != undefined){
      for (let value of options.role_type_array){
        if(value == 1){
          role_team_show = false
          this.setData({
            role_type:1,
            active:0,
          })
        }
        if (value == 2) {
          role_team_show = false
          this.setData({
            role_type: 2,
            active: 1,
          })
        }
        if (value == 3) {
          role_team_show = false
          this.setData({
            role_type: 3,
            active: 2,
          })
        }
      }
    }
    this.getUserWchat();
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
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function () {

  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {

  },

  //获取用户证书是否设置
  getUserWchat: function () {
    const that = this;
    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getUserWchat,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code === 0) {
          that.setData({
            wechat_show:true,
          })
        }else if(res.data.code == 1){
          that.data.wchat_name = res.data.wchat_name
          that.getUserCredential();
        }else {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
        }
      },
      fail: (res) => { },
    })
  },

  //填写微信号底部弹框关闭
  weChatBoxClose:function(){
    this.setData({
      wechat_show:false,
    })
    wx.navigateBack({
      complete: (res) => {},
    })
  },
  
  weChatNumber:function(e){
    let wchat_name = e.detail.value;
    this.setData({
      wchat_name: wchat_name
    })
  },

  wxChatBoxSure:function(){    
    this.getUserCredential();
  },

  // 多角色类型
  onRoleChange: function (e) {
    const that = this;
    let index = e.detail.index;
    if (index == 0) {
      that.data.role_type = 1;
      that.setData({
        cred_no: '',
        code_img: ''
      })
    } else if (index == 1) {
      that.data.role_type = 2;
      that.setData({
        cred_no: '',
        code_img: ''
      })
    } else if (index == 2) {
      that.data.role_type = 3;
      that.setData({
        cred_no: '',
        code_img: ''
      })
    }
    that.getUserCredential();
  },

  //授权证书
  getUserCredential:function(){
    const that = this;
    if (that.data.wchat_name == ''){
      wx.showToast({
        title: '微信号不能为空',
        icon:'none',
      })
      return;
    }
    let postData = {
      "type": that.data.type,
      "wchat_name": that.data.wchat_name
    }
    if(that.data.type == 2){
      postData['role_type'] = that.data.role_type
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getUserCredential,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 0) {
          this.setData({
            wechat_show:false,
          })
          let code_img = res.data.data.img_path;
          if (code_img.substring(0, 1) == 'h'){            
          }else{
            code_img = getApp().publicUrl  + '/' +code_img
          }
          that.setData({
            cred_no: res.data.data.cred_no,
            code_img: code_img
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

  //复制到剪切面板
  copyCreNo: function () {
    let copy_no = this.data.cred_no;
    wx.setClipboardData({
      data: copy_no,
      success(res) {
        wx.getClipboardData({
          success(res) {
            console.log(res.data) // data
          }
        })
      }
    })
  },

  //点击把生成的canvas图片保存到相册
  saveCanvasShareImg: function () {
    var that = this;
    wx.showLoading({
      title: '正在保存',
      mask: true,
    })
    setTimeout(function () {
      wx.downloadFile({
        url: that.data.code_img,
        success: function (res) {
          wx.hideLoading();
          if (res.statusCode === 200) {
            wx.saveImageToPhotosAlbum({
              filePath: res.tempFilePath,
              success(res) {
                wx.showModal({
                  content: '图片已保存到相册，赶紧晒一下吧~',
                  showCancel: false,
                  confirmText: '好的',
                  confirmColor: '#333',
                  success: function (res) {

                  },
                  fail: function (res) {
                    console.log(res)
                  }
                })
              },
              fail: function (res) {

              }
            });
          } else {
            wx.showToast({
              title: '背景图片下载失败！',
              icon: 'none',
              duration: 2000,
              success: function () {
               
              }
            })
          }
        },
        fail: function (res) {
          wx.hideLoading();
          wx.showModal({
            title: '提示',
            content: '生成图片出错',
            showCancel: false,
            success(res) {
              
            }
          })
        }
      })      
    }, 1000);
  },

})