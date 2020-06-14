var time = require('../../../../utils/time.js');
var requestSign = require('../../../../utils/requestData.js');
var util = require('../../../../utils/util.js');
var api = require('../../../../utils/api.js').open_api;
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    //生日
    currentDate: '',
    maxDate: new Date().getTime(),
    minDate: new Date(1970,0,1).getTime(),
    getTime:'',
    //生日弹框
    birthdayShow:false,
    sex_list: [
      { name: '1', value: '男', checked: false},
      { name: '2', value: '女', checked: false},
      { name: '0', value: '保密', checked: false},
    ],
    areaList:'',
    areaIdList:'',
    areaShow:false,
    //地址
    area:'',
    //省id
    province_id:'',
    //市id
    city_id:'',
    //区id
    county_id:'',
    //性别
    sex:'',
    //用户名
    user_name:'',
    //昵称
    nick_name:'',
    //真实姓名
    real_name:'',
    //qq
    user_qq:'',
    area_code:'',
    //自定义表单数据
    customform: '',  
    
    
    
    


  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {    
    this.getAreaList();
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
    this.getMemberBaseInfo();
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
   * 获取用户基本信息
   */
  getMemberBaseInfo: function () {
    const that = this;
    let postData = {};
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getMemberBaseInfo,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          let area = '';
          if (res.data.data.province_id != 0){
            area = res.data.data.province_name + '/' + res.data.data.city_name + '/' + res.data.data.district_name;
          }          
          let sex_list = that.data.sex_list;
          for (let value of sex_list){
            if (value.name == res.data.data.sex){
              value.checked = true;
            }
          }
          if (res.data.data.birthday != 0){
            that.data.currentDate = res.data.data.birthday;
            that.timeChangeDate();
          }          

          //自定义表单数据
          if (that.data.customform == '' && res.data.data.custom_data.length > 0){
            let customform = res.data.data.custom_data;
            that.setData({
              customform: customform,
            })
            that.selectComponent('#getForm').customformDataSet();
          }         
         

          that.setData({
            real_name: res.data.data.real_name,
            user_qq: res.data.data.qq,
            nick_name: res.data.data.nick_name,
            user_name: res.data.data.user_name,
            area: area,
            sex_list: sex_list,
            sex: res.data.data.sex,
            province_id: res.data.data.province_id,
            city_id: res.data.data.city_id,
            county_id: res.data.data.district_id,
            
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



  //生日弹框展示
  birthdayFun:function(){
    this.setData({
      birthdayShow:true
    })
  },
  //生日弹框关闭
  onBirthdayClose:function(){
    this.setData({
      birthdayShow: false,
    })
  },
  /**
   * 生日日期修改
   */
  onTimeConfirm(event) {
    const that = this;
    console.log(event.detail)
    let currentDate = parseInt(event.detail / 1000) ;
    console.log(currentDate);
    that.setData({
      currentDate: currentDate,
      birthdayShow:false,
    });
    that.timeChangeDate();
  },

  //时间戳转时间格式
  timeChangeDate: function () {
    const that = this;
    let getTime = '';
    var time = new Date(that.data.currentDate * 1000);
    var year = time.getFullYear();
    var month = time.getMonth() + 1;
    if (month > 0 && month < 10) {
      month = "0" + month
    }
    var date = time.getDate();
    if (date > 0 && date < 10) {
      date = "0" + date
    }
    getTime = year + '-' + month + '-' + date;
    console.log(getTime);
    that.setData({
      getTime: getTime,
    })
  },

  /**
   * 性别单选框
   */
  sexRadioChange:function(e){
    this.setData({
      sex: e.detail.value
    })
  },

  //获取省市区
  getAreaList: function () {
    const that = this;
    var postData = {};
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

  //地址弹框打开
  areaShow:function(){
    this.setData({
      areaShow:true
    })
  },
  //地址弹框关闭
  areaOnClose:function(){
    this.setData({
      areaShow: false
    })
  },
  //地址修改
  confirmArea:function(e){
    const that = this;
    let area = e.detail.detail.province + '/' + e.detail.detail.city + '/' + e.detail.detail.county
    let province_code = e.detail.values[0].code;
    let city_code = e.detail.values[1].code;
    let county_code = e.detail.values[2].code;
    let province_id = '';
    let city_id = '';
    let county_id = '';
    let area_code = e.detail.detail.code;
    for (let key in that.data.areaIdList.province_id_list){
      if (key = province_code){
        province_id = that.data.areaIdList.province_id_list[key];
        break
      }     
    }
    for (let key in that.data.areaIdList.city_id_list) {
      if (key = city_code) {
        city_id = that.data.areaIdList.city_id_list[key];
        break
      }
    }
    for (let key in that.data.areaIdList.county_id_list) {
      if (key = county_code) {
        county_id = that.data.areaIdList.county_id_list[key];
        break
      }
    }
    that.setData({
      area:area,
      areaShow:false,
      province_id: province_id,
      city_id: city_id,
      county_id: county_id,
      area_code: area_code
    })    

  },

  //用户名
  userNameFun:function(e){
    let user_name = e.detail.value;
    this.setData({
      user_name:user_name
    })
  },

  //昵称
  nickNameFun:function(e){
    let nick_name = e.detail.value;
    this.setData({
      nick_name: nick_name
    })
  },

  //真实姓名
  realNameFun:function(e){
    let real_name = e.detail.value;
    this.setData({
      real_name: real_name
    })
  },

  //QQ
  userQQFun:function(e){
    let user_qq = e.detail.value;
    this.setData({
      user_qq: user_qq
    })
  },

  //保存信息
  saveMemberBaseInfo: function () {
    const that = this;
    if (that.checkPhone() == false){
      return;
    }
    let customform = '';
    if (that.data.customform != '') {
      let required = util.isRequired(this.data.customform);
      if (required) {
        customform = {
          "form_data": this.data.customform
        }
        customform = JSON.stringify(customform) 
      } else {
        return;
      }      
    }
    var postData = {
      real_name: that.data.real_name,
      sex: that.data.sex,
      user_name: that.data.user_name,
      nick_name: that.data.nick_name,      
      area_code: that.data.area_code,
      post_data: customform
    };
    if (that.data.currentDate != ''){
      postData.birthday = that.data.currentDate
    }
    if (that.data.user_qq != '') {
      postData.user_qq = that.data.user_qq
    }
    if (that.data.province_id != '') {
      postData.province_id = that.data.province_id;
      postData.city_id = that.data.city_id;
      postData.district_id = that.data.district_id || that.data.county_id;
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    wx.request({
      url: api.get_saveMemberBaseInfo,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if(res.data.code >= 0){
          wx.showToast({
            title: res.data.message,
            icon: 'none',
          })
          setTimeout(()=>{           
            let onPageData = {
              url: 1,
              num: 5,
              param: '',
            }
            util.jumpPage(onPageData);
          },2000)
        }else{
          wx.showToast({
            title: res.data.message,
            icon: 'none',
          })
        }
        
      },
      fail: (res) => { },
    })
  },

  //自定义表单
  customformData:function(e){
    console.log(e.detail);
    const that = this;
    that.setData({
      customform: e.detail.customform
    })
  },

  //是否有电话
  checkPhone: function () {
    let that = this;
    let have_mobile = wx.getStorageSync('have_mobile');
    if (have_mobile != true) {
      that.setData({
        phoneShow: true
      })
    }
    return have_mobile
  },

  //绑定手机结果返回
  phonereResult: function (e) {
    const that = this
    let result = e.detail.result;
    if (result == 'success') {
      
      
    }
  },


  
})