var requestSign = require('../../../utils/requestData.js');
var api = require('../../../utils/api.js').open_api;
var util = require('../../../utils/util.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    boxShow:false,
    items:[
      { name: '-1', value: '余额', checked: 'true' },
      { name: '-2', value: '微信' },
    ],
    //佣金
    commission:'',
    //提现金额
    cash:'',
    //最低提现额度
    withdrawals_min:'0',
    //提现类型-1余额，-2微信
    account_id:-1,

    payShow: false,
    //支付密码
    password: '',

    user_tel: '',
    //手机验证码倒计时设置
    phoneCode: '获取验证码',
    codeDis: false,
    //手机验证码
    verification_code: '',
    //验证码校验正确（0-不正确，1-正确）
    check_code: 0,
    //验证码类型
    type: 'change_pay_password',
    //新的支付密码
    new_pay_password: '',
    //确认新的支付密码
    confirm_pay_password: '',
    //设置密码框显示
    reset_pay_show: false,
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    
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
    const value = wx.getStorageSync('user_token');
    if(value){
      this.setDistributionData();
      this.commissionWithdrawShow();
    }else{
      wx.navigateTo({
        url: '/pages/logon/index',
      })
    }
    
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

  //设置分销的文案字段
  setDistributionData: function () {
    const that = this;
    let distributionData = getApp().globalData.distributionData;
    if (distributionData == '') {
      util.distributionSet().then((res) => {
        let resultData = res.data.data;
        that.setData({         
          txt_commission: resultData.commission,
        })
      });

    } else {
      that.setData({        
        txt_commission: distributionData.commission,
      })
    }
  },

  commissionWithdrawShow:function(){
    const that = this;
    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    wx.request({
      url: api.get_commissionWithdraw_show,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if(res.data.code >=0){
          if (res.data.data.is_datum == 2) {
            wx.showToast({
              title: '请完善资料',
            })
            wx.navigateTo({
              url: '../apply/index?applyType=replenish',
            })
            return                        
          }
          that.setData({
            boxShow: true
          })
          that.setData({
            commission: res.data.data.commission,
            withdrawals_min: res.data.data.withdrawals_min,
            withdrawData: res.data.data
          })
        }else{
          wx.showToast({
            title: res.data.message,
          })
        }
      },
      fail: (res) => { },
    })
  },

  //提现金额
  cashNumFun:function(e){
    const that = this;
    that.setData({
      cash:e.detail.value
    })
  },

  //提现方式
  radioChange:function(e){
    const that = this;
    let account_id = e.detail.value;
    that.data.account_id = account_id
  },

  //佣金提现
  commissionWithdraw:function(){
    const that = this;
    if (that.data.withdrawals_min > that.data.cash){
      wx.showToast({
        title: '提现金额不能低于最低提现额度',
        icon:'none'
      })
      return
    }
    let postData = {
      'cash': that.data.cash,
      'account_id': that.data.account_id,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    wx.request({
      url: api.get_commissionWithdraw,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          wx.showToast({
            title: res.data.message,
          })
          wx.navigateBack({
            delta:1
          })
        } else {
          wx.showToast({
            title: res.data.message,
          })
        }
      },
      fail: (res) => { },
    })
  },

  //支付框显示
  payBoxShow:function(){
    const that  = this;
    if (getApp().globalData.no_check_phone === 0){
      that.commissionWithdraw();
    }else{
      that.setData({
        payShow: true
      })
    }
    
  },

  //检查密码
  checkPaypassword: function () {
    const that = this;

    let postData = {
      'password': that.data.password
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_check_pay_password,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          that.commissionWithdraw();
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

  //获取密码
  passwordFun: function (e) {
    const that = this;
    let password = e.detail.value;
    that.setData({
      password: password
    })
  },

  //密码框确认按钮
  passwordConfirmFun: function () {
    const that = this;
    that.checkPaypassword();
    that.setData({
      payShow: false,
    })
  },
  //密码框关闭
  onPasswordClose: function () {
    const that = this;
    that.setData({
      payShow: false,
    })
  },

  //设置支付密码框开启
  resetPayPasswordShow: function () {
    const that = this;
    that.getMemberBaseInfo();
    that.setData({
      reset_pay_show: true,
    })
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

  //手机验证码
  verificationCodeFun: function (e) {
    this.setData({
      verification_code: e.detail.value
    })
  },

  /**
  * 检查手机验证码
  */
  checkVerificationCode: function () {
    const that = this;
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
            check_code: 1,
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

  textFun: function () {
    this.setData({
      check_code: 1
    })
  },

  //新的支付密码
  newPayPasswordFun: function (e) {
    this.setData({
      new_pay_password: e.detail.value,
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
          that.setData({
            reset_pay_show: false,
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