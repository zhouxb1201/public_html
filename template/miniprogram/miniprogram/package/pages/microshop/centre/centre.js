var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var util = require('../../../../utils/util.js');
var re = require('../../../../utils/request.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    pageShow: false,
    //是否是分销商，0未申请，1代表待审核，2代表已通过，3代表资料不完整，-1不通过审核
    isdistributor: '',
    //分销商数据
    distributorData: '',
    info:{}, //店主信息
    sets:{},
    goods_info:[],
    incomelist:{}, //收益
    order_type:''
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    let order_type = options.order_type;
    if (order_type && order_type > 2) {
      this.setData({
        order_type: order_type
      })
    }
  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {
    const value = wx.getStorageSync('user_token');
    if(value){
      this.loadData();
    }else{
      this.setData({
        loginShow: true,
      })
    }    
    getApp().globalData.credential_type = 4
  },
  //登录结果返回
  requestLogin: function (e) {
    const that = this;
    let result = e.detail.result;
    if (result == true) {
      this.loadData();
    }
  },

  loadData:function(){
    const that = this;
    let postData = {};
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_micCentreInfo, postData, header).then((res) => {
      if(res.data.code >= 0){
        let result = res.data.data;
        if (result.isdistributor == 2 || result.microshop_set.shopKeeper_check == '1' ){
          let info = {
            isdistributor: result.isdistributor,
            isshopkeeper: result.isshopkeeper,
            is_default_shopkeeper: result.is_default_shopkeeper,
            member_name: result.member_name,
            user_headimg: result.user_headimg,
            shopkeeper_level_name: result.shopkeeper_level_name,
            become_shopkeeper_time: result.become_shopkeeper_time,
            shopkeeper_level_time: result.shopkeeper_level_time,
            uid: result.uid
          }
          let sets = {
            microshop_logo: result.microshop_logo,
            microshop_name: result.microshop_name,
            shopRecruitment_logo: result.shopRecruitment_logo,
            microshop_goods: result.microshop_goods,
            microshop_introduce: result.microshop_introduce
          }
          let goods_info = result.microshop_set.goods_info;
          let incomelist = {
            profit: result.profit,
            withdrawals: result.withdrawals,
            total_profit: result.total_profit
          }
          that.setData({
            info: info,
            sets: sets,
            goods_info: goods_info,
            incomelist: incomelist,
            pageShow:true
          })
          wx.setStorageSync("isshopkeeper", result.isshopkeeper);
          wx.setStorageSync("shopkeeper_id", result.uid);

          wx.setStorageSync("microshop_logo", result.microshop_logo);
          wx.setStorageSync("microshop_name", result.microshop_name);
          wx.setStorageSync("microshop_introduce", result.microshop_introduce);
          wx.setStorageSync("shopRecruitment_logo", result.shopRecruitment_logo);
        }else{
          wx.showModal({
            title: '提示',
            content: '你还不是分销商，请先申请！',
            showCancel: false,
            success(res) {
              wx.navigateBack({
                delta: 1,
              })
            }
          })
        }
        
      }
    })
  }
})