Component({
  /**
   * 组件的属性列表
   */
  properties: {
    temDataitem: Object,
    type: String
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
    _onSearchPage: function() {
      const that = this;
      if (that.data.temDataitem.shop_id != undefined) {
        wx.navigateTo({
          url: '/pages/search/index?shopId=' + that.data.temDataitem.shop_id,
        })
      } else if (that.properties.type == 9) {
        wx.navigateTo({
          url: '/pages/search/index?searchKey=integralgoods'
        })
      } else {
        wx.navigateTo({
          url: '/pages/search/index',
        })
      } 
    }
  }
})