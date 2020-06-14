// packageSecond/pages/goodthingcircle/component/mine/index.js
var requestSign = require('../../../../../utils/requestData.js');
var api = require('../../../../../utils/api.js').open_api;
var header = getApp().header;
Component({
  /**
   * 组件的属性列表
   */
  properties: {

  },

  /**
   * 组件的初始数据
   */
  data: {
    info: {},
    active: 0,
    showType: 0,
    list: [],
    listParams: {
      page_index: 1,
      page_size: 10
    }
  },
  lifetimes: {
    attached: function () {
      this.getUserInfo();
    }
  },
  /**
   * 组件的方法列表
   */
  methods: {
    getUserInfo() {
      const that = this;
      let postData = {}
      let datainfo = requestSign.requestSign(postData);
      header.sign = datainfo
      wx.request({
        url: api.get_ThingcircleUserInfo,
        data: postData,
        header: header,
        method: 'POST',
        dataType: 'json',
        responseType: 'text',
        success: (res) => {
          if (res.data.code == 1) {
            that.setData({
              info: res.data.data
            })
          } else {
            wx.showToast({
              title: res.data.message,
              icon: 'none'
            })
          }
        }
      })
    },
    toFollow() {
      wx.navigateTo({
        url: '../follow/index',
      })
    },
    toFans() {
      wx.navigateTo({
        url: '../fans/index',
      })
    },
    getUserThingList(init) {
      const that = this;
      let postData = {
        page_index: init ? 1 : that.data.listParams.page_index,
        page_size: that.data.listParams.page_size
      }
      if (that.data.listParams.thing_option) {
        postData.thing_option = that.data.listParams.thing_option
      }
      let datainfo = requestSign.requestSign(postData);
      header.sign = datainfo
      wx.request({
        url: api.get_thingcircleUserThingList,
        data: postData,
        header: header,
        method: 'POST',
        dataType: 'json',
        responseType: 'text',
        success: (res) => {
          if (res.data.code >= 0) {
            const data = res.data.data
            let list = that.data.list.concat(data.data);
            that.data.listParams.page_count = data.page_count
            that.setData({
              showType: data.display_model,
              list: list
            })
          } else {
            wx.showToast({
              title: res.data.message,
              icon: 'none'
            })
          }
        }
      })
    },
    onTabsChange(e) {
      const that = this
      that.data.active = e.detail.index
      console.log(e.detail.index)
    }
  }
})