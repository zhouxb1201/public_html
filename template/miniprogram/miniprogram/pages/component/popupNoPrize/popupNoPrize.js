Component({
  properties: {
    isShow: {
      type: Boolean,
      value: true
    }
  },
  data: {
    publicUrl: getApp().publicUrl
  },
  methods: {
    onClose: function () {
      this.setData({
        isShow: false
      })
    }
  }
})