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
    dateIndex:0,
    dateshow:false,
    getTime:'',
    currentDate: new Date().getTime()
  },

  lifetimes: {
    attached() {
      // 在组件实例进入页面节点树时执行
    },
    ready(){
      const that = this;
      let customitem = that.data.customitem;      
      if (customitem.value != ''){
        that.setData({
          currentDate: customitem.value * 1000
        })
      }
      that.timeChangeDate();
    },
    detached() {
      // 在组件实例被从页面节点树移除时执行
    },
  },

  /**
   * 组件的方法列表
   */
  methods: {
    //时间
    onClickedDate: function (event) {
      const that = this;
      let dateIndex = event.currentTarget.dataset.id;
      let index = event.currentTarget.dataset.index;
      that.setData({
        dateshow: true,
        dateIndex: dateIndex,
        customIndex: index,
      })
    },
    //时间弹框关闭
    onDateClose: function () {
      const that = this;
      that.setData({
        dateshow: false
      })
    },
    //时间改变
    onDateChange(event) {
      const that = this;
      that.setData({
        currentDate: event.detail,
      });
      console.log(event.detail);
      that.onDateClose();
      that.timeChangeDate();
    },
    //时间戳转时间格式
    timeChangeDate: function () {
      const that = this;
      let getTime = '';
      var time = new Date(that.data.currentDate);
      var year = time.getFullYear();
      var month = time.getMonth() + 1;
      if (month > 0 && month < 10) {
        month = "0" + month
      }
      var date = time.getDate();
      if (date > 0 && date < 10) {
        date = "0" + date
      }
      getTime = year + '-' + month + '-' + date;
      if (that.data.dateIndex == 0){
        that.setData({
          getTime: getTime,
        })  
        that.data.customform[that.data.index].value = Math.round(that.data.currentDate / 1000).toString();   
      }
      if (that.data.dateIndex == 1) {
        that.setData({
          getTime: getTime,
        })
        that.data.customform[that.data.customIndex].value = Math.round(that.data.currentDate / 1000).toString();
      }      
      
      that.triggerEvent('customformInfo', { customform: that.data.customform })

    },
  }
})
