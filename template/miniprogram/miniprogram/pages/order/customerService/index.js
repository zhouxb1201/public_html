var requestSign = require('../../../utils/requestData.js');
var api = require('../../../utils/api.js').open_api;
var util = require('../../../utils/util.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    refundReason: [
      '拍错/多拍/不想要',
      '协商一致退款/退货',
      '缺货',
      '未按约定时间发货',
      '其他'
    ],
    goodData: '',
    refund_detail: '',
    index: '',
    order_goods_id: '',
    order_id: '',
    //商家信息
    shop_info: '',    
    //选中的物流公司id
    select_logistics_id: '',
    //物流单号
    logistics_no: '',
    //物流列表显示
    expressShow: false,
    page_index: 1,
    search_text:'',
    //选择的物流公司
    select_logistics_company:'',
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {
    const that = this;
    if (options.ordergoodid != undefined) {
      that.data.order_goods_id = options.ordergoodid;
    } else {
      that.data.order_id = options.orderid;
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
    this.refundDetail()
  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function() {

  },

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function() {
    const that = this;
    that.data.page_index = that.data.page_index + 1;
    that.getvExpressCompany();
  },

  // 监听滚动条坐标
  // onPageScroll: function (e) {
  //   const that = this;
  //   let scrollTop = e.scrollTop;
  //   console.log(scrollTop);

  // },


  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function() {
    const that = this;

    
  },


  //查询退款详情
  refundDetail: function() {
    const that = this;
    let postData = {};
    if (that.data.order_goods_id != '') {
      postData.order_goods_id = that.data.order_goods_id
    } else {
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
          let shop_info = res.data.data.shop_info;
          if (that.data.order_goods_id == '') {
            let order_goods_id_list = [];
            for (let value of refund_detail.goods_list) {
              order_goods_id_list.push(value.order_goods_id);
            }
            that.data.order_goods_id = order_goods_id_list
          } else {
            let order_goods_id_list = []
            order_goods_id_list.push(that.data.order_goods_id);
            that.data.order_goods_id = order_goods_id_list
          }
          that.setData({
            goodData: goodData,
            refund_detail: refund_detail,
            order_id: order_id,
            index: (refund_detail.refund_reason - 1),
            shop_info: shop_info,
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

  //查询物流公司列表
  getvExpressCompany: function() {
    const that = this;
    let postData = {
      'page_index': that.data.page_index,
      'page_size': 50,
      'search_text': that.data.search_text,
    };
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getvExpressCompany,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code > 0) {
          that.setData({
            expressList: res.data.data.expressList,
            expressShow: true
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
  //关键字
  inputKey:function(e){
    const that = this;
    let key = e.detail.value;
    that.setData({
      search_text:key
    })
  },

  //搜索关键字
  searchKeyexpress:function(){
    const that = this;    
    that.getvExpressCompany();
  },

  //物流列表隐藏
  expressClose: function() {
    this.setData({
      expressShow: false
    })
  },

  //取消退款
  refundCancel: function() {
    const that = this;
    wx.showModal({
      title: '确认提示',
      content: '确认取消退款吗?',
      success: (res) => {
        if (res.confirm) {


          let postData = {
            'order_id': that.data.order_id,
            'order_goods_id': that.data.order_goods_id,
          }
          let datainfo = requestSign.requestSign(postData);
          header.sign = datainfo
          wx.request({
            url: api.get_cancelOrderRefund,
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
                  wx.navigateBack({
                    delta: 1
                  })
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
        } else if (res.cancel) {
          return
        }
      }
    })

  },  

  //选择的物流公司
  selectCompany:function(e){
    const that = this;    
    that.setData({
      select_logistics_id: e.currentTarget.dataset.cid,
      select_logistics_company: e.currentTarget.dataset.companyname,
      expressShow:false,
    })
  },

  //物流单号
  logisticsNo: function(e) {
    let logistics_no = e.detail.value;
    this.data.logistics_no = logistics_no;
  },

  //订单退货信息提交
  orderGoodsRefundExpress: function() {
    const that = this;
    let postData = {
      'order_id': that.data.order_id,
      'order_goods_id': that.data.order_goods_id,
      'refund_express_company': that.data.select_logistics_id,
      'refund_shipping_no': that.data.logistics_no
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_orderGoodsRefundExpress,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code > 0) {
          wx.showToast({
            title: '提交成功！',
          })
          wx.navigateBack({
            delta: 1
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

  //重新申请
  refundAgain: function() {
    const that = this;
    if (that.data.refund_detail.refund_type == 2) {
      let onPageData = {
        url: '../refund/index',
        num: 3,
        param: '?orderid=' + that.data.order_id + '&sign=order',
      }
      util.jumpPage(onPageData);
    } else if (that.data.refund_detail.refund_type == 1) {
      let onPageData = {
        url: '../refund/index',
        num: 3,
        param: '?ordergoodid=' + that.data.order_goods_id + '&sign=goods',
      }
      util.jumpPage(onPageData);
    }

  }

})