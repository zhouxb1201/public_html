var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var header = getApp().header;
var Base64 = require('../../../../utils/base64.js').Base64;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    //账户类型
    typeArray: [],
    index:-1,
    //账户姓名
    realname:'',
    //账户号码
    account_number:'',
    // 银行名称
    bank_name:'',
    info:'',
    state:'',
    account_id:'',
    //账户类型
    account_type:'3',
    
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    const that = this;
    let type_list = getApp().globalData.config.config.withdraw_conf.withdraw_message;
    let typeArray = [];
    for (let item of type_list) {
      console.log(item);
      
      if (item == '1'|| item== '4') {
        typeArray.push({
          text: "银行卡",
          type: item,
          
        })
      }
      if (item == '3') {
        typeArray.push({
          text: "支付宝",
          type: item,
          checked:true
        })
      }
    }
    this.setData({
      typeArray
    })
    
    if (options.fun == 'edit'){
      wx.setNavigationBarTitle({
        title:'编辑账户'
      })
      let info = Base64.decode(options.info);
      info = JSON.parse(info);
      let type_name = '';
      if (info.type == 1||info.type == 4){
        type_name = '银行卡'
        for (let i in typeArray){
          if(typeArray[i].type==info.type){
            typeArray[i].checked=true
          }
        }
      } else if (info.type == 3){
        type_name = '支付宝'
        for (let i in typeArray){
          if(typeArray[i].type==info.type){
            typeArray[i].checked=true
          }
        }
      }
      console.log(info);
      that.setData({
        typeArray: typeArray,
        realname: info.realname,
        account_number: info.account_number,
        info:info,
        account_type: info.type,
        state:'edit',  
        bank_name:info.open_bank,  
        type_name:type_name, 
        account_id: info.account_id
      })
    }else{
      wx.setNavigationBarTitle({
        title: '新增账户'
      })
      
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
  //账户类型选择
  bindPickerChange(e) {
    const that = this;
    console.log(e)
    that.setData({
      
        account_type: e.detail.value
    })
  },
  //账户姓名
  realnameFun:function(event){    
    this.data.realname = event.detail.value;
  },
  //账户号码
  accountNumberFun:function(event){
    this.data.account_number = event.detail.value;
  },
  //银行姓名
  bankNameFun: function (event) {
    this.data.bank_name = event.detail.value;
  },
  //银行卡号
  accountNumberFun: function (event) {
    this.data.account_number = event.detail.value;
  },
  //银行人姓名
  realNameFun: function (event) {
    this.data.realname = event.detail.value;
  },
  //新增账户
  addBankAccount:function(){
    const that = this;
    let realname = that.data.realname;
    let type = (parseInt(that.data.account_type));
    let account_number = that.data.account_number;
    let bank_name = this.data.bank_name;
    if(realname == ''){
      wx.showToast({
        title: '请输入账户姓名',
        icon:'loading',
      })
      return;
    }
    if (type <= 0) {
      wx.showToast({
        title: '请输入账户类型',
        icon: 'loading',
      })
      return;
    }
    if (account_number == '') {
      wx.showToast({
        title: '请输入账户号码',
        icon: 'loading',
      })
      return;
    }
    if (type==1||type==4){
      if (bank_name == '') {
        wx.showToast({
          title: '请输入银行名称',
          icon: 'loading',
        })
        return;
      }
    }   
    let url = '';
    let postData = {
      'realname': realname,
      'type': type,
      'account_number': account_number,
      "bank_name": bank_name
    };
    if (that.data.state == 'edit') {
      url = api.get_update_account;
      postData['account_id'] = that.data.account_id;
    } else {
      url = api.get_add_bank_account;      
    }    
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;    
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
          })
        
            wx.navigateTo({
              url:'../account/index'
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
  }
  
})