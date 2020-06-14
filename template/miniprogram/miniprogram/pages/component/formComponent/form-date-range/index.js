Component({
  /**
   * 组件的属性列表
   */
  properties: {
    index: String,
    customitem: Object,
    customform: Object,
    getTime: String,
  },

  /**
   * 组件的初始数据
   */
  data: {
    dateshow: false,
    getStartTime:'',
    getEndTime:'',   
    dateIndex:0,
    date_range_array:[],
    //自定义表单的初始时间
    currentDate: new Date().getTime(), 
    
  },

  lifetimes: {
    attached() {
      const that = this;

      if (that.data.customitem.start_type == 1){
        that.setData({
          getStartTime: that.data.customitem.start_default
        })
      }else{
        that.timeChangeDate()
      }
      if (that.data.customitem.end_type == 1) {
        that.setData({
          getEndTime: that.data.customitem.end_default
        })
      }else{
        that.timeChangeDate()
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
    //时间
    onClickedDate: function (event) {
      const that = this;
      let dateIndex = event.currentTarget.dataset.id;
      let index = event.currentTarget.dataset.index;
      console.log(index);
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
          getStartTime: getTime,
          getEndTime: getTime,
        })
        let date_range_array = [
          Math.round(that.data.currentDate / 1000).toString(),
          Math.round(that.data.currentDate / 1000).toString()
        ]
        let date_range_string = date_range_array.join(',');
        that.data.customform[that.data.index].value = date_range_string;
      }
            
      
      if (that.data.dateIndex == 2) {
        let date_range_array = that.data.date_range_array;
        if (date_range_array.length == 2){
          date_range_array[0] = Math.round(that.data.currentDate / 1000).toString();
        }else{
          date_range_array.push(Math.round(that.data.currentDate / 1000).toString());
        }
        
        let date_range_string = date_range_array.join(',');
        that.setData({
          getStartTime: getTime,
        })
        that.data.customform[that.data.customIndex].value = date_range_string;
      }
      if (that.data.dateIndex == 3) {
        let date_range_array = that.data.date_range_array;
        if (date_range_array.length == 2) {
          date_range_array[1] = Math.round(that.data.currentDate / 1000).toString();
        } else {
          date_range_array.push(Math.round(that.data.currentDate / 1000).toString());
        }
        let date_range_string = date_range_array.join(',');
        that.setData({
          getEndTime: getTime,
        })
        that.data.customform[that.data.customIndex].value = date_range_string;
      }
      that.triggerEvent('customformInfo', { customform: that.data.customform })

    },
  }
})
