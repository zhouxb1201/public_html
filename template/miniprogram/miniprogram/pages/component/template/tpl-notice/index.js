
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
    animationData: {}, //公告动画
    announcementText: '',//公告内容
  },

  lifetimes:{
    attached() {
      // 在组件实例进入页面节点树时执行
      const that = this;      
      that.setData({
        announcementText: that.data.temDataitem.params.text
      })
      if (that.data.temDataitem.params.leftIcon.substring(0, 1) == 'h'){
        let leftIcon = that.data.temDataitem.params.leftIcon;
        that.setData({
          leftIcon: leftIcon
        })
      }else{
        let leftIcon = getApp().publicUrl + that.data.temDataitem.params.leftIcon;
        that.setData({
          leftIcon: leftIcon
        })
      }
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
