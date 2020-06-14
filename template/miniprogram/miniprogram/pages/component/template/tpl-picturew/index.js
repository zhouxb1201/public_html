var util = require('../../../../utils/util.js');
Component({
  /**
   * 组件的属性列表
   */
  properties: {
    temDataitem: Object
  },

  /**
   * 组件的初始数据
   */
  data: {
    img_list:'',
  },

  lifetimes: {
    attached() {
      // 在组件实例进入页面节点树时执行
      const that = this;
      that.imageList(that.data.temDataitem);
      
    },
    detached() {
      // 在组件实例被从页面节点树移除时执行
    },
  },

  

  observers: {
    'temDataitem': function (temDataitem) {
      // 每次 setData 都触发
      const that = this;
      that.imageList(temDataitem);
    },
  },
  

  /**
   * 组件的方法列表
   */
  methods: {
    //链接跳转
    linkurlPage:function(e){
      let url = e.currentTarget.dataset.linkurl
      url =  '/' + url 
      let onPageData = {
        url: url,
        num: 4,
        param: ''
      }
      util.jumpPage(onPageData); 
    },

    //触发组件
    touchEvent:function(){
      const that = this;
      that.imageList();
    },

    imageList: function (temDataitem){
      const that = this;      
      let img_list = [];
      if (temDataitem.params.row == '1') {
        for (let i in temDataitem.data) {
          img_list.push(temDataitem.data[i])
        }
        that.setData({
          img_list: img_list
        })
      }
    },

  }
})
