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
    //自定义表单的单选框
    radioChange: function (e) {
      const that = this;
      let index = e.currentTarget.dataset.index;
      let radio_string = e.detail.value;
      let customform = that.data.customform; 
      for (let item of customform[index].options){
        item.checked = false;
        if (radio_string == item.value){
          item.checked = true
        }
      }
      
      that.data.customform[index].value = radio_string;
      this.triggerEvent('customformInfo', { customform: customform })
    },
  }
})
