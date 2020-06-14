const app = getApp();
Component({
  /**
   * 组件的属性列表
   */
  properties: {
    avater: { // 图片
      type: String,
      value: ''
    },
    price: { // 价格
      type: String,
      value: ''
    },
    productname: { // 名称
      type: String,
      value: ''
    },
    codeimg: { // 二维码
      type: String,
      value: ''
    },
    marketprice: { //市场价
      type: String,
      value: ''
    },
    configposter: { //平台系统是否有海报应用
      type: Number,
      value: 0,
    },
    posterimg: {
      type: String,
      value: '',
    }
  },

  /**
   * 组件的初始数据
   */
  data: {
    productCode: "",
    showpost: false,
    imgHeight: 0,
    productCode: "", //二维码
    imgsrc: '',
    showCanvas: true,
    saveImgBtnHidden: false,
    openSettingBtnHidden: true
  },

  ready: function() {

  },

  /**
   * 组件的方法列表
   */
  methods: {
    getPosterImg: function() {
      var that = this;
      that.setData({
        showpost: true,
      })
    },
    //下载产品图片
    getAvaterInfo: function() {

      var that = this;
      that.setData({
        showpost: true,
      })



      var productImage = that.data.avater;
      console.log(productImage)

      if (that.data.showCanvas) {
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
                  that.getQrCode(productSrc, data);
                })
              } else {
                wx.showToast({
                  title: '产品图片下载失败！',
                  icon: 'none',
                  duration: 2000,
                  success: function() {
                    var productSrc = "";
                    that.getQrCode(productSrc);
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
          var productSrc = "";
          that.getQrCode(productSrc);
        }
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
      console.log(productCode)
      //that.sharePosteCanvas(productSrc, productCode, imgInfo);
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

    handleSetting: function(e) {
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
        var height = rect.height;
        var right = rect.right;
        width = rect.width;
        var left = rect.left;
        ctx.setFillStyle('#fff');
        ctx.fillRect(0, 0, rect.width, height);

        //头像
        if (avaterSrc) {
          if (imgInfo) {
            var imgheght = parseFloat(imgInfo);
          }
          ctx.drawImage(avaterSrc, 0, 0, width, imgheght ? imgheght : width);
          ctx.setFontSize(14);
          ctx.setFillStyle('#fff');
          ctx.setTextAlign('left');
        }

        //产品名称
        if (that.data.productname) {
          const CONTENT_ROW_LENGTH = 24; // 正文 单行显示字符长度
          let [contentLeng, contentArray, contentRows] = that.textByteLength((that.data.productname).substr(0, 40), CONTENT_ROW_LENGTH);
          ctx.setTextAlign('left');
          ctx.setFillStyle('#000');
          ctx.setFontSize(14);
          let contentHh = 22 * 1;
          console.log(contentArray)
          for (let m = 0; m < contentArray.length; m++) {
            ctx.fillText(contentArray[m], 15, imgheght + 20 + contentHh * m);
          }
        }


        //产品金额
        let price_x = '';
        let price_y = '';
        if (that.data.price || that.data.price == 0) {
          ctx.setFontSize(20);
          ctx.setFillStyle('#f44');
          ctx.setTextAlign('left');
          var price = that.data.price;
          if (!isNaN(price)) {
            price = "¥" + that.data.price
          }
          price_x = left - 15;
          price_y = imgheght + 90;
          ctx.fillText(price, price_x, price_y);
        }

        // 市场价
        if (that.data.marketprice || that.data.marketprice == 0) {
          ctx.setFontSize(14);
          ctx.setFillStyle('#999');
          ctx.setTextAlign('left');
          var marketprice = that.data.marketprice;
          if (!isNaN(marketprice)) {
            marketprice = "¥" + that.data.marketprice
          }
          ctx.fillText(marketprice, price_x + 110, price_y);
          // ctx.beginPath()
          // ctx.moveTo(left + 70, imgheght + 85)
          // ctx.lineTo(left + 110, imgheght + 85)
          // ctx.stroke()
        }


        //  绘制二维码
        if (codeSrc) {
          ctx.drawImage(codeSrc, width - width / 4, imgheght + 10, width / 4, width / 4)
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


   

    textByteLength(text, num) { // text为传入的文本  num为单行显示的字节长度
      let strLength = 0; // text byte length
      let rows = 1;
      let str = 0;
      let arr = [];
      for (let j = 0; j < text.length; j++) {
        if (text.charCodeAt(j) > 255) {
          strLength += 2;
          if (strLength > rows * num) {
            strLength++;
            arr.push(text.slice(str, j));
            str = j;
            rows++;
          }
        } else {
          strLength++;
          if (strLength > rows * num) {
            arr.push(text.slice(str, j));
            str = j;
            rows++;
          }
        }
      }
      arr.push(text.slice(str, text.length));
      return [strLength, arr, rows] //  [处理文字的总字节长度，每行显示内容的数组，行数]
    },


    //点击把图片保存到相册
    saveImg: function() {
      const that = this;
      if (that.data.configposter == 1) {
        that.savePosterImg();
      } else {
        that.saveCanvasShareImg();
      }
    },

    //把系统生成的海报保存到相册
    savePosterImg: function() {
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
                  success: function(res) {
                    that.closePoste();
                    if (res.confirm) {}
                  },
                  fail: function(res) {
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
    saveCanvasShareImg: function() {
      var that = this;
      wx.showLoading({
        title: '正在保存',
        mask: true,
      })
      setTimeout(function() {
        wx.canvasToTempFilePath({
          canvasId: 'myCanvas',
          success: function(res) {
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
                  success: function(res) {
                    that.closePoste();
                    if (res.confirm) {}
                  },
                  fail: function(res) {
                    console.log(res)
                  }
                })
              },
              fail: function(res) {
                that.setData({
                  saveImgBtnHidden: true,
                  openSettingBtnHidden: false
                })
              }
            })
          },
          fail: function(err) {
            console.log(err)
          }
        }, that);
      }, 1000);
    },


    //关闭海报
    closePoste: function() {
      this.setData({
        showpost: false,
        showCanvas: true,
      })
      // detail对象，提供给事件监听函数
      this.triggerEvent('myevent', {
        showVideo: true
      })
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
              if (imgHeight > 420) {
                imgHeight = 420
              }
              that.setData({
                imgHeight: imgHeight
              })
              cb(imgHeight - 100);
            }
          })
        }
      })
    }
  }
})