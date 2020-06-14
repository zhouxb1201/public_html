var requestSign = require('../../../../utils/requestData.js');
var util = require('../../../../utils/util.js');
var time = require('../../../../utils/time.js');
var api = require('../../../../utils/api.js').open_api;
var header = getApp().header;
var app = getApp()

Page({

	data: {
		message: "",
		autosize: {
			maxHeight: 100,
			minHeight: 100
		},
		type: "",
		reportAction: [],
		violation_id: null,
		arrImg: [],
		isSuccess: false,
		comment_id:0,
		isLoading:false
	},

	onLoad: function (options) {
		this.data.comment_id = options.comment_id
		this.getList()
	},

	getList: function () {
		const that = this;
		let postData = {};
		let datainfo = requestSign.requestSign(postData);
		header.sign = datainfo
		wx.request({
			url: api.get_thingcircleViolationList,
			data: postData,
			header: header,
			method: 'POST',
			dataType: 'json',
			responseType: 'text',
			success: (res) => {
				if (res.data.code >= 0) {
					that.setData({
						reportAction: res.data.data.data
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

	onMessage: function (e) {
		this.data.message = e.detail
	},

	onSelect: function (e) {
		const that = this;
		const {
			item
		} = e.currentTarget.dataset
		that.data.type = item.violation_id;
		that.data.violation_id = item.violation_id;
		that.setData({
			type: item.violation_id
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
	
  //上传图片
  onUploadImages: function () {
		const that = this;
		wx.showLoading({
			title: '上传中',
		})
    wx.chooseImage({
      count: 3,
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
              
            },
            success: (res) => {
              let image_data = res.data;
              let image_src = JSON.parse(image_data);
              let imgList = that.data.arrImg;
							imgList.push(image_src.data.src);
							wx.hideLoading()
              that.setData({
                arrImg: imgList
              })
            }
          })
        }

      },
    })
  },

	onSubmit: function () {
		const that = this;
		let params = {
			comment_id: that.data.comment_id,
			violation_id: that.data.violation_id,
			report_reason: that.data.message,
			report_photo: that.data.arrImg.join()
		};
		if (!params.violation_id) {
			wx.showToast({
				title: '请选择违规类型',
				icon: 'none'
			})
			return false;
		}
		if (!params.report_reason) {
			wx.showToast({
				title: '请填写举报内容',
				icon: 'none'
			})
			return false;
		}
		if (!params.report_photo) {
			wx.showToast({
				title: '请上传举报图片',
				icon: 'none'
			})
			return false;
		}
		// return console.log(params)
		that.setData({
			isLoading: true
		})
		let postData = params;
		let datainfo = requestSign.requestSign(postData);
		header.sign = datainfo
		wx.request({
			url: api.get_thingcircleAddViolation,
			data: postData,
			header: header,
			method: 'POST',
			dataType: 'json',
			responseType: 'text',
			success: (res) => {
				if (res.data.code >= 0) {
					console.log(res.data)
					that.setData({
						isSuccess: true
					})
				} else {
					wx.showToast({
						title: res.data.message,
						icon: 'none'
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