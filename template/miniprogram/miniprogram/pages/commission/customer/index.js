var requestSign = require('../../../utils/requestData.js');
var api = require('../../../utils/api.js').open_api;
var re = require('../../../utils/request.js');
var util = require('../../../utils/util.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    pageShow:false,
    page_index:1,
    customer_list:'',
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
    const that = this;
    const value = wx.getStorageSync('user_token');
    if(value){
      that.distributionCenter();
      that.setDistributionData();
      that.customerList();
    }else{
      this.setData({
        loginShow: true,
      })
    }
    
  },

  //登录结果返回
  requestLogin: function (e) {
    const that = this;
    let result = e.detail.result;
    if (result == true) {
      that.distributionCenter();
      that.setDistributionData();
      that.customerList();
    }
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
    that.data.page_index = that.data.page_index +1;
    that.customerList();
  },

  //分销中心，判断是否申请成为分销商
  distributionCenter: function () {
    const that = this;
    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    wx.showLoading({
      title: '加载中',
    })
    re.request(api.get_distributionCenter, postData, header).then((res) => {
      if (res.data.code >= 0) {
        wx.hideLoading();
        let isdistributor = res.data.data.isdistributor
        if (isdistributor != 2) {
          wx.showModal({
            title: '提示',
            content: '你还不是分销商，请先申请！',
            success(res) {
              if (res.confirm) {
                let onPageData = {
                  url: '../apply/index',
                  num: 4,
                  param: '?isdistributor=' + isdistributor,
                }
                util.jumpPage(onPageData);
              } else if (res.cancel) {
                wx.navigateBack({
                  delta: 1,
                })
              }
            }
          })
        } else {
          that.setData({
            pageShow: true
          })
        }
      } else {
        wx.showToast({
          title: res.data.message,
          icon: 'none'
        })
      }
    })
  },

 

  //客户列表
  customerList:function(){
    const that = this;
   
    let postData = {
      'page_index': that.data.page_index
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_customerList, postData, header).then((res) =>{
      if (res.data.code >= 0) {
        if (that.data.page_index > 1){
          let old_customer_list = that.data.customer_list;
          old_customer_list = old_customer_list.concat(res.data.data.data);
          that.setData({
            customer_list: old_customer_list,
          })
        }else{
          that.setData({
            customer_list: res.data.data.data,
          })
        }

        console.log(that.data.customer_list);
        
      } else {
        wx.showToast({
          title: res.data.message,
          icon: 'none'
        })
      }
    })   
    
  },

  //设置分销的文案字段
  setDistributionData: function () {
    const that = this;
    let distributionData = getApp().globalData.distributionData;
    if (distributionData == '') {
      util.distributionSet().then((res) => {
        let resultData = res.data.data;
        wx.setNavigationBarTitle({
          title: resultData.my_customer,
        })
      });

    } else {
      wx.setNavigationBarTitle({
        title: distributionData.my_customer,
      })      
    }
  },
})