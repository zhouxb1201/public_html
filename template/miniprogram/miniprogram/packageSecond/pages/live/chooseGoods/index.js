var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {    
    clientHeight: 0,
    itemIndex: 0,
    //分类列表
    category_list: '',
    //分类id
    category_id: '',
    page_index: 1,
    search_text: '',
    //商品列表
    goods_list: '',
    
   
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    const that = this;
    that.data.anchor_id = options.anchor_id;
    //获取滚动条可滚动高度
    wx.getSystemInfo({
      success: (res) => {
        this.setData({
          clientHeight: res.windowHeight - res.windowWidth / 750 * 96
        });
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
    const that = this;
    that.getGoodsCate();    
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
    that.pickGoods();
  },

  //获取分类
  getGoodsCate: function () {
    const that = this;
    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getGoodsCate,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          that.setData({
            category_list: res.data.data.category_list,
            category_id: res.data.data.category_list[0].category_id
          })

          that.pickGoods();
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

  //根据分类id获取商品列表
  pickGoods: function () {
    const that = this;
    let postData = {
      'page_index': that.data.page_index,
      'page_size': 20,      
      'category_id': that.data.category_id,
      'search_text': that.data.search_text,
      'anchor_id': that.data.anchor_id
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_pickGoods,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          if (that.data.page_index >= 2) {
            let old_goods_list = that.data.goods_list;
            let new_goods_list = old_goods_list.concat(res.data.message.data);
            that.setData({
              goods_list: new_goods_list
            })
          } else {
            that.setData({
              goods_list: res.data.message.data
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


  //查询商品
  searchFun: function (e) {
    const that = this;
    that.data.page_index = 1;
    that.setData({
      search_text: e.detail.value,
    })
    that.pickGoods();
  },

  navChange: function (e) {
    const that = this;
    that.data.page_index = 1;
    that.setData({
      category_id: e.currentTarget.dataset.id,
      itemIndex: e.currentTarget.dataset.index,
      goods_list: '',
    });
    that.pickGoods();
  },


  //挑选商品
  actPickGoods: function (e) {
    const that = this;
    let goods_id = e.currentTarget.dataset.goodsid
    let postData = {     
      'goods_id': goods_id,
      'anchor_id': that.data.anchor_id
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_actPickGoods,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          let goods_list = that.data.goods_list;
          for(let i=0;i<goods_list.length;i++){
            if (goods_list[i].goods_id == goods_id){
              goods_list[i].is_picked = true
              break;
            }
          }
          that.setData({
            goods_list: goods_list
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


  //取消商品
  canclePickGoods: function (e) {
    const that = this;
    let goods_id = e.currentTarget.dataset.goodsid
    let postData = {
      'goods_id': goods_id,
      'anchor_id': that.data.anchor_id
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_canclePickGoods,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          let goods_list = that.data.goods_list;
          for (let i = 0; i < goods_list.length; i++) {
            if (goods_list[i].goods_id == goods_id) {
              goods_list[i].is_picked = false
              break;
            }
          }
          that.setData({
            goods_list: goods_list
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
 

  

  
  



})