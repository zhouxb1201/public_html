var requestSign = require('../../../../../utils/requestData.js');
var util = require('../../../../../utils/util.js');
var api = require('../../../../../utils/api.js').open_api;
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    page_index:1,
    //sku库存 1 - 采购，2-出货，3-自提，4-零售
    tag_status:1,
    //商品的sku_id
    sku_id:'',
    cloudDetailData:'',
    status_text:'采购'
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    const that = this;
    that.data.sku_id = options.sku_id;
    that.cloudStorageDetail();
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
    that.data.page_index = that.data.page_index + 1;
    that.cloudStorageDetail();
  },

  //云仓库明细
  cloudStorageDetail:function(){
    const that = this;
    wx.showLoading({
      title: '加载中',
    })
    let postData = {
      'page_index': that.data.page_index,
      'page_size': 20,
      'tag_status': that.data.tag_status,
      'sku_id': that.data.sku_id,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_cloudStorageDetail,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        wx.hideLoading();
        if (res.data.code >= 0) {
          let detailData = res.data.data.channel_goods_info;
          detailData = that.discountFun(detailData);
          if (that.data.page_index >= 2) {
            let oldData = that.data.cloudDetailData;
            let cloudDetailData = '';
            cloudDetailData = oldData.concat(detailData);
            that.setData({
              cloudDetailData: cloudDetailData
            })
          } else {
            that.setData({
              cloudDetailData: detailData
            })
          }

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

  //折扣的换算
  discountFun:function(detailData){
    const that = this;
    if(detailData.length != 0){
      for (let value of detailData) {
        value.channel_purchase_discount = parseFloat(value.channel_purchase_discount) * 100
      }
    }    
    return detailData;
  },

  //切换状态
  tagStatusFun:function(e){
    const that = this;
    that.setData({
      cloudDetailData:[]
    })
    let index = e.detail.index;
    switch(index){
      case 0:
        that.setData({
          status_text:'采购',
          tag_status:1
        })
        break
      case 1:
        that.setData({
          status_text: '出货',
          tag_status: 2
        })
        break
      case 2:
        that.setData({
          status_text: '提货',
          tag_status: 3
        })
        break
      case 3:
        that.setData({
          status_text: '零售',
          tag_status: 4
        })
        break
    }
    that.data.page_index = 1;
    that.cloudStorageDetail();
  },
})