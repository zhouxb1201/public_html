var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var util = require('../../../../utils/util.js');
var QQMapWX = require('../../../../common/wxmap/qqmap-wx-jssdk.min.js');
var qqmapsdk;
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    is_textarea: true, //解决textarea层级穿透问题
    is_goods: false,
    is_topic: false,
    is_location: false,
    checked: true,
    arrImg: [], //保存上传图片

    arrVideo: [],

    goods_list: [], //推荐商品
    title_goods: "推荐商品",
    text_goods: "推荐购买过的商品",
    arry_goodsId: [],

    topic_state: 0,
    tab_topic_list: [],
    topic_search_text: "",
    topic_id: "",
    title_topic_list: [],
    title_topic: "参与话题",
    text_topic: "选择合适的话题会有更多赞",

    title_location: "添加定位",
    text_location: "让附近更多的人发现你",

    page_index: 1,
    page_size: 20,

    thing_type: 1,

    address_list: [],

    title: '',
    content: '',
    address: {},
    isLoading: false,

    areaParams: {
      page_index: 1,
      page_size: 40,
      query: '',
      lat: '',
      lng: ''
    }
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    this.getGoodsList();
    qqmapsdk = new QQMapWX({
      key: 'F7ZBZ-F7KKU-KGOVW-2454F-DV2AE-TRB75'
    });
  },

  onThingTypeChange: function (e) {
    this.setData({
      thing_type: parseInt(e.detail)
    })
  },
  //上传图片
  onUploadImages: function () {
    const that = this;
    wx.chooseImage({
      count: 9,
      sizeType: ['original', 'compressed'],
      sourceType: ['album', 'camera'],
      success: function (res) {
        for (let path of res.tempFilePaths) {
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
              "type": 'evaluate'
            },
            success: (res) => {
              let image_data = res.data;
              let image_src = JSON.parse(image_data);
              let imgList = that.data.arrImg;
              imgList.push(image_src.data.src);
              that.setData({
                arrImg: imgList
              })
            }
          })
        }
      },
    })
  },
  onUnloadVideo: function () {
    const that = this;
    wx.chooseVideo({
      sourceType: ['album', 'camera'],
      maxDuration: 30,
      camera: 'back',
      success(res) {
        console.log(res.tempFilePath)
        wx.uploadFile({
          url: api.get_uploadImage,
          filePath: res.tempFilePath,
          name: 'file',
          header: {
            'Content-Type': 'multipart/form-data',
            'X-Requested-With': 'XMLHttpRequest',
            'user-token': wx.getStorageSync('user_token'),
          },
          formData: {},
          success: (res) => {
            console.log(res)
          }
        })
      }
    })
  },
  //删除图片
  deleteImg: function (e) {
    const that = this;
    let index = e.currentTarget.dataset.index;
    console.log();
    wx.showModal({
      title: '提示',
      content: '请确认是否删除图片',
      success(res) {
        if (res.confirm) {
          let imgList = that.data.arrImg;
          imgList.splice(index, 1);
          that.setData({
            arrImg: imgList
          })
        }
      }
    })
  },
  //推荐商品
  onGoods: function () {
    this.setData({
      is_goods: true,
      is_textarea: false
    })
  },
  getGoodsList: function () {
    const that = this;
    let postData = {};
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_recommendGoods,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code === 1) {
          for (let i = 0; i < res.data.data.length; i++) {
            res.data.data[i].checked = false;
          }
          that.setData({
            goods_list: res.data.data
          })
        }
      },
      fail: (res) => {},
    })
  },
  closeGoods: function () {
    this.setData({
      is_goods: false,
      is_textarea: true
    })
  },
  toggleGoods: function (e) {
    const that = this;
    let _target = e.currentTarget.dataset;
    let _list = that.data.goods_list;
    if (_list[_target.index].checked == false) {
      _list[_target.index].checked = true;
    } else {
      _list[_target.index].checked = false;
    }
    that.setData({
      goods_list: _list
    })
  },
  onRecommend: function () {
    const that = this;
    let arr = [];
    for (let i = 0; i < that.data.goods_list.length; i++) {
      if (that.data.goods_list[i].checked == true) {
        arr.push(that.data.goods_list[i].goods_id);
      }
    }
    that.setData({
      is_goods: false,
      is_textarea: true,
      text_goods: arr.length > 0 ? "" : "推荐购买过的商品",
      title_goods: arr.length > 0 ?
        "已选中" + arr.length + "件商品" : "推荐商品",
      arry_goodsId: arr
    })
  },
  //参与话题
  onTopic: function () {
    this.setData({
      is_topic: true,
      is_textarea: false
    })
    this.getTopic();
  },
  onLeftTopic: function () {
    this.setData({
      is_topic: false,
      is_textarea: true
    })
  },
  getTopic: function () {
    const that = this;
    let postData = {};
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_topicList,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 1) {
          if (res.data.data.topic_state == 1) {
            that.setData({
              topic_state: res.data.data.topic_state,
              tab_topic_list: res.data.data.data
            })
            this.get_nextTopicList();
          } else {
            that.setData({
              title_topic_list: res.data.data.data
            })
          }
        }
      },
      fail: (res) => {},
    })
  },
  onTabTopic: function (e) {
    const that = this;
    let _target = e.currentTarget.dataset;
    that.setData({
      topic_id: _target.id
    })
    that.get_nextTopicList();
  },
  get_nextTopicList: function () {
    const that = this;
    let postData = {
      page_index: that.data.page_index,
      page_size: that.data.page_size,
      superiors_id: that.data.topic_id,
      search_text: that.data.topic_search_text
    };
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_lowerTopicList,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 1) {
          that.setData({
            title_topic_list: res.data.data.data
          })
        }
      },
      fail: (res) => {},
    })
  },
  onAddTopics: function (e) {
    const that = this;
    let _target = e.currentTarget.dataset;
    let topics = {
      title: _target.title,
      topic_id: _target.id
    };
    that.data.topic_id = _target.id
    that.setData({
      is_topic: false,
      is_textarea: true,
      text_topic: _target.title ? "" : "选择合适的话题会有更多赞",
      title_topic: _target.title ? _target.title : "参与话题"
    })
  },
  getNOTopics: function () {

  },
  //添加定位
  onLocation: function () {
    this.setData({
      is_location: true,
      is_textarea: false,
    })
    this.getLocationInfo();
  },
  onReachBottom: function () {

  },
  getLocationInfo: function () {
    const that = this;
    wx.getLocation({
      type: 'gcj02',
      isHighAccuracy: true,
      success: function (location) {
        console.log(location)
        const lo = util.txMapTransBMap(location.longitude, location.latitude)
        that.data.areaParams.lat = lo.lat
        that.data.areaParams.lng = lo.lng
        that.getAreaList()
      }
    })
  },
  getAreaList: function (init) {
    const that = this;
    let postData = {
      ...that.data.areaParams,
      query: that.data.areaParams.query || '公司企业$交通设施$教育培训$金融'
    };
    if (init) {
      that.setData({
        address_list: []
      })
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_thingcircleArea,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          that.setData({
            address_list: res.data.data.results
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
  onSelectAddress: function (e) {
    const that = this
    const {
      location,
      title
    } = e.currentTarget.dataset
    that.data.address = {
      name: title,
      lat: location.lat,
      lng: location.lng,
    }
    that.setData({
      text_location: title ? '' : "让附近更多的人发现你",
      title_location: title || "添加定位"
    })
    that.onLeftLocation()
  },
  onLeftLocation: function () {
    this.setData({
      is_location: false,
      is_textarea: true,
    })
  },
  onLocationSearch: function () {
    this.getAreaList('init')
  },
  onLocationSearchText: function (e) {
    this.data.areaParams.query = e.detail.value
  },
  onTitleInput: function (e) {
    this.data.title = e.detail.value
  },
  onContentInput: function (e) {
    this.data.content = e.detail.value
  },
  // 提交
  onSubmit: function () {
    const that = this;
    let params = {};
    params.thing_type = that.data.thing_type;
    params.topic_id = that.data.topic_id || "";
    params.content = that.data.content.replace(/\s*/g, "");
    params.thing_title = that.data.title;
    params.img_id = that.data.arrImg.join();
    params.goods_array = that.data.arry_goodsId.join();
    params.location = that.data.address.name ? that.data.address.name : "";
    params.lat = that.data.address.lat ? that.data.address.lat : "";
    params.lng = that.data.address.lng ? that.data.address.lng : "";
    if (!params.content) {
      wx.showToast({
        title: '请填写话题内容',
        icon: 'none'
      })
      return false;
    }
    if (!params.img_id) {
      wx.showToast({
        title: '请添加话题图片',
        icon: 'none'
      })
      return false;
    }
    that.setData({
      isLoading: true
    })
    console.log(params)
    let postData = {
      ...params
    };
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_thingcircleAdd,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 1) {
          wx.showToast({
            title: res.data.message,
            icon: 'success',
            complete() {
              wx.redirectTo({
                url: '../home/index',
              })
            }
          })
        }
      },
      complete: (res) => {
        that.setData({
          isLoading: false
        })
      },
    })
  }
})