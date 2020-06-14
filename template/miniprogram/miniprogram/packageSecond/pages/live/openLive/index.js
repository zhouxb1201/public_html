var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var time = require('../../../../utils/time.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    //主播id
    anchor_id:'',
    //状态 0-待审核 1-直播预告 2-直播中
    status:"",
    //页面显示状态：1-待审核填写申请，2-已提交申请但还未审核，3-已提交申请但审核失败，4-开始直播或预告，5-已提交并通过但未到开播时间
    pageStatus:'',
    is_play_checked:'',
    tips_txt:'当前平台开启了直播审核，审核通过后才能开启直播',
    cate_list:'',
    //直播分类
    cate_id:'',
    dateShow:false,
    minDate: new Date().getTime(),
    maxDate: new Date(2030, 12, 30).getTime(),
    currentDate: new Date().getTime(),
    //时间弹框标识：0-开播时间，1-结束时间，2-预告时间
    time_sign:'',
    predict_start_time:'',
    predict_end_time:'',
    room_no:'',
    live_img:'',
    live_title:'',
    live_introduce:'',  
    //直播预告临界点时间用于判断
    advance_limit_time:'', 
    startShow:false,
    
    
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    let anchor_id = options.anchor_id;
    this.data.anchor_id = anchor_id;
    this.getPlayData();
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

  //获取开播页面数据（就是在申请主播第一个表单）
  getPlayData: function () {
    const that = this;
    let postData = {
      "anchor_id": that.data.anchor_id
    }    
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getPlayData,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code === 1) {
          let cate_list = res.data.data.cate_list;
          that.data.cate_list = cate_list;
          let cate_name = [];
          //分类名组成数组 
          for (let item of cate_list){
            cate_name.push(item.cate_name)
          } 
          if(res.data.data.is_play_checked==0){
            that.setData({
              pageStatus: 4,
              status: 2,                
              live_id: res.data.data.live_info.live_id,
              advance_limit_time: res.data.data.live_info.advance_limit_time,  
              startShow:true
            })       
            that.timeDown();  
          } else{
             //apply == 0 未申请过
          if (res.data.data.live_info.is_apply == 0){
            that.setData({
              pageStatus:1,
              status:0,
              is_play_checked: res.data.data.is_play_checked
            })
          //apply == 1 已申请过
          } else{
            //已申请但拒绝了
            if (res.data.data.live_info.status == -1){
              that.setData({
                pageStatus: 3,
                status: 0,
                txt: "您的申请不通过，原因：" + res.data.data.live_info.uncheck_reason
              })
            //已申请待审核
            } else if (res.data.data.live_info.status == 0){
              that.setData({
                pageStatus: 2,
                status: 0,
                txt:'您的申请已提交至平台审核，请耐心等待！'
              })
            //已申请,开始直播
            } else if (res.data.data.live_info.status == 1){
              that.setData({
                pageStatus: 4,
                status: 2,                
                live_id: res.data.data.live_info.live_id,
                advance_limit_time: res.data.data.live_info.advance_limit_time,  
              })       
              that.timeDown();       
            //已申请且通过但未到开播时间
            } else if (res.data.data.live_info.status == 3) {
              let start_time = time.js_date_time_second(res.data.data.live_info.predict_start_time);
              let end_time = time.js_date_time_second(res.data.data.live_info.predict_end_time);
              let before_play_min = res.data.data.live_info.before_play_min;
              that.moveUpTime(res.data.data.live_info.advance_limit_time);
              that.setData({
                pageStatus: 5,
                status: 1,
                txt: '您的直播申请已审核通过，' + start_time + '~' + end_time + "内开播无需再次审核，可提前" + before_play_min +"分钟开播。",
                advance_limit_time: res.data.data.live_info.advance_limit_time,
                live_img: res.data.data.live_info.live_img,
                live_title: res.data.data.live_info.live_title,
                live_id: res.data.data.live_info.live_id 
              })
              that.timeDown();   
            } else if (res.data.data.live_info.status == 2){
              that.data.live_id = res.data.data.live_info.live_id 
              that.onPusherPage()
            }         
          }
          } 
         

          that.setData({
            cate_name: cate_name,
            room_no: res.data.data.room_no,            
          })

        } else {
          wx.showModal({
            title: '提示',
            content: res.data.message,
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
      },
      fail: (res) => { },
    })
  },

  //直播分类选择
  bindCateNameChange(e){
    const that = this;
    let index = e.detail.value;
    let cate_list = that.data.cate_list;
    that.data.cate_id = cate_list[index].cate_id; 
    that.setData({
      index: index
    })   
  },

  //开播时间或结束时间
  changeTime(e){
    const that = this;
    this.setData({
      minDate: new Date().getTime(),
    currentDate: new Date().getTime()
    })
    let time_sign = e.currentTarget.dataset.timesign;
    that.data.time_sign = time_sign;    
    that.setData({
      dateShow:true,
    })
  },

  //时间选择
  onDateChange(event) {
    const that = this;
    let times = time.js_date_time_second(event.detail / 1000)
    
    if (that.data.time_sign == 0){
      that.data.predict_start_time = (event.detail / 1000).toString().substring(0, 10);
      that.setData({
        startTime: times
      })
    }
    if (that.data.time_sign == 1){
      that.data.predict_end_time = (event.detail / 1000).toString().substring(0, 10);
      that.setData({
        endTime: times
      })
    }
    if (that.data.time_sign == 2) {
      that.data.advance_time = (event.detail / 1000).toString().substring(0, 10);
      console.log(that.data.advance_time)
      let differ_time = that.data.advance_time - that.data.advance_limit_time;
      console.log(differ_time)
      if (differ_time > 0){
        wx.showToast({
          title: '预告时间不能大于后台设置的提前时间!',
          icon:'none',
        })
        that.setData({
          notice_time: '',
          advance_time:'',
        })
      }else{        
        that.setData({
          notice_time: times
        })
      }      
      
    }    
    that.onDateClose();
  },

  //时间弹框关闭
  onDateClose(){
    this.setData({
      dateShow: false,
    })
  },

  //开播倒计时
  timeDown(){
    const that = this;    
    let nowDate = new Date();
    nowDate = parseInt(nowDate.getTime() / 1000);    
    if (that.data.advance_limit_time <= nowDate){
      that.setData({
        startShow:true
      })
    }
  },

  //申请开播
  applyPlay: function () {
    const that = this;
    let postData = {
      "anchor_id": that.data.anchor_id,
      "status": that.data.status,
    }
    if (that.checkValue() == false){
      return
    }else{
      // 0-待审核
      if (that.data.status == 0){
        postData.predict_start_time = that.data.predict_start_time;
        postData.predict_end_time = that.data.predict_end_time;
        postData.cate_id = that.data.cate_id;

      // 2-开始直播
      } else if (that.data.status == 2){
        postData.cate_id = that.data.cate_id;
        postData.live_img = that.data.live_img;
        postData.live_title = that.data.live_title;
      // 1-直播预告
      } else if (that.data.status == 1){
        postData.cate_id = that.data.cate_id;
        postData.live_img = that.data.live_img;
        postData.live_title = that.data.live_title;
        postData.advance_time = that.data.advance_time;
        postData.live_introduce = that.data.live_introduce;
      }
    }
    
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_applyPlay,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code === 1) {
          if (that.data.is_play_checked == 0 && that.data.status == 0){
            that.getPlayData();
          }else{
            if(that.data.status == 2){
              that.onPusherPage();
            }else{
              that.setData({
                pageStatus: 2,
                txt: '您的申请已提交至平台审核，请耐心等待！',
              })
            }
            
          }         
          
        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none'
          })
        }
      },
      fail: (res) => { },
    })
  },

  //检查参数
  checkValue(){
    const that = this;
    let value = true

    if (that.data.cate_id == '') {
      wx.showToast({
        title: '请选择直播分类',
        icon: 'none'
      })
      return value = false
    }

    if(that.data.status == 0){      
      if (that.data.predict_start_time == '') {
        wx.showToast({
          title: '请选择开播时间',
          icon: 'none'
        })
        return value = false
      }
      if (that.data.predict_end_time == '') {
        wx.showToast({
          title: '请选择直播结束时间',
          icon: 'none'
        })
        return value = false
      }      
    } else if (that.data.status == 2){      
      if (that.data.live_img == '') {
        wx.showToast({
          title: '请上传直播封面',
          icon: 'none'
        })
        return value = false
      }
      if (that.data.live_title == '') {
        wx.showToast({
          title: '请填写直播标题',
          icon: 'none'
        })
        return value = false
      }
    }else if(that.data.status == 1){
      if (that.data.live_img == '') {
        wx.showToast({
          title: '请上传直播封面',
          icon: 'none'
        })
        return value = false
      }
      if (that.data.live_title == '') {
        wx.showToast({
          title: '请填写直播标题',
          icon: 'none'
        })
        return value = false
      }
      if (that.data.live_introduce == '') {
        wx.showToast({
          title: '请填写直播介绍',
          icon: 'none'
        })
        return value = false
      }
      if (that.data.advance_time == '') {
        wx.showToast({
          title: '请填写直播预告时间',
          icon: 'none'
        })
        return value = false
      }
    }
    
  },

  //前往直播
  goToLive(e){
    const that = this;
    //直播标识，开始直播（open_live）或开始预告（open_notice）
    let live_sign = e.currentTarget.dataset.livesign;
    if (live_sign == 'open_live'){
      that.data.status = 2
    }else{
      that.data.status = 1
    }
    this.setData({
      pageStatus:4,      
      live_sign: live_sign
    })
  },

  //重新申请
  resetApply(){
    this.setData({
      pageStatus:1,
      status:0,
    })
  },

  //审核成功后，可提前15分钟开播
  moveUpTime(startTime){
    const that = this;
    let nowDate = new Date();
    nowDate = parseInt(nowDate.getTime() / 1000);

    //算出中间差并且已秒数返回; ；
    var countDown = startTime - nowDate;    
    //时间差
    let time_c = parseInt(countDown / 60 % 60);
    console.log(time_c)
    //可提前分钟开播
    if(time_c <= 0){
      that.setData({
        openLiveShow:true
      })
    }else{
      that.setData({
        openLiveShow: false
      })
    }
  },

  //从手机获取图片
  getImagesFun: function () {
    const that = this;    
    wx.chooseImage({
      count: 1,
      sizeType: ['original', 'compressed'],
      sourceType: ['album', 'camera'],
      success: function (res) {
        let tempFilePaths = res.tempFilePaths
        that.uploadImg(tempFilePaths)
      },
    })
  },

  //上传图片到服务器
  uploadImg: function (tempFilePaths) {
    const that = this;
    let path = tempFilePaths[0];
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
        'type':'live'
      },
      success: (res) => {
        let image_data = res.data;
        let image_src = JSON.parse(image_data);
        that.setData({
          live_img: image_src.data.src
        })
      }
    })
  },

  //直播标题
  liveTitle(e){
    let live_title = e.detail.value;
    this.setData({
      live_title: live_title
    })
  },

  //直播介绍
  liveIntroduce(e){
    let live_introduce = e.detail.value;
    this.setData({
      live_introduce: live_introduce
    })
  },

  //跳到主播
  onPusherPage(){
    const that = this;
    wx.navigateTo({
      url: '../pusher/index?live_id=' + that.data.live_id + '&anchor_id=' + that.data.anchor_id,
    })
  },


})