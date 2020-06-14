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
		info: {},
		active: 0,
		showType: 0,
		list: [],
		listParams: {
			page_index: 1,
			page_size: 10
		}
	},

  mixins:[require('../mixin/mixin')],
	/**
	 * 生命周期函数--监听页面加载
	 */
	onLoad: function (options) {
		this.getUserInfo()
		this.getUserThingList()
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
	onReady: function () {
		
	},

	onShow: function () {
		this.setData({
			isOpen: getApp().globalData.config.addons.thingcircle
		})
	},

	onReachBottom: function () {
		const that = this;
		if (that.data.listParams.page_index < that.data.listParams.page_count) {
			that.data.listParams.page_index += 1;
			that.getUserThingList();
		}
	},

	getUserInfo() {
		const that = this;
		let postData = {}
		let datainfo = requestSign.requestSign(postData);
		header.sign = datainfo
		wx.request({
			url: api.get_ThingcircleUserInfo,
			data: postData,
			header: header,
			method: 'POST',
			dataType: 'json',
			responseType: 'text',
			success: (res) => {
				if (res.data.code == 1) {
					that.setData({
						info: res.data.data
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
	toFollow() {
		wx.navigateTo({
			url: '../follow/index',
		})
	},
	toFans() {
		wx.navigateTo({
			url: '../fans/index',
		})
	},
	getUserThingList(init) {
		const that = this;
		let postData = {
			page_index: init ? 1 : that.data.listParams.page_index,
			page_size: that.data.listParams.page_size
		}
		if (init) {
			that.setData({list:[]})
		}
		if (that.data.active) {
			postData.thing_option = that.data.listParams.thing_option
		}
		let datainfo = requestSign.requestSign(postData);
		header.sign = datainfo
		wx.request({
			url: api.get_thingcircleUserThingList,
			data: postData,
			header: header,
			method: 'POST',
			dataType: 'json',
			responseType: 'text',
			success: (res) => {
				if (res.data.code >= 0) {
					const data = res.data.data
					let list = that.data.list.concat(data.data);
					that.data.listParams.page_count = data.page_count
					that.setData({
						showType: data.display_model,
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

	onTabsChange(e) {
		const that = this
		that.setData({
			active:e.detail.index
		})
		if (e.detail.index) {
			that.data.listParams.thing_option = e.detail.index + 1
		}
		that.getUserThingList('init')
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
		let index = e.currentTarget.dataset.index;
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
					that.data.list[index].is_like = that.data.list[index].is_like == 1 ? 0:1
					that.data.list[index].likes = res.data.count
					that.setData({
						list:that.data.list
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

	toMessage:function(){
		wx.navigateTo({
			url: '../../messageCenter/index'
		})
	}

})