var requestSign = require('../../utils/requestData.js');
var api = require('../../utils/api.js').open_api;
var re = require('../../utils/request.js');
var header = getApp().header;
var WxParse = require('../../common/wxParse/wxParse.js');
var Base64 = require('../../utils/base64.js').Base64;
const app = getApp();
Page({

  /**
   * 页面的初始数据
   */
  data: {
    //所有图片的高度  
    imgheights: [],
    //默认  
    current: 0,
    //商品数据
    goodsList: "",
    idarray: [],
    shopId:'',
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    const that = this;
    let custom_id =  options.id
    that.data.custom_id = custom_id;
    if (options.shop_id != undefined){
      that.data.shopId = options.shop_id;
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
    this.getTemData()
     
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

  },

 


  //获取自定义数据 
  getTemData: function () {
    const that = this;
    let postData = {
      "type": 6,
      'is_mini':1,
      "id": that.data.custom_id
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_custom, postData, header).then((res) => {
      if (res.data.code < 0) {
        wx.showToast({
          title: res.data.message,
          icon: 'loading'
        })
      } else {
        //判断图片地址是本地图片还是网络图片
        let template_data = res.data.data.template_data
        for (var item in template_data.items) {
          let item_data = template_data.items[item].data;
          if (that.data.shopId != '') {
            template_data.items[item].shop_id = that.data.shopId
          }
          for (var index in item_data) {
            if (item_data[index].imgurl != undefined) {
              if (item_data[index].imgurl.substring(0, 1) == 'h') {
              } else {
                item_data[index].imgurl = getApp().publicUrl + item_data[index].imgurl
              }
            }
          }
        }

        let copyData = ''
        if (res.data.data.copyright != undefined){
          copyData = res.data.data.copyright
        }
        wx.setNavigationBarTitle({
          title: template_data.page.title,
        })
        that.setData({
          copyData: copyData,
          temData: template_data,
        });
        
      }
    })

  },

  


})