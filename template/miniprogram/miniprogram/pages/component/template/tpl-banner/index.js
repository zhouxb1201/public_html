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
    //默认  
    current: 0,
    //所有图片的高度  
    imgheights: [],
    idarray: [],

  },

  /**
   * 组件的方法列表
   */
  methods: {
    //获取图片真实宽度
    imageLoad: function (e) {
      const that = this;
      var imgwidth = e.detail.width,
        imgheight = e.detail.height,
        //宽高比  
        ratio = imgwidth / imgheight;
      console.log(imgwidth, imgheight)
      //计算的高度值  
      var viewHeight = 750 / ratio;
      var imgheight = viewHeight;
      var imgheights = that.data.imgheights;
      that.data.idarray.push(e.target.dataset.id);
      for (let key in that.data.idarray) {
        //把每一张图片的对应的高度记录到数组里  
        imgheights[key] = imgheight;
      }

      that.setData({
        imgheights: imgheights
      })
    },

    bindImgchange: function (e) {
      const that = this;
      that.setData({ current: e.detail.current })
    },
  }
})
