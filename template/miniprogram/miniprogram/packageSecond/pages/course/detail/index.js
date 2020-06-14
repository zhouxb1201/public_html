var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var util = require('../../../../utils/util.js');
var time = require('../../../../utils/time.js');
var header = getApp().header;
const audioContext = wx.createInnerAudioContext();
Page({

  /**
   * 页面的初始数据
   */
  data: {
    goodsId: '',
    cid: '',
    //评价当前选择
    evaluatenum: 0,
    is_catalog: false,

    imgSource: {},
    source: [],
    is_buy: '',

    is_image: null,
    explain_type: null,

    evaluateData: '',
    videoOverlayShow: false,

    audioOverlayShow: false,
    playing: false, //该字段是音频是否处于播放状态的属性
    sliderTime: 0,
    currentTime: 0, //音频当前播放时长
    maxTime: 0 // 音频最大播放时长
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    const that = this;
    if (options.goods_id) {
      that.setData({
        goodsId: options.goods_id
      })
    }
    if (options.cid) {
      that.setData({
        cid: options.cid
      })
    }
    wx.showLoading({
      title: '加载中',
    })
    that.loadDataDetail();
    that.getDetailList();
    that.getGoodsReviewsList();
  },
  onUnload: function () {
    const that = this;
    //页面卸载，停止语音播放
    if (that.data.playing == true) {
      audioContext.stop();
    }
  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {

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
  //获取课程商品详情
  loadDataDetail: function () {
    const that = this;
    let postData = {
      goods_id: that.data.goodsId,
      knowledge_payment_id: that.data.cid //164
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    wx.request({
      url: api.get_courseDetail,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        wx.hideLoading();
        if (res.data.code >= 0) {
          that.setData({
            imgSource: res.data.data
          })
          if (!that.data.imgSource.is_buy && that.data.imgSource.is_see == -1) {
            that.setData({
              audioOverlayShow: true,
              videoOverlayShow: true
            })
          }
          if (res.data.data.type == 2) { //音频
            audioContext.src = res.data.data.content;
            audioContext.onCanplay(() => {
              // 必须,初始化时长
              audioContext.duration;
              // 必须,不然获取不到时长
              setTimeout(() => {
                that.setData({
                  maxTime: parseInt(audioContext.duration)
                })
              }, 20)
            })
          }
        }
      }
    })
  },
  //获取课程商品详情目录列表
  getDetailList: function () {
    const that = this;
    let postData = {
      goods_id: that.data.goodsId
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    wx.request({
      url: api.get_courseDetailList,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          that.setData({
            source: res.data.data.konwledge_payment_list,
            is_buy: res.data.data.is_buy
          })
        }
      }
    })
  },
  //评价选项卡
  checkEvaluate: function (e) {
    const that = this;
    let current = e.currentTarget.dataset.current
    that.setData({
      evaluatenum: current
    });
    if (current == 0) {
      that.data.is_image = '';
      that.data.explain_type = '';
    } else if (current == 1) {
      that.data.is_image = true;
      that.data.explain_type = '';
    } else if (current == 2) {
      that.data.explain_type = 5
      that.data.is_image = '';
    } else if (current == 3) {
      that.data.explain_type = 3;
      that.data.is_image = '';
    } else if (current == 4) {
      that.data.explain_type = 1;
      that.data.is_image = '';
    }
    that.getGoodsReviewsList();

  },
  //显示目录
  onChangeShow: function () {
    const that = this;
    that.setData({
      is_catalog: true
    })
  },
  //隐藏目录
  onChangeHide: function () {
    const that = this;
    that.setData({
      is_catalog: false
    })
  },
  //请求评价数据 
  getGoodsReviewsList: function () {
    const that = this;
    let postData = {
      "goods_id": that.data.goodsId,
      'page_index': 1,
      'page_size': 20,
      'is_image': that.data.is_image,
      'explain_type': that.data.explain_type,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_goodsReviewsList,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 1) {
          let evaluateData = res.data.data;
          for (let value of evaluateData.review_list) {
            value.addtime = time.js_date_time(value.addtime);
          }
          that.setData({
            evaluateData: evaluateData
          })
        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none',
          })
        }
      },
      fail: (res) => { },
    })
  },
  //视频播放进度
  onCurrentTime: function (event) {
    let seconds = this.data.imgSource.is_see * 60;
    if (!this.data.imgSource.is_buy && this.data.imgSource.is_see > 0) {
      if (event.detail.currentTime > seconds) {
        this.setData({
          videoOverlayShow: true
        })
      }
    }
  },
  //播放音频
  onPlayAudio: function (e) {
    const that = this;
    let event = e.currentTarget.dataset;
    //audioContext.src = event.src;
    setTimeout(() => {
      audioContext.play();
      that.setData({
        playing: true
      })
      that.onTimeupdate();
    }, 50)
  },
  //暂停播放音频
  onPauseAudio: function () {
    const that = this;
    that.setData({
      playing: false
    })
    audioContext.pause();
    that.onTimeupdate();
  },
  onTimeupdate: function () {
    const that = this;
    audioContext.onTimeUpdate(() => {
      let seconds = this.data.imgSource.is_see * 60;
      if (!this.data.imgSource.is_buy && this.data.imgSource.is_see > 0) {
        if (audioContext.currentTime > seconds) {
          audioContext.stop();
          this.setData({
            audioOverlayShow: true,
            playing: false
          })
        }
      }
      let sliderTime = parseInt(audioContext.currentTime / audioContext.duration * 100);
      that.setData({
        sliderTime: sliderTime,
        currentTime: audioContext.currentTime
      })


    })


  },
  //立即购买
  onShop: function () {
    const that = this;
    wx.navigateTo({
      url: '/pages/goods/detail/index?goodsId=' + that.data.goodsId,
    })
  },
  //点击目录中的课程
  onCloseCourse: function (e) {
    const that = this;
    that.setData({
      cid: e.currentTarget.dataset.cid,
      is_catalog: false
    })
    wx.showLoading({
      title: '加载中',
    })
    that.loadDataDetail();
    that.getDetailList();
    that.getGoodsReviewsList();
  }
})