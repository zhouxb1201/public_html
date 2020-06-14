var requestSign = require('../../../utils/requestData.js');
var api = require('../../../utils/api.js').open_api;
var util = require('../../../utils/util.js');
var header = getApp().header;
var timeFun = require('../../../utils/time.js');
var Base64 = require('../../../utils/base64.js').Base64;
var integral_data = {};
Page({

  /**
   * 页面的初始数据
   */
  data: {
    payboxShow: false,
    out_trade_no: '',
    //剩余时间
    uplogtime: "00 : 00 : 00",
    //余额支付状态
    balanceSelected: false,
    //微信支付状态
    weChatSelected: false,
    //货到付款支付状态
    deliverySelected: false,
    //Globe支付状态
    globeSelected: false,
    //支付金额
    payMoney: '0.00',
    //确认付款按钮状态
    disabled: 'disabled',
    //支付密码
    password: '',
    //订单号
    orderno: '',
    payShow: false,
    //用户当前余额
    balance: '',
    //用户当前积分
    point: '',
    //支付密码0，未设置 1，已设置	
    pay_password: '',
    mini_type: 6,
    hiddtime: false,

    user_tel: '',
    //手机验证码倒计时设置
    phoneCode: '获取验证码',
    codeDis: false,
    //手机验证码
    verification_code: '',
    //验证码校验正确（0-不正确，1-正确）
    check_code: 0,

    //新的支付密码
    new_pay_password: '',
    //确认新的支付密码
    confirm_pay_password: '',
    //设置密码框显示
    reset_pay_show: false,

    //从订单列表的立即付款带过来的参数 now
    payment: '',
    //提交支付后返回的状态码
    paycode: '',
    isShow: true,
    //支付类型
    pageType: '',
    //小程序支付设置" wechat_pay": false,微信支付 "bpay": false,//余额支付 "dpay": false//货到付款支付 "GlobePay" :false
    mipConfig: '',
    //订阅消息的模板id
    templateId: [],
    
    payAction:{
      gppay:false
    }
  },


  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    const that = this;
    that.mipConfig();
    if (options.orderno) {
      that.data.out_trade_no = options.orderno;
      //that.data.orderno = options.orderno;  
    }
    that.data.type = options.type;
    console.log(options.hash)
    if (options.hash == 'recharge') {
      //hash 为recharge时 表示充值支付逻辑       
      that.setData({
        payTypeShow: 1
      })
      that.getPayInfo();
      that.subscribeTemplateId('3');
    } else if (options.hash == 'orderPay') {
      // hash 为orderPay时，表示从订单列表或者详情进来支付
      that.data.order_id = options.orderid;
      that.orderPay();
    } else if (options.hash && options.hash == "integral") {
      //从积分商城订单进行支付逻辑
      integral_data = JSON.parse(Base64.decode(options.order_data));
      that.setData({
        payMoney: options.pay_money,
        payboxShow: true,
        isShow: false,
        pageType: options.hash,
        pay_password: getApp().globalData.is_password_set
      })
      that.getMemberBalancePoint();
    } else if (options.hash && options.hash == "purchase") {
      // hash 为purchase时，表示从微商中心采购订单进来的采购支付
      that.data.buy_type = options.hash
      that.getPayInfo()
    } else {
      that.getPayInfo();
    }

    if (options.payment != undefined) {
      that.data.payment = options.payment
    }

    
    that.setData({
      payAction:{
        gppay: getApp().globalData.config?getApp().globalData.config.config.gppay:false
      }
    })

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
    that.getMemberBaseInfo();

  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function () {
    console.log('页面隐藏')
  },

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function () {
    console.log('页面卸载')
    const that = this;
    that.setData({
      hiddtime: true
    })
    if (that.data.paycode === '') {
      if (that.data.payment != 'now') {
        wx.navigateBack({
          delta: 1
        })
      }
    }


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

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {

  },

  /**
   * 倒计时函数
   */
  newTime: function (time) {
    const that = this;
    let remainingTime = '';

    function resetTime() {
      //定义当前时间
      var startTime = new Date();
      //除以1000将毫秒数转化成秒数方便运算
      startTime = startTime.getTime() / 1000
      //定义结束时间
      var endTime = time;

      //算出中间差并且已秒数返回; ；
      var countDown = endTime - startTime;

      //获取天数 1天 = 24小时  1小时= 60分 1分 = 60秒
      var oDay = parseInt(countDown / (24 * 60 * 60));
      // if (oDay < 10) {
      //   oDay = '0' + oDay
      // }

      //获取小时数 
      //特别留意 %24 这是因为需要剔除掉整的天数;
      var oHours = parseInt(countDown / (60 * 60) % 24);
      if (oHours < 10) {
        oHours = '0' + oHours
      }

      //获取分钟数
      //同理剔除掉分钟数
      var oMinutes = parseInt(countDown / 60 % 60);
      if (oMinutes < 10) {
        oMinutes = '0' + oMinutes
      }

      //获取秒数
      //因为就是秒数  所以取得余数即可
      var oSeconds = parseInt(countDown % 60);
      if (oSeconds < 10 && oSeconds >= 0) {
        oSeconds = '0' + oSeconds
      }
      if (oDay != 0) {
        remainingTime = oDay + '天 ' + oHours + ' : ' + oMinutes + ' : ' + oSeconds;
      } else {
        remainingTime = oHours + ' : ' + oMinutes + ' : ' + oSeconds;
      }


      that.setData({
        uplogtime: remainingTime
      })

      if (that.data.hiddtime == true) {
        clearInterval(timer);
      }

      //别忘记当时间为0的，要让其知道结束了;
      if (countDown < 0) {
        clearInterval(timer);
        if (that.data.hiddtime == false) {
          wx.showToast({
            title: '倒计时已结束',
          })
          let onPageData = {
            url: '/pages/index/index',
            num: 4,
            param: '',
          }
          util.jumpPage(onPageData);
        }



      }


    }
    var timer = setInterval(resetTime, 1000);


  },

  //小程序支付设置
  mipConfig: function () {
    const that = this;
    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_mipConfig,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          that.setData({
            mipConfig: res.data.data.config
          })
        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
        }
      },
      fail: (res) => {
        wx.showModal({
          title: '提示',
          content: '接口出错！',
        })
      },
    })
  },

  //获取支付信息
  getPayInfo: function () {
    const that = this;
    wx.showLoading({
      title: '加载中',
    })
    let postData = {
      'out_trade_no': that.data.out_trade_no,

    }
    let datainfo = requestSign.requestSign(postData);

    header.sign = datainfo
    wx.request({
      url: api.get_getPayValue,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        wx.hideLoading();

        if (res.data.code == 1) {
          that.newTime(res.data.data.end_time)
          that.setData({
            payMoney: res.data.data.pay_money,
            balance: res.data.data.balance,
            pay_password: res.data.data.pay_password,
            payboxShow: true,
          })
        } else if (res.data.code == 2) {
          that.data.paycode = res.data.code;
          wx.reLaunch({
            url: '../paysuccess/index?out_trade_no=' + that.data.out_trade_no,
          })
        } else if (res.data.code == 0) {
          wx.showModal({
            title: '提示',
            content: res.data.message,
            showCancel: false,
            success(res) {
              if (res.confirm) {
                wx.navigateBack({
                  delta: 1
                })
              }
            }
          })
        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
          that.setData({
            errorFail: true
          })
        }
      },
      fail: (res) => {
        wx.showModal({
          title: '提示',
          content: '接口出错！',
        })
      },
    })
  },

  //获取订单支付信息
  orderPay: function () {
    const that = this;
    let postData = {
      'order_id': that.data.order_id,

    }
    let datainfo = requestSign.requestSign(postData);

    header.sign = datainfo
    wx.request({
      url: api.get_orderPay,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        wx.hideLoading();

        if (res.data.code == 1) {
          that.newTime(res.data.data.end_time)
          that.setData({
            payMoney: res.data.data.pay_money,
            balance: res.data.data.balance,
            pay_password: res.data.data.pay_password,
            out_trade_no: res.data.data.out_trade_no,
            payboxShow: true,
          })
        } else if (res.data.code == 2) {
          wx.showModal({
            title: '提示',
            content: res.data.message,
            showCancel: false,
            success(res) {
              wx.reLaunch({
                url: '../paysuccess/index?out_trade_no=' + that.data.out_trade_no,
              })
            }
          })
        } else if (res.data.code == 0) {
          wx.showModal({
            title: '提示',
            content: res.data.message,
            showCancel: false,
            success(res) {
              if (res.confirm) {
                wx.navigateBack({
                  delta: 1
                })
              }
            }
          })
        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
          that.setData({
            errorFail: true
          })
        }
      },
      fail: (res) => {
        wx.showModal({
          title: '提示',
          content: '接口出错！',
        })
      },
    })
  },

  //余额支付状态
  balancePayStatus: function (e) {
    const that = this;
    that.subscribeTemplateId('1,3');
    let selectednum = e.currentTarget.dataset.selected;
    if (selectednum == false) {
      that.setData({
        balanceSelected: true,
        weChatSelected: false,
        globeSelected: false,
        deliverySelected: false,
        disabled: ''
      })
    } else {
      that.setData({
        balanceSelected: false,
        weChatSelected: false,
        globeSelected: false,
        deliverySelected: false,
        disabled: 'disabled'
      })
    }
  },
  //微信支付状态
  weChatPayStatus: function (e) {
    const that = this;
    that.subscribeTemplateId('1');
    let selectednum = e.currentTarget.dataset.selected;
    if (selectednum == false) {
      that.setData({
        balanceSelected: false,
        deliverySelected: false,
        weChatSelected: true,
        globeSelected: false,
        disabled: ''
      })
    } else {
      that.setData({
        balanceSelected: false,
        weChatSelected: false,
        globeSelected: false,
        deliverySelected: false,
        disabled: 'disabled'
      })
    }
  },
  //globe支付状态
  globePayStatus: function (e) {
    const that = this;
    that.subscribeTemplateId('1');
    let selectednum = e.currentTarget.dataset.selected;
    if (selectednum == false) {
      that.setData({
        balanceSelected: false,
        deliverySelected: false,
        weChatSelected: false,
        globeSelected: true,
        disabled: ''
      })
    } else {
      that.setData({
        balanceSelected: false,
        weChatSelected: false,
        globeSelected: false,
        deliverySelected: false,
        disabled: 'disabled'
      })
    }
  },
  //货到付款支付状态
  dPayStatus: function (e) {
    const that = this;
    let selectednum = e.currentTarget.dataset.selected;
    if (selectednum == false) {
      that.setData({
        balanceSelected: false,
        weChatSelected: false,
        globeSelected: false,
        deliverySelected: true,
        disabled: ''
      })
    } else {
      that.setData({
        balanceSelected: false,
        weChatSelected: false,
        globeSelected: false,
        deliverySelected: false,
        disabled: 'disabled'
      })
    }
  },

  //订阅模板id
  subscribeTemplateId: function (type) {
    const that = this;
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

  //订阅消息
  subscribeMessage: function () {
    const that = this;
    if (that.data.templateId.length == 0) {
      that.makepay();
    } else {
      //订阅消息模板
      wx.requestSubscribeMessage({
        tmplIds: that.data.templateId,
        success(res) {
          console.log(res);
          that.makepay();
          util.postUserMpTemplateInfo(res);
        },
        fail(res) {
          console.log(res);
          that.makepay();
        }
      })
    }

  },


  makepay: function () {
    const that = this;

    //余额支付
    if (that.data.balanceSelected == true) {
      //检查余额是否足够支付
      if (parseFloat(that.data.payMoney) > parseFloat(that.data.balance)) {
        wx.showModal({
          content: '当前余额不足，请选择其他支付方式！',
          showCancel: true,
        })
        return;
      }
      if (getApp().globalData.no_check_phone === 0) {
        if (that.data.pageType == 'integral') {
          that.onBpayIntegral();
        } else {
          that.balancePay();
        }
      } else {
        //检查是否设置支付密码
        if (that.data.pay_password == 0) {
          wx.showModal({
            title: '提示',
            content: '您还没有设置支付密码！',
            confirmText: '设置',
            confirmColor: '#1989fa',
            showCancel: true,
            success(res) {
              if (res.confirm) {
                that.resetPayPasswordShow();
              }
            }
          })
          return;
        }
        that.setData({
          payShow: true
        })
      }

    }
    //微信支付
    if (that.data.weChatSelected == true) {
      if (that.data.pageType == 'integral') {
        that.wxIntegralPay();
      } else {
        that.setData({
          disabled: 'disabled'
        })
        that.wchatPay();
      }
    }
    //globe付款
    if (that.data.globeSelected == true) {
      that.globePay();
    }
    //货到付款
    if (that.data.deliverySelected == true) {
      that.dPay();
    }
  },

  //货到付款支付接口
  dPay: function () {
    const that = this;
    let postData = {
      'out_trade_no': that.data.out_trade_no,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_dPay,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          wx.showToast({
            title: res.data.message,
            icon: 'success'
          })
          wx.reLaunch({
            url: '../paysuccess/index?out_trade_no=' + that.data.out_trade_no,
          })
        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
        }
      },
      fail: (res) => {
        wx.showModal({
          title: '提示',
          content: '接口出错！',
        })
      },
    })
  },

  //微信支付接口
  wchatPay: function () {
    const that = this;

    let postData = {
      'out_trade_no': that.data.out_trade_no,
      'type': that.data.mini_type,
      'form_id': that.data.form_id,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_wchatPay,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        that.data.paycode = res.data.code;
        if (res.data.code >= 0) {
          wx.requestPayment({
            timeStamp: res.data.data.timeStamp,
            nonceStr: res.data.data.nonceStr,
            package: res.data.data.package,
            signType: res.data.data.signType,
            paySign: res.data.data.paySign,
            success(res) {
              console.log(res);
              if (res.errMsg == "requestPayment:ok") { // 调用支付成功
                setTimeout(function () {
                  wx.redirectTo({
                    url: '../paysuccess/index?out_trade_no=' + that.data.out_trade_no,
                  })
                }, 500)

              } else if (res.errMsg == 'requestPayment:cancel') { // 用户取消支付的操作
                wx.showToast({
                  title: '请支付',
                  icon: 'none',
                  duration: 2000,
                })
              }

            },
            fail(res) {
              wx.showToast({
                title: '支付失败,请稍后再试',
                icon: 'none',
                duration: 2000,
              })
            }
          })
        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
          that.setData({
            disabled: ''
          })
        }
      },
      fail: (res) => {},
    })
  },

  // globe 支付接口 
  globePay: function () {
    const that = this;

    let postData = {
      'out_trade_no': that.data.out_trade_no,
      'type': that.data.mini_type,
      'form_id': that.data.form_id,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_globePay,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        that.data.paycode = res.data.code;
        if (res.data.code >= 0) {
          wx.requestPayment({
            timeStamp: res.data.data.sdk_params.timeStamp,
            nonceStr: res.data.data.sdk_params.nonceStr,
            package: res.data.data.sdk_params.package,
            signType: res.data.data.sdk_params.signType,
            paySign: res.data.data.sdk_params.paySign,
            success(res) {
              console.log(res);
              if (res.errMsg == "requestPayment:ok") { // 调用支付成功
                wx.redirectTo({
                  url: '../paysuccess/index?out_trade_no=' + that.data.out_trade_no,
                })

              } else if (res.errMsg == 'requestPayment:cancel') { // 用户取消支付的操作
                wx.showToast({
                  title: '请支付',
                  icon: 'none',
                  duration: 2000,
                })
              }

            },
            fail(res) {
              wx.showToast({
                title: '支付失败,请稍后再试',
                icon: 'none',
                duration: 2000,
              })
            }
          })
          
        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
          that.setData({
            disabled: ''
          })
        }
      },
      fail: (res) => {},
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
          if (that.data.pageType == 'integral') {
            that.onBpayIntegral();
          } else {
            that.balancePay();
          }

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
      password: '',
    })
  },

  //余额支付
  balancePay: function () {
    const that = this;
    let postData = {
      'out_trade_no': that.data.out_trade_no,
      'pay_money': that.data.payMoney,
      'form_id': that.data.form_id,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_balance_pay,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        that.data.paycode = res.data.code;
        if (res.data.code >= 0) {
          wx.showToast({
            title: res.data.message,
            icon: 'success'
          })
          wx.reLaunch({
            url: '../paysuccess/index?out_trade_no=' + that.data.out_trade_no,
          })
        } else if (res.data.code == -1) {
          wx.showToast({
            title: '余额不足,请选择别的支付方式',
            icon: 'none'
          })
        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
          let onPageData = {
            url: '../payfail/index',
            num: 4,
            param: '?out_trade_no=' + that.data.out_trade_no
          }
          util.jumpPage(onPageData);
        }
      },
      fail: (res) => {},
    })
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
      fail: (res) => {},
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
      type: "change_pay_password",
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
      fail: (res) => {},
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
      fail: (res) => {},
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
    if (new_pay_password.length <= 8 || new_pay_password.length >= 21) {
      wx.showToast({
        title: '密码由9-20个字母、数字、普通字符组成',
        icon: 'none'
      })
      return;
    }
    if (new_pay_password != confirm_pay_password) {
      wx.showToast({
        title: '确认密码不正确！',
        icon: 'none'
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
          if (that.data.pageType == 'integral') {
            that.setData({
              pay_password: 1,
              payboxShow: true
            })
          } else {
            that.getPayInfo();
          }

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
  //获取用户余额与积分（积分商城）
  getMemberBalancePoint: function () {
    const that = this;
    let postData = {};
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    wx.request({
      url: api.get_MemberBalancePoint,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code > 0) {
          that.setData({
            point: res.data.data.point,
            balance: res.data.data.balance
          })
        }
      }
    })
  },
  //余额支付（积分商城）
  onBpayIntegral: function () {
    const that = this;
    integral_data.order_data.pay_type = 5;
    integral_data.password = that.data.password;
    let postData = integral_data;
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    wx.request({
      url: api.get_integralPay,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        that.data.paycode = res.data.code;
        if (res.data.code == 0) {
          wx.reLaunch({
            url: '../paysuccess/index?out_trade_no=' + res.data.data.out_trade_no
          })
        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
        }
      }
    })
  },
  //微信支付（积分商城）
  wxIntegralPay: function () {
    const that = this;
    integral_data.order_data.pay_type = 1;
    let postData = integral_data;
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    wx.request({
      url: api.get_integralPay,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        that.data.paycode = res.data.code;
        if (res.data.code == 0) {
          wx.requestPayment({
            timeStamp: res.data.data.timeStamp,
            nonceStr: res.data.data.nonceStr,
            package: res.data.data.package,
            signType: res.data.data.signType,
            paySign: res.data.data.paySign,
            success(res) {
              wx.reLaunch({
                url: '../paysuccess/index?out_trade_no=' + res.data.data.out_trade_no
              })
            },
            fail(res) {}
          })
        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
        }
      }
    })
  },

  //获取消息模板id
  templateSend: function (e) {
    let that = this;
    let form_id = e.detail.formId;
    that.data.form_id = form_id;
  },






})