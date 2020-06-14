var requestSign = require('../../../../../utils/requestData.js');
var api = require('../../../../../utils/api.js').open_api;
var header = getApp().header;
var WxParse = require('../../../../../common/wxParse/wxParse.js');
var time = require('../../../../../utils/time.js');
var util = require('../../../../../utils/util.js');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    boxShow:false,
    //1全球代理, 2区域代理, 3团队代理
    type: '',
    //申请成为代理商的条件数据
    bonusApplyData: '',
    radioChecked: false,
    //真实姓名
    real_name: '',
    
    level_array:['省级代理','市级代理','区级代理'],
    level_index:'',
    //代理级别（省-1，市-2，区-3）
    bonus_level:'',
    //代理区域
    bonusAreaList:'',
    bonusAreaIdList:'',
    //代理区域底部弹框显示
    bonusAreaShow:false,
    //代理区域名称
    bonus_area_name: '',
    bonus_province_id: '',
    bonus_city_id: '',
    bonus_district_id: '',

    //自定义表单数据
    customform: '',
   
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    const that = this;
    let type = 2;
    that.data.type = type;
    //判断手机是否存在
    if (util.hasPhone() == false){
      that.setData({
        phoneShow:true
      })
    }else{
      that.setData({
        boxShow: true
      })
      that.applyagent()
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

  //申请成为代理商，所需条件，情况多种，
  applyagent: function () {
    const that = this;
    let postData = {
      'type': that.data.type
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_applyagent,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          let result = res.data.data.area_bonus_agreement.content;
          WxParse.wxParse('content', 'html', result, that);

          //自定义表单数据
          let customform = res.data.data.area_bonus_agreement.customform;
          let customform_array = Object.keys(customform);//判断对象是否为空，返回值是数组
          if (customform_array.length != 0){
            that.setData({
              customform: customform
            })
          }
          that.setData({
            bonusApplyData: res.data.data,
          })
          that.areaTxt();

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

  //申请全球分红文案
  areaTxt: function () {
    const that = this;
    let areaTxtData = that.data.bonusApplyData.area_bonus_agreement;
    wx.setNavigationBarTitle({
      title: areaTxtData.apply_area,
    })
    that.setData({
      txt_area_agreement: areaTxtData.area_agreement
    })
  },

  //图片错误
  errorimg: function () {
    const that = this;
    let bonusApplyData = that.data.bonusApplyData;
    bonusApplyData.global_bonus_agreement.logo = '/images/rectangle-error.png'
    that.setData({
      bonusApplyData: bonusApplyData
    })
  },

  //阅读协议勾选
  radioApplyChange: function (e) {
    const that = this;
    let radioChecked = e.currentTarget.dataset.checked;
    if (radioChecked == true) {
      that.setData({
        radioChecked: false
      })
    } else {
      that.setData({
        radioChecked: true
      })
    }
  },

  //真实姓名
  realNameFun: function (e) {
    const that = this;
    that.setData({
      real_name: e.detail.value
    })
  },

  //选择代理级别
  bindLevelPickerChange:function(e){
    const that = this;
    that.setData({
      level_index: e.detail.value,
      bonus_level: (parseInt(e.detail.value) +1)
    })
  },

  //代理区域底部弹框显示
  bonusAreaShow:function(){
    const that = this;
    if (that.data.bonus_level == ''){
      wx.showToast({
        title: '请先选择代理级别',
        icon:'none'
      })
      return;
    }
    that.setData({
      bonusAreaShow:true,
    });
    that.getBonusAreaList();
  },

  //代理区域底部弹框关闭
  onBonusAreaClose:function(){
    const that = this;
    that.setData({
      bonusAreaShow: false,
    })
  },

  //获取代理省市区
  getBonusAreaList: function () {
    const that = this;
    var postData = {};
    let header = getApp().header;
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    wx.request({
      url: api.get_area,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        let bonusAreaList = {
          province_list: res.data.data.province_list,
          city_list: res.data.data.city_list,
          county_list: res.data.data.county_list,
        }
        let bonusAreaIdList = {
          province_id_list: res.data.data.province_id_list,
          city_id_list: res.data.data.city_id_list,
          county_id_list: res.data.data.county_id_list,
        }
        that.setData({
          bonusAreaList: bonusAreaList,
          bonusAreaIdList: bonusAreaIdList
        })
      },
      fail: (res) => { },
    })
  },

  //代理区域选择改变时触发
  bonusAreaChange: function (e){
    const that = this;
    console.log(e.detail.values);
    let bonusArea_array = e.detail.values;
    let area = [];
    let province_id = '';
    let city_id = '';
    let district_id = '';
    for (let value of bonusArea_array){
      area.push(value.name);
    }

    let province_code = bonusArea_array[0].code;
    province_id = that.data.bonusAreaIdList.province_id_list[province_code];

    if(area.length == 2){
      let city_code = bonusArea_array[1].code;
      city_id = that.data.bonusAreaIdList.city_id_list[city_code];
    }

    if(area.length == 3){
      let city_code = bonusArea_array[1].code;
      city_id = that.data.bonusAreaIdList.city_id_list[city_code];

      let district_code = bonusArea_array[2].code;
      district_id = that.data.bonusAreaIdList.county_id_list[district_code];
    }

    let area_name = area.join('/');
    that.setData({
      bonus_area_name:area_name,
      bonus_province_id: province_id,
      bonus_city_id: city_id,
      bonus_district_id: district_id,
      bonusAreaShow:false,
    })
  },

  //申请成为地区代理
  areaAgentApply: function () {
    const that = this;
    if (that.data.radioChecked == false) {
      wx.showToast({
        title: '请先阅读协议',
        icon: 'none'
      })
      return;
    }
    let postData = {};
    if (that.data.customform != '') {
      let required = util.isRequired(that.data.customform);
      if (required) {
        postData['post_data'] = JSON.stringify(that.data.customform);
      } else {
        return;
      }      
    } else {
      postData['real_name'] = that.data.real_name;
      postData['area_id'] = that.data.bonus_level;
      postData['province_id'] = that.data.bonus_province_id;
      postData['city_id'] = that.data.bonus_city_id;
      postData['district_id'] = that.data.bonus_district_id;
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_areaAgentApply,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          wx.showToast({
            title: res.data.message,
            icon: 'success'
          })
          wx.navigateBack({
            delta: 1
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

  //自定义表单
  customformData: function (e) {
    console.log(e.detail);
    const that = this;
    that.setData({
      customform: e.detail.customform
    })
  },

  //绑定手机结果返回
  phonereResult: function (e) {
    const that = this
    let result = e.detail.result;
    if (result == 'success') {
      that.setData({
        boxShow: true
      })
      that.applyagent()
    }
  },







  


})