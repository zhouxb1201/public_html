var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    record_id:'',
    //拼团数据
    groupData:'',
    oDay:'00',
    oHours:'00',
    oMinutes:'00',
    oSeconds:'00',
    //拼团买家
    buyer_list:'',
    //结束状态（true-已结束，false-未结束）
    finish_status:'',
    //订单id
    order_id:'',
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    const that = this;
    console.log(options.record_id);
    that.data.record_id = options.record_id;
    that.getGroupMemberListForWap();
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
  //获取拼团数据
  getGroupMemberListForWap: function () {
    const that = this;
    let postData = {
      'record_id': that.data.record_id
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getGroupMemberListForWap,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          let buyer_list = [];
          for (let i = 0; i < res.data.data.buyer_list.length;i++){
            if(i<=4){
              buyer_list.push(res.data.data.buyer_list[i]);
            }else{
              break;
            }
          }
          that.newTime(res.data.data.finish_time);
          that.setData({
            groupData: res.data.data,
            buyer_list: buyer_list,
            order_id: res.data.data.self_order_id,
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

  /**
   * 倒计时函数
   */
  newTime: function (time) {
    const that = this;
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

      
      that.setData({
        oDay: oDay,
        oHours: oHours,
        oMinutes: oMinutes,
        oSeconds: oSeconds,
        finish_status:'false',
      })
      

      //别忘记当时间为0的，要让其知道结束了;
      if (countDown < 0) {
        clearInterval(timer);
        that.setData({
          finish_status:'true'
        })
      }
    }
    var timer = setInterval(resetTime, 1000);

  },

  //查看订单详情
  onOrderDetail:function(){
    const that =this;
    wx.redirectTo({
      url: '/pages/order/detail/index?orderId='+that.data.order_id,
    })
  },

  onGoodsDetail:function(){
    const that = this;
    wx.redirectTo({
      url: '/pages/goods/detail/index?goodsId=' + that.data.groupData.goods.goods_id,
    })
  }
})