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
    //自定义表单的多选框
    checkboxChange: function (e) {
      const that = this;
      let index = e.currentTarget.dataset.index;
      let checkbox_array = e.detail.value;
      let checkbox_string = checkbox_array.join(',');
      let customform = that.data.customform;
      console.log(that.data.customform)
      for (let item of customform[index].options) {
        for (let v of checkbox_array){
          if (v == item.value) {
            item.checked = true
          }
        }        
      }
      that.data.customform[index].value = checkbox_string;
      that.triggerEvent('customformInfo', { customform: customform })
    },
  }
})
