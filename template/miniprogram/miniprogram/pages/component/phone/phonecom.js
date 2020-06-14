var requestSign = require('../../../utils/requestData.js');
var api = require('../../../utils/api.js').open_api;
var util = require('../../../utils/util.js');
var header = getApp().header;
Component({
  /**
   * 组件的属性列表
   */
  properties: {
    phoneShow:Boolean,
    
  },

  /**
   * 组件的初始数据
   */
  data: {    
    //手机验证码倒计时设置
    phoneCode: '获取验证码',
    codeDis: false,
  
  
    //手机号码
    user_tel: '',
    //手机验证码
    verification_code: '',
    password:'',
    sure_password:'',
    passwordShow:false,
    // 区号
    country_code: '',
    // 是否显示区号
    is_country_code: '',
  },
  lifetimes: {

    ready: function () {
      console.log()
      const that = this;
      // 是否显示区号组件
      that.setData({
        is_country_code: getApp().globalData.config.config.mobile_type,
      })
    },

  },

  /**
   * 组件的方法列表
   */
  methods: {
    // 获取区号value
    onGetValue(e) {
      console.log(e.detail.value)
      this.setData({
        country_code: e.detail.value
      })
    },

    //手机弹框关闭
    phoneOnclose: function () {
      const that = this;
      that.setData({
        phoneShow: false
      })
    },

    // 绑定手机开始
    //验证码倒计时
    changeCode: function () {
      const that = this;
      let telphone = that.data.user_tel;
      
      console.log('请求验证码')
      var postData = {
        mobile: telphone,
        type: 'bind_mobile',
        country_code: this.data.country_code
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
          if (res.data.code < 0) {
            that.setData({
              codeDis: false
            })
            wx.showToast({
              title: res.data.message,
              icon: 'loading'
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

    //手机号码
    userPhoneFun: function (e) {
      this.setData({
        user_tel: e.detail.value
      })
    },

    //手机验证码
    verificationCodeFun: function (e) {
      this.setData({
        verification_code: e.detail.value
      })
    },

    //关联账号，绑定手机号码
    associateAccountFun: function () {
      const that = this;
      if (that.data.sure_password != that.data.password) {
        wx.showToast({
          title: '密码与确认密码不一致',
          icon: 'none'
        })
        return
      }
      if (that.data.verification_code == ''){
        wx.showToast({
          title: '请填写验证码',
          icon: 'none'
        })
        return
      }
      var postData = {
        country_code: that.data.country_code,
        mobile: that.data.user_tel,
        verification_code: that.data.verification_code,
        encrypted_data: wx.getStorageSync('encrypted_data'),
        iv: wx.getStorageSync('iv'),
        type:3,
        mall_port:2
      };
      if (that.data.password != ''){
        postData.password = that.data.password
      }
      let datainfo = requestSign.requestSign(postData);
      header.sign = datainfo;

      wx.request({
        url: api.get_AssociateAccount,
        data: postData,
        header: header,
        method: 'POST',
        dataType: 'json',
        responseType: 'text',
        success: (res) => {
          if(res.data.code >= 0){
            that.setData({
              phoneShow: false,
            })
            wx.setStorageSync("user_token", res.data.data.user_token);
            wx.setStorageSync("have_mobile", res.data.data.have_mobile);
            getApp().header['user-token'] = res.data.data.user_token;
            getApp().loginStatus = true;
            wx.showToast({
              title: res.data.message,              
            })
            setTimeout(function(){
              that.triggerEvent('phoneEven', { result: 'success' })
            },1000)            
          }else{
            that.setData({
              phoneShow: false,
            })
            if(res.data.code == -1000){
              wx.showModal({
                title: '提示',
                content: res.data.message,
                showCancel: false,
                success:function(){
                  wx.navigateTo({
                    url: '/pages/logon/index',
                  })
                }
              })
            }else{
              wx.showModal({
                title: '提示',
                content: res.data.message,
                showCancel: false,
              })  
            }            
          }
          

        },
        fail: (res) => { },
      })
    }, 

    //密码
    passwordFun:function(e){
      const that = this;
      that.setData({
        password: e.detail.value
      })
    },

    passwordSureFun:function(e){
      const that = this;
      that.setData({
        sure_password: e.detail.value
      })
      
    },

    //检查手机是否存在
    checkMobile: function () {
      const that = this;
      if (util.checkPhone(that.data.user_tel) == false) {
        wx.showToast({
          title: '手机号码有误，请重新再填！',
          icon: 'none',
        })
        return;
      };
      that.setData({
        codeDis: true
      })
      var postData = {
        'mobile': that.data.user_tel,
        'mall_port':2,
      };     
      
      let datainfo = requestSign.requestSign(postData);
      header.sign = datainfo;

      wx.request({
        url: api.get_mobile,
        data: postData,
        header: header,
        method: 'POST',
        dataType: 'json',
        responseType: 'text',
        success: (res) => {
          if (res.data.code == 1) {
            that.setData({
              passwordShow: false
            })
            that.changeCode();
          } else if (res.data.code < 0){
            wx.showToast({
              title: res.data.message,
              icon: 'none'
            })
          }else if(res.data.code == 0){
            that.changeCode();
            that.setData({
              passwordShow:true
            })
          }


        },
        fail: (res) => { },
      })
    }, 

    //分享链接或者扫码成为下线
    checkReferee: function () {
      const that = this; 
      let higherExtendCode = wx.getStorageSync('higherExtendCode');
      if (higherExtendCode) {
        let postData = {
          'extend_code': higherExtendCode
        }
        let datainfo = requestSign.requestSign(postData);
        header.sign = datainfo
        wx.request({
          url: api.get_checkReferee,
          data: postData,
          header: header,
          method: 'POST',
          dataType: 'json',
          responseType: 'text',
          success: (res) => {            
            if (res.data.code <= 0) {
              wx.showToast({
                title: res.data.message,
              })
            }
          },
          fail: (res) => { },
        })
      }
    },

  }
})
