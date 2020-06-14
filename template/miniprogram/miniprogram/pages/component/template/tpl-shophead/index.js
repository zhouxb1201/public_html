var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var re = require('../../../../utils/request.js');
var util = require('../../../../utils/util.js');
var header = getApp().header;

Component({
  /**
   * 组件的属性列表
   */
  properties: {
    temDataitem: Object
  },

  /**
   * 组件的初始数据
   */
  data: {
    //店铺id
    shopId: '',
    //店铺基础数据
    shopData: '',
    //自定义模板数据
    temData: '',
    dataUrl: getApp().publicUrl,
    goodsList: '',

    //店铺是否被收藏
    isCollection: '',
    items: {}
  },

  lifetimes: {
    attached() {
      // 在组件实例进入页面节点树时执行
      const that = this;
      that.data.shopId = that.data.temDataitem.shop_id;
      that.initCustom(that.data.temDataitem);      
      that.getShopInfo();
    },
    ready() {

    },
    detached() {
      // 在组件实例被从页面节点树移除时执行
    },
  },

  /**
   * 组件的方法列表
   */
  methods: {
    //获取店铺数据  
    getShopInfo: function() {
      const that = this;
      wx.showToast({
        title: '加载中',
        icon: 'loading'
      })
      setTimeout(function() {
        wx.hideToast();
      }, 3000)
      let postData = {
        'shop_id': that.data.shopId,
      }

      let datainfo = requestSign.requestSign(postData);
      header.sign = datainfo;
      re.request(api.get_shopInfo, postData, header).then((res) => {
        if (res.data.code >= 0) {
          let shopData = res.data.data          
          let isCollection = res.data.data.is_collection
          that.setData({
            shopData: shopData,
            isCollection: isCollection
          })
        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
        }
      })
    },

    //附近门店
    allStoreFun: function () {
      const that = this;
      let onPageData = {
        url: '/package/pages/store/list/index',
        num: 4,
        param: '',
      }
      util.jumpPage(onPageData);
    },

    //收藏店铺
    collectShopFun: function() {
      const that = this;
      let postData = {
        'shop_id': that.data.shopId
      }

      let datainfo = requestSign.requestSign(postData);
      header.sign = datainfo
      wx.request({
        url: api.get_collectShop,
        data: postData,
        header: header,
        method: 'POST',
        dataType: 'json',
        responseType: 'text',
        success: (res) => {
          if (res.data.code >= 0) {
            that.getShopInfo();
          } else {
            wx.showToast({
              title: res.data.message,
              icon: 'none'
            })
          }
        },
        fail: (res) => {},
      })
    },

    //取消收藏店铺
    cancelcollectShopFun: function() {
      const that = this;
      let postData = {
        'shop_id': that.data.shopId
      }

      let datainfo = requestSign.requestSign(postData);
      header.sign = datainfo
      wx.request({
        url: api.get_cancelCollectShop,
        data: postData,
        header: header,
        method: 'POST',
        dataType: 'json',
        responseType: 'text',
        success: (res) => {
          if (res.data.code >= 0) {
            that.getShopInfo();
          } else {
            wx.showToast({
              title: res.data.message,
              icon: 'none'
            })
          }
        },
        fail: (res) => {},
      })
    },

    onSearchPage: function() {
      const that = this;
      wx.navigateTo({
        url: '/pages/search/index?shopId=' + that.data.shopId,
      })
    },
    onGoodsPage: function () {
      const that = this;
      wx.navigateTo({
        url: '/pages/goodlist/index?shop_id=' + that.data.shopId,
      })
    },
    initCustom: function(temDataitem) {
      const that = this;
      let item = temDataitem;
      let src = "";
      let type = item.params.styletype;
      if (type == undefined) {
        type = 1
      }
      if (item.style && item.style.backgroundimage) {
        src = item.style.backgroundimage;
      } else {
        src = that.data.dataUrl + '/wap/static/images/style/shop-head-0' + type + '.jpg';
      }
      const items = {
        bgSrc: src,
        styletype: type
      };
      that.setData({
        items: items
      })
    }


  }
})