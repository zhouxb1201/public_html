var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    live_id: '',
    page_index: 1,
    //是否预约了提醒 1-预约了提醒 0-未预约提醒
    is_remind: 1,
    //是否关注 1-已关注 0-未关注
    is_focus: 0,
    hiddtime:false,
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {
    this.data.live_id = options.live_id;
  },


  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function() {
    this.getAdvanceLiveData();
    this.getAnchorGoodsList();
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


  },


  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function() {
    const that = this;
    that.data.page_index = that.data.page_index + 1;
    that.getAnchorGoodsList();
  },

  //获取预告内容
  getAdvanceLiveData: function() {
    const that = this;
    let postData = {
      'live_id': that.data.live_id
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getAdvanceLiveData,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code < 0) {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
        } else {
          that.setData({
            live_advance_info: res.data.live_advance_info,
            is_remind: res.data.live_advance_info.is_remind,
            is_focus: res.data.live_advance_info.is_focus
          })
          that.newTime(res.data.live_advance_info.advance_limit_time);
          that.getAnchorGoodsList();
        }

      },
      fail: (res) => {},
    })
  },

  //挑选的商品
  getAnchorGoodsList: function() {
    const that = this;
    let postData = {
      'page_index': that.data.page_index,
      'page_size': 10,
      'anchor_id': that.data.live_advance_info.anchor_id
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getAnchorGoodsList,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 1) {
          if (that.data.page_index > 1) {
            let goodlist = that.data.goodlist;
            goodlist = goodlist.concat(res.data.data.anchor_goods_list);
            that.setData({
              goodlist: goodlist
            })
          } else {
            that.setData({
              goodlist: res.data.data.anchor_goods_list
            })
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

  //添加直播间提醒、想看数
  addLiveRemind: function() {
    const that = this;
    let postData = {
      'live_id': that.data.live_advance_info.live_id,
      'anchor_id': that.data.live_advance_info.anchor_id
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_addLiveRemind,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 1) {

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

  //添加直播间提醒、想看数
  addLiveRemind: function() {
    const that = this;
    let postData = {
      'live_id': that.data.live_advance_info.live_id,
      'anchor_id': that.data.live_advance_info.anchor_id
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_addLiveRemind,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 1) {
          that.setData({
            is_remind: 1
          })
          wx.showToast({
            title: res.data.message,
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

  //取消直播间提醒、想看数
  cancleLiveRemind: function() {
    const that = this;
    let postData = {
      'live_id': that.data.live_advance_info.live_id,
      'anchor_id': that.data.live_advance_info.anchor_id
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_cancleLiveRemind,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 1) {
          that.setData({
            is_remind: 0
          })
          wx.showToast({
            title: res.data.message,
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

  //添加或取消关注
  focus: function() {
    const that = this;
    let postData = {
      'follow_uid': that.data.live_advance_info.follow_uid
    }
    let url = ''
    let is_focus = that.data.is_focus;

    if (is_focus == 1) {
      url = api.get_cancleFocus;
    } else {
      url = api.get_addFocus;
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: url,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 1) {
          if (is_focus == 1) {
            that.setData({
              is_focus: 0
            })
          } else {
            that.setData({
              is_focus: 1
            })
          }
          wx.showToast({
            title: res.data.message,
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

  /**
   * 倒计时函数
   */
  newTime: function(time) {
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
      if (oDay < 10) {
        oDay = '0' + oDay
      }

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

      console.log(remainingTime)
      // that.setData({
      //   uplogtime: remainingTime
      // })
      that.setData({
        d: oDay,
        h: oHours,
        minute: oMinutes,
        second: oSeconds,
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
        }



      }


    }
    var timer = setInterval(resetTime, 1000);


  },

})