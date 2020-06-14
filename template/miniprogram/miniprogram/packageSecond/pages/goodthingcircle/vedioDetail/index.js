var requestSign = require('../../../../utils/requestData.js');
var util = require('../../../../utils/util.js');
var time = require('../../../../utils/time.js');
var api = require('../../../../utils/api.js').open_api;
var app = getApp()
var header = getApp().header;
var Base64 = require('../../../../utils/base64').Base64;

Page({
	data: {
		uid: 0,
		thing_id: '',
		head_info: {
			user_headimg: '',
			thing_user_name: '',
			is_attention: ''
		},
		bannerImg: [],
		goods_list: [],

		editorial: {},
		detail: {},

		active: 0,
		list: [],

		listOption: [{
			id: 0,
			play: false,
			show: true,
			contentShow: false,
			commentPopupShow: false,
			commentList: [],
			is_more: [],
			flag_like: true,
			page_index: 1,
			page_size: 10,
			page_count: 1,

			sendOptions: null,

			sendPopupShow: false,
			sendPopupContent: '',
			sendPopupPlaceholder: false,

			thing_id: 0,
			topic_id: 0,
			user_id: 0,
			goods_list: [],
			goodsPopupShow: false,
			is_attention: 0,

		}],

		shareInfo: {},

	},
	onLoad: function (options) {
		const that = this
		const items = that.data.listOption[that.data.active]
		let params = {
			thing_id: options.thing_id || '',
		}
		if (options.uid) {
			params.uid = decodeURIComponent(options.uid)
		}
		items.thing_id = params.thing_id

		that.getVideoDetailList(params)
		that.getThingcircleShareInfo()

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
		that.shareScene(options)
	},
	shareScene: function (e) {
		const value = wx.getStorageSync('user_token');
		if (e.extend_code) {
			wx.setStorageSync('higherExtendCode', e.extend_code)
		}
		if (e.extend_code && value) {
			util.checkReferee();
		}
	},
	onReady: function () {

	},


	onVideoAction: function (e) {
		const {
			index
		} = e.currentTarget.dataset
		let listOption = this.data.listOption
		let item = listOption[index]
		item.play ? item.context.pause() : item.context.play()
		item.play = !item.play

		this.setData({
			listOption: listOption
		})

	},

	onChange: function (e) {
		const that = this;
		const items = that.data.listOption[that.data.active]
		let listOption = that.data.listOption
		listOption.forEach(function (item, i) {
			if (i == e.detail.current) {
				item.context.play()
				item.play = !item.play
			} else {
				item.play = false
				item.context.pause()
			}
		})
		this.setData({
			active: e.detail.current,
			listOption: listOption
		})

	},

	bindContentShow: function () {
		const that = this
		const items = that.data.listOption[that.data.active]
		items.contentShow = !items.contentShow
		that.setData({
			listOption: that.data.listOption
		})
	},

	// 获取详情视频列表
	getVideoDetailList(params = {}) {
		const that = this;
		let postData = {
			...params
		}
		let datainfo = requestSign.requestSign(postData);
		header.sign = datainfo
		wx.request({
			url: api.get_thingcircleVideoDetail,
			data: postData,
			header: header,
			method: 'POST',
			dataType: 'json',
			responseType: 'text',
			success: (res) => {
				if (res.data.code >= 0) {
					const list = res.data.data
					let listOption = []
					wx.nextTick(function () {
						list.data.forEach((e, i) => {
							listOption.push({
								...that.data.listOption[0],
								topic_id: e.topic_id,
								user_id: e.user_id,
								goods_list: e.recommend_goods_list,
								is_attention: e.is_attention,
								thing_id: e.id,
								id: e.id,
								play: i === 0,
								show: true,
								context: wx.createVideoContext('video_' + i)
							})
						})
						that.setData({
							listOption: listOption
						})
					})
					that.setData({
						list: list.data
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

	onShareAppMessage: function (e) {
		const that = this
		const items = that.data.listOption[that.data.active]
		const {
			extend_code,
			uid
		} = app.globalData
		let path = 'packageSecond/pages/goodthingcircle/vedioDetail/index?' + util.encodeUriParams({
			extend_code,
			uid: encodeURIComponent(Base64.encode(uid)),
			thing_id: items.thing_id
		})
		console.log(path)
		return {
			path: path,
			title: that.shareInfo.title || '',
			imageUrl: that.shareInfo.imageUrl || '',
			success: (res) => {
				console.log('转发成功！');
			},
			fail: (res) => {
				console.log('转发失败！');
			}
		}
	},

	// 获取分享参数
	getThingcircleShareInfo: function () {
		const that = this;
		const items = that.data.listOption[that.data.active]
		let postData = {
			thing_id: items.thing_id,
		}
		let datainfo = requestSign.requestSign(postData);
		header.sign = datainfo
		wx.request({
			url: api.get_thingcircleShareInfo,
			data: postData,
			header: header,
			method: 'POST',
			dataType: 'json',
			responseType: 'text',
			success: (res) => {
				if (res.data.code >= 0) {
					that.data.shareInfo = {
						title: res.data.data.thing_title,
						imageUrl: res.data.data.thing_pic
					}
				}
			},
			fail: (res) => {},
		})
	},

	// 关注
	sensitiveOthers: function () {
		const user_token = wx.getStorageSync('user_token');
		if (!user_token) {
			wx.navigateTo({
				url: '/pages/logon/index',
			})
			return false;
		}
		const that = this;
		const items = that.data.listOption[that.data.active]

		let postData = {
			thing_auid: that.data.listOption[that.data.active].user_id
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
					if (res.data.message == "关注成功") {
						items.is_attention = 1;
					} else {
						items.is_attention = 0;
					}
					that.setData({
						listOption: that.data.listOption
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
		const items = that.data.listOption[that.data.active]
		console.log(items, that.data.active)
		if (init) {
			items.commentList = []
		}
		let postData = {
			page_index: init ? 1 : items.page_index,
			page_size: items.page_size,
			thing_id: items.thing_id,
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
					let commentList = items.commentList.concat(list.data);
					items.page_count = list.page_count
					items.commentList = commentList
					items.is_more = is_more
					items.opened = true
					console.log(that.data.listOption)
					that.setData({
						listOption: that.data.listOption
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

	onCommentBottom: function () {
		const that = this;
		const items = that.data.listOption[that.data.active]
		if (items.page_index < items.page_count) {
			items.page_index += 1;
			that.getCommentList();
		}
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
		if (!user_token) {
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
		const items = that.data.listOption[that.data.active]
		let postData = {
			comment_id: item.id
		}
		if (items.flag_like == false) {
			return false;
		}
		items.flag_like = false;
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
				items.flag_like = true;
				if (res.data.code == 1) {
					var commentItem = typeof cindex == 'undefined' ? items.commentList[index] : items.commentList[index].reply_list.data[cindex]
					if (res.data.message == "取消点赞成功") {
						commentItem.is_like = 0;
					} else {
						commentItem.is_like = 1;
					}
					commentItem.comment_likes = res.data.count;
					commentItem.like_count = res.data.count;
					items.commentItem = commentItem
					that.setData({
						listOption: that.data.listOption
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
		if (!user_token) {
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
		const items = that.data.listOption[that.data.active]
		const isUid = app.globalData.uid == item.from_uid; //判断是否是自己的回复
		console.log(index, item, hash, cindex)

		wx.showActionSheet({
			itemList: ['回复', isUid ? '删除' : '举报'],
			success(res) {
				if (res.tapIndex === 0) {
					items.sendOptions = {
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
		const items = that.data.listOption[that.data.active]
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
					items.commentList[index].reply_list.data = res.data.data.data || [];
					items.is_more[index] = 0;
					that.setData({
						listOption: that.data.listOption
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
		const user_token = wx.getStorageSync('user_token');
		if (!user_token) {
			wx.navigateTo({
				url: '/pages/logon/index',
			})
			return false;
		}
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
	onBottomFabulous: function (e) {
		const user_token = wx.getStorageSync('user_token');
		if (!user_token) {
			wx.navigateTo({
				url: '/pages/logon/index',
			})
			return false;
		}
		const that = this;
		const items = that.data.listOption[that.data.active]
		const {
			item,
			index,
			cindex
		} = e.currentTarget.dataset
		let postData = {
			thing_id: items.thing_id
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
						that.data.list[index].is_like = 0;
					} else {
						that.data.list[index].is_like = 1;
					}
					that.data.list[index].likes = res.data.count;
					that.setData({
						list: that.data.list
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
	onBottomCollection: function (e) {
		const user_token = wx.getStorageSync('user_token');
		if (!user_token) {
			wx.navigateTo({
				url: '/pages/logon/index',
			})
			return false;
		}
		const that = this;
		const items = that.data.listOption[that.data.active]
		const {
			item,
			index,
			cindex
		} = e.currentTarget.dataset
		let postData = {
			thing_id: items.thing_id
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
						that.data.list[index].is_collect = 0;
					} else {
						that.data.list[index].is_collect = 1;
					}
					that.data.list[index].collects = res.data.count;
					that.setData({
						list: that.data.list
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
		const that = this
		const items = that.data.listOption[that.data.active]
		if (!items.sendOptions) {
			items.sendOptions = {}
		}
		items.sendOptions.content = e.detail.value
		that.setData({
			listOption: that.data.listOption
		})
	},
	// 关闭弹窗
	popupClose: function (e) {
		const that = this
		const items = that.data.listOption[that.data.active]
		items.sendPopupShow = false
		items.sendPopupPlaceholder = '说点什么...'
		items.sendOptions = null
		that.setData({
			listOption: that.data.listOption
		})
	},
	// 显示回复弹窗
	onPopMessage: function (option) {
		const that = this
		const items = that.data.listOption[that.data.active]
		items.sendPopupShow = true
		items.sendPopupPlaceholder = option && option.placeholder ? option.placeholder : '说点什么...'
		that.setData({
			listOption: that.data.listOption
		})
	},
	// 发送回复内容
	onSend: function (option) {
		const user_token = wx.getStorageSync('user_token');
		if (!user_token) {
			wx.navigateTo({
				url: '/pages/logon/index',
			})
			return false;
		}
		const that = this
		const items = that.data.listOption[that.data.active]
		if (items.sendFlag) {
			return false
		}
		items.sendFlag = true
		if (!items.sendOptions || !items.sendOptions.content) {
			return wx.showToast({
				title: '没有什么想说的吗',
				icon: 'none'
			})
		}
		let postData = {
			thing_id: items.thing_id,
			topic_id: items.topic_id || "",
			content: items.sendOptions.content,
			...items.sendOptions
		}
		console.log(postData)
		let url = items.sendOptions.comment_pid ? api.get_thingcircleDetailReplyComment : api.get_thingcircleDetailComment
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
				items.sendFlag = false
			},
		})
	},
	// 底部弹窗评论
	onBottomComment: function (e) {
		const that = this
		const items = that.data.listOption[that.data.active]
		const {
			item,
			index,
			cindex
		} = e.currentTarget.dataset
		items.commentPopupShow = true
		if (!items.opened) {
			that.getCommentList()
		}
		items.stopTouchMove = true
		that.setData({
			listOption: that.data.listOption
		})
	},
	// 关闭评论弹窗
	commentPopupClose: function (e) {
		const that = this
		const items = that.data.listOption[that.data.active]
		const {
			item,
			index,
			cindex
		} = e.currentTarget.dataset
		items.commentPopupShow = false
		items.stopTouchMove = false
		that.setData({
			listOption: that.data.listOption
		})
	},
	// 底部弹窗商品
	onBottomGoods: function (e) {
		const that = this
		const items = that.data.listOption[that.data.active]
		const {
			item,
			index,
			cindex
		} = e.currentTarget.dataset
		items.goodsPopupShow = true
		that.setData({
			listOption: that.data.listOption
		})
	},
	goodsPopupClose: function (e) {
		const that = this
		const items = that.data.listOption[that.data.active]
		const {
			item,
			index,
			cindex
		} = e.currentTarget.dataset
		items.goodsPopupShow = false
		that.setData({
			listOption: that.data.listOption
		})
	}
})