var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var header = getApp().header;
var time = require('../../../../utils/time.js');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    page_index:1,
    page_size:20,
    couponBannerImg: getApp().publicUrl + '/wap/static/images/coupon-adv.png',
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    const that = this;
    const value = wx.getStorageSync('user_token')
    if (value) {
      console.log('已登录');
      that.couponCentre();
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
      that.couponCentre();
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

  /*
     * 有关参数
     * id : canvas 组件的唯一标识符 canvas-id 
     * x : canvas 绘制圆形的半径 
     * w : canvas 绘制圆环的宽度 
     */
  drawCircleBg: function (id, x, w) {
    // 设置圆环外面盒子大小 宽高都等于圆环直径
    this.setData({
      size: 2 * x   // 更新属性和数据的方法与更新页面数据的方法类似
    });
    // 使用 wx.createContext 获取绘图上下文 ctx  绘制背景圆环
    var ctx = wx.createCanvasContext(id)
    ctx.setLineWidth(w / 2);
    ctx.setStrokeStyle('rgb(232, 199, 201)');
    ctx.setLineCap('round');
    ctx.beginPath();//开始一个新的路径
    //设置一个原点(x,y)，半径为r的圆的路径到当前路径 此处x=y=r
    ctx.arc(x, x, x - w, 0, 2 * Math.PI, false);
    ctx.stroke();//对当前路径进行描边
    ctx.draw();

  },
  drawCircledraw: function (id, x, w, step) {
    // 使用 wx.createContext 获取绘图上下文 context  绘制彩色进度条圆环
    var context = wx.createCanvasContext(id);
    // 设置渐变
    var gradient = context.createLinearGradient(2 * x, x, 0);
    gradient.addColorStop("0", "rgb(255, 69, 78)");
    // gradient.addColorStop("0.5", "#40ED94");
    // gradient.addColorStop("1.0", "#5956CC");
    context.setLineWidth(w);
    context.setStrokeStyle(gradient);
    context.setLineCap('round')
    context.beginPath();//开始一个新的路径
    // step 从0到2为一周
    context.arc(x, x, x - w, -Math.PI / 2, step * Math.PI - Math.PI / 2, false);
    context.stroke();//对当前路径进行描边
    context.draw()
  },

  

  //获取优惠券
  couponCentre: function () {
    const that = this;
    let postData = {
      'page_index': that.data.page_index,
      'page_size': that.data.page_size
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_couponCentre,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          let couponList = res.data.data.list;
          for (var i = 0; i < couponList.length; i++) {
            couponList[i].start_time = time.js_date_time(couponList[i].start_time);
            couponList[i].end_time = time.js_date_time(couponList[i].end_time);
            couponList[i].discount = parseInt(couponList[i].discount);
            let circle_bg = 'circle_bg'+i;
            let circle_draw = 'circle_draw'+i;
            if (couponList[i].count == 0){
              that.drawCircleBg(circle_bg, 25, 4);
              couponList[i].circle_num = '无限制';
            }else{
              let circle_num = parseFloat(couponList[i].receive_times / couponList[i].count).toFixed(2);
              couponList[i].circle_num = Math.round(circle_num * 100) + '%';
              that.drawCircleBg(circle_bg, 25, 4);
              that.drawCircledraw(circle_draw, 25, 4, circle_num * 2);//从0到2为一周， 所以要 * 2
            }
            
          }
          
          that.setData({
            couponList: couponList,
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

  //用户领取优惠券
  userArchiveCoupon: function (e) {
    const that = this;
    if (that.checkPhone() == false){
      return
    }
    let coupon_type_id = e.currentTarget.dataset.couponid;
    let postData = {
      'coupon_type_id': coupon_type_id,
      'get_type': 10
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_userArchiveCoupon,
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
          that.couponCentre();

        } else {
          wx.showModal({
            title: '提示',
            content: res.data.message,
            showCancel:false,
          })
        }
      },
      fail: (res) => { },
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
      that.couponCentre();
    }
  },


})