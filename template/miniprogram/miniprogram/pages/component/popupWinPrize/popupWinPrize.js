Component({
  properties: {
    termname: String,
    prizename: String,
    isShow: {
      type: Boolean,
      value: true
    }
  },
  data: {
    publicUrl: getApp().publicUrl
  },
  methods: {
    onClose: function() {
      this.setData({
        isShow: false
      })
    },
    /**
     * 跳转到我的奖品
     */
    toPrize: function() {
      wx.navigateTo({
        url: '../../pages/prize/list/list'
      })
      this.setData({
        isShow: false
      })
    }
  }
})