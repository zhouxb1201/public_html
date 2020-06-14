var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var util = require('../../../../utils/util.js');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    detailid:'',
    areaList:'',
    areaIdList:'',
    popupShow:false,
    areaText:'选择省/市/区',
    //收货人姓名
    receiverName:'',
    //收货人电话
    receiverPhone: '',
    //邮政编码
    postalCode: '',
    //街道地址
    streetAddress: '',
    //省id
    provinceid:'',
    //市id
    cityid:'',
    //区id
    districtid:'',
    is_default:0,
    checked: false,
    savebtnDisabled:'',
    save_test:'保存'
    
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    const that = this ;
    
    if (options.id != undefined){
      let detailid = options.id;
      that.setData({
        detailid: detailid
      })
      that.getDetailAddress(detailid);
    }
    that.getAreaList();
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

  //获取单个地址
  getDetailAddress: function (detailid){
    const that = this;
    var postData = {
      id: detailid
    };
    let header = getApp().header;
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    wx.request({
      url: api.get_receiverAddressDetail,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        that.setData({
          receiverName:res.data.data.consigner,
          receiverPhone: res.data.data.mobile,
          streetAddress: res.data.data.address,
          areaText: res.data.data.province_name,
          provinceid: res.data.data.province,
          cityid: res.data.data.city,
          districtid: res.data.data.district,
          postalCode: res.data.data.zip_code,
          is_default: res.data.data.is_default

        })
      },
      fail: (res) => { },
    })
  },

  //获取省市区
  getAreaList: function () {
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
        let areaList = {
          province_list: res.data.data.province_list,
          city_list: res.data.data.city_list,
          county_list: res.data.data.county_list,
        }
        let areaIdList = {
          province_id_list: res.data.data.province_id_list,
          city_id_list: res.data.data.city_id_list,
          county_id_list: res.data.data.county_id_list,
        }
        that.setData({
          areaList: areaList,
          areaIdList: areaIdList
        })
      },
      fail: (res) => { },
    })
  },

  //获取选择的省市区
  changeArea: function (event){
    const that =this;
    console.log(event.detail.values);
    let areaArray = event.detail.values;
    let area = [];
    let provinceCode = '';
    let cityCode = '';
    let countyCode = '';
    let provinceId= '';
    let cityId = '';
    let countyId = '';
    for(var i=0;i<areaArray.length;i++){
      area.push(areaArray[i].name);
      provinceCode = areaArray[0].code;
      cityCode = areaArray[1].code;
      countyCode = areaArray[2].code;
    }
    let areaIdList = that.data.areaIdList;
    for (var key in areaIdList.province_id_list){
      if (key == provinceCode){
         provinceId = areaIdList.province_id_list[key];
      }
    }
    for (var key in areaIdList.city_id_list) {
      if (key == cityCode) {
        cityId = areaIdList.city_id_list[key];
      }
    }
    for (var key in areaIdList.county_id_list) {
      if (key == countyCode) {
        countyId = areaIdList.county_id_list[key];
      }
    }
    let areaText = area.join("/");
    that.setData({
      areaText: areaText,
      provinceid: provinceId,
      cityid: cityId,
      districtid: countyId
    })
    that.areaOnClose();
  },

  //弹出层开启
  areaOnShow: function () {
    this.setData({
      popupShow: true
    })
  },

  //弹出层关闭
  areaOnClose: function () {
    this.setData({
      popupShow: false
    })
  },
  //收货人姓名
  receiverName:function(e){
    this.setData({
      receiverName:e.detail.value
    })
  },
  //收货人姓名
  receiverPhone: function (e) {
    this.setData({
      receiverPhone: e.detail.value
    })
    if (util.checkPhone(e.detail.value) == false){      
      wx.showToast({
        title: '手机号码有误，请重填！',
        icon:'none',
      })
    } 
    
  },
  //邮政编码
  postalCode: function (e) {
    this.setData({
      postalCode: e.detail.value
    })
  },
  //街道地址
  streetAddress: function (e) {
    this.setData({
      streetAddress: e.detail.value
    })
  },

  onSwitchChange: function ({ detail }){    
    const that = this;
    let is_default = '';
    if (detail == true){
      is_default = 1
    }else{
      is_default = 0
    }
    this.setData({ 
      checked: detail,
      is_default: is_default
    });
    
  },

  //保存新增地址
  setaddAddress:function(){
    const that = this;
    if (that.data.receiverName == ''){
      wx.showToast({
        title: '请填写收货人名称',
        icon:'none'
      })
      setTimeout(function(){
        wx.hideToast()
      },2000)
      return;      
    }
    if (that.data.receiverPhone == '') {
      wx.showToast({
        title: '请填写收货人电话',
        icon: 'none'
      })
      setTimeout(function () {
        wx.hideToast()
      }, 2000)
      return;
    }
    if (that.data.provinceid == '') {
      wx.showToast({
        title: '请填写收货人地址',
        icon: 'none'
      })
      setTimeout(function () {
        wx.hideToast()
      }, 2000)
      return;
    }
    if (that.data.streetAddress == '') {
      wx.showToast({
        title: '请填写详细地址',
        icon: 'none'
      })
      setTimeout(function () {
        wx.hideToast()
      }, 2000)
      return;
    }
    that.setData({
      save_test:'保存中....',
      savebtnDisabled:true
    })
    var postData = {
      consigner: that.data.receiverName,
      mobile: that.data.receiverPhone,
      province: that.data.provinceid,
      city: that.data.cityid,
      district: that.data.districtid,
      address: that.data.streetAddress,
      zip_code: that.data.postalCode,
      is_default: that.data.is_default,
    };
    if(that.data.detailid != ''){
      postData.id = that.data.detailid
    }
    let header = getApp().header;
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    wx.request({
      url: api.get_saveReceiverAddress,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        wx.showToast({
          title: res.data.message,
          icon: 'success'
        })        
        setTimeout(function () {
          wx.hideToast()
          wx.navigateBack({
            delta:1
          })
        }, 2000)
        
      },
      fail: (res) => { },
    })
  },
})