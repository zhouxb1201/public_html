var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var util = require('../../../../utils/util.js');
var time = require('../../../../utils/time.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    page_index: 1,
    page_size: 10,
    search_text: '',
    course_list:[]
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    this.loadList();
  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {

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
    that.data.page_index = that.data.page_index + 1;
    that.loadList();
  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {

  },
  loadList:function(){
    const that = this;
    let postData = {
      'page_index': that.data.page_index,
      'page_size': that.data.page_size,
      'search_text': that.data.search_text
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    wx.request({
      url: api.get_myCourse,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          if(that.data.page_index > 1){
            let list = that.data.course_list;
            list = list.concat(res.data.data.knowledge_payment_list);
            that.setData({
              course_list: list
            })
          }else{
            that.setData({
              course_list: res.data.data.knowledge_payment_list
            })
          }
        }
        
      }
    }) 
  },
  //前往学习
  onStudy:function(e){
    const that = this;
    wx.navigateTo({
      url: '/packageSecond/pages/course/detail/index?goods_id='+e.currentTarget.dataset.goodsid,
    })
  },
  //搜索
  searchContent:function(e){
    const that = this;
    let search_text = e.detail.value
    that.setData({
      search_text: search_text,
      page_index:1
    })
    that.loadList();
  }
})