var requestSign = require('../../../utils/requestData.js');
var api = require('../../../utils/api.js').open_api;
var re = require('../../../utils/request.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    detailData:'',
    //商品图片
    avater:'',
    openSettingBtnHidden: true,
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    this.data.goods_id = options.goods_id;
    this.goodsShareDetail();
  },  

  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function () {

  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {
    
  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function () {

  },

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function () {

  },

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function () {

  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {

  },

  //商品分享
  goodsShareDetail: function () {
    const that = this;    
    let postData = {
      'goods_id': that.data.goods_id,     
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_goodsShareDetail,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          that.setData({
            detailData:res.data.data,
            avater: res.data.data.image
          })
          that.getAvaterInfo();
        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
        }

      },
      fail: (res) => {

      },
    })
  },

  //小程序太阳码生成
  getLimitMpCode: function (productSrc, data) {
    const that = this;
    const appConfig = getApp().globalData;
    var postData = {
      'website_id': appConfig.website_id,
      'auth_id': appConfig.auth_id,
      'page': 'pages/goods/detail/index',
      "goodsId": that.data.goods_id
    };
    if (wx.getStorageSync('extend_code')) {
      postData.code = wx.getStorageSync('extend_code')
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_getUnLimitMpCode, postData, header).then((res) => {
      if (res.data.code == 1) {
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


  //下载产品图片
  getAvaterInfo: function () {
    let that = this;
    var productImage = that.data.avater;
    wx.showLoading({
      title: '生成中...',
      mask: true,
    });
    if (productImage) {
      wx.downloadFile({
        url: productImage,
        success: function (res) {
          wx.hideLoading();
          if (res.statusCode === 200) {
            var productSrc = res.tempFilePath;
            that.calculateImg(productSrc, function (data) {
              that.getLimitMpCode(productSrc,data);
              
            })
          } else {
            wx.showToast({
              title: '产品图片下载失败！',
              icon: 'none',
              duration: 2000,
              success: function () {
                var productSrc = "";
                that.getQrCode(productSrc);
              }
            })
          }
        },
        fail: function (res) {
          wx.hideLoading();
          wx.showModal({
            title: '提示',
            content: '生成图片出错',
            showCancel: false,
            success(res) {
              if (res.confirm) {
                wx.navigateBack({
                  delta:1
                })
              }
            }
          })
        }
      })
    } else {
      wx.hideLoading();
      var productSrc = "";
      that.getQrCode(productSrc);
    }

  },

  //下载二维码
  getQrCode: function (productSrc, imgInfo = "") {
    wx.showLoading({
      title: '生成中...',
      mask: true,
    });
    var that = this;
    var productCode = that.data.codeimg;    
    if (productCode) {
      wx.downloadFile({
        url: productCode,
        success: function (res) {
          wx.hideLoading();
          if (res.statusCode === 200) {
            var codeSrc = res.tempFilePath;
            that.sharePosteCanvas(productSrc, codeSrc, imgInfo);
          } else {
            wx.showToast({
              title: '二维码下载失败！',
              icon: 'none',
              duration: 2000,
              success: function () {
                var codeSrc = "";
                that.sharePosteCanvas(productSrc, codeSrc, imgInfo);
              }
            })
          }
        },
        fail: function (res) {
          wx.hideLoading();
          wx.showModal({
            title: '提示',
            content: '生成图片出错',
            showCancel: false,
            success(res) {
              if (res.confirm) {
                that.setData({
                  showpost: false,
                })
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

  handleSetting: function (e) {
    let that = this;
    // 对用户的设置进行判断，如果没有授权，即使用户返回到保存页面，显示的也是“去授权”按钮；同意授权之后才显示保存按钮
    if (!e.detail.authSetting['scope.writePhotosAlbum']) {
      wx.showModal({
        title: '警告',
        content: '若不打开授权，则无法将图片保存在相册中！',
        showCancel: false
      })
      that.setData({
        saveImgBtnHidden: true,
        openSettingBtnHidden: false
      })
    } else {
      wx.showModal({
        title: '提示',
        content: '您已授权，赶紧将图片保存在相册中吧！',
        showCancel: false
      })
      that.setData({
        saveImgBtnHidden: false,
        openSettingBtnHidden: true
      })
    }
  },

  //canvas绘制分享海报
  sharePosteCanvas: function (avaterSrc, codeSrc, imgInfo) {
    wx.showLoading({
      title: '生成中...',
      mask: true,
    })
    var that = this;
    const ctx = wx.createCanvasContext('myCanvas', that);
    var width = "";
    
    const query = wx.createSelectorQuery().in(this);
    query.select('#canvas-container').boundingClientRect(function (rect) {
      var height = rect.width;
      var right = rect.right;
      width = rect.width;
      var left = rect.left;
      let imgheght = that.data.imgHeight
      ctx.setFillStyle('#fff');
      ctx.fillRect(0, 0, rect.width, height);

      //头像
      if (avaterSrc) {
        // if (imgInfo) {
        //   var imgheght = parseFloat(imgInfo);
        // }
        
        ctx.drawImage(avaterSrc, 0, 0, width, imgheght);
        ctx.setFontSize(14);
        ctx.setFillStyle('#fff');
        ctx.setTextAlign('left');
      }   

      //  绘制二维码
      if (codeSrc) {
        ctx.drawImage(codeSrc, width - width / 4, imgheght - width / 4, width / 4, width / 4)
        ctx.setFontSize(10);
        ctx.setFillStyle('#000');
        //ctx.fillText("微信扫码或长按保存图片", left + 165, imgheght + 110);
      }
    }).exec()
    setTimeout(function () {
      ctx.draw();
      wx.hideLoading();
      that.data.showCanvas = false;
    }, 1000)

  },

  //点击把图片保存到相册
  saveImg: function () {
    const that = this;
    if (that.data.configposter == 1) {
      that.savePosterImg();
    } else {
      that.saveCanvasShareImg();
    }
  },

  //把系统生成的海报保存到相册
  savePosterImg: function () {
    const that = this;
    wx.showLoading({
      title: '正在保存',
      mask: true,
    })
    wx.downloadFile({
      url: that.data.posterimg,
      success(res) {
        if (res.statusCode === 200) {
          var posterImg = res.tempFilePath;
          wx.hideLoading();
          wx.saveImageToPhotosAlbum({
            filePath: posterImg,
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
            }
          })
        }
      }
    })
  },

  //点击把生成的canvas图片保存到相册
  saveCanvasShareImg: function () {
    var that = this;
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
  },  

  //计算图片尺寸
  calculateImg: function (src, cb) {
    var that = this;
    wx.getImageInfo({
      src: src,
      success(res) {
        wx.getSystemInfo({
          success(res2) {
            var ratio = res.width / res.height;
            var imgHeight = (res2.windowWidth * 0.85 / ratio) + 100;
            if (imgHeight < res2.windowWidth){
              that.setData({
                imgHeight: imgHeight
              })
            }else{
              that.setData({
                imgHeight: res2.windowWidth
              })
            }           
            
            cb(imgHeight - 100);
          }
        })
      }
    })
  }


})