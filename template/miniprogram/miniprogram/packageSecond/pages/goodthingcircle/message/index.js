var requestSign = require('../../../../utils/requestData.js');
var util = require('../../../../utils/util.js');
var api = require('../../../../utils/api.js').open_api;
var header = getApp().header;

Page({


	data: {
		hash: '',
		listParams: {
			page_index: 1,
			page_size: 10
		},
		list: []
	},

	onLoad: function (options) {
		this.setData({
			hash: options.hash
		})
		wx.setNavigationBarTitle({
			title: this.data.hash == 'collect' ? '赞和收藏' : '评论和@',
		})
		this.getList()
	},

	onReachBottom: function () {
		const that = this;
		if (that.data.listParams.page_index < that.data.listParams.page_count) {
			that.data.listParams.page_index += 1;
			that.getList();
		}
	},

	getList: function (init) {
		const that = this;
		let postData = {
			page_index: init ? 1 : that.data.listParams.page_index,
			page_size: that.data.listParams.page_size
		}
		if (init) {
			that.data.list = []
		}
		let url = that.data.hash == 'collect' ? api.get_thingcircleMessageLac : api.get_thingcircleMessageComment
		let datainfo = requestSign.requestSign(postData);
		header.sign = datainfo
		wx.request({
			url: url,
			data: postData,
			header: header,
			method: 'POST',
			dataType: 'json',
			responseType: 'text',
			success: (res) => {
				if (res.data.code >= 1) {
					const data = res.data.data
					let list = that.data.list.concat(data.data);
					that.data.listParams.page_count = data.page_count
					that.setData({
						list: list
					})
				} else {
					wx.showToast({
						title: res.data.message,
						icon: 'none'
					})
				}
			}
		})
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