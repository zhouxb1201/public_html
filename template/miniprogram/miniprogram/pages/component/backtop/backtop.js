// pages/component/backtop/backtop.js
Component({
  /**
   * 组件的属性列表
   */
  properties: {
    backTopValue:Boolean
  },

  /**
   * 组件的初始数据
   */
  data: {
    // top标签显示（默认不显示）
    backTopValue: false,
  },

  /**
   * 组件的方法列表
   */
  methods: {
    
    // 滚动到顶部
    backTop: function () {
      const that = this;
      // 控制滚动
      wx.pageScrollTo({
        scrollTop: 0
      })
    }
  }
})
