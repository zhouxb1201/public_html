var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var util = require('../../../../utils/util.js');
var app = getApp()
var header = getApp().header;
module.exports = {
	data: {
		isOpen: 1,
		thing_id:'',
		shareInfo: {}
	},
	// onShareAppMessage: function () {

	// },
	onLoad: function (option) {
		const _this = this
		
		if (getApp().globalData.config) {
			_this.setData({
				isOpen: getApp().globalData.config.addons.thingcircle
			})
		} else {
			getApp().watch('config', function (e) {
				_this.setData({
					isOpen: e.addons.thingcircle
				})
			})
		}
		_this.shareScene(option)
		_this.getThingcircleShareInfo(option)
	},
	shareScene: function (e) {
		const value = wx.getStorageSync('user_token');
		if(e.extend_code){
			wx.setStorageSync('higherExtendCode', e.extend_code)
		}
		if (e.extend_code && value) {
			util.checkReferee();
		}
	},
	// 获取分享参数
	getThingcircleShareInfo: function (option) {
		const that = this;
		let postData = {
			thing_id: option.thing_id || '',
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
						...res.data.data
					}
				}
			},
			fail: (res) => {},
		})
	},
}