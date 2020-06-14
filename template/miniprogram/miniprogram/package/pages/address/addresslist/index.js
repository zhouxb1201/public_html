var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var util = require('../../../../utils/util.js');
var re = require('../../../../utils/request.js');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    
    addresslist:'',
    page_index:1,
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
    that.getAddresslist()
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
    that.data.page_index = that.data.page_index + 1;
    that.getAddresslist();
  },
  //获取地址列表
  getAddresslist:function(){
    const that = this;
    var postData = {
      page_index: that.data.page_index,
      page_size:20,
    };
    let header = getApp().header;
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_receiverAddressList, postData, header).then((res) =>{
      if (res.data.code >= 0) {
        if(that.data.page_index > 1){
          let oldAddresslist = that.data.addresslist;
          oldAddresslist = oldAddresslist.concat(res.data.data.address_list);
          that.setData({
            addresslist: oldAddresslist
          })
        }else{
          that.setData({
            addresslist: res.data.data.address_list
          })
        }
        
      } else {
        wx.showToast({
          title: res.data.message,
        })
      }
    })
    
  },
  //跳转到新增地址页面
  onaddAddressPage:function(){
    if (this.checkPhone() == false){
      return;
    }
    let onPageData = {
      url: '../addAddress/index',
      num: 4,
      param: ''
    }
    util.jumpPage(onPageData);
  },

  //选定默认地址
  selectDefault:function(e){
    const that = this;
    let addressid = e.currentTarget.dataset.id;
    var postData = {
      id:addressid
    };
    let header = getApp().header;
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    wx.request({
      url: api.get_setDefaultAddress,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if(res.data.code >= 0){
          that.data.page_index = 1;
          that.getAddresslist();
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

  //删除地址
  deleteAddress:function(e){
    const that = this;
    let addressid = e.currentTarget.dataset.addressid;
    wx.showModal({
      title: '提示',
      content: '请确认是否删除该地址',
      success(res){
        if(res.confirm){
          var postData = {
            id: addressid
          };
          let header = getApp().header;
          let datainfo = requestSign.requestSign(postData);
          header.sign = datainfo;
          wx.request({
            url: api.get_deleteAddress,
            data: postData,
            header: header,
            method: 'POST',
            dataType: 'json',
            responseType: 'text',
            success: (res) => {
              wx.showToast({
                title: res.data.message,
                icon: 'none'
              })
              setTimeout(function () {
                wx.hideToast();
              }, 2000)
              that.getAddresslist();

            },
            fail: (res) => { },
          })
        }
      }
    })
    
  },

  onDetailPage:function(e){
    const that = this;
    let addressid = e.currentTarget.dataset.addressid;   

    let onPageData = {
      url: '../addAddress/index',
      num: 4,
      param: '?id=' + addressid,
    }
    util.jumpPage(onPageData);

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
      that.getAddresslist()
    }
  },

})