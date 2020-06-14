// miniprogram/package/pages/scratchcard/scratchcard.js
const Luck = require("./luck.js");
var requestSign = require('../../../utils/requestData.js');
var api = require('../../../utils/api.js').open_api;
var re = require('../../../utils/request.js');
var header = getApp().header;
var postData = {}; //接口参数
var nowTime = null;
var resScartch = {};
Page({

  /**
   * 页面的初始数据
   */
  data: {
    scratchcardid: 1,
    publicUrl: getApp().publicUrl,
    frequency: 0,
    info: {},
    prizeCode: null,

    termname: '', //奖项名称
    prizename: '', //奖品名称

    winPrize: false, //中奖弹框
    noPrize: false, //未中奖弹框
    activity: false, //活动结束弹框
    isExplain: false, //活动说明弹框
    isContinue: true, //是否在砸一次

    boxColor_yellow: 'boxColor_yellow',
    boxColor_gray: 'boxColor_gray',
    listData: [], //中奖名单
    scrollY: 0,

    isHide:'none' //通过display状态来去除微信小程序canvas层级过高中bug
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {

    //跳转进来
    if (options.scratchcardid != undefined){
      this.data.scratchcardid = options.scratchcardid;
      postData.scratch_card_id = options.scratchcardid;
    }    

    // 扫码进来
    if (options.scene != undefined) {
      let scene = options.scene;
      let value_index = scene.indexOf('_');
      this.data.scratchcardid = scene.substring(value_index + 1)//获取id值 
      postData.scratch_card_id = scene.substring(value_index + 1)//获取id值         
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
    wx.hideLoading();
    const that = this;
    if (that.checkPhone() == false) {
      return;
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_scratchFrequency, postData, header).then(result => {
      let res = result.data;
      if (res.code == 1) {
        if (res.data.state == 1) { //活动未开始
          //wx.hideLoading();
        } else if (res.data.state == 2) { //活动进行中
          that.setData({
            frequency: res.data.frequency
          })
          that.init();
          that.loadLuck();
        } else if (res.data.state == 3) { //活动已结束
          that.setData({
            activity: true
          })
          that.loadLuck();
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
    re.request(api.get_scratchcardInfo, postData, header).then(res => {
      if (res.data.code == 1) {
        that.setData({
          info: res.data.data
        })
      }
    })
  },
  loadLuck: function() {
    this.luck = new Luck(this, {
      callback: () => {
        let frequency = this.data.frequency;
        if (this.data.prizeCode == 0) {
          this.setData({
            noPrize: true
          })
          if (frequency !== -9999) {
            this.setData({
              frequency: frequency - 1
            })
          }
        } else if (this.data.prizeCode == 1) {
          this.setData({
            winPrize: true
          })
          if (frequency !== -9999) {
            this.setData({
              frequency: frequency - 1
            })
          }
        }
      },
      handCallback: () => {
        const that = this;
        const {
          prizeCode
        } = that.data;
        let datainfo = requestSign.requestSign(postData);
        header.sign = datainfo;
        re.request(api.get_userScratchcard, postData, header).then(res => {
          that.setData({
            prizeCode: res.data.code
          })
          resScartch = res;
          if (res.data.code == 0) {
            that.setData({
              termname: res.data.message
            })
          } else if (res.data.code == 1) {
            that.setData({
              termname: res.data.data.term_name,
              prizename: res.data.data.prize_name
            })
          } else {
            wx.showToast({
              title: res.data.message,
              icon: 'none',
              duration: 2000
            })
          }
        })
      }
    });
    this.setData({
      isHide: 'block'
    })
  },
  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function(res) {
    if (res.from === 'button') {
      // 来自页面内转发按钮

      console.log(res.target)
    }
    return {
      title: '幸运刮刮乐',
      path: 'package/pages/scratchcard/scratchcard?scratchcardid=' + this.data.scratchcardid
    }
  },
  /**
   * 跳转到我的奖品
   */
  toPrize: function() {
    wx.navigateTo({
      url: '../prize/list/list'
    })
  },
  onContinue() {
    const {
      isContinue
    } = this.data;
    if (isContinue == false) {
      this.luck.init();
      this.setData({
        isContinue: true
      })
    }

  },
  /**
   * 弹出活动说明
   */
  openExplain: function() {
    this.setData({
      isExplain: true,
      isHide:'none',
      termname:''
    })
  },
  /**
   * 关闭活动说明
   */
  closeExplain: function () {
    this.setData({
      isExplain: false,
      isHide: 'block'
    })    
  },
  /**
   * 中奖名单
   */
  roster: function() {
    const that = this;
    postData.page_index = 1;
    postData.page_size = 20;
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_scratchRecords, postData, header).then(result => {
      let res = result.data;
      if (res.code == 1) {
        that.setData({
          listData: res.data.data
        })
        that.moveTop();
      }
    })
  },
  moveTop: function() {
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
  onUnload: function() {
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