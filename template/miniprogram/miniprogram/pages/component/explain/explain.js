Component({
  properties: {
    isShow: {
      type: Boolean,
      value: true
    },
    info: {
      type:Object
    }
  },
  data: {
    publicUrl: getApp().publicUrl
  },
  methods: {
    onClose:function(){
      this.triggerEvent('explainClose');
    }
  }
})