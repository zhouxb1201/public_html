const app = getApp();
var requestSign = require('../../utils/requestData.js');
var api = require('../../utils/api.js').open_api;
var util = require('../../utils/util.js');
var re = require('../../utils/request.js');
var header = getApp().header;

Page({

  /**
   * 页面的初始数据
   */
  data: {
    boxShow:false,
    //自定义模板数据
    copyData: "",
    temData: "",
    //所有图片的高度  
    imgheights: [],
    //默认  
    current: 0,
    //商品数据
    goodsList: "",
    idarray: [],

    //精选店铺
    shopsList: '',
    recommendnum: "",

    customerImgUrl:'',
    publicUrl: app.publicUrl
  },

  bindViewTap: function (event) {
    let v = event.currentTarget.dataset.recommendnum
    this.setData({
      recommendnum: v
    })
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    const that = this;  
  
    // 扫码进来
    if (options.scene != undefined) {
      let scene = options.scene;
      let scene_arr = scene.split('_');
      if (scene_arr[0] != -1) {
        wx.setStorageSync('higherExtendCode', scene_arr[0]);
      }      
      wx.setStorageSync('posterId', scene_arr[1])//获取超级海报id
      wx.setStorageSync('posterType', scene_arr[2])//获取超级海报类型                         
      const value = wx.getStorageSync('user_token');
      if (value) {//已登录
        util.checkReferee();
      }
    }
    
    if (options.extend_code != undefined) {
      wx.setStorageSync('higherExtendCode', options.extend_code)
    }

    wx.showLoading({
      title: '加载中',
    })
     
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
    that.getTemData();
    // util.extend_code();
    that.getMember();
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

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {
    let that = this;
    let path_url = "/pages/index/index";
    console.log(that.data.extend_code)
    if (that.data.extend_code != '') {
      path_url = path_url + '?extend_code=' + that.data.extend_code;
      console.log(path_url);
    }

    return {
      path: path_url,
      success: (res) => {
        console.log('转发成功！');
      },
      fail: (res) => {
        console.log('转发失败！');
      }
    }
  },

  //获取自定义数据 
  getTemData: function () {
    const that = this;
    let postData = {
      "type": 1,
      "is_mini":1
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_custom, postData, header).then((res) => {
      wx.hideLoading();      
      if (res.data.code < 0) {        
        wx.showModal({
          title: '提示',
          content: res.data.message,
          showCancel:true,
        })
      } else {        
        //判断图片地址是本地图片还是网络图片
        let template_data = res.data.data.template_data
        for (var item in template_data.items) {
          let item_data = template_data.items[item].data;
          for (var index in item_data) {
            if (item_data[index].imgurl != undefined) {
              if (item_data[index].imgurl.substring(0, 1) == 'h') {
              } else {
                item_data[index].imgurl = getApp().publicUrl + item_data[index].imgurl
              }
            }
          }
        }

        let copyright = '';
        if (res.data.data.copyright != undefined) {
          copyright = res.data.data.copyright
        }
        that.initCustomData(template_data);
        that.setData({
          copyData: copyright,
          temData: template_data,
          boxShow:true,
        });   
      }
    })

  },

  // 监听滚动条坐标
  onPageScroll: function (e) {
    const that = this;
    let scrollTop = e.scrollTop;
    let backTopValue = scrollTop > 500 ? true : false;
    that.setData({
      backTopValue: backTopValue
    })
  },
  //获取template_data中items数据
  initCustomData(template_data) {
    const that = this;
    const templateItems = template_data.items;
    for (let key in templateItems) {
      const item = templateItems[key];
      if (item.id == "customer"){
        that.setData({
          customerImgUrl: item.params.imgurl
        })
      }
    }
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



})