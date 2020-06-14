Component({
  /**
   * 组件的属性列表
   */
  properties: {
    items: Object,
    memberData: Object
  },

  /**
   * 组件的初始数据
   */
  data: {

  },

  /**
   * 组件的方法列表
   */
  methods: {
    onOrderListPage(e) {
      const that = this;      
      let value = wx.getStorageSync('user_token');
      if (value){
        let status = e.currentTarget.dataset.status;
        if (status == undefined) {
          wx.navigateTo({
            url: '../order/list/index',
          })
        } else {
          wx.navigateTo({
            url: '../order/list/index?status=' + status,
          })
        }
      }else{
        wx.navigateTo({
          url: '/pages/logon/index',
        })
      }
      
    },
    

  }
})