var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var util = require('../../../../utils/util.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    //1-手持身份证照，2-身份证正照，3-身份证发照
    img_type: '',
    publicUrl: getApp().publicUrl,
    exampleShow: false,
    example_img_num: '',
    real_name: '',
    user_tel: '',
    //身份证照片显示
    idCardShow: false,
    //手持身份证照
    idCardImg: '',
    //身份证正面照显示
    idCardfrontShow: false,
    //身份证正面照
    idCardfrontImg: '',
    //身份证反面照显示
    idCardbehindShow: false,
    //身份证反面照
    idCardbehindImg: '',
    //是否勾选阅读协议 0-是，1-否
    have_read: 0,
    //有没有申请过主播 0-未申请 1-已申请
    is_anchor:0,
    //主播状态1-申请主播 2-查看主播信息 3-完善主播信息 4-填写资料提交审核
    page_status:'',
    uncheck_reason:'',

  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {
    let page_status = options.page_status;
    this.setData({
      page_status: page_status
    })    
    this.applyAnchor();
  },
 
  //获取审核主播的状态和用户头像
  getBeAnchorCheckStatus: function () {
    const that = this;
    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getBeAnchorCheckStatus,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code === 1) {
          that.setData({
            user_img: res.data.data.user_headimg
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

  //申请成为主播页面
  applyAnchor: function() {
    const that = this;
    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_applyAnchor,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code === 1) {          
          that.setData({
            is_anchor: res.data.data.is_anchor,
            real_name: res.data.data.real_name,
            user_tel: res.data.data.user_tel,
            id_card: res.data.data.id_card,
            idCardfrontImg: res.data.data.front_card,
            idCardbehindImg: res.data.data.back_card,
            idCardImg: res.data.data.hand_card,
            uncheck_reason: res.data.data.uncheck_reason
          })
          wx.setStorageSync("live_protocol",res.data.data.live_protocol)
          if (that.data.idCardfrontImg != '' && that.data.idCardfrontImg != undefined){
            that.setData({
              idCardfrontShow:true
            })
          };
          if (that.data.idCardbehindImg != '' && that.data.idCardbehindImg != undefined) {
            that.setData({
              idCardbehindShow: true
            })
          };
          if (that.data.idCardImg != '' && that.data.idCardImg != undefined) {
            that.setData({
              idCardShow: true
            })
          };
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

  //获取手机号
  userPhone(e){
    let user_tel = e.detail.value;
    this.setData({
      user_tel: user_tel
    })
  },

  //提交申请成为主播资料
  actApplyAnchor: function() {
    const that = this;

    if (that.data.idCardImg == '') {
      wx.showToast({
        title: '请上传手持身份证照',
        icon: 'none'
      })
      return;
    }
    if (that.data.idCardfrontImg == '') {
      wx.showToast({
        title: '请上传身份证正面照',
        icon: 'none'
      })
      return;
    }
    if (that.data.idCardbehindImg == '') {
      wx.showToast({
        title: '请上传身份证反面照',
        icon: 'none'
      })
      return;
    }
    if (that.data.user_tel == '') {
      wx.showToast({
        title: '请填写手机号',
        icon: 'none'
      })
      return;
    }
    let postData = {
      'real_name': that.data.real_name,
      'user_tel': that.data.user_tel,
      'id_card': that.data.id_card,      
      'front_card': that.data.idCardfrontImg,
      'back_card': that.data.idCardbehindImg,
      'hand_card': that.data.idCardImg,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_actApplyAnchor,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code === 1) {
          this.getBeAnchorCheckStatus();
          that.setData({
            is_anchor:1,
            page_status:4,
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

  //示例图片弹出层开启
  exampleImgOnShow: function(e) {
    let img_num = e.currentTarget.dataset.imgnum;
    this.setData({
      example_img_num: img_num,
      exampleShow: true
    })
  },

  //示例图片弹出层关闭
  exampleImgOnClose: function() {
    this.setData({
      exampleShow: false
    })
  },

  //真实姓名
  realUserName(e) {
    const that = this;
    that.data.real_name = e.detail.value;
  },

  idCardNo(e) {
    const that = this;
    if (util.checkIdCardNo(e.detail.value) == false) {
      return
    }
    that.setData({
      id_card: e.detail.value
    })
  },

  //从手机获取图片
  getImagesFun: function(e) {
    const that = this;
    let img_type = e.currentTarget.dataset.imgtype;
    that.data.img_type = img_type;
    wx.chooseImage({
      count: 1,
      sizeType: ['original', 'compressed'],
      sourceType: ['album', 'camera'],
      success: function(res) {
        let tempFilePaths = res.tempFilePaths
        console.log(tempFilePaths)
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
        'type':'idcard'
      },
      success: (res) => {
        let image_data = res.data;
        let image_src = JSON.parse(image_data);
        console.log(image_src.data.src)
        if (that.data.img_type == 1) {
          that.setData({
            idCardImg: image_src.data.src,
            idCardShow: true,
          })
        };

        if (that.data.img_type == 2) {
          that.setData({
            idCardfrontImg: image_src.data.src,
            idCardfrontShow: true,
          })
        };
        if (that.data.img_type == 3) {
          that.setData({
            idCardbehindImg: image_src.data.src,
            idCardbehindShow: true,
          })
        };

      }
    })
  },

  //删除图片
  deleteImg: function(e) {
    const that = this;
    let img_type = e.currentTarget.dataset.imgtype;
    wx.showModal({
      title: '提示',
      content: '请确认是否删除图片',
      success(res) {
        if (res.confirm) {
          if (img_type == 1) {
            that.setData({
              idCardShow: false
            })
          };
          if (img_type == 2) {
            that.setData({
              idCardfrontShow: false
            })
          };
          if (img_type == 3) {
            that.setData({
              idCardbehindShow: false
            })
          };

        }
      }
    })
  },

  readAgreement(e){
    const that = this;
    let have_read = e.currentTarget.dataset.haveread;
    if(have_read == 0){
      that.setData({
        have_read: 1
      })
    }else{
      that.setData({
        have_read: 0
      })
    }
    
  },
})