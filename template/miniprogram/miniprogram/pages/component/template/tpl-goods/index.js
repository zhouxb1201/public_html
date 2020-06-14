var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var re = require('../../../../utils/request.js');
var header = getApp().header;
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
    shop_id:'',
  },

  lifetimes: {
    attached() {
      // 在组件实例进入页面节点树时执行
      const that = this;
      that.setSearchData();
    },
    ready(){

    },
    detached() {
      // 在组件实例被从页面节点树移除时执行
    },
  },

  //监听数据变化
  observers:{
    'temDataitem': function (temDataitem){
      this.setSearchData();
    }
  },

  /**
   * 组件的方法列表
   */
  methods: {

    //整理请求数据
    setSearchData:function(){
      const that = this;
      let pageSize = that.data.temDataitem.params.recommendnum;
      let goodssort = that.data.temDataitem.params.goodssort;
      let goodstype = that.data.temDataitem.params.goodstype;
      let shop_id = that.data.temDataitem.shop_id;
      if (shop_id != undefined) {
        that.setData({
          shop_id: shop_id
        })
      }
      that.getGoodsData(goodssort, pageSize, goodstype);
    },

    // 请求商品列表
    getGoodsData: function (goodssort, recommendnum, goodstype) {
      const that = this;
      let order = '';
      let sort = '';
      switch (goodssort) {
        case '0':
          order = 'create_time';
          sort = 'ASC';
          break;
        case '1':
          order = 'create_time';
          sort = 'DESC';
          break;
        case '2':
          order = 'sales';
          sort = 'ASC';
          break;
        case '3':
          order = 'sales';
          sort = 'DESC';
          break;
        case '4':
          order = 'collects';
          sort = 'ASC';
          break;
        case '5':
          order = 'collects';
          sort = 'DESC';
          break;

      };

      // 请求商品列表
      let goodsRequestData = {
        'page_index': 1,
        'order': order,
        'sort': sort,
        'page_size': recommendnum,
        'goods_type': goodstype,
      }
      if(that.data.shop_id != ''){
        goodsRequestData.shop_id = that.data.shop_id;
      }
      let goodsSign = requestSign.requestSign(goodsRequestData);
      header.sign = goodsSign;
      re.request(api.get_goodsList, goodsRequestData, header).then((res) => {
        if (res.data.code > 0) {
          this.setData({
            goodsList: res.data.data.goods_list,
          })
        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
        }
      })

    },

    //商品列表图片加载出错，替换为默认图片
    avatarError: function (e) {
      var errorImgIndex = e.target.dataset.imgindex
      let goodsList = this.data.goodsList;
      goodsList[errorImgIndex].logo = "/images/no-goods.png"
      this.setData({
        goodsList: goodsList
      })
    },

    //跳转到商品页面
    ongoodsDetail: function (e) {
      const that = this;
      let goods_id = e.currentTarget.dataset.goodsid
      wx.navigateTo({
        url: '/pages/goods/detail/index?goodsId=' + goods_id,
      })
    },
  }
})
