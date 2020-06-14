Component({
  /**
   * 组件的属性列表
   */
  properties: {
    index:String,
    customitem:Object,
    customform:Object,
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
    //获取自定义表单手机框的值
    onPhoneFun: function (e) {
      let index = e.currentTarget.dataset.index;
      let phoneValue = e.detail.value;
      this.data.customform[index].value = phoneValue;
      this.triggerEvent('customformInfo', { customform: this.data.customform })
    },
  }
})
