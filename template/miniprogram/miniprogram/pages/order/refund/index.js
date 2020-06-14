var requestSign = require('../../../utils/requestData.js');
var api = require('../../../utils/api.js').open_api;
var util = require('../../../utils/util.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    items: '',
    refundReason: [
      '拍错/多拍/不想要',
      '协商一致退款/退货',
      '缺货',
      '未按约定时间发货',
      '其他'
    ],
    index: 0,
    order_id: '',
    order_goods_id: '',
    //退款标识（order/good）
    sign: '',
    goodData: '',
    refund_detail: '',
    //退款方式
    refund_type: '',
    //退款金额
    refund_require_money: '',
    //消息模板id
    form_id: '',
    //订阅消息的模板id
    templateId: [],
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {
    const that = this;
    let sign = options.sign;
    that.data.sign = sign
    if (sign == 'goods') {
      let order_goods_id = options.ordergoodid;
      that.data.order_goods_id = order_goods_id;
    } else if (sign == 'order') {
      let order_id = options.orderid;
      that.data.order_id = order_id;
    }


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
    const that = this;
    this.refundDetail();

    //获取订阅模板的模板id
    let type = 4;
    const tId = util.getMpTemplateId(type);
    tId.then((res) => {
      if (res.data.code == 1 && res.data.data.length > 0) {
        let tem_array = [];
        for (let item of res.data.data) {
          if (item.status == 1) {
            tem_array.push(item.template_id)
          }
        }
        that.data.templateId = tem_array
      }
    })
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

  //退款原因选择
  pickReasonChage(e) {
    let index = e.detail.value;
    this.setData({
      index: index
    })
  },

  //退款方式
  radioChange: function(e) {
    const that = this;
    let radioSelected = e.detail.value;
    that.data.refund_type = radioSelected;
  },

  //退款金额
  refundMoneyFun: function(e) {
    const that = this;
    let refund_require_money = e.detail.value;
    that.data.refund_require_money = refund_require_money;
  },

  //查询退款详情
  refundDetail: function() {
    const that = this;
    let postData = {};
    if (that.data.sign == 'goods') {
      postData.order_goods_id = that.data.order_goods_id
    } else if (that.data.sign == 'order') {
      postData.order_id = that.data.order_id
    }

    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_refundDetail,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code > 0) {
          let goodData = res.data.data.refund_detail.goods_list;
          let refund_detail = res.data.data.refund_detail;
          let order_id = res.data.data.refund_detail.order_id;
          let items;
          if (refund_detail.order_status == 1) {
            items = [{
              name: '1',
              value: '退款'
            }];
          } else {
            //判断是否为虚拟商品，goods_type == 3，虚拟商品只能退款
            if (refund_detail.goods_type == 3) {
              items = [{
                name: '1',
                value: '仅退款'
              }, ]
            } else {
              items = [{
                  name: '1',
                  value: '仅退款'
                },
                {
                  name: '2',
                  value: '退货退款'
                },
              ]
            }

          }

          if (that.data.sign == 'order') {
            let order_goods_id = [];
            for (let value of refund_detail.goods_list) {
              order_goods_id.push(value.order_goods_id);
            }
            that.setData({
              order_goods_id: order_goods_id
            })
          }



          that.setData({
            goodData: goodData,
            refund_detail: refund_detail,
            order_id: order_id,
            items: items,

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

  //退款提交
  refundAsk: function() {
    const that = this;
    let order_goods_id = [];
    if (that.data.sign == 'goods') {
      order_goods_id.push(that.data.order_goods_id);
    } else {
      order_goods_id = that.data.order_goods_id
    }

    if (that.data.refund_require_money == '') {
      wx.showToast({
        title: '退款金额不能为空！',
        icon: 'none',
      })
      return
    }

    if (isNaN(that.data.refund_require_money)) {
      wx.showToast({
        title: '退款金额请填写数字',
        icon: 'none'
      })
      return
    }

    if (that.data.refund_require_money > that.data.refund_detail.refund_max_money) {
      wx.showToast({
        title: '输入的退款金额大于实际的退款金额',
        icon: 'none',
      })
      return
    }

    if (that.data.refund_type == '') {
      wx.showToast({
        title: '请选择退款方式！',
        icon: 'none',
      })
      return
    }
    //退款金额
    let refund_require_money = '';
    if (that.data.refund_detail.refund_max_money == 0) {
      refund_require_money = 0
    } else {
      refund_require_money = that.data.refund_require_money
    }

    let postData = {
      'order_id': that.data.order_id,
      'order_goods_id': order_goods_id,
      'refund_type': that.data.refund_type,
      'refund_require_money': refund_require_money,
      'refund_reason': (parseInt(that.data.index) + 1),
      'form_id': that.data.form_id,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_refundAsk,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code > 0) {
          wx.showToast({
            title: res.data.message,
            icon: 'success'
          })
          setTimeout(function() {
            let onPageData = {
              url: '1',
              num: 5,
              param: ''
            }
            util.jumpPage(onPageData);
          }, 2000)
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

  //获取消息模板id
  templateSend: function(e) {
    let that = this;
    let form_id = e.detail.formId;
    that.data.form_id = form_id;
    that.refundAsk();
  },

  //订阅消息
  subscribeMessage: function() {
    const that = this;
    if (that.data.templateId.length == 0) {
      that.refundAsk();
    } else {
      //订阅消息模板
      wx.requestSubscribeMessage({
        tmplIds: that.data.templateId,
        success(res) {
          console.log(res);
          that.refundAsk();
          util.postUserMpTemplateInfo(res);
        },
        fail(res) {
          console.log(res);
          that.refundAsk();
        }
      })
    }
  },


})