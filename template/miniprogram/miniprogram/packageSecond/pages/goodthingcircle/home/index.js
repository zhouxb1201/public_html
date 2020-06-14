var requestSign = require('../../../../utils/requestData.js');
var util = require('../../../../utils/util.js');
var api = require('../../../../utils/api.js').open_api;
var header = getApp().header;

Page({

  /**
   * 页面的初始数据
   */
  data: {
    isOpen: 1,
    like: 'like-o',
    page_index: 1,
    page_size: 20,
    search_text: '',
    //纬度
    lat: '',
    //经度
    lng: '',
    //关注 1
    follow: '',
    //干货数据
    thingData: '',
    active: '1',
    noGoodShow: false,
    display_model: "1",

    isTabbar: 0 //0 => 首页, 1 => 我的
  },

  mixins:[require('../mixin/mixin')],
  onLoad: function (options) {
    this.getThingcircleList();
  },
  onShareAppMessage:function(){
    const that = this
    const {
      extend_code,
      uid
    } = getApp().globalData
    let path = 'packageSecond/pages/goodthingcircle/home/index?' + util.encodeUriParams({
      extend_code
    })
    console.log(path,that.data.shareInfo)
    return {
      path: path,
      title: that.data.shareInfo.other_title || '',
      imageUrl: that.data.shareInfo.other_pic || '',
      success: (res) => {
        console.log('转发成功！');
      },
      fail: (res) => {
        console.log('转发失败！');
      }
    }
  },
  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady:function(){
		
	},

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {
    
  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {
    const that = this;
    that.data.page_index += 1;
    that.getThingcircleList();
  },

  //好物圈干货列表
  getThingcircleList: function () {
    const that = this;
    let postData = {
      'page_index': that.data.page_index,
      'page_size': that.data.page_size,
      'search_text': that.data.search_text,
      'lat': that.data.lat,
      'lng': that.data.lng,
      'follow': that.data.follow,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getThingcircleList,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code === 1) {
          if (that.data.page_index > 1) {
            let oldthingData = that.data.thingData;
            let newthingData = oldthingData.concat(that.addLikeState(res.data.data.data));
            that.setData({
              thingData: newthingData,
              display_model: res.data.data.display_model
            })
          } else {
            if (res.data.data.data.length == 0) {
              that.setData({
                noGoodShow: true,
              })
            } else {
              that.setData({
                thingData: that.addLikeState(res.data.data.data),
                noGoodShow: false,
                display_model: res.data.data.display_model
              })
            }

          }
        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
        }
      },
      fail: (res) => {},
    })
  },

  //添加点赞状态
  addLikeState: function (data) {
    const that = this;
    let thingData = data;
    for (let i = 0; i < thingData.length; i++) {
      thingData[i].like = 'like-o';
    }
    return thingData;
  },

  //头部状态栏
  onTopTabChange: function (e) {
    const that = this;
    if (e.detail.title == '关注') {
      that.setData({
        active: '0',
        page_index: 1,
        follow: 1,
        lat: '',
        lng: '',
      })
      that.getThingcircleList();
      that.backTop();
    } else if (e.detail.title == '附近') {
      that.setData({
        active: '2',
        page_index: 1,
        follow: '',
      })
      that.getUserLocation('a','b','c');
      that.backTop();
    } else if (e.detail.title == '发现') {
      that.setData({
        active: '1',
        page_index: 1,
        follow: '',
        lat: '',
        lng: '',
      })
      that.getThingcircleList();
      that.backTop();
    }
  },

  // 滚动到顶部
  backTop: function () {
    // 控制滚动
    wx.pageScrollTo({
      scrollTop: 0
    })
  },

  //获取当前位置的经纬度
  getUserLocation: function (a,b,c) {
    const that = this;
    wx.getLocation({
      type: 'gcj02',
      success: function (res) {
        //纬度，范围为 -90~90，负数表示南纬
        const latitude = res.latitude
        //经度，范围为 -180~180，负数表示西经
        const longitude = res.longitude
        const obj = {
          ...util.txMapTransBMap(longitude,latitude)
        }
        that.setData({
          lng: obj.lng,
          lat: obj.lat
        })
        that.getThingcircleList();
      },
    })
  },

  //收搜好物圈关键字
  searchGoodsThing: function (e) {
    const that = this;
    let search_text = e.detail.value
    that.setData({
      search_text: search_text
    })
    that.getThingcircleList();
  },

  //点赞干货
  likesThingcircle: function (e) {
    const user_token = wx.getStorageSync('user_token');
    if(!user_token){
      wx.navigateTo({
        url: '/pages/logon/index',
      })
      return false;
    }
    const that = this;
    let thing_id = e.currentTarget.dataset.thingid;
    let postData = {
      'thing_id': thing_id
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_likesThingcircle,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code === 1) {
          let thingData = that.data.thingData;
          for (let i = 0; i < thingData.length; i++) {
            if (thingData[i].id == thing_id) {
              if (thingData[i].is_like == 1) {
                thingData[i].is_like = 0;
              } else {
                thingData[i].is_like = 1;
              }
              thingData[i].likes = res.data.count;
            }
          }
          that.setData({
            thingData: thingData
          })
        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
        }
      },
      fail: (res) => {},
    })

  },
  //点击底部导航栏
  getTabbar: function (e) {
    const that = this;
  },
  //跳转详情
  toDetail: function (e) {
    const {
      thing_id,
      thing_type
    } = e.currentTarget.dataset
    let url = ''
    if (thing_type == 1) {
      url = '../grapDetail/index?thing_id=' + thing_id
    }
    if (thing_type == 2) {
      url = '../vedioDetail/index?thing_id=' + thing_id
    }
    if (url) {
      wx.navigateTo({
        url: url
      })
    }
  }

})