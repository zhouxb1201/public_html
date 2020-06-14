class Luck {
  constructor(page, opts) {
    opts = opts || {};
    this.page = page;
    this.canvasId = opts.canvasId || 'luck';
    this.width = opts.width || 300;
    this.height = opts.height || 120;
    this.maskColor = opts.maskColor || '#dddddd';
    this.size = opts.size || 16;
    this.r = this.size * 2;
    this.area = this.r * this.r;
    this.scale = opts.scale || 0.75;
    this.totalArea = this.width * this.height;
    this.endCallBack = opts.callback;
    this.handCallback = opts.handCallback;
    this.init();
  }
  init() {
    this.isContinue = true;
    this.show = false;
    this.clearPoints = [];
    this.ctx = wx.createCanvasContext(this.canvasId, this);
    this.drawMask();
    this.bindTouch();
    this.num = 0;
    this.prizeCode = null;
    wx.hideLoading();
  }
  drawMask() {
    this.ctx.setFillStyle(this.maskColor);
    this.ctx.fillRect(0, 0, this.width, this.height);
    this.ctx.setFontSize(20);
    this.ctx.setFillStyle("#333");
    this.ctx.setTextAlign('center');
    this.ctx.fillText('刮一刮', this.width / 2, 60);
    this.ctx.draw();
  }
  eraser(e, bool) {
    if (!this.isContinue) return;
    if (this.page.data.frequency === 0) {
      wx.showToast({
        title: '抱歉，您已经没有抽奖机会了。',
        icon: 'none',
        duration: 2000
      });
      return false;
    }
    this.num++;
    let len = this.clearPoints.length;
    let count = 0;
    let x = e.touches[0].x, y = e.touches[0].y;
    let x1 = x - this.size;
    let y1 = y - this.size;
    if (bool) {
      this.clearPoints.push({
        x1: x1,
        y1: y1,
        x2: x1 + this.r,
        y2: y1 + this.r
      })
    }
    for (let val of this.clearPoints) {
      if (val.x1 > x || val.y1 > y || val.x2 < x || val.y2 < y) {
        count++;
      } else {
        break;
      }
    }
    if (len === count) {
      this.clearPoints.push({
        x1: x1,
        y1: y1,
        x2: x1 + this.r,
        y2: y1 + this.r
      })
    }
    if (this.num === 1 && this.isContinue == true) {
      this.handCallback && this.handCallback();
    }
    if (this.page.data.prizeCode !== 0 && this.page.data.prizeCode !== 1) {
      return false;
    }
    if (this.clearPoints.length && this.r * this.r * this.clearPoints.length > this.scale * this.totalArea) {
      this.show = true;
    }
    this.ctx.clearRect(x1, y1, this.r, this.r);
    this.ctx.draw(true);
  }
  bindTouch() {
    const _this = this;
    _this.page.onTouchStart = function (e) {
      _this.eraser(e, true);
    }
    _this.page.onTouchMove = function (e) {
      _this.eraser(e);
    }
    _this.page.onTouchEnd = function (e) {
      if (_this.show && _this.isContinue == true) {
        _this.endCallBack && _this.endCallBack();
        _this.isContinue = false;
        _this.page.setData({
          isContinue:false
        })
        _this.ctx.clearRect(0, 0, _this.width, _this.height);
        _this.ctx.draw();
      }
    }
  }
}
module.exports = Luck;