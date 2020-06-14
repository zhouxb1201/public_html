var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var re = require('../../../../utils/request.js');
var header = getApp().header;
Component({
  /**
   * 组件的属性列表
   */
  properties: {
    temDataitem:Object
  },

  /**
   * 组件的初始数据
   */
  data: {

  },

  lifetimes: {
    attached() {
      // 在组件实例进入页面节点树时执行
      const that = this;
      let recommendnum = that.data.temDataitem.params.recommendnum
      that.getShopList(recommendnum);
    },
    detached() {
      // 在组件实例被从页面节点树移除时执行
    },
  },

  /**
   * 组件的方法列表
   */
  methods: {
    //请求店铺列表
    getShopList: function (recommendnum) {
      const that = this;
      let postData = {
        'order': 'shop_create_time',
        'sort': 'ASC',
        'page_index': 1
      }
      let datainfo = requestSign.requestSign(postData);
      header.sign = datainfo;
      re.request(api.get_shopSearch, postData, header).then((res) => {
        if (res.data.code > 0) {
          let shopsList = [];
          for (var i = 0; i < res.data.data.shop_list.length; i++) {
            if (i == recommendnum) {
              break
            }
            shopsList.push(res.data.data.shop_list[i])
          }
          that.setData({
            shopsList: shopsList
          })
        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
        }
      })

    },
  }
})
