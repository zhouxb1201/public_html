Component({
  /**
   * 组件的属性列表
   */
  properties: {
    index: String,
    customitem: Object,
    customform: Object,
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
    //获取自定义表单多行输入的值
    customTextareaFun: function (e) {
      let index = e.currentTarget.dataset.index;
      let textareaValue = e.detail.value;
      this.data.customform[index].value = textareaValue;
      this.triggerEvent('customformInfo', { customform: this.data.customform })
    },
  }
})
