var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    page_index:1,
    category_list:'',
    live_cate:0,
    active:1,
    //是否是主播 1-是 0-不是
    is_anchor:'',
    //直播应用是否开启
    config_show:true,
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {        
    if (getApp().globalData.config.addons.liveshopping != 1){
      this.setData({
        config_show:false
      })
    }
  }, 

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {
    this.getWapLiveList();
    this.getLiveCateList();
  },
 

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {

  },
  
  //直播广场列表
  getWapLiveList: function () {
    const that = this;
    wx.showLoading({
      title: '加载....',
    })
    let postData = {
      'page_index': that.data.page_index,
      'page_size':20,
           
    }
    if (that.data.live_cate == -1){
      postData.is_focus = 1
    }
    if (that.data.live_cate == 0) {
      postData.is_recommend = 1
    }
    if (that.data.live_cate > 0) {
      postData.live_cate = that.data.live_cate
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getWapLiveList,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        wx.hideLoading();
        if (res.data.code === 1) {         
          let live_list = '';
          if(that.data.page_index > 1){
            let old = that.data.live_list;
            let new_live_list = old.concat(that.statusType(res.data.data.live_list));
            live_list = new_live_list;
          }else{
            live_list = that.statusType(res.data.data.live_list);
          }
          
          that.setData({
            live_list: live_list,
            is_anchor: res.data.data.is_anchor,
            anchor_id: res.data.data.anchor_id
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

  statusType(live_list){
    const list = live_list.filter(e =>{
      if(e.status == -1){
        e.status_name = '拒绝'
      }
      if (e.status == 0) {
        e.status_name = '待审核'
      }
      if (e.status == 1) {
        e.status_name = '直播预告'
      }
      if (e.status == 2) {
        e.status_name = '直播中'
      }
      if (e.status == 3) {
        e.status_name = '已审核'
      } 
      if (e.status == 4) {
        e.status_name = '已下播'
      }
      return e; 
    })
    return list;
  },

  onMyLivePage(){
    wx.navigateTo({
      url: '../mylive/index',
    })
  },

  openLivePage(){
    wx.navigateTo({
      url: '../openLive/index?anchor_id=' + this.data.anchor_id,
    })
  },

  onLivePage(e){
    let status = e.currentTarget.dataset.status;
    let live_id = e.currentTarget.dataset.liveid;
    let anchor_id = e.currentTarget.dataset.anchorid;
    switch (status){
      case 1:
        wx.navigateTo({
          url: '../notice/index?live_id=' + live_id,
        })
        break;
      case 2:
        wx.navigateTo({
          url: '../player/index?live_id=' + live_id + '&anchor_id='+ anchor_id,
        })
        break;        
    }    
  },

  //分类列表
  getLiveCateList: function () {
    const that = this;
    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getLiveCateList,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code === 1) {                
                
          that.setData({
            category_list: res.data.data
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

  categoryChange(e){
    const that = this;
    let index = e.detail.index;    
    let category_list =  that.data.category_list;
    let cate_id =  category_list[index].cate_id;    
    that.setData({
      live_cate: cate_id
    })
    that.getWapLiveList();
  }

})