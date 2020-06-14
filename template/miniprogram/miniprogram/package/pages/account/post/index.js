var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    user_tel:'',
    //手机验证码倒计时设置
    phoneCode:'获取验证码',
    codeDis: false,
    //新手机验证码倒计时设置
    newPhoneCode:'获取验证码',
    newCodeDis:false,
    //邮箱验证码倒计时设置
    newEmailCode: '获取验证码',
    newEmailCodeDis:false,
    //手机验证码
    verification_code:'',
    //修改信息标记（1-修改密码，2-修改支付码，3-修改手机号码，4-修改电子邮箱）
    reset_sign:'',
    //验证码校验正确（0-不正确，1-正确）
    check_code:0,
    //验证码类型
    type:'',
    //新登录密码
    new_password:'',
    //确认登录新密码
    confirm_password:'',
    //新的支付密码
    new_pay_password:'',
    //确认新的支付密码
    confirm_pay_password:'',
    //新关联的手机号码
    new_phone_num:'',
    //新手机的验证码
    new_phone_code:'',
    //电子邮箱
    email:'',
    //电子邮箱验证码
    email_code:'',
    // 区号
    country_code:'',
    // 是否显示区号
    is_country_code:'',
    getShowCode:'',
    getCity:''
  },

  // 获取区号value
  onGetValue(e){
    this.setData({
      country_code: e.detail.value
    })
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    const that = this;
    that.setData({
      is_country_code: getApp().globalData.config.config.mobile_type,
      reset_sign : options.sign
    })
    if (options.sign == 1){
      that.data.type = 'change_password';
    } else if (options.sign == 2){
      that.data.type = 'change_pay_password';
    } else if (options.sign == 3){
      that.data.type = 'bind_mobile';
    } else if (options.sign == 4){
      that.data.type = 'bind_email';
    }
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
    this.getMemberBaseInfo();
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
            getShowCode: res.data.data.country_code,
            getCity: res.data.data.country,
            user_tel: res.data.data.user_tel
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

  //验证码倒计时
  changeCode: function () {
    const that = this;
    let telphone = that.data.user_tel;    
    that.setData({
      codeDis: true
    })
    console.log('请求验证码')
    var postData = {    
      mobile: telphone,
      type: that.data.type,
    };

    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    
    wx.request({
      url: api.get_getVerificationCode,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        console.log('请求成功')
        if (res.data.code < 0) {
          that.setData({
            codeDis: false
          })          
          wx.showModal({
            title: '提示',
            content: res.data.message,
            showCancel:false,
          })
         
        } else {
          that.setData({
            phoneCode: 60
          })

          let time = setInterval(() => {
            let phoneCode = that.data.phoneCode;
            phoneCode--
            that.setData({
              phoneCode: phoneCode
            })
            if (phoneCode == 0) {
              clearInterval(time);
              that.setData({
                phoneCode: "获取验证码",
                codeDis: false
              });
            }
          }, 1000)
        }
      },
      fail: (res) => { },
    })
  },

  //手机验证码
  verificationCodeFun:function(e){
    this.setData({
      verification_code:e.detail.value
    })
  },

  /**
  * 检查手机验证码
  */
  checkVerificationCode: function () {
    const that = this;
    if (that.data.verification_code == ''){
      wx.showToast({
        title: '短信验证码不能为空！',
        icon:'none',
      })
      return;
    }
    let postData = {
      'mobile': that.data.user_tel,
      'verification_code': that.data.verification_code,
    };
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_checkVerificationCode,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          that.setData({
            check_code:1,
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

  textFun:function(){
    this.setData({
      check_code: 1
    })
  },

  //新密码
  newPasswordFun:function(e){
    this.setData({
      new_password:e.detail.value
    })
  },
  //确认密码
  confirmPasswordFun:function(e){
    this.setData({
      confirm_password:e.detail.value
    })
  },

  /**
  * 修改登录密码
  */
  updatePassword: function () {
    const that = this;
    let new_password = that.data.new_password;
    let confirm_password = that.data.confirm_password;
    if(new_password != confirm_password){
      wx.showToast({
        title: '确认密码不正确！',
        icon:'error'
      })
      return;
    }
    let postData = {
      'password': new_password,
    };
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_updatePassword,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code > 0) {
          wx.showToast({
            title: res.data.message,
            icon: 'success'
          })
          setTimeout(()=>{
            wx.redirectTo({
              url: '../set/index',
            })
          },1000)
          
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



  //新的支付密码
  newPayPasswordFun:function(e){
    this.setData({
      new_pay_password:e.detail.value,
    })
  },
  //确认支付密码
  confirmPayPasswordFun: function (e) {
    this.setData({
      confirm_pay_password: e.detail.value,
    })
  },
  /**
  * 修改支付密码
  */
  updatePayPassword: function () {
    const that = this;
    let new_pay_password = that.data.new_pay_password;
    let confirm_pay_password = that.data.confirm_pay_password;
    if (new_pay_password != confirm_pay_password) {
      wx.showToast({
        title: '确认密码不正确！',
        icon: 'error'
      })
      return;
    }
    let postData = {
      'payment_password': new_pay_password,
    };
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_updatePaymentPassword,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code > 0) {
          wx.showToast({
            title: res.data.message,
            icon: 'success'
          })
          setTimeout(() => {
            wx.redirectTo({
              url: '../set/index',
            })
          }, 1000)

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



  //新手机号码
  newPhoneNumFun:function(e){
    this.setData({
      new_phone_num:e.detail.value,
    })
  },
  //新手机验证码
  newPhoneCodeFun:function(e){
    this.setData({
      new_phone_code:e.detail.value,
    })
  },

  //新手机的验证码倒计时
  newChangeCode: function () {
    const that = this;
    let telphone = that.data.new_phone_num;
    that.setData({
      newCodeDis: true
    })
    var postData = {
      mobile: telphone,
      type: that.data.type,
      country_code: that.data.country_code
    };

    let datainfo = requestSign.requestSign(postData);

    header.sign = datainfo
    wx.request({
      url: api.get_getVerificationCode,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code < 0) {
          that.setData({
            newCodeDis: false
          })
          wx.showToast({
            title: res.data.message,
            icon: 'loading'
          })

        } else {
          that.setData({
            newPhoneCode: 60
          })

          let time = setInterval(() => {
            let newPhoneCode = that.data.newPhoneCode;
            newPhoneCode--
            that.setData({
              newPhoneCode: newPhoneCode
            })
            if (newPhoneCode == 0) {
              clearInterval(time);
              that.setData({
                newPhoneCode: "获取验证码",
                newCodeDis: false
              });
            }
          }, 1000)
        }
      },
      fail: (res) => { },
    })
  },


  /**
   * 修改关联手机号码
   */
  updateMobile:function(){
    const that = this;    
    let postData = {
      'mobile': that.data.new_phone_num,
      'verification_code': that.data.new_phone_code,
      'country_code': that.data.country_code
    };
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_updateMobile,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code > 0) {
          wx.showToast({
            title: res.data.message,
            icon: 'success'
          })
          setTimeout(() => {
            wx.redirectTo({
              url: '../set/index',
            })
          }, 1000)

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

  //修改电子邮箱
  emailNumFun:function(e){
    this.setData({
      email:e.detail.value
    })
  },
  //邮箱验证码
  emailCode:function(e){
    this.setData({
      email_code:e.detail.value
    })
  },

  //邮箱的验证码倒计时
  newEmailCode: function () {
    const that = this;
    let email = that.data.email;
    that.setData({
      newEmailCodeDis: true
    })
    var postData = {
      email: email,
      type: that.data.type,
    };

    let datainfo = requestSign.requestSign(postData);

    header.sign = datainfo
    wx.request({
      url: api.get_getEmailVerificationCode,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code < 0) {
          that.setData({
            newEmailCodeDis: false
          })
          wx.showToast({
            title: res.data.message,
            icon: 'loading'
          })

        } else {
          that.setData({
            newEmailCode: 60
          })

          let time = setInterval(() => {
            let newEmailCode = that.data.newEmailCode;
            newEmailCode--
            that.setData({
              newEmailCode: newEmailCode
            })
            if (newEmailCode == 0) {
              clearInterval(time);
              that.setData({
                newEmailCode: "获取验证码",
                newEmailCodeDis: false
              });
            }
          }, 1000)
        }
      },
      fail: (res) => { },
    })
  },


  /**
   * 修改邮箱
   */
  updateEmail:function(){
    const that = this;
    let postData = {
      'email': that.data.email,
      'email_verification': that.data.email_code,
    };
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_updateEmail,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code > 0) {
          wx.showToast({
            title: res.data.message,
            icon: 'success'
          })
          setTimeout(() => {
            wx.redirectTo({
              url: '../set/index',
            })
          }, 1000)

        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
        }
      },
      fail: (res) => { },
    })
  }


})