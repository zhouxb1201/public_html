var requestSign = require('../../../utils/requestData.js');
var api = require('../../../utils/api.js').open_api;
var util = require('../../../utils/util.js');
var WxParse = require('../../../common/wxParse/wxParse.js');
var time = require('../../../utils/time.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    //申请类型replenish-完善资料
    apply_status:'',
    //是否是分销商，0未申请，1代表待审核，2代表已通过，3代表资料不完整，-1不通过审核
    isdistributor:'',
    //申请条件
    distributorApplyData:'',
    radioChecked:false,
    //1: 满足以下所有条件，2: 满足条件之一，-1：直接申请
    distributor_condition:'',
    //需要满足的条件，2:订单消费达到多少远，3：订单数达到，4：购买商品，并完成订单，5：购买指定商品
    conditions_array:'',
    //真实姓名
    real_name:'',

    //自定义表单数据
    customform: '',
    
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    const that = this;
    if (options.isdistributor != undefined){
      that.setData({
        isdistributor: options.isdistributor
      })
    }    
    if (options.applyType == 'replenish'){
      that.setData({
        apply_status: options.applyType
      })
      wx.setNavigationBarTitle({
        title: '完善资料',
      })
    }
    
    that.distributorApply_show();
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

  //申请成为分销商条件
  distributorApply_show:function(){
    const that = this;
    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_distributorApply_show,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if(res.data.code >= 0){
          let result = res.data.data.xieyi.content;
          if (result) WxParse.wxParse('content', 'html', result, that);
          let distributor_conditions = res.data.data.condition.distributor_conditions;
          let conditions_array = distributor_conditions.split(',');

          that.setData({
            distributorApplyData:res.data.data,
            distributor_condition: res.data.data.condition.distributor_condition,
            conditions_array: conditions_array, 
            isdistributor: res.data.data.isdistributor,           
          })

          //自定义表单数据
          if (that.data.customform == ''){
            let customform = res.data.data.customform;
            if (customform.length > 0){
              that.setData({
                customform: customform
              })
              that.selectComponent('#getForm').customformDataSet();
            }            
          }
          
        }else{
          wx.showToast({
            title: res.data.message,
            icon:'none'
          })
        }
      },
      fail: (res) => { },
    })
  },

  //阅读协议勾选
  radioApplyChange:function(e){
    const that = this;
    let radioChecked = e.currentTarget.dataset.checked;
    if (radioChecked == true){
      that.setData({
        radioChecked:false
      })
    }else{
      that.setData({
        radioChecked: true
      })
    }
  },

  //真实姓名
  realNameFun:function(e){
    const that = this;    
    that.setData({
      real_name: e.detail.value
    })
  },

  //申请成为分销商，提交表单
  distributorapply:function(){
    const that = this;
    if (that.data.radioChecked == false){
      wx.showToast({
        title: '请先阅读协议',
        icon:'none'
      })
      return;
    }
    let postData = {};
    if (that.data.customform.length != 0){
      let required = util.isRequired(that.data.customform);
      if (required) {
        postData['post_data'] = JSON.stringify(that.data.customform);
      } else {
        return;
      }      
    }else{
      postData['real_name'] = that.data.real_name;
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    let url = '';
    if (that.data.apply_status == 'replenish'){
      url = api.get_dataComplete
    }else{
      url = api.get_distributorapply
    }
    wx.request({
      url: url,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {          
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
          setTimeout(()=>{            
            let onPageData = {
              url: '1',
              num: 5,
              param: '',
            }
            util.jumpPage(onPageData);
          },1500)
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

  //自定义表单
  customformData: function (e) {
    console.log(e.detail);
    const that = this;
    that.setData({
      customform: e.detail.customform
    })
  },




  
})