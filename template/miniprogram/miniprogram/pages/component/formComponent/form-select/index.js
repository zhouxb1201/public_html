
Component({
  /**
   * 组件的属性列表
   */
  properties: {
    index: String,   
    customitem: Object, 
    customform: Object
  },

  /**
   * 组件的初始数据
   */
  data: {
    //自定义表单下拉框定位
    selectIndex: '',
  },

  

  /**
   * 组件的方法列表
   */
  methods: {
    //自定义表单的下拉框选择器
    bindPickerChange: function (e) {
      let index = e.currentTarget.dataset.index;
      this.setData({
        selectIndex: e.detail.value,
      })
      this.data.customform[index].value = this.data.customform[index].options[e.detail.value];
      this.triggerEvent('customformInfo', { customform: this.data.customform })
    },
    
  }
})
