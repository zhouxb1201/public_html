var util = require('../../utils/util.js');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    historyArray: [],
    inputValue: '',
    //搜索的类型（shop-搜索店铺，搜索商品）
    searchKey: '',
    shopId: '',
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {
    var that = this;
    console.log(options.searchKey)
    if (options.searchKey == 'shop') {
      that.setData({
        searchKey: options.searchKey
      })
      wx.getStorage({
        key: 'searchShopLog',
        success: function(res) {
          console.log(res.data);
          that.setData({
            historyArray: res.data
          })
        },
      });
    } else if (options.searchKey == 'integralgoods') {
      that.setData({
        searchKey: options.searchKey
      })
      wx.getStorage({
        key: 'searchIntegralLog',
        success: function(res) {
          console.log(res.data);
          that.setData({
            historyArray: res.data
          })
        },
      });
    } else if (options.searchKey == 'microshopchoose') {
      that.setData({
        searchKey: options.searchKey
      })
      wx.getStorage({
        key: 'searchMicroshopchooseLog',
        success: function(res) {
          console.log(res.data);
          that.setData({
            historyArray: res.data
          })
        },
      });
    } else if (options.searchKey == 'microshoppreview') {
      that.setData({
        searchKey: options.searchKey
      })
      wx.getStorage({
        key: 'searchMicroshoppreviewLog',
        success: function (res) {
          console.log(res.data);
          that.setData({
            historyArray: res.data
          })
        },
      });
    } else {
      wx.getStorage({
        key: 'searchLog',
        success: function(res) {
          console.log(res.data);
          that.setData({
            historyArray: res.data
          })
        },
      });
    }

    if (options.shopId != undefined) {
      that.data.shopId = options.shopId;
    }


  },

  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function() {

  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function() {

  },

  searchValue: function(e) {
    const that = this;
    that.setData({
      inputValue: e.detail.value,
    })
  },
  searchSubmit: function() {
    const that = this;
    //这里的_val是获取input值的临时变量
    let _val = that.data.inputValue;
    if (this.data.searchKey == 'shop') {
      that.onShopPage(_val);
    } else if (this.data.searchKey == 'integralgoods') {
      that.onIntegralGoodsPage(_val);
    } else if (this.data.searchKey == 'microshopchoose') {
      that.onChooseGoodsPage(_val);
    } else if (this.data.searchKey == 'microshoppreview') {
      that.onPreviewGoodsPage(_val);
    } else {
      that.onGoodsListPage(_val);
    }

  },

  //跳转回店铺列表
  onShopPage: function(_val) {
    //检索的字符串值没有出现，返回 -1。
    if (this.data.historyArray.indexOf(_val) == -1) {
      this.data.historyArray.unshift(_val);
      //把新的arr存储到缓存中
      wx.setStorage({
        key: 'searchShopLog',
        data: this.data.historyArray,
      })
    }
    getApp().globalData.searchSign = 'shop';
    getApp().globalData.searchShopKey = _val;
    let onPageData = {
      url: '/pages/shop/list/index',
      num: 4,
      param: ''
    }
    util.jumpPage(onPageData);
  },

  //跳转回商品列表
  onGoodsListPage: function(_val) {
    if (this.data.historyArray.indexOf(_val) == -1) {
      this.data.historyArray.unshift(_val);
      //把新的arr存储到缓存中
      wx.setStorage({
        key: 'searchLog',
        data: this.data.historyArray,
      })
    };
    let parm = '';
    if (this.data.shopId != '') {
      parm = '?key=' + _val + '&shop_id=' + this.data.shopId;
    } else {
      parm = '?key=' + _val;
    }

    getApp().globalData.searchSign = 'goods';
    let onPageData = {
      url: '/pages/goodlist/index',
      num: 4,
      param: parm,
    }
    util.jumpPage(onPageData);
  },
  //跳转回积分商城列表
  onIntegralGoodsPage: function(_val) {
    if (this.data.historyArray.indexOf(_val) == -1) {
      this.data.historyArray.unshift(_val);
      //把新的arr存储到缓存中
      wx.setStorage({
        key: 'searchIntegralLog',
        data: this.data.historyArray,
      })
    }
    let parm = '?search_text=' + _val;
    getApp().globalData.searchSign = 'integralgoods';
    let onPageData = {
      url: '/package/pages/integral/goods/list/list',
      num: 4,
      param: parm,
    }
    util.jumpPage(onPageData);

  },
  //跳转回微店挑选商品
  onChooseGoodsPage: function(_val) {
    if (this.data.historyArray.indexOf(_val) == -1) {
      this.data.historyArray.unshift(_val);
      //把新的arr存储到缓存中
      wx.setStorage({
        key: 'searchMicroshopchooseLog',
        data: this.data.historyArray,
      })
    }
    let parm = '?key=' + _val;
    getApp().globalData.searchSign = 'microshopchoose';
    let onPageData = {
      url: '/package/pages/microshop/choosegoods/list/list',
      num: 4,
      param: parm,
    }
    util.jumpPage(onPageData);

  },
  onPreviewGoodsPage:function(_val){
    if (this.data.historyArray.indexOf(_val) == -1) {
      this.data.historyArray.unshift(_val);
      //把新的arr存储到缓存中
      wx.setStorage({
        key: 'searchMicroshoppreviewLog',
        data: this.data.historyArray,
      })
    }
    let parm = '?key=' + _val;
    getApp().globalData.searchSign = 'microshoppreview';
    let onPageData = {
      url: '/package/pages/microshop/preview/list/list',
      num: 4,
      param: parm,
    }
    util.jumpPage(onPageData);
  },
  //清理缓存中的搜索记录
  clearSearch: function() {
    if (this.data.searchKey == 'shop') {
      wx.removeStorage({
        key: 'searchShopLog',
        success: function(res) {
          console.log(res);
        },
      });
    } else if (this.data.searchKey == 'integralgoods') {
      wx.removeStorage({
        key: 'searchIntegralLog',
        success: function(res) {
          console.log(res);
        },
      });
    } else if (this.data.searchKey == 'microshopchoose') {
      wx.removeStorage({
        key: 'searchMicroshopchooseLog',
        success: function(res) {
          console.log(res);
        },
      });
    } else if (this.data.searchKey == 'microshoppreview') {
      wx.removeStorage({
        key: 'searchMicroshoppreviewLog',
        success: function (res) {
          console.log(res);
        },
      });
    } else {
      wx.removeStorage({
        key: 'searchLog',
        success: function(res) {
          console.log(res);
        },
      });
    }

    this.setData({
      historyArray: []
    })
  },

  changeValue: function(e) {
    const that = this;
    let last_val = e.currentTarget.dataset.value;
    that.setData({
      inputValue: last_val
    })
    that.searchSubmit();
  },

  cancelValue: function() {
    this.setData({
      inputValue: ''
    })
  }


})