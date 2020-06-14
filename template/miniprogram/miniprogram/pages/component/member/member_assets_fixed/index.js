var util = require('../../../../utils/util.js');
Component({
  /**
   * 组件的属性列表
   */
  properties: {
    memberData: Object,
    items: Object,
    isLogin: Boolean,
  },

  /**
   * 组件的初始数据
   */
  data: {
    more_account: false,
    iconShow: 'downs',  
    show_array:[], 
  },
  lifetimes: {
    ready: function() {
      
      this.setData({
        coupontype_addons: getApp().globalData.config.addons.coupontype,
        giftvoucher_addons: getApp().globalData.config.addons.giftvoucher,
        store_addons: getApp().globalData.config.addons.store
      })  
    }
  },

  
  /**
   * 组件的方法列表
   */
  methods: {
   
    //跳到我的资产页
    onMyPropertyPage() {
      let value = wx.getStorageSync('user_token');
      if (value) {
        let onPageData = {
          url: '/package/pages/property/myProperty/index',
          num: 4,
          param: ''
        }
        util.jumpPage(onPageData);
      } else {
        wx.navigateTo({
          url: '/pages/logon/index',
        })
      }

    },
    //跳转到余额页
    onBalancePage() {
      let onPageData = {
        url: '/package/pages/property/balance/index',
        num: 4,
        param: ''
      }
      util.jumpPage(onPageData);
    },
    //跳转到积分页
    onPointPage() {
      let onPageData = {
        url: '/package/pages/property/points/index',
        num: 4,
        param: ''
      }
      util.jumpPage(onPageData);

    },
    //跳转到优惠券页
    onCouponPage() {
      let onPageData = {
        url: '/package/pages/coupon/list/index',
        num: 4,
        param: ''
      }
      util.jumpPage(onPageData);
    },
    //跳转到礼品卷页面
    onGifvoucherPage() {
      let onPageData = {
        url: '/package/pages/giftvoucher/list/index',
        num: 4,
        param: ''
      }
      util.jumpPage(onPageData);
    },
    //跳转到消费卡页面
    onConsumerCardPage() {
      let onPageData = {
        url: '/package/pages/consumercard/list/index',
        num: 4,
        param: ''
      }
      util.jumpPage(onPageData);
    },
    //我的资产底部展开icon切换
    iconChangeShow: function(e) {
      const that = this;
      let icon = e.currentTarget.dataset.icon;
      if (icon == 'downs') {
        that.setData({
          iconShow: 'ups',
          more_account: true,
        })
      } else {
        that.setData({
          iconShow: 'downs',
          more_account: false,
        })
      }
    },
  }
})