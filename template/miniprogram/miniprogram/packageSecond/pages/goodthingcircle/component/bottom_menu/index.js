// packageSecond/pages/goodthingcircle/component/bottom_menu/index.js
Component({
  /**
   * 组件的属性列表
   */
  properties: {
    active: Number
  },

  /**
   * 组件的初始数据
   */
  data: {
    isActive: 0
  },

  /**
   * 组件的方法列表
   */
  methods: {
    switchTo(e) {
      const that = this;
      var index = e.currentTarget.dataset.index;
      const user_token = wx.getStorageSync('user_token');
      var isTabbar;
      if (index == 0) {
        wx.navigateTo({
          url: '../home/index',
        })
      } else if (index == 1) {
        wx.navigateTo({
          url: user_token ? '../release/index' : '/pages/logon/index',
        })
      } else if (index == 2) {
        wx.navigateTo({
          url: user_token ? '../mine/index' : '/pages/logon/index',
        })
      }
      // that.setData({
      //   isActive: isTabbar
      // })
      that.triggerEvent('onTabbar', isTabbar);
    }
  }
})