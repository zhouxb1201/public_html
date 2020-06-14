var requestSign = require('../../../../utils/requestData.js');
var util = require('../../../../utils/util.js');
var time = require('../../../../utils/time.js');
var api = require('../../../../utils/api.js').open_api;
var app = getApp()
var header = getApp().header;
var Base64 = require('../../../../utils/base64').Base64;

Page({
  data: {
    thing_id: '',
    head_info: {
      user_headimg: '',
      thing_user_name: '',
      is_attention: ''
    },
    bannerImg: [],
    goods_list: [],
    user_id: 0,
    uid:'',
    editorial: {},
    detail: {},

    shareInfo: {},

    commentParams: {
      page_index: 1,
      page_size: 10,
      page_count: 1
    },
    commentList: [],
    is_more: [],
    flag_like: true,

    popupOption: {
      show: false,
      value: '',
      placeholder: '说点什么...'
    },

    sendOptions: null
  },
  mixins:[require('../mixin/mixin')],
  onLoad: function (options) {
    const that =this
    let params = {
      thing_id: options.thing_id || '',
    }
    
    this.setData(params)
    if (options.uid) {
      params.uid = decodeURIComponent(options.uid)
    }
    if (getApp().globalData.uid) {
			that.setData({
				uid: getApp().globalData.uid
			})
		} else {
			getApp().watch('uid', function (e) {
				that.setData({
					uid: e
				})
			})
    }
    console.log(params)
    this.getThingcircleDetail(params)
  },

  onReachBottom: function () {
    const that = this;
    if (that.data.commentParams.page_index < that.data.commentParams.page_count) {
      that.data.commentParams.page_index += 1;
      that.getCommentList();
    }
  },

  onShareAppMessage: function (e) {
    const that = this
    const {
      extend_code,
      uid
    } = app.globalData
    let path = 'packageSecond/pages/goodthingcircle/grapDetail/index?' + util.encodeUriParams({
      extend_code,
      uid: encodeURIComponent(Base64.encode(uid)),
      thing_id: this.data.thing_id
    })
    console.log(path,that.data.shareInfo)
    return {
      path: path,
      title: that.data.shareInfo.thing_title || '',
      imageUrl: that.data.shareInfo.thing_pic || '',
      success: (res) => {
        console.log('转发成功！');
      },
      fail: (res) => {
        console.log('转发失败！');
      }
    }
  },

  // 获取干货详情
  getThingcircleDetail: function (params = {}) {
    const that = this;
    let postData = {
      ...params
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_thingcircleDetail,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          const detail = res.data.data
          that.setData({
            head_info: {
              user_headimg: detail.user_headimg,
              thing_user_name: detail.thing_user_name,
              is_attention: detail.is_attention
            },
            bannerImg: detail.img_temp_array || [],
            goods_list: detail.recommend_goods_list || [],
            user_id: detail.user_id,
            editorial: {
              thing_id: detail.id,
              topic_id: detail.topic_id,
              likes: detail.likes,
              collects: detail.collects,
              is_like: detail.is_like,
              is_collect: detail.is_collect
            },
            detail: {
              title: detail.title,
              content: detail.content,
              create_time: time.js_date_time_second(detail.create_time),
              location: detail.location,
              reading_volumes: detail.reading_volumes,
              topic_title: detail.topic_title,
              user_headimg: detail.user_headimg,
              thing_user_name: detail.thing_user_name,
              user_id: detail.user_id,
              is_attention: detail.is_attention,
              id: detail.id
            }
          })
          that.getCommentList()
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

  // 关注
  sensitiveOthers: function () {
    const user_token = wx.getStorageSync('user_token');
    if(!user_token){
      wx.navigateTo({
        url: '/pages/logon/index',
      })
      return false;
    }
    const that = this;
    let postData = {
      thing_auid: that.data.user_id
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_thingcircleAttention,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 1) {
          let head_info = {
            ...that.data.head_info
          }
          if (res.data.message == "关注成功") {
            head_info.is_attention = 1;
          } else {
            head_info.is_attention = 0;
          }
          that.setData({
            head_info: head_info
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

  // 获取评论列表
  getCommentList: function (init) {
    const that = this;
    if (init) {
      that.data.commentList = []
    }
    let postData = {
      page_index: init ? 1 : that.data.commentParams.page_index,
      page_size: that.data.commentParams.page_size,
      thing_id: that.data.thing_id,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_thingcircleDetailCommentList,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          const list = res.data.data
          const is_more = []
          list.data.forEach(function (e) {
            if (e.reply_list.total_count > 1) {
              is_more.push(1);
            } else {
              is_more.push(0);
            }
          })
          let commentList = that.data.commentList.concat(list.data);
          // console.log(commentList)
          that.data.commentParams.page_count = list.page_count
          that.setData({
            is_more: is_more,
            commentList: commentList
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

  toGoodsDetail: function (e) {
    const {
      goodsid
    } = e.currentTarget.dataset
    wx.navigateTo({
      url: '/pages/goods/detail/index?goodsId=' + goodsid
    })
  },

  //点赞
  onFabulous: function (e) {
    const user_token = wx.getStorageSync('user_token');
    if(!user_token){
      wx.navigateTo({
        url: '/pages/logon/index',
      })
      return false;
    }
    const {
      item,
      index,
      cindex
    } = e.currentTarget.dataset
    const that = this;
    let postData = {
      comment_id: item.id
    }
    if (that.data.flag_like == false) {
      return false;
    }
    that.data.flag_like = false;
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo

    wx.request({
      url: api.get_thingcircleDetailCommentLikes,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        that.data.flag_like = true;
        if (res.data.code == 1) {

          var commentItem = typeof cindex == 'undefined' ? that.data.commentList[index] : that.data.commentList[index].reply_list.data[cindex]
          if (res.data.message == "取消点赞成功") {
            commentItem.is_like = 0;
          } else {
            commentItem.is_like = 1;
          }
          commentItem.comment_likes = res.data.count;
          commentItem.like_count = res.data.count;

          that.setData({
            commentList: that.data.commentList
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
  // 选择弹窗
  onReflex: function (e) {
    const user_token = wx.getStorageSync('user_token');
    if(!user_token){
      wx.navigateTo({
        url: '/pages/logon/index',
      })
      return false;
    }
    const that = this
    const {
      item,
      index,
      hash,
      cindex,
      commentpid
    } = e.currentTarget.dataset
    const isUid = app.globalData.uid == item.from_uid; //判断是否是自己的回复
    // console.log(index, item, hash, cindex)
    that.data.ans = {
      data: item,
      hash: hash,
      comment_pid: commentpid
    };

    wx.showActionSheet({
      itemList: ['回复', isUid ? '删除' : '举报'],
      success(res) {
        if (res.tapIndex === 0) {
          that.data.sendOptions = {
            to_uid: item.from_uid,
            comment_pid: commentpid,
          }
          that.onPopMessage({
            placeholder: '回复：' + item.thing_user_name
          })
        }
        if (res.tapIndex === 1) {
          that[isUid ? 'onDelete' : 'onReport']({
            comment_id: item.id
          })
        }
      },
      fail(res) {
        console.log(res.errMsg)
      }
    })
  },
  // 更多回复
  onMoreReply: function (e) {
    const that = this
    const {
      item,
      index,
      cindex
    } = e.currentTarget.dataset

    let postData = {
      thing_id: item.thing_id,
      comment_pid: item.id
    }

    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo

    wx.request({
      url: api.get_thingcircleDetailCommentMore,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          that.data.commentList[index].reply_list.data = res.data.data.data || [];
          that.data.is_more[index] = 0;
          let commentList = that.data.commentList
          let is_more = that.data.is_more
          that.setData({
            is_more: is_more,
            commentList: commentList
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
  // 回复评论
  onAnswer: function (option) {
    console.log(option)
  },
  // 删除评论
  onDelete: function (option) {
    const that = this;
    let postData = {
      comment_id: option.comment_id
    }

    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo

    wx.request({
      url: api.get_thingcircleDetailCommentDel,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 1) {
          wx.showToast({
            title: '删除成功',
            icon: 'success'
          })
          that.getCommentList('init')
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
  // 举报
  onReport: function (option) {
    wx.navigateTo({
      url: '../report/index?comment_id=' + option.comment_id,
    })
  },

  // 底部点赞
  onBottomFabulous: function () {
    const user_token = wx.getStorageSync('user_token');
    if(!user_token){
      wx.navigateTo({
        url: '/pages/logon/index',
      })
      return false;
    }
    const that = this;
    let postData = {
      thing_id: that.data.thing_id
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_thingcircleLikes,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 1) {
          if (res.data.message == "取消点赞成功") {
            that.data.editorial.is_like = 0;
          } else {
            that.data.editorial.is_like = 1;
          }
          that.data.editorial.likes = res.data.count;
          that.setData({
            editorial: that.data.editorial
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
  // 底部收藏
  onBottomCollection: function () {
    const user_token = wx.getStorageSync('user_token');
    if(!user_token){
      wx.navigateTo({
        url: '/pages/logon/index',
      })
      return false;
    }
    const that = this;
    let postData = {
      thing_id: that.data.thing_id
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_thingcircleCollection,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 1) {
          if (res.data.message == "取消收藏成功") {
            that.data.editorial.is_collect = 0;
          } else {
            that.data.editorial.is_collect = 1;
          }
          that.data.editorial.collects = res.data.count;
          that.setData({
            editorial: that.data.editorial
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
  onPopupInput: function (e) {
    if (!this.data.sendOptions) {
      this.data.sendOptions = {}
    }
    this.data.sendOptions.content = e.detail.value
    this.setData({
      sendOptions: this.data.sendOptions
    })
  },
  // 关闭弹窗
  popupClose: function (e) {
    this.data.popupOption.show = false
    this.data.popupOption.value = ''
    this.data.popupOption.placeholder = '说点什么...'
    this.data.sendOptions = null
    this.setData({
      popupOption: this.data.popupOption
    })
  },
  // 显示回复弹窗
  onPopMessage: function (option) {
    this.data.popupOption.show = true
    this.data.popupOption.placeholder = option && option.placeholder ? option.placeholder : '说点什么...'
    this.setData({
      popupOption: this.data.popupOption
    })
  },
  // 发送回复内容
  onSend: function (option) {
    const user_token = wx.getStorageSync('user_token');
    if(!user_token){
      wx.navigateTo({
        url: '/pages/logon/index',
      })
      return false;
    }
    const that = this
    if (that.data.sendFlag) {
      return false
    }
    that.data.sendFlag = true
    if (!that.data.sendOptions || !that.data.sendOptions.content) {
      return wx.showToast({
        title: '没有什么想说的吗',
        icon: 'none'
      })
    }
    let postData = {
      thing_id: that.data.thing_id,
      topic_id: that.data.editorial.topic_id || "",
      content: that.data.sendOptions.content,
      ...that.data.sendOptions
    }

    console.log(postData)
    let url = that.data.sendOptions.comment_pid ? api.get_thingcircleDetailReplyComment : api.get_thingcircleDetailComment
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.showLoading({
      title: '发布中',
    })
    wx.request({
      url: url,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          wx.showToast({
            title: '发布成功',
            icon: 'success'
          })
          wx.hideLoading()
          that.popupClose()
          that.getCommentList('init')
        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
        }
      },
      complete: (res) => {
        that.data.sendFlag = false
      },
    })
  },
})