var requestSign = require('../../../../../utils/requestData.js');
var api = require('../../../../../utils/api.js').open_api;
var util = require('../../../../../utils/util.js');
var re = require('../../../../../utils/request.js');
var header = getApp().header;
Component({
  /**
   * 组件的属性列表
   */
  properties: {
    shopkeeperInfo: Object,
    incomelist: Object
  },

  /**
   * 组件的初始数据
   */
  data: {

  },

  /**
   * 组件的方法列表
   */
  methods: {
    onWithdrawPage() {
      let incomelist = this.properties.incomelist;
      if (incomelist.profit) {
        return true;
      } else {
        return false;
      }
      wx.navigateTo({
        url: '../profit/withdraw/withdraw'
      })
    },
    toGrade() { //跳转到等级中心
      let info = this.properties.shopkeeperInfo;
      wx.navigateTo({
        url: '../grade/grade?shopkeeper_level_name=' + info.shopkeeper_level_name + '&shopkeeper_level_time=' + info.shopkeeper_level_time + '&is_default_shopkeeper=' + info.is_default_shopkeeper
      })
    },
    toProfitDeatail(){ //跳转到微店收益详情
      wx.navigateTo({
        url: '../profit/detail/detail'
      })
    },
    toQrcode(){
      wx.navigateTo({
        url: '../qrcode/qrcode',
      })
    },
    toManageSet(){
      wx.navigateTo({
        url: '../manage/set/set',
      })
    },
    toChooseGoods(){
      wx.navigateTo({
        url: '../choosegoods/category/category',
      })
    },
    toPreviewIndex(){      
      wx.navigateTo({
        url: '../preview/index/index?shopkeeper_id=' + this.data.shopkeeperInfo.uid,
      })
    },
    toCredentialCode(){
      wx.navigateTo({
        url: '/packageSecond/pages/credential/code/index',
      })
    },
  }
})