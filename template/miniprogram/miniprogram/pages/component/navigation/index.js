const app = getApp();
Component({
  /**
   * 组件的属性列表
   */
  properties: {
    type:Number,
    url:String,
    param:String,
  },

  /**
   * 组件的初始数据
   */
  data: {
    //1-navigate 2-redirect 3-switchTab 4-reLaunch 5-navigateBack 6-exit
    openType:'',
    turl:'',
  },

  lifetimes: {
    attached() {
      // 在组件实例进入页面节点树时执行
      const that = this;
      that.urlNav(that.data.url)
    },
    detached() {
      // 在组件实例被从页面节点树移除时执行
    },
  },

  observers: {
    'url': function (url) {
      // 每次 setData 都触发
      const that = this;
      that.urlNav(url);
    },
  },

  /**
   * 组件的方法列表
   */
  methods: {
    urlNav: function (url){
      const that = this;
      let type = that.data.type;
      for (let value of app.globalData.tab_list) {
        if (value == url) {
          type = 3
        }
      }
      url = '/' + url;

      if (that.data.param != '') {
        url = url + that.data.param
      }

      switch (type) {
        case 1:
          that.setData({
            openType: 'navigate',
            turl: url
          })
          break
        case 2:
          that.setData({
            openType: 'redirect',
            turl: url
          })
          break
        case 3:

          that.setData({
            openType: 'switchTab',
            turl: url
          })
          break
        case 4:
          that.setData({
            openType: 'reLaunch',
            turl: url
          })
          break
        case 5:
          that.setData({
            openType: 'navigateBack',
            turl: url
          })
          break
        case 6:
          that.setData({
            openType: exit
          })
          break
      }
    }
  }
})
