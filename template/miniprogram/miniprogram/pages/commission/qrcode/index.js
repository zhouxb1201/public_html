var requestSign = require('../../../utils/requestData.js');
var api = require('../../../utils/api.js').open_api;
var re = require('../../../utils/request.js');
var util = require('../../../utils/util.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    pageShow: false,
    code_img: '',
    //平台系统是否有海报应用 0-没有 1-有
    config_poster: 0,
    //海报类型：0-默认海报，1-系统设置的海报
    posterImgType: 1,
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {

  },

  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function() {

  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function() {
    const value = wx.getStorageSync('user_token');
    if (value) {
      util.extend_code();
      this.setDistributionData();
      this.distributionCenter();
    } else {
      this.setData({
        loginShow: true,
      })
    }

    //this.getLimitMpCode();
  },

  //登录结果返回
  requestLogin: function(e) {
    const that = this;
    let result = e.detail.result;
    if (result == true) {
      util.extend_code();
      that.setDistributionData();
      that.distributionCenter();
    }
  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function() {

  },

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function() {

  },

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function() {

  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function() {

  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function() {

  },

  //分销中心，判断是否申请成为分销商
  distributionCenter: function() {
    const that = this;
    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    wx.showLoading({
      title: '加载中',
    })
    re.request(api.get_distributionCenter, postData, header).then((res) => {
      if (res.data.code >= 0) {
        wx.hideLoading();
        let isdistributor = res.data.data.isdistributor
        if (isdistributor != 2) {
          wx.showModal({
            title: '提示',
            content: '你还不是分销商，请先申请！',
            success(res) {
              if (res.confirm) {
                let onPageData = {
                  url: '../apply/index',
                  num: 4,
                  param: '?isdistributor=' + isdistributor,
                }
                util.jumpPage(onPageData);
              } else if (res.cancel) {
                wx.navigateBack({
                  delta: 1,
                })
              }
            }
          })
        } else {
          that.setData({
            pageShow: true
          })

          //平台设置，是否有海报应用 0-没开启 1-开启
          let config_poster = getApp().globalData.config.addons.poster;
          that.setData({
            config_poster: config_poster
          })
          //系统平台是否有海报应用1-有，0-没有
          if (that.data.config_poster == 1) {            
            that.getKindPoster();
          }else{
            that.getCodeBackground();
          }
          
        }
      } else {
        wx.showToast({
          title: res.data.message,
          icon: 'none'
        })
      }
    })
  },



  getLimitMpCode: function(productSrc, data) {
    const that = this;
    const appConfig = getApp().globalData;
    var postData = {
      'website_id': appConfig.website_id,
      'auth_id': appConfig.auth_id,
      'page': 'pages/index/index',
    };
    if (wx.getStorageSync('extend_code')) {
      postData.code = wx.getStorageSync('extend_code')
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_getUnLimitMpCode, postData, header).then((res) => {
      if (res.data.code == 1) {
        // that.setData({
        //   code_img: res.data.data
        // })
        let code_img = res.data.data;
        that.data.codeimg = code_img;
        that.getQrCode(productSrc, data);
      } else {
        that.setData({
          code_img: '/images/no-goods.png'
        })
      }
    })
  },

  saveCode: function() {
    const that = this;
    let img_list = [];
    img_list.push(that.data.poster_img);
    wx.previewImage({
      urls: img_list,
    })

  },

  //设置分销的文案字段
  setDistributionData: function() {
    const that = this;
    let distributionData = getApp().globalData.distributionData;
    if (distributionData == '') {
      util.distributionSet().then((res) => {
        let resultData = res.data.data;
        wx.setNavigationBarTitle({
          title: resultData.extension_code,
        })
        that.setData({
          txt_distribution_tips: resultData.distribution_tips,
        })
      });

    } else {
      wx.setNavigationBarTitle({
        title: distributionData.extension_code,
      })
      that.setData({
        txt_distribution_tips: distributionData.distribution_tips,
      })
    }
  },

  //下载背景图片
  getCodeBackground: function() {
    let that = this;
    var productImage = getApp().publicUrl + '/wap/static/images/poster.jpg';
    wx.showLoading({
      title: '生成中...',
      mask: true,
    });
    if (productImage) {
      wx.downloadFile({
        url: productImage,
        success: function(res) {
          wx.hideLoading();
          if (res.statusCode === 200) {
            var productSrc = res.tempFilePath;
            that.calculateImg(productSrc, function(data) {
              that.getLimitMpCode(productSrc, data);
            })
          } else {
            wx.showToast({
              title: '背景图片下载失败！',
              icon: 'none',
              duration: 2000,
              success: function() {
                var productSrc = "";
                wx.navigateBack({
                  delta: 1
                })
              }
            })
          }
        },
        fail: function(res) {
          wx.hideLoading();
          wx.showModal({
            title: '提示',
            content: '生成图片出错',
            showCancel: false,
            success(res) {
              if (res.confirm) {
                wx.navigateBack({
                  delta: 1
                })
              }
            }
          })
        }
      })
    } else {
      wx.hideLoading();
      var productSrc = "";

    }

  },

  //下载二维码
  getQrCode: function(productSrc, imgInfo = "") {
    wx.showLoading({
      title: '生成中...',
      mask: true,
    });
    var that = this;
    var productCode = that.data.codeimg;
    if (productCode) {
      wx.downloadFile({
        url: productCode,
        success: function(res) {
          wx.hideLoading();
          if (res.statusCode === 200) {
            var codeSrc = res.tempFilePath;
            that.sharePosteCanvas(productSrc, codeSrc, imgInfo);
          } else {
            wx.showToast({
              title: '二维码下载失败！',
              icon: 'none',
              duration: 2000,
              success: function() {
                var codeSrc = "";
                that.sharePosteCanvas(productSrc, codeSrc, imgInfo);
              }
            })
          }
        },
        fail: function(res) {
          wx.hideLoading();
          wx.showModal({
            title: '提示',
            content: '生成图片出错',
            showCancel: false,
            success(res) {
              if (res.confirm) {

              }
            }
          })
        }
      })
    } else {
      wx.hideLoading();
      var codeSrc = "";
      that.sharePosteCanvas(productSrc, codeSrc);
    }
  },

  //canvas绘制分享海报
  sharePosteCanvas: function(avaterSrc, codeSrc, imgInfo) {
    wx.showLoading({
      title: '生成中...',
      mask: true,
    })
    var that = this;
    const ctx = wx.createCanvasContext('myCanvas', that);
    var width = "";

    const query = wx.createSelectorQuery().in(this);
    query.select('#canvas-container').boundingClientRect(function(rect) {
      var height = rect.width;
      var right = rect.right;
      width = rect.width;
      var left = rect.left;
      let imgheght = that.data.imgHeight
      ctx.setFillStyle('#fff');
      ctx.fillRect(0, 0, rect.width, height);

      //头像
      if (avaterSrc) {

        ctx.drawImage(avaterSrc, 0, 0, width, imgheght);
        ctx.setFontSize(14);
        ctx.setFillStyle('#fff');
        ctx.setTextAlign('left');
      }

      //  绘制二维码
      if (codeSrc) {
        ctx.drawImage(codeSrc, width - width / 1.5, imgheght - width - 20, width / 3, width / 3)
        ctx.setFontSize(10);
        ctx.setFillStyle('#000');
        //ctx.fillText("微信扫码或长按保存图片", left + 165, imgheght + 110);
      }
    }).exec()
    setTimeout(function() {
      ctx.draw();
      wx.hideLoading();
      that.data.showCanvas = false;
    }, 1000)

  },



  //点击把生成的canvas图片保存到相册
  saveCanvasShareImg: function() {
    var that = this;
    if (that.data.posterImgType == 0){
      wx.showLoading({
        title: '正在保存',
        mask: true,
      })
      setTimeout(function () {
        wx.canvasToTempFilePath({
          canvasId: 'myCanvas',
          success: function (res) {
            wx.hideLoading();
            var tempFilePath = res.tempFilePath;
            wx.saveImageToPhotosAlbum({
              filePath: tempFilePath,
              success(res) {
                wx.showModal({
                  content: '图片已保存到相册，赶紧晒一下吧~',
                  showCancel: false,
                  confirmText: '好的',
                  confirmColor: '#333',
                  success: function (res) {
                    that.closePoste();
                    if (res.confirm) { }
                  },
                  fail: function (res) {
                    console.log(res)
                  }
                })
              },
              fail: function (res) {
                that.setData({
                  saveImgBtnHidden: true,
                  openSettingBtnHidden: false
                })
              }
            })
          },
          fail: function (err) {
            console.log(err)
          }
        }, that);
      }, 1000);
    }
    
  },

  //计算图片尺寸
  calculateImg: function(src, cb) {
    var that = this;
    wx.getImageInfo({
      src: src,
      success(res) {
        wx.getSystemInfo({
          success(res2) {
            var ratio = res.width / res.height;
            var imgHeight = (res2.windowWidth * 0.85 / ratio) + 100;

            that.setData({
              imgHeight: imgHeight
            })

            cb(imgHeight - 100);
          }
        })
      }
    })
  },

  //获取超级海报
  getKindPoster: function() {
    const that = this;
    var postData = {
      "poster_type": 1,
      "is_mp": 1,
      "mp_page": 'pages/index/index'
    };

    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_getKindPoster, postData, header).then((res) => {
      if (res.data.code == 0) {
        that.setData({
          posterImgType: 0
        })
        that.getCodeBackground();
      } else {
        that.setData({
          poster_img: res.data.data.poster
        })
      }
    })
  },


})