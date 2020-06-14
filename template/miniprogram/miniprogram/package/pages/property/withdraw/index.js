var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var util = require('../../../../utils/util.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    //账户底部弹框显示
    accountShow:false,
    //余额
    balance:0,
    //账户列表
    account_list:'',
    //提现账户
    account:'',
    openid:'',
    //提现余额
    cash:'',
    //1银行卡2微信3支付宝
    type:'',
    //最低提现金额
    withdraw_cash_min:0,
    withdraw_form:'',

    payShow: false,
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
    //系统开启的提现方式
    account_type_list:[],
    //银行卡是否开启
    is_bank:0,
    //微信是否开启
    is_wechat:0,
    //支付宝是否开启
    is_Alipay:0,

    //消息模板id
    form_id:'',

    //订阅消息的模板id
    templateId: [],
   
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    const that = this;
    //系统设置了那几种支付方式，并把支付方式开启
    let account_type_list = getApp().globalData.config.config.withdraw_conf.withdraw_message;
    that.data.account_type_list = account_type_list;
    for (let item of account_type_list){
      if(item == '1' || item == '4'){
        
        that.setData({
          is_bank:1
        })
      }
      if (item == '2') {
        that.setData({
          is_wechat: 1
        })
      }
      if (item == '3') {
        that.setData({
          is_Alipay: 1
        })
      }
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
    const that = this;
    this.getwithdrawData();
    this.getAccountList();

    //获取订阅模板的模板id
    let type = 3;
    const tId = util.getMpTemplateId(type);
    tId.then((res) => {
      if (res.data.code == 1 && res.data.data.length > 0) {
        let tem_array = [];
        for (let item of res.data.data) {
          if (item.status == 1) {
            tem_array.push(item.template_id)
          }
        }
        that.data.templateId = tem_array
      }
    })
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
  //获取余额
  getwithdrawData:function(){
    const that = this;
    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_withdraw_form,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          that.setData({
            balance: res.data.data.balance,
            openid: res.data.data.wx_openid,
            withdraw_cash_min: res.data.data.withdraw_cash_min,
            withdraw_form:res.data.data,
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
  //账户弹框关闭
  onAccountClose:function(){
    const that = this;
    that.setData({
      accountShow:false
    })
  },
  //账户弹框开启
  onAccountShow:function(){
    const that = this;
    that.setData({
      accountShow: true
    })
  },
  //获取账户列表
  getAccountList:function(){
    const that = this;
    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_bank_account,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          let account_list = [];
          if (that.data.is_wechat != 0){
            let wxOpj = {
              realname: '微信账号',
              type: 2,
              account_number: '',
            }
            account_list.push(wxOpj);      
          }          
          account_list = account_list.concat(res.data.data);
          for(var i of account_list){
            i.select = false
            //判断银行卡提现开启
            if(i.type == 1 || i.type == 4){
              if(that.data.is_bank == 1){
                i.type_start = true
              }
            }
            //判断微信提现开启
            if (i.type == 2) {
              if (that.data.is_wechat == 1) {
                i.type_start = true
              }
            }
            //判断支付宝提现开启
            if (i.type == 3) {
              if (that.data.is_Alipay == 1) {
                i.type_start = true
              }
            }
          }
          that.setData({
            account_list: account_list
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
  //选择账户
  selectAccount:function(e){
    const that = this;
    let type_start = e.currentTarget.dataset.typestart;
    if (type_start != true){
      return;
    }
    let realname = e.currentTarget.dataset.realname;
    let account_number = e.currentTarget.dataset.accountnumber;
    let id = e.currentTarget.dataset.id;
    let type = e.currentTarget.dataset.type;
    let account_list = that.data.account_list;     
    for (var value of account_list){
      value.select = false;
      if(value.id == id){
        value.select = true
      }
    }
    let account_name = '';
    if(type == 2){
      account_name = realname + ' - 微信账号'
    }else{
      account_name = realname + ' - ' + account_number
      that.data.bank_account_id = e.currentTarget.dataset.id
    }
    that.setData({
      account_list: account_list,
      account: account_name,
      accountShow:false,
      type: type,      
    })
  },

  //提现余额
  cashMoneyFun:function(e){
    const that = this;
    let cash = e.detail.value;
    that.data.cash = cash;        
  },

  //提现
  withdrawFun:function(){
    const that = this;    
    let postData = {
      type: that.data.type,
      cash: that.data.cash,
      password: that.data.password,
      bank_account_id: that.data.bank_account_id,
      form_id: that.data.form_id,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_withdraw,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          wx.navigateBack({
            delta:1,
          })
          wx.showToast({
            title: res.data.message,
            icon: 'none'
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
          that.withdrawFun();
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

  //订阅消息
  subscribeMessage: function () {
    const that = this;
    if (that.data.templateId.length == 0) {
      that.onPasswordShow();
    } else {
      //订阅消息模板
      wx.requestSubscribeMessage({
        tmplIds: that.data.templateId,
        success(res) {
          console.log(res);
          that.onPasswordShow();
          util.postUserMpTemplateInfo(res);
        },
        fail(res) {
          console.log(res);
          that.onPasswordShow();
        }
      })
    }
  },


  //密码框开启
  onPasswordShow: function () {
    const that = this;
    if (that.data.withdraw_form.is_start != 1) {
      wx.showToast({
        title: '提现功能未开启！',
        icon: 'none',
      })
      return;
    }
    if (that.data.withdraw_form.is_alipay == 0 && that.data.type == 3){
      wx.showToast({
        title: '支付宝提现未开启！',
        icon:'none',
      })
      return;
    }
    if (that.data.withdraw_form.is_wpy == 0 && that.data.type == 2) {
      wx.showToast({
        title: '微信提现未开启！',
        icon: 'none',
      })
      return;
    }
    if (that.data.cash < that.data.withdraw_cash_min){
      wx.showToast({
        title: '提现金额不能低于最小提现金额' + that.data.withdraw_cash_min,
        icon:'none',
      })
      return;
    }
    if (that.data.type == 2 && that.data.openid == '') {
      wx.showToast({
        title: 'openid为空，不能发起微信提现',
        icon: 'none',
      })
      return;
    }
    
    if (getApp().globalData.no_check_phone === 0){
      that.withdrawFun();
    }else{
      that.setData({
        payShow: true,
      })
    }
    
  },

  //设置支付密码框关闭
  resetPayPasswordClose: function () {
    const that = this;
    that.setData({
      reset_pay_show: false,
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

  //获取消息模板id
  templateSend: function (e) {
    let that = this;
    let form_id = e.detail.formId;
    that.data.form_id = form_id;
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
          that.getPayInfo();

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