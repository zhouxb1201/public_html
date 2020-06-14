
var requestSign = require('../../../utils/requestData.js');
var api = require('../../../utils/api.js').open_api;
var header = getApp().header;

Component({

  data: {
    show: true,
    //普通选择器：（普通数组）
    array: ['区号'],
    index:0
  },
  lifetimes:{
    attached(){
      const that = this;
      
      if(getApp().globalData.config.config.mobile_type==1){
        var postData = {};
        let datainfo = requestSign.requestSign(postData);
        header.sign = datainfo;
        wx.request({
          url: api.get_CountryCode,
          data: postData,
          header: header,
          method: 'POST',
          dataType: 'json',
          responseType: 'text',
          success: (res) => {
            let arr=[]
            for (var index in res.data.data){
              arr.push(res.data.data[index].country +' (' + res.data.data[index].country_code+')')
            }
            this.setData({
              array: arr
            })
            this.triggerEvent('getValue', { value: arr[0].replace(/[^0-9]/ig, "")});
          },
          fail: (res) => { },
        })
      }
     
      
    }
  },

  /**
   * 组件的方法列表
   */
  methods: {

    
    // 关闭
    onClose() {
      this.setData({ show: false });
    },
    onChange(event) {
      console.log(event)
      const { picker, value, index } = event.detail
    },
    // 选择器确定
    bindPickerChange: function (e) {
      this.setData({
        index: e.detail.value
      })
      let newvalue = this.data.array[e.detail.value]
      
      this.triggerEvent('getValue', { value: newvalue.replace(/[^0-9]/ig, "")});
    },
  }
})
