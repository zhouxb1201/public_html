// pages/component/order/qrcode/index.js
Component({
  /**
   * 组件的属性列表
   */
  properties: {
    code_img:String,
    code_num:String,
    boxshow:Boolean,
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
    boxCodeClose:function(){
      const that = this;
      that.setData({
        boxshow:false,
      })
    }
  }
})
