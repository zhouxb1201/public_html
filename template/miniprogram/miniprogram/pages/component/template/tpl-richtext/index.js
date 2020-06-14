var WxParse = require('../../../../common/wxParse/wxParse.js');
var Base64 = require('../../../../utils/base64.js').Base64;
Component({
  /**
   * 组件的属性列表
   */
  properties: {
    temDataitem:Object
  },

  /**
   * 组件的初始数据
   */
  data: {

  },

  lifetimes: {
    attached() {
      // 在组件实例进入页面节点树时执行
      const that = this;
      var richtext = that.data.temDataitem.params.content;
      var result = Base64.decode(richtext);
      WxParse.wxParse('richtext', 'html', result, that);
    },
    detached() {
      // 在组件实例被从页面节点树移除时执行
    },
  },

  /**
   * 组件的方法列表
   */
  methods: {

  }
})
