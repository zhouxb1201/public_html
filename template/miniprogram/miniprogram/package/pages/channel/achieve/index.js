var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var util = require('../../../../utils/util.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    page_index:1,
    date_time:'',
    year:'',
    month:'',
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    const that = this;
    that.newDate();
    that.myChannelPerformance();
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
    const that = this;
    that.data.page_index += 1;
    that.myChannelPerformance();
  },

  //我的业绩
  myChannelPerformance: function () {
    const that = this;
    wx.showLoading({
      title: '加载中',
    })
    let postData = {
      'page_index': that.data.page_index,
      'page_size':20,
      'date_time': that.data.date_time,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_MyChannelPerformance,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        wx.hideLoading();
        if (res.data.code >= 0) {
          let myChannelData = res.data.data.data;
          
          if (that.data.page_index >= 2) {
            let oldData = that.data.myChannelData;
            let newData = '';
            newData = oldData.concat(myChannelData);
            that.setData({
              myChannelData: newData
            })
          } else {
            that.setData({
              myChannelData: myChannelData
            })
          }


        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
        }

      },
      fail: (res) => {
        
      },
    })
  },

  
  

  //当前时间
  newDate:function(){
    const that = this;
    let dateTime = new Date();
    let year = dateTime.getFullYear();
    let month = dateTime.getMonth() + 1;
    if(month < 10){
      month = '0' + month
    }
    that.data.year = year;
    that.data.month = month;
    let date_time = year + '-' + month
    that.setData({
      date_time: date_time
    })
  },

  //处理月份函数
  addMonth:function(e){
    const that = this;
    let num = e.currentTarget.dataset.num;
    num = parseInt(num);    
    let year = parseInt(that.data.year);
    let month =  parseInt(that.data.month);

    let eYear = year;
    let eMonth = month + num;
    while(eMonth > 12){
      eYear++;
      eMonth-=12;
    }
    while (eMonth < 1) {
      eYear--;
      eMonth += 12;
    }
    if (eMonth < 10) {
      eMonth = '0' + eMonth
    }
    that.data.year = eYear;
    that.data.month = eMonth;
    let date_time = eYear + '-' + eMonth;
    that.setData({
      date_time: date_time
    })
    that.myChannelPerformance();
  },

  



})