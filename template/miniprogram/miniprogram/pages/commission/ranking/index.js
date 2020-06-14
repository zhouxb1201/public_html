var requestSign = require('../../../utils/requestData.js');
var api = require('../../../utils/api.js').open_api;
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    active:0,
    //1推荐榜 2佣金榜 3积分榜
    types:1,
    //month月榜 year年榜 all全榜
    times:'month',
    userlist:'',
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {

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
    this.rankingData();
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

  typesChange(e){
    const that = this;
    let index = e.detail.index;
    let types = index + 1;
    that.setData({
      types: types
    })
    that.rankingData();
  },

  timeChange(e){
    const that = this;
    let times = e.currentTarget.dataset.times;
    that.setData({
      times:times
    })
    that.rankingData();
  },

  //排行榜
  rankingData: function () {
    const that = this;    
    let postData = {
      'types': that.data.types,
      'times': that.data.times,
      'psize':10,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_ranking,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {         
          let ranklist = res.data.data.rankinglists;
          let userlist = res.data.data.user;
          if(userlist.ranking > 0 && userlist.ranking < 9){
            console.log(1)
            that.userItem(userlist);
          }else{
            that.setData({
              userlist: ''
            })
          }
          
          that.addnumber(ranklist)
          that.topThree(ranklist);
          that.listItem(ranklist);
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
  userItem(userlist){
    const that = this;
    if (that.data.types == 1) {
      userlist.number = userlist.total;
      userlist.unit = '人'
    }
    if (that.data.types == 2) {
      userlist.number = userlist.commissions;
      userlist.unit = '佣金'
    }
    if (that.data.types == 3) {
      userlist.number = userlist.points;
      userlist.unit = '积分'
    }
    that.setData({
      userlist: userlist
    })

  },
  addnumber(ranklist){
    const that = this;
    let list = ranklist.map(item =>{
      if(that.data.types == 1){
        item.number = item.total;
        item.unit = '人'
      }
      if (that.data.types == 2) {
        item.number = item.commissions;
        item.unit = '佣金'
      }
      if (that.data.types == 3) {
        item.number = item.points;
        item.unit = '积分'
      }
    })
    return list
  },
  topThree(ranklist){
    const that = this;
    let topThreeArr = [];
    const list = ranklist.filter(e =>{
      if (e.ranking == 1) {
        e.sort = 2;
      }
      if (e.ranking == 2) {
        e.sort = 1;
      }
      if (e.ranking == 3) {
        e.sort = 3;
      }
      if (e.ranking < 4) {
        return e;
      }      
    })
    list.sort((a, b) => a.sort - b.sort);
    that.setData({
      topThreeList:list
    })
  },

  listItem(ranklist){
    const that = this;
    const list = ranklist.filter(e =>{
      if(e.ranking > 3){
        return e;
      }
    })
    that.setData({
      listItem:list
    })

  }

})