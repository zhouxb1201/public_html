var requestSign = require('../../../utils/requestData.js');
var api = require('../../../utils/api.js').open_api;
var util = require('../../../utils/util.js');
var header = getApp().header;
var Base64 = require('../../../utils/base64.js').Base64;
Page({

  /**
   * 页面的初始数据
   */
  data: {   
       
    //店铺描述评分
    shop_desc:'',
    //店铺服务评分
    shop_service:'',
    //店铺物流评分
    shop_stic:'',
    //商品评论
    goods_evaluate:'',
    //订单id
    order_id:'',
    //商品评价内容
    context:'',
    //店铺名称
    shop_name:'',
    good_list:'',
    order_goods_id:'',
    //标识评价或再次评价（begin/again）
    sign:'',
    //提交按钮
    eva_btn_show:true,
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    const that = this;
    let sign = options.sign;
    console.log(sign);
    let order_info = JSON.parse(Base64.decode(options.order_info));
    that.data.order_id = order_info.shop.order_id;
    that.data.shop_name = order_info.shop.shop_name;
    let good_list = order_info.shop.goods;
    for(let value of good_list){
      value.type = 5;
      // value.imgUrl = [];
      value.uploadImg = [];
      value.context = '';
    }
    that.setData({
      shop_name: order_info.shop.shop_name,
      good_list: good_list,
      sign: sign
    })

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
  
  //获取图片
  getImagesFun:function(e){
    const that = this;
    wx.chooseImage({
      count:5,
      sizeType: ['original', 'compressed'],
      sourceType: ['album', 'camera'],
      success: function(res) {

        for (let path of res.tempFilePaths){          
          wx.uploadFile({
            url: api.get_uploadImage,
            filePath: path,
            name: 'file',
            header: {
              'Content-Type': 'multipart/form-data',
              'X-Requested-With': 'XMLHttpRequest',
              'user-token': wx.getStorageSync('user_token'),
            },
            formData:{
              "type":'evaluate'
            },
            success:(res) =>{
              let image_data = res.data;
              let image_src = JSON.parse(image_data);
              let order_good_id = e.currentTarget.dataset.ordergoodid;
              let good_list = that.data.good_list;
              for (let value of good_list) {
                if (value.order_goods_id == order_good_id) {
                  if (value.uploadImg.length < 5) {
                    let img_obj = {
                      id: value.uploadImg.length+1,
                      src: image_src.data.src
                    }
                    value.uploadImg.push(img_obj);
                  }else{
                    wx.showModal({
                      title: '提示',
                      content: '最多可选5张照片',
                      showCancel:false,
                    })
                  }
                }
              }

              that.setData({
                good_list: good_list
              })              
            }
          })
        }
        
      },
    })
  },

  //全屏预览图片
  previewImage: function (e) {
    let img_src = e.currentTarget.dataset.imgsrc;
    let img_list = [];
    img_list.push(img_src);
    wx.previewImage({
      urls: img_list,
    })
  },

  deleteImg:function(e){
    const that = this;
    let order_good_id = e.currentTarget.dataset.ordergoodid;
    let img_id = e.currentTarget.dataset.imgid;
    let good_list = that.data.good_list;
    wx.showModal({
      title: '提示',
      content: '请确认是否删除图片',
      success(res){
        if(res.confirm){
          for (let item of good_list) {
            if (order_good_id == item.order_goods_id) {
              for (let i = 0; i < item.uploadImg.length; i++) {
                if (img_id == item.uploadImg[i].id){
                  item.uploadImg.splice(i, 1);
                  break
                }                
              }
            }
          }
          that.setData({
            good_list: good_list
          })
        }
      }
    })
    
  },
  

  //评价类型（好，中，差）
  typeSelectFun:function(e){
    const that = this;
    let type = e.currentTarget.dataset.type;
    let order_good_id = e.currentTarget.dataset.ordergoodid;
    let good_list = that.data.good_list;
    for(let value of good_list){
      if (value.order_goods_id == order_good_id){
        value.type = type
      }
    }

    that.setData({
      good_list: good_list
    })
  },

  //描述评分
  onDescChange:function(e){
    this.data.shop_desc = e.detail   
  },
  //服务评分
  onServiceChange: function (e) {
    this.data.shop_service = e.detail
  },
  //物流评分
  onSticChange: function (e) {
    this.data.shop_stic = e.detail
  },
  //商品评价
  goodSContextFun:function(e){
    const that = this;
    let context = e.detail.value;
    let order_good_id = e.currentTarget.dataset.ordergoodid;
    let good_list = that.data.good_list;
    for (let value of good_list) {
      if (value.order_goods_id == order_good_id) {
        value.context = context
      }
    }

    that.setData({
      good_list: good_list
    })

  },

  

  

  //提交订单评论
  addOrderEvaluate:function(){
    const that = this;
    let goods_evaluate = [];
    that.setData({
      eva_btn_show:false,
    })
    for(let item of that.data.good_list){
      let images = [];
      for (let value of item.uploadImg){
        images.push(value.src)
      }
      let itemObj = {
        order_goods_id: item.order_goods_id,
        content: item.context,
        images: images,
        explain_type:item.type
      }
      goods_evaluate.push(itemObj);
    }

    let postData = {
      'order_id': that.data.order_id,
      'goods_evaluate': goods_evaluate,
    }
  
    if(that.data.sign == 'begin'){
      postData['shop_desc'] = that.data.shop_desc;
      postData['shop_service'] = that.data.shop_service;
      postData['shop_stic'] = that.data.shop_stic;
    }

    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    
    let url = ''
    if(that.data.sign == 'begin'){
      url = api.get_addOrderEvaluate 
    }else if(that.data.sign == 'again'){
      url = api.get_addOrderEvaluateAgain 
    }
    
    wx.request({
      url: url,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code > 0) {          
          // let onPageData = {
          //   url: '../detail/index',
          //   num: 4,
          //   param: '?orderId=' + that.data.order_id,
          // }
          // util.jumpPage(onPageData);

          let onPageData = {
            url: 1,
            num: 5,
            param: '',
          }
          util.jumpPage(onPageData);
        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
          that.setData({
            eva_btn_show: true,
          })
        }
      },
      fail: (res) => { },
    }) 
  }
 
})