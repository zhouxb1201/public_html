// pages/component/member/member_fixed/index.js
Component({
  /**
   * 组件的属性列表
   */
  properties: {
    extend_code: String,
    user_name: String,
    memberData:Object,
    member_img:String,
    items:Object,
    isLogin:Boolean,
  },

  /**
   * 组件的初始数据
   */
  data: {

  },
  lifetimes: {
    ready: function () {
     
    }
  },
    
  /**
   * 组件的方法列表
   */
  methods: {
    //复制到剪切面板
    setClipboardData: function (e) {
      let copy_data = e.currentTarget.dataset.code;
      wx.setClipboardData({
        data: copy_data,
        success(res) {
          wx.getClipboardData({
            success(res) {
              console.log(res.data) // data
            }
          })
        }
      })
    },
    

    onLoginPage(){
      wx.navigateTo({
        url: '/pages/logon/index',
      })
    },
    //跳到账户设置页面
    onAccountPage(){
      let value = wx.getStorageSync('user_token');
      if(value){
        wx.navigateTo({
          url: '/package/pages/account/set/index',
        })
      }else{
        wx.navigateTo({
          url: '/pages/logon/index',
        })
      }
      
    },
  }
})
