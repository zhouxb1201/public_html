var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var util = require('../../../../utils/util.js');
var Base64 = require('../../../../utils/base64.js').Base64;
var header = getApp().header;

Page({

  /**
   * 页面的初始数据
   */
  data: {
    loginShow:false,
    bargain_id:'',
    goods_id:'',
    bargain_uid:'',
    //当前砍价数据
    action_bargain:'',
    //用户砍价记录id
    bargain_record_id:'',
    //倒计时-天
    oDay: '00',
    //倒计时-时
    oHours: '00',
    //倒计时-分
    oMinutes: '00',
    //倒计时-秒
    oSeconds: '00',
    //sku弹出框
    skuShow:false,
    sku:'',
    selectArray:[],
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    const that = this;
    
    //判断是否登录
    const value = wx.getStorageSync('user_token')
    if (value) {
      console.log('已登录');
      that.checkHaveChone();
    } else {
      console.log('未登录')
      wx.hideTabBar();
      that.setData({
        loginShow: true,
      })
    }

    that.data.bargain_id = options.bargain_id;
    that.data.goods_id = options.goods_id;
    that.data.bargain_uid = options.bargain_uid;    
    that.myActionBargain();
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
    const that = this;    
    that.myActionBargain();
  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {

  },

  // onShareAppMessage(res) {
  //   const that = this;
  //   if (res.from === 'button') {
  //     // 来自页面内转发按钮
  //     console.log(res.target)
  //   }
  //   return {
  //     title: '帮我砍一刀:' + that.data.action_bargain.goods_name,
  //     imageUrl: that.data.action_bargain.pic_cover,
  //     path: '/page/bargain/detail/index?bargain_id=' + that.data.bargain_id + '&goods_id=' + that.data.goods_id + '&bargain_uid=' + that.data.bargain_uid
  //   }
  // },

  //判断是否有手机
  checkHaveChone:function(){    
    const that = this;
    const phone = wx.getStorageSync('have_mobile')
    if (phone == false) {
      that.setData({
        phoneShow: true
      })
    }
  },

  //登录结果返回
  requestLogin: function (e) {
    const that = this;
    let result = e.detail.result;
    if (result == true) {
      that.myActionBargain();
      that.checkHaveChone();      
    }
  },


  /**
   * 我要砍价
   */
  myActionBargain: function () {
    const that = this;
    let postData = {
      'bargain_id': that.data.bargain_id,
      'goods_id': that.data.goods_id,
      'bargain_uid': that.data.bargain_uid,
    };
    console.log(postData);
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_myActionBargain,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        console.log(res);
        wx.stopPullDownRefresh()
        if (res.data.code >= 0) {
          that.countDownTime(res.data.data.end_bargain_time);

          let sku = res.data.data.sku;
          for(let value of sku.list){
            value.isSelect = 'false'
          }          

          that.setData({
            action_bargain:res.data.data,
            bargain_record_id: res.data.data.bargain_record_id,    
            sku: sku        
          })

          that.istreehas();
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


  /**
   * 倒计时函数
   */
  countDownTime: function (time) {
    const that = this;
    function resetTime() {
      //定义当前时间
      var startTime = new Date();
      //除以1000将毫秒数转化成秒数方便运算
      startTime = parseInt(startTime.getTime() / 1000)
      //定义结束时间
      var endTime = time;

      //算出中间差并且已秒数返回; ；
      var countDown = endTime - startTime;

      //获取天数 1天 = 24小时  1小时= 60分 1分 = 60秒
      var oDay = parseInt(countDown / (24 * 60 * 60));
      if (oDay < 10) {
        oDay = '0' + oDay
      }

      //获取小时数 
      //特别留意 %24 这是因为需要剔除掉整的天数;
      var oHours = parseInt(countDown / (60 * 60) % 24);
      if (oHours < 10) {
        oHours = '0' + oHours
      }

      //获取分钟数
      //同理剔除掉分钟数
      var oMinutes = parseInt(countDown / 60 % 60);
      if (oMinutes < 10) {
        oMinutes = '0' + oMinutes
      }

      //获取秒数
      //因为就是秒数  所以取得余数即可
      var oSeconds = parseInt(countDown % 60);
      if (oSeconds < 10 && oSeconds >= 0) {
        oSeconds = '0' + oSeconds
      }



      that.setData({
        oDay: oDay,
        oHours: oHours,
        oMinutes: oMinutes,
        oSeconds: oSeconds,
      })

      //别忘记当时间为0的，要让其知道结束了;
      if (countDown < 0) {
        clearInterval(timer);
        
      }
    }
    var timer = setInterval(resetTime, 1000);

  },

  //帮忙砍价
  helpBargain:function(){
    const that = this;
    if (that.data.action_bargain.is_help_bargain == false){
      wx.showToast({
        title: '不能帮砍',
        icon:'none',
      })
      return;
    }
    let postData = {      
      'bargain_record_id': that.data.bargain_record_id,
    };
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_helpBargain,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        console.log(res);
        if (res.data.code >= 0) {
          that.myActionBargain();
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

  //我要砍价
  myBargain:function(){
    const that = this;
    that.setData({
      bargain_uid: that.data.action_bargain.bargain_uid
    })
    that.myActionBargain();
  },


  //sku弹出层开启
  skuShow: function () {
    this.setData({
      skuShow: true
    })
  },
  //sku弹出层关闭
  skuOnclose: function () {
    this.setData({
      skuShow: false
    })
  },

  //判断是否有sku规格
  istreehas:function(){
    const that = this;
    var sku = that.data.sku;
    if (sku.hasOwnProperty('tree')) {
      //有属性规格      
      that.setData({
        skuGroupShow: true,
      })
    } else {
      that.setData({
        skuGroupShow: false,
      })
      //没有规格(基本属性)
      that.basicAttribute();
    }
  },


  //规格属性的选择
  clickMenu: function (e) {
    const that = this;
    var selectIndex = e.currentTarget.dataset.selectIndex;//组的index
    var attrIndex = e.currentTarget.dataset.attrIndex;//当前的index
    var attrId = e.currentTarget.dataset.attrId;
    let sku = that.data.sku;
    let spec = sku.tree;

    for (var i = 0; i < spec.length; i++) {
      for (var n = 0; n < spec[i].v.length; n++) {
        if (selectIndex == i) {
          spec[selectIndex].v[n].isSelect = "false";
        }
      }
    }
    spec[selectIndex].v[attrIndex].isSelect = "true";

    that.setData({
      sku: sku
    })

    that.data.selectArray[selectIndex] = attrId.toString();

    for (var m = 0; m < sku.list.length; m++) {
      let bool = that.arrayIsEqual(that.data.selectArray.sort(), sku.list[m].s.sort());
      let maxBuy = '';
      if (bool == true) {
        if (sku.list[m].hasOwnProperty('max_buy')) {
          maxBuy = sku.list[m].max_buy
        } else if (sku.list[m].hasOwnProperty('group_limit_buy')) {
          maxBuy = sku.list[m].group_limit_buy
        } else {
          maxBuy = sku.list[m].stock_num
        }
        that.setData({
          stockNum: sku.list[m].stock_num,
          specName: sku.list[m].sku_name,
          skuId: sku.list[m].id,
          buyNum: 1,
          maxBuy: maxBuy,
        })
        if (sku.list[m].stock_num == 0) {
          wx.showToast({
            title: '没有库存',
            icon: 'none'
          })
        }
        break;
      }
    }
    
  },
  

  //没有规格(基本属性)
  basicAttribute:function(){
    const that = this;
    let sku = that.data.sku;
    that.setData({
      stockNum: sku.list[0].stock_num,
      skuId: sku.list[0].id,
      buyNum: 1,
      maxBuy: sku.list[0].max_buy,
    })
  },
  

  //判断2个数组是否相等
  arrayIsEqual: function (arr1, arr2) {
    if (arr1 === arr2) {//如果2个数组对应的指针相同，那么肯定相等，同时也对比一下类型
      return true;
    } else {
      if (arr1.length != arr2.length) {
        return false;
      } else {//长度相同
        for (let i in arr1) {//循环遍历对比每个位置的元素
          if (arr1[i] != arr2[i]) {//只要出现一次不相等，那么2个数组就不相等
            return false;
          }
        }//for循环完成，没有出现不相等的情况，那么2个数组相等
        return true;
      }
    }
  },

  //购买数量
  changeBuynum: function (e) {
    let stockNum = this.data.stockNum;
    if (e.detail == stockNum) {
      wx.showToast({
        title: '库存不足',
        icon: 'none'
      })
      setTimeout(function () {
        wx.hideToast()
      }, 1000)
    }
    this.setData({
      buyNum: e.detail
    })
  },

  //立即下单
  buyNowOrder: function () {
    const that = this;    
    let sku_id = that.data.skuId;
    let url = '';
    let skuObj = {
      sku_id: sku_id,
      num: that.data.buyNum,
      bargain_id: that.data.bargain_id,
    };
    if (sku_id == '') {
      wx.showToast({
        title: '请选择规格数量！',
        icon: 'loading'
      })
      return
    }
    let sku_list = [];
    sku_list.push(skuObj);
    let params = {
      sku_list: sku_list,
      order_tag: 'buy_now',
    }

    params = Base64.encode(JSON.stringify(params));    

    let onPageData = {
      url: '/pages/orderInfo/index',
      num: 4,
      param: '?params=' + params
    }
    util.jumpPage(onPageData);

  },


  onGoodsDetailPage:function(){
    const that = this;
    let onPageData = {
      url: '/pages/goods/detail/index',
      num: 4,
      param: '?goodsId=' + that.data.goods_id
    }
    util.jumpPage(onPageData);
  }
})