// pages/component/form-com/index.js
Component({
  /**
   * 组件的属性列表
   */
  properties: {
    customform:Object
  },

  /**
   * 组件的初始数据
   */
  data: {

  },

  lifetimes: {
    attached() {
      // 在组件实例进入页面节点树时执行
    },
    ready(){
      this.customformDataSet();
    },
    detached() {
      // 在组件实例被从页面节点树移除时执行
    },
  },

  /**
   * 组件的方法列表
   */
  methods: {
    //自定义表单
    customformFun: function (e) {
      console.log(e.detail);
      const that = this;
      that.setData({
        customform: e.detail.customform
      })
      that.triggerEvent('customformEven', { customform: e.detail.customform })
    },

    customformDataSet:function(){
      const that = this;
      let customform = that.data.customform;
      if (customform.length > 0) {

        for (var i = 0; i < customform.length; i++) {
          if (customform[i].tag == 'select') {
            if (typeof (customform[i].options) == "string") {
              customform[i].options = customform[i].options.split('\n');
            }
          }
          if (customform[i].tag == 'checkbox') {
            if (typeof (customform[i].options) == "string") {
              let checkbox_array = customform[i].options.split('\n');
              let options = [];
              for (let value of checkbox_array) {
                let item = {
                  'value': value,
                  'checked': false,
                }
                options.push(item);
              }
              customform[i].options = options
            }
          }
          if (customform[i].tag == 'radio') {
            if (typeof (customform[i].options) == "string") {
              let radio_array = customform[i].options.split('\n');
              let options = [];
              for (let value of radio_array) {
                let item = {
                  'value': value,
                  'checked': false,
                }
                options.push(item);
              }
              customform[i].options = options
            }

          }
        }
      }
      that.setData({
        customform: customform
      })
    }
  }
})
