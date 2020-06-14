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
    //所以秒杀的时间
    allSecTime:'',
    currentIndex:'',
    //秒杀时间段
    condition_time:'',
    //秒杀日期
    condition_day:'',
    //标签状态
    tag_status:'',
    goods_list:'',
    isOpen: 0,
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    const that = this;
    
    that.getAllSecTime().then((res)=>{
      if (res.data.code > 0) {
        that.setData({
          allSecTime: res.data.data,
          condition_day: res.data.data[0].condition_day,
          condition_time: res.data.data[0].condition_time,
          tag_status: res.data.data[0].tag_status,
        })
        that.getSeckillGoodsList().then((res) => {
          if (res.data.code) {
            let goods_list = res.data.data.sec_goods_list;    
            for(let value of goods_list){
              value.robbed_percent = parseFloat(value.robbed_percent);
            }
            that.setData({
              goods_list: goods_list
            })
          } else {
            wx.showToast({
              title: res.data.message,
              icon: 'none',
            })
          }
        })
      } else {
        wx.showToast({
          title: res.data.message,
          icon: 'none'
        })        
      }
    });
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
    this.setData({
      isOpen: getApp().globalData.config.addons.seckill
    })
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
    that.data.page_index = that.data.page_index + 1
    that.getSeckillGoodsList().then((res)=>{
      if(res.data.code){
        let goods_list = that.data.goods_list;
        goods_list = goods_list.concat(res.data.data.sec_goods_list);
        that.setData({
          goods_list: goods_list
        })        
      }else{
        wx.showToast({
          title: res.data.message,
          icon:'none',
        })
      }
    })
  },

  /**
   * 获取所有秒杀时间
   */
  getAllSecTime: function () {
    const that = this;
    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    return new Promise((resolve,reject) =>{
      wx.request({
        url: api.get_getAllSecTime,
        data: postData,
        header: header,
        method: 'POST',
        dataType: 'json',
        responseType: 'text',
        success: (res) => {
          resolve(res);
        },
        fail: (res) => { },
      })
    })
    
  },

  /**
   * 切换时间效果
   */
  onTapFun:function(e){
    const that = this;
    let currentIndex = e.currentTarget.dataset.index;
    let condition_time = e.currentTarget.dataset.conditiontime;
    let condition_day = e.currentTarget.dataset.conditionday;
    let tag_status = e.currentTarget.dataset.tagstatus;
    that.setData({
      currentIndex: currentIndex,
      condition_time: condition_time,
      condition_day: condition_day,
      tag_status: tag_status,
    });

    that.getSeckillGoodsList().then((res) => {
      if (res.data.code) {
        let goods_list = res.data.data.sec_goods_list;
        for (let value of goods_list) {
          value.robbed_percent = parseFloat(value.robbed_percent);
        }
        that.setData({
          goods_list: goods_list
        })
      } else {
        wx.showToast({
          title: res.data.message,
          icon: 'none',
        })
      }
    })
  },

  /**
   * 秒杀商品列表
   */
  getSeckillGoodsList:function(){
    const that = this;
    let postData = {
      'page_index': that.data.page_index,
      'condition_time': that.data.condition_time,
      'condition_day': that.data.condition_day,
      'tag_status': that.data.tag_status,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    return new Promise((resolve, reject) => {
      wx.request({
        url: api.get_getSeckillGoodsList,
        data: postData,
        header: header,
        method: 'POST',
        dataType: 'json',
        responseType: 'text',
        success: (res) => {
          resolve(res);
        },
        fail: (res) => { },
      })
    })
  },

  /**
   * 跳转到商品页
   */
  onGoodDetail:function(e){
    const that = this;
    let goods_id = e.currentTarget.dataset.goodsid;   
    let onPageData = {
      url: '/pages/goods/detail/index',
      num: 4,
      param: '?goodsId=' + goods_id,
    }
    util.jumpPage(onPageData);
  },

  /**
   * 收藏商品
   */
  collectionGood:function(e){
    const that = this;
    let goods_id = e.currentTarget.dataset.goodsid;
    let seckill_id = e.currentTarget.dataset.seckillid;
    let is_collection = e.currentTarget.dataset.iscollection;
    let postData = {
      'goods_id': goods_id,
      'seckill_id': seckill_id,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    let url = '';
    if (is_collection){
      url = api.get_cancelCollectGoods; 
    }else{
      url = api.get_collectGoods;
    }
    wx.request({
      url: url,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if(res.data.code > 0){
          that.getSeckillGoodsList().then((res) => {
            if (res.data.code) {
              let goods_list = res.data.data.sec_goods_list;
              that.setData({
                goods_list: goods_list
              })
            } else {
              wx.showToast({
                title: res.data.message,
                icon: 'none',
              })
            }
          })
          wx.showToast({
            title: res.data.message,
            icon: 'success'
          })
        }else{
          wx.showToast({
            title: res.data.message,
            icon:'none'
          })
        }
      },
      fail: (res) => { },
    })

  }
})