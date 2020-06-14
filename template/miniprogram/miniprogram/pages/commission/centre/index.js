var requestSign = require('../../../utils/requestData.js');
var api = require('../../../utils/api.js').open_api;
var util = require('../../../utils/util.js');
var re = require('../../../utils/request.js');
var header = getApp().header;
var WxParse = require('../../../common/wxParse/wxParse.js');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    pageShow:false,
    //是否是分销商，0未申请，1代表待审核，2代表已通过，3代表资料不完整，-1不通过审核
    isdistributor: '',
    //分销商数据
    distributorData:'',

    //自定义模板数据
    temData: '',
    dataUrl: getApp().publicUrl,
    //商品数据
    goodsList: "",
    commissionFixed:{}    
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
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
    const value = wx.getStorageSync('user_token');
    wx.hideLoading();
    if(value){
      if (util.hasPhone() == false) {        
        that.setData({
          phoneShow: true,
        })
      } else {
        that.distributionCenter();
      }
      this.setDistributionData();
    }else{
      that.setData({
        loginShow: true,
      })
    }
    
    getApp().globalData.credential_type = 1
    
  },

  //登录结果返回
  requestLogin:function(e){
    const that = this;
    let result = e.detail.result;    
    if (result == true){            
      if (util.hasPhone() == false) {
        that.setData({
          phoneShow: true,
        })
      } else {
        that.distributionCenter();
      }
      that.setDistributionData();      
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

  },

  distributionCenter: function () {
    const that = this;    
    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_distributionCenter, postData, header).then((res) =>{
      if (res.data.code >= 0) {
        wx.hideLoading();
        let isdistributor = res.data.data.isdistributor;
        console.log(wx.getStorageSync('extend_code'))
        if (wx.getStorageSync('extend_code') == ''){
          wx.setStorageSync('extend_code', res.data.data.extend_code);
        }
        that.setData({
          isdistributor: isdistributor,
          distributorData: res.data.data,
        })
        that.getTemData();
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

  //获取自定义数据  
  getTemData: function () {
    const that = this;
    let postData = {
      'type': 5,
      'is_mini':1
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_custom,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          //判断图片地址是本地图片还是网络图片
          let template_data = res.data.data.template_data
          for (var item in template_data.items) {
            let item_data = template_data.items[item].data;
            for (var index in item_data) {
              if (item_data[index].imgurl != undefined) {
                if (item_data[index].imgurl.substring(0, 1) == 'h') {
                } else {
                  item_data[index].imgurl = that.data.dataUrl + item_data[index].imgurl
                }
              }
            }
          }

          that.setData({
            temData: template_data
          })
          that.initCustomData(template_data);

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

  //跳转到提现页面
  onWithdrawPage:function(e){
    const that = this;
    let is_datum = e.currentTarget.dataset.isdatum;
    if (is_datum == 2){
      wx.navigateTo({
        url: '../apply/index?applyType=replenish',
      })
    }else{
      wx.navigateTo({
        url: '../withdraw/index',
      })
    }
  },

  //绑定手机结果返回
  phonereResult: function (e) {
    const that = this
    let result = e.detail.result;
    if (result == 'success') {
      that.distributionCenter();
    }
  },

  //设置分销的文案字段
  setDistributionData:function(){
    const that = this;
    let distributionData = getApp().globalData.distributionData;
    if (distributionData == '') {
      util.distributionSet().then((res) => {
        let resultData = res.data.data;
        that.setData({
          txt_distribution_commission: resultData.distribution_commission,
          txt_withdrawable_commission: resultData.withdrawable_commission,
          txt_withdrawals_commission: resultData.withdrawals_commission,
          txt_commission: resultData.commission,
        })
      });

    } else {
      that.setData({
        txt_distribution_commission: distributionData.distribution_commission,
        txt_withdrawable_commission: distributionData.withdrawable_commission,
        txt_withdrawals_commission: distributionData.withdrawals_commission,
        txt_commission: distributionData.commission,
      })
    }
  },
  //获取template_data中items数据
  initCustomData:function(template_data) {
    const that = this;
    const templateItems = template_data.items;
    for (let key in templateItems) {
      const newItems = templateItems[key];
      if (newItems.id == "commission_fixed"){
        let item = newItems;
        let src = "";
        let type = '1';    
        if (Object.prototype.isPrototypeOf(item.params)){
          type = item.params.styletype
        }
        if (item.style && item.style.backgroundimage) {
          src = item.style.backgroundimage;
        } else {
          src = that.data.dataUrl + '/wap/static/images/style/commission-head-0' + type + '.png';
        }
        const commissionFixed = {
          bgSrc: src,
          styletype: type
        };
        that.setData({
          commissionFixed: commissionFixed
        })
      }
    }
   
  }  
  
  
})