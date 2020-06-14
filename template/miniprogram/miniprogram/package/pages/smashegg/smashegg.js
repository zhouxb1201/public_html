// miniprogram/package/pages/smashegg/smashegg.js
var requestSign = require('../../../utils/requestData.js');
var api = require('../../../utils/api.js').open_api;
var re = require('../../../utils/request.js');
var header = getApp().header;
var postData = {}; //接口参数
var eggCloseImg = getApp().publicUrl + "/wap/static/images/egg-close.png";
var eggOpenImg = getApp().publicUrl + "/wap/static/images/egg-open.png";
var nowTime = null;
Page({
  data: {
    publicUrl: getApp().publicUrl,
    info: {},

    itemsEgg: [{
        eggimg: eggCloseImg,
        hammer: getApp().publicUrl + "/wap/static/images/hammer.png",
        hammerMove: ""
      },
      {
        eggimg: eggCloseImg,
        hammer: getApp().publicUrl + "/wap/static/images/hammer.png",
        hammerMove: ""
      },
      {
        eggimg: eggCloseImg,
        hammer: getApp().publicUrl + "/wap/static/images/hammer.png",
        hammerMove: ""
      }
    ],
    listData:[],//中奖名单
    scrollY: 0,
    frequency: 0, //抽奖次数
    smasheggid: 1,

    termname: null, //奖项名称
    prizename: null, //奖品名称

    winPrize: false, //中奖弹框
    noPrize: false, //未中奖弹框
    activity: false, //活动结束弹框
    isExplain: false, //活动说明弹框

    isContinue: true, //是否在砸一次

    clickFlag: true, //防止砸蛋过程中重复砸蛋

    boxColor_yellow: 'boxColor_yellow',
    boxColor_gray: 'boxColor_gray'
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {

    //跳转进来
    if (options.smasheggid != undefined){
      this.data.smasheggid = options.smasheggid;
      postData.smash_egg_id = options.smasheggid;
    }    

    // 扫码进来
    if (options.scene != undefined) {
      let scene = options.scene;
      let value_index = scene.indexOf('_');
      this.data.smasheggid = scene.substring(value_index + 1)//获取id值 
      postData.smash_egg_id = scene.substring(value_index + 1)//获取id值         
    }

    const value = wx.getStorageSync('user_token')
    if (value) {
      console.log('已登录');
      this.loadData();
      this.roster();
    } else {
      console.log('未登录');
      this.setData({
        loginShow: true,
      })
    }
  },

  //登录结果返回
  requestLogin: function (e) {
    const that = this;
    let result = e.detail.result;
    if (result == true) {
      this.loadData();
      this.roster();
    }
  },
  loadData: function() {
    wx.showLoading({
      title: '加载中',
    })
    const that = this;
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_smasheggFrequency, postData, header).then(result => {
      wx.hideLoading();
      let res = result.data;
      if (res.code == 1) {
        if (res.data.state == 1) { //活动未开始

        } else if (res.data.state == 2) { //活动进行中
          that.setData({
            frequency: res.data.frequency
          })
          that.init();
        } else if (res.data.state == 3) { //活动已结束
          that.setData({
            activity: true
          })
        }
      } else {
        wx.showToast({
          title: res.message,
          icon: 'none',
          duration: 2000
        })
      }
    })
  },
  init: function() {
    const that = this;
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_smasheggInfo, postData, header).then(res => {
      if (res.data.code == 1) {
        that.setData({
          info: res.data.data
        })
      }
    })
  },
  /**
   * 弹出活动说明
   */
  openExplain: function() {
    this.setData({
      isExplain: true
    })
  },
  /**
   * 关闭活动说明
   */
  closeExplain: function () {
    this.setData({
      isExplain: false
    })
  },
  /**
   * 跳转到我的奖品
   */
  toPrize: function() {
    wx.navigateTo({
      url: '../prize/list/list'
    })
  },
  /**
   * 砸蛋
   */
  haveHand: function(e) {
    const that = this;
    if (that.checkPhone() == false) {
      return;
    }
    let event = e.currentTarget.dataset;
    let index = event.index;
    if (!that.data.isContinue) return;
    if (that.data.frequency === 0) {
      wx.showToast({
        title: '抱歉，您已经没有抽奖机会了。',
        icon: 'none',
        duration: 2000
      });
      return false;
    }
    if (!that.data.clickFlag) return;
    that.data.clickFlag = false; // 砸蛋结束前，不允许再次触发
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_userSmashegg, postData, header).then(res => {
      if (res.data.code == 0) {
        // 0 ==> 未中奖
        that.handld(index);
        setTimeout(() => {
          that.setData({
            noPrize: true
          })
        }, 500);
      } else if (res.data.code == 1) {
        // 1 ==> 中奖
        that.handld(index);
        that.setData({
          termname: res.data.data.term_name,
          prizename: res.data.data.prize_name
        })
        setTimeout(() => {
          that.setData({
            winPrize: true
          })
        }, 500);
      }else{
        wx.showToast({
          title: res.data.message,
          icon: 'none',
          duration: 2000
        })
        that.data.clickFlag = true;
      }
      setTimeout(() => {
        that.setData({
          isContinue: false
        })
      }, 500);
    })
  },
  handld: function(index) {
    const that = this;
    if (that.data.frequency !== -9999) {
      that.setData({
        frequency: that.data.frequency - 1
      })
    }
    if (that.data.itemsEgg[index].eggimg == eggCloseImg) {
      let hammerMove_index = 'itemsEgg[' + index + '].hammerMove';
      that.setData({
        [hammerMove_index]: "shak"
      })
      console.log("shak");
      setTimeout(() => {
        let eggimg_index = 'itemsEgg[' + index + '].eggimg';
        that.setData({
          [eggimg_index]: eggOpenImg
        })
        that.data.clickFlag = true;
      }, 500);
      console.log(that.data.itemsEgg);
    }
  },
  onContinue() {
    //在砸一次
    const that = this;
    that.setData({
      isContinue: true
    })

    for (let i = 0; i < that.data.itemsEgg.length; i++) {
      let eggimg_i = 'itemsEgg[' + i + '].eggimg';
      let hammerMove_i = 'itemsEgg[' + i + '].hammerMove';
      that.setData({
        [eggimg_i]: eggCloseImg,
        [hammerMove_i]: ''
      })
    }
  },
  /**
   * 分享
   */
  onShareAppMessage: function (res) {
    if (res.from === 'button') {
      // 来自页面内转发按钮
      console.log(res.target)
    }
    return {
      title: '疯狂砸金蛋',
      path: 'package/pages/smashegg/smashegg?smasheggid=' + this.data.smasheggid
    }    
  },
  /**
   * 中奖名单
   */
  roster: function () {
    const that = this;
    postData.page_index = 1;
    postData.page_size = 20;
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_smasheggRecords, postData, header).then(result => {
      let res = result.data;
      if (res.code == 1) {
        that.setData({
          listData: res.data.data
        })
        that.moveTop();
      }       
    })
  },
  moveTop: function () {
    const that = this;
    let top = null;
    let num = 0;
    if (this.data.listData.length <= 6) {
      //如果数据无6条以上不执行无缝滚动
      return false;
    }
    nowTime = setInterval(() => {
      if (num < this.data.listData.length * 56 - 392) {
        num++;        
      } else {
        num = 0;
      }
      that.setData({
        scrollY: (-num)
      })
    }, 50);
  },
  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function () {
    clearInterval(nowTime); //页面关闭清除定时器
    nowTime = null; //清除定时器标识
  },
  //是否有电话
  checkPhone: function () {
    let that = this;
    let have_mobile = wx.getStorageSync('have_mobile');
    if (have_mobile != true) {
      that.setData({
        phoneShow: true
      })
    }
    return have_mobile
  },

  //绑定手机结果返回
  phonereResult: function (e) {
    const that = this
    let result = e.detail.result;
    if (result == 'success') {
      that.loadData();
      taht.roster();
    }
  }
})