
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
    //获取自定义表单（单行文本的value）
    onInputValue: function (event) {
      const that = this;
      let inputValue = event.detail;
      let index = event.currentTarget.dataset.index;
      that.setData({
        customIndex: index,
        customvalue: inputValue,
      })
      that.valueInfo();
    },

    //自定义表单赋值
    valueInfo: function () {
      this.data.customform[this.data.customIndex].value = this.data.customvalue;
      this.triggerEvent('customformInfo', { customform: this.data.customform})      
    }

  }
})
