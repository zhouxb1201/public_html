
var requestSign = require('../../../utils/requestData.js');
var api = require('../../../utils/api.js').open_api;
var re = require('../../../utils/request.js');
var header = getApp().header;
var startRotDegree = 0; //初始旋转角度
var clickFlag = true; //是否可以旋转抽奖
var prizeCode = null; //是否中奖
var postData = {}; //接口参数
var nowTime = null;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    pageShow:false,
    info: {},
    baseURL: getApp().publicUrl,
    palaceBgImg: '', //大转盘背景图
    pointerImg: getApp().publicUrl + '/wap/static/images/wheels-pointer.png',
    frequency: 0, //抽奖次数
    rotateAngle: '0', //将要旋转的角度

    defaultImg: [], //大转盘奖品默认图
    deg: 0, //大转盘奖品位置的度数
    rotateWidth: null,
    rotateLeft: null,
    listData: [], //中奖名单
    scrollY: 0,
    wheelsurfid: 1,

    termname: null, //奖项名称z
    prizename: null, //奖品名称

    winPrize: false, //中奖弹框
    noPrize: false, //未中奖弹框
    activity: false, //活动结束弹框
    isExplain: false //活动说明弹框

  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {
    const that = this;

    //跳转进来    
    if (options.wheelsurfid != undefined){
      this.data.wheelsurfid = options.wheelsurfid;
      postData.wheelsurf_id = options.wheelsurfid;
    }

    // 扫码进来
    if (options.scene != undefined) {
      let scene = options.scene;
      let value_index = scene.indexOf('_');
      this.data.wheelsurfid = scene.substring(value_index + 1)//获取id值 
      postData.wheelsurf_id = scene.substring(value_index + 1)//获取id值         
    }
    const value = wx.getStorageSync('user_token')
    if (value) {
      console.log('已登录');      
      this.loadData();
      this.roster();
    } else {
      console.log('未登录');
      that.setData({
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
    re.request(api.get_userFrequency, postData, header).then(result => {
      wx.hideLoading();
      that.setData({
        pageShow:true
      })
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
    });
  },
  init: function() {
    const that = this;
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_wheelsurfInfo, postData, header).then(res => {
      if (res.data.code == 1) {
        let prize = res.data.data.prize;
        that.setData({
          info: res.data.data
        })
        if (prize.length == 6) {
          that.setData({
            palaceBgImg: that.data.baseURL + '/wap/static/images/wheels-bg-palace-6.png',
            deg: 60,
            rotateWidth: 230 + 'rpx',
            rotateleft: 100 + 'rpx'
          })
        } else if (prize.length == 8) {
          that.setData({
            palaceBgImg: that.data.baseURL + '/wap/static/images/wheels-bg-palace-8.png',
            deg: 45,
            rotateWidth: 176 + 'rpx',
            rotateleft: 130 + 'rpx'
          })
        } else if (prize.length == 10) {
          that.setData({
            palaceBgImg: that.data.baseURL + '/wap/static/images/wheels-bg-palace-10.png',
            deg: 36,
            rotateWidth: 144 + 'rpx',
            rotateleft: 144 + 'rpx'
          })
        } else if (prize.length == 12) {
          that.setData({
            palaceBgImg: that.data.baseURL + '/wap/static/images/wheels-bg-palace-12.png',
            deg: 30,
            rotateWidth: 120 + 'rpx',
            rotateleft: 156 + 'rpx'
          })
        }
        let imgSrc = '';
        let type = null;
        let prizeDefaultImg = [];
        for (let i = 0; i < prize.length; i++) {
          type = prize[i].prize_type;
          if (type == 0) {
            // 0 => 未中奖
            imgSrc = that.data.baseURL + '/wap/static/images/default-no.jpg';
          } else if (type == 1) {
            // 1 => 余额
            imgSrc = that.data.baseURL + '/wap/static/images/default-balance.png';
          } else if (type == 2) {
            // 2 => 积分
            imgSrc = that.data.baseURL + '/wap/static/images/default-integral.png';
          } else if (type == 3) {
            // 3 => 优惠券
            imgSrc = that.data.baseURL + '/wap/static/images/default-coupon.png';
          } else if (type == 4) {
            // 4 => 礼品券
            imgSrc = that.data.baseURL + '/wap/static/images/default-giftvoucher.png';
          } else if (type == 5) {
            // 5 => 商品
            imgSrc = that.data.baseURL + '/wap/static/images/default-goods.png';
          } else if (type == 6) {
            // 6 => 赠品
            imgSrc = that.data.baseURL + '/wap/static/images/default-gift.png';
          }
          prizeDefaultImg.push(imgSrc);
        }
        that.setData({
          defaultImg: prizeDefaultImg
        })
      }
    })
  },
  /**
   * 点击指针旋转大转盘
   */
  onRotateHandle: function() {
    const that = this;
    if (that.checkPhone() == false) {
      return;
    }
    if (that.data.frequency === 0) {
      wx.showToast({
        title: '抱歉，您已经没有抽奖机会了。',
        icon: 'none',
        duration: 2000
      });
      return false;
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    if (!clickFlag) return;
    clickFlag = false; // 旋转结束前，不允许再次触发
    re.request(api.get_userWheelsurf, postData, header).then(res => {
      prizeCode = res.data.code;
      if (res.data.code == 0 || res.data.code == 1) {
        that.setData({
          termname: res.data.data.term_name,
          prizename: res.data.data.prize_name
        })
        for (let index = 0; index < that.data.info.prize.length; index++) {
          if (that.data.info.prize[index].prize_id == res.data.data.prize_id) {
            //指定每次旋转到的奖品下标
            that.rotating(index);
            break;
          }
        }
      } else {
        wx.showToast({
          title: res.data.message,
          icon: 'none',
          duration: 2000
        })
        clickFlag = true;
      }
    })
  },
  /**
   * 开始转动转盘
   */
  rotating: function(index) {
    const that = this;
    if (that.data.frequency !== -9999) {
      that.setData({
        frequency: that.data.frequency - 1
      })
    }
    let resultIndex = index, // 最终要旋转到哪一块，对应prize_list的下标
      randCircle = 10; // 附加多转几圈
    let resultAngle = []; //最终会旋转到下标的位置所需要的度数
    if (that.data.info.prize.length == 6) {
      resultAngle = [360, 300, 240, 180, 120, 60];
    } else if (that.data.info.prize.length == 8) {
      resultAngle = [360, 315, 270, 225, 180, 135, 90, 45];
    } else if (that.data.info.prize.length == 10) {
      resultAngle = [360, 324, 288, 252, 216, 180, 144, 108, 72, 36];
    } else if (that.data.info.prize.length == 12) {
      resultAngle = [360, 330, 300, 270, 240, 210, 180, 150, 120, 90, 60, 30];
    }
    // 转动盘子
    let rotateAngle =
      startRotDegree +
      randCircle * 360 +
      resultAngle[resultIndex] -
      (startRotDegree % 360);
    startRotDegree = rotateAngle;
    that.setData({
      rotateAngle: "rotate(" + rotateAngle + "deg)"
    })

    // 旋转结束后，允许再次触发
    setTimeout(() => {
      clickFlag = true;
      this.gameOver();
    }, 5000); // 延时，保证转盘转完
  },
  gameOver: function() {
    if (prizeCode === 0) {
      //未中奖
      console.log("未中奖");
      this.setData({
        noPrize: true
      })
    } else if (prizeCode === 1) {
      //中奖
      console.log("中奖");
      this.setData({
        winPrize: true
      })
    }
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
    re.request(api.get_prizeRecords, postData, header).then(result => {
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
  /**
   * 分享
   */
  onShareAppMessage: function(res) {
    if (res.from === 'button') {
      // 来自页面内转发按钮
      console.log(res.target)
    }
    return {
      title: '幸运大转盘',
      path: 'package/pages/wheelsurf/wheelsurf?wheelsurfid=' + this.data.wheelsurfid
    }
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
  closeExplain: function() {
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