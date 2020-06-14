// pages/component/topnav/topnav.js
var util = require('../../../utils/util.js');
Component({
  /**
   * 组件的属性列表
   */
  properties: {

  },

  /**
   * 组件的初始数据
   */
  data: {
    items: [{
        icon: "home",
        name: "首页"
      },
      {
        icon: "applicat",
        name: "分类"
      },
      {
        icon: "cart1",
        name: "购物车"
      },
      {
        icon: "user1",
        name: "我的"
      }
    ],
    showMenuClass: "",
    iconClass: "applicat"
  },

  /**
   * 组件的方法列表
   */
  methods: {
    onTabBar: function(e) {
      let url = '';
      if (e.currentTarget.dataset.index === 0) {
        url = '/pages/index/index';
      } else if (e.currentTarget.dataset.index === 1) {
        url = '/pages/category/index';
      } else if (e.currentTarget.dataset.index === 2) {
        url = '/pages/shopcart/index';
      } else if (e.currentTarget.dataset.index === 3) {
        url = '/pages/member/index';
      }
      if (url) {
        let onPageData = {
          url: url,
          num: 1,
          param: '',
        }
        util.jumpPage(onPageData);
      }
    },
    onShowActive: function() {
      if (this.data.iconClass == "applicat") {
        this.setData({
          showMenuClass: "show",
          iconClass: "close"
        })
      } else {
        this.setData({
          showMenuClass: " ",
          iconClass: "applicat"
        })
      }

    }
  }
})