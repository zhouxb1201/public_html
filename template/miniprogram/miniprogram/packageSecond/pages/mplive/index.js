var requestSign = require('../../../utils/requestData.js');
var api = require('../../../utils/api.js').open_api;
var re = require('../../../utils/request.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    page_index:1,
    index:0,
    seconds:0,
    hiddtime:false,
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    this.getMember();  
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
    this.getWapMpLiveList();    
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
    this.setData({
      hiddtime: true
    }) 
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
    const that = this;
    that.data.page_index = that.data.page_index + 1;
    that.getWapMpLiveList();
  },

  //直播广场列表
  getWapMpLiveList: function () {
    const that = this;
    wx.showLoading({
      title: '加载....',
    })
    let postData = {
      'page_index': that.data.page_index,
      'page_size': 20,

    }   
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getWapMpLiveList,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        wx.hideLoading();
        if (res.data.code === 1) {
          let live_list = '';
          if (that.data.page_index > 1) {
            let old = that.data.live_list;
            let new_live_list = old.concat(that.statusType(res.data.data));
            live_list = new_live_list;
          } else {
            live_list = that.statusType(res.data.data);
          }          
          that.setData({
            live_list: live_list,            
          })
          that.newTime();          
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

  statusType(live_list) {
    const list = live_list.filter(e => {
      if (e.live_status == 101) {
        e.status_name = '直播中'
      }
      if (e.live_status == 102) {
        e.status_name = '预告'
      }
      if (e.live_status == 103) {
        e.status_name = '回放'
      }
      if (e.live_status == 104) {
        e.status_name = '禁播'
      }
      if (e.live_status == 105) {
        e.status_name = '暂停中'
      }
      if (e.live_status == 106) {
        e.status_name = '异常'
      }
      if (e.live_status == 107) {
        e.status_name = '已过期'
      }
      return e;
    })
    return list;
  },  

  onMPLivePage(e){
    const that = this;    
    let roomId = e.currentTarget.dataset.roomid;  
    let customParams = encodeURIComponent(JSON.stringify({ extend_code: that.data.extend_code}))   
    console.log(that.data.extend_code)
    console.log(customParams)
    wx.navigateTo({
      url: `plugin-private://wx2b03c6e691cd7370/pages/live-player-plugin?room_id=${roomId}&custom_params=${customParams}`
    })
  },

  //直播状态
  getLiveStatus(room_id){
    const that = this;
    let livePlayer = requirePlugin('live-player-plugin');
    // 首次获取立马返回直播状态，往后间隔1分钟或更慢的频率去轮询获取直播状态
    const roomId = room_id // 房间 id    
    livePlayer.getLiveStatus({ room_id: roomId }).then(res => {
      // 101: 直播中, 102: 未开始, 103: 已结束, 104: 禁播, 105: 暂停中, 106: 异常，107：已过期 
      const liveStatus = res.liveStatus;      
      let live_status = that.data.live_list[that.data.index - 1].live_status      
      if (live_status != liveStatus){
        that.updateMplive(roomId, liveStatus);
        let live_list = that.data.live_list;
        live_list[that.data.index - 1].live_status = liveStatus;
        live_list = that.statusType(live_list);
        that.setData({
          live_list: live_list
        })
      }
      console.log('get live status success=' + roomId + ':', liveStatus)
    }).catch(err => {
      console.log('get live status fail=' + roomId + ':', err)
    })
  }, 
 

  //会员中心
  getMember: function () {
    const that = this;
    var postData = {};
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_memberIndex, postData, header).then((res) => {
      if (res.data.code >= 0) {
        that.data.extend_code = res.data.data.extend_code;
      }
    })
  },

  //把直播间的状态传给后台
  updateMplive: function (roomid, live_status) {
    const that = this;    
    let postData = {
      'roomid': roomid,
      'live_status': live_status,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_updateMplive,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        wx.hideLoading();
        if (res.data.code === 1) {
                 
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

  /**
   * 倒计时函数
   */
  newTime: function () {
    const that = this;
    let remainingTime = '';

    function resetTime() {      
      that.data.seconds = that.data.seconds + 1      
      //每隔30秒，请求一次一个直播间的状态，
      if (that.data.seconds == 10){
        that.data.seconds = 0;
        that.data.index = that.data.index + 1;
        let room_id = that.data.live_list[that.data.index - 1].roomid;
        if (that.data.index == that.data.live_list.length) {
          that.data.index = 0
        }
        that.getLiveStatus(room_id);
      }      

      //关闭定时
      if (that.data.hiddtime == true) {
        clearInterval(timer);
      }

    }
    var timer = setInterval(resetTime, 1000);


  },
})