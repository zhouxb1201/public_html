var api = require('../../../../utils/api.js').open_api;
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
    //微信上传的图片
    imgUrl: [],
    //自定义表单的图片数组
    image_array: [],
  },

  lifetimes: {
    attached() {
      // 在组件实例进入页面节点树时执行
    },
    ready(){
      const that = this;
      let customform = that.data.customform;
      if (customform[that.data.index].value != ''){
        let imgUrl = customform[that.data.index].value.split(',');
        that.setData({
          imgUrl: imgUrl
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
    //自定义表单的图片
    customImgFun: function (e) {
      const that = this;
      let index = e.currentTarget.dataset.index;
      let max = that.data.customform[index].max
      if (that.data.imgUrl.length >= max){
        wx.showToast({
          title: '图片不能超出限制数量',
          icon:'none'
        })
        return;
      }
      wx.chooseImage({
        count: max,
        sizeType: ['original', 'compressed'],
        sourceType: ['album', 'camera'],
        success: function (res) {
          let imgUrl = that.data.imgUrl;
          if (imgUrl.length < max) {
            imgUrl = imgUrl.concat(res.tempFilePaths)
          }
          
          that.imgUpload(index,res.tempFilePaths);

        },
      })
    },

    imgUpload: function (index,tempFilePaths){
      const that = this;
      let customform = that.data.customform;
      for (let path of tempFilePaths) {
        that.uploadFile(path).then((res) => {
          customform[index].value = res;         
          that.triggerEvent('customformInfo', { customform: customform })
        })
      }
            
    },

    uploadFile: function (path){
      const that = this;
      return new Promise((resolve,reject) =>{
        wx.uploadFile({
          url: api.get_uploadImage,
          filePath: path,
          name: 'file',
          header: {
            'Content-Type': 'multipart/form-data',
            'X-Requested-With': 'XMLHttpRequest',
            'user-token': wx.getStorageSync('user_token'),
          },
          formData: {
            "type": 'customform'
          },
          success: (res) => {
            let image_data = res.data;
            let image_obj = JSON.parse(image_data);
            that.data.image_array.push(image_obj.data.src);
            let image_string = that.data.image_array.join(',');            
            let imgUrl = that.data.image_array;            
            that.setData({
              imgUrl: imgUrl,
            })
            resolve(image_string);                   
          }
        })
      })
    },

    //删除图片
    _deleteImg:function(e){
      const that = this;
      let img_index = e.currentTarget.dataset.index;
      let index = that.data.index;
      let imgUrl = that.data.imgUrl;
      wx.showModal({
        title: '提示',
        content: '请确认是否删除图片',
        success(res){
          if(res.confirm){
            imgUrl.splice(img_index, 1);
            that.setData({
              imgUrl: imgUrl,
            })
            let image_string = imgUrl.join(',');
            let customform = that.data.customform;
            customform[index].value = image_string;
            that.triggerEvent('customformInfo', { customform: customform })
          }
        }
      })      
    },

    //预览图片
    _previewImg:function(e){
      const that = this;
      let img_list = e.currentTarget.dataset.imglist;
      wx.previewImage({
        urls: img_list,
      })
    },


  }
})
