Component({
  /**
   * 组件的属性列表
   */
  properties: {
    copyData:Object
  },

  /**
   * 组件的初始数据
   */
  data: {
    img_src:'',
  },

  lifetimes: {
    attached() {
      // 在组件实例进入页面节点树时执行
    },
    ready() {
      const that = this;  
      that.copyFun(that.data.copyData)
    },
    detached() {
      // 在组件实例被从页面节点树移除时执行
    },
  },



  /**
   * 组件的方法列表
   */
  methods: {
    copyFun: function (copyData){
      const that = this;
      //判断图片地址是本地图片还是网络图片      
      if (!copyData.params){
        return false;
      }
      if (copyData.params.src != '') {
        if (copyData.params.src.substring(0, 1) != 'h') {
          copyData.params.src = getApp().publicUrl + copyData.params.src
        }
      };
      that.setData({
        img_src: copyData.params.src
      })  
    }
  }
})
