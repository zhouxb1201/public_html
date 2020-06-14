let TIM = require("../common/tim-wx.js");
let COS = require("../common/cos-wx-sdk-v5.js");
let genTestUserSig = require("../common/GenerateTestUserSig.js");
var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var header = getApp().header;

let tim = '';

Page({
  data: {
    //SD（标清）, HD（高清）, FHD（超清）, RTC（实时通话）
    mode: 'SD',
    //画面方向
    orientation: 'vertical',
    //是否静音
    muted: false,
    //进入后台时是否静音
    backgroundMute: false,
    //填充模式，可选值有 contain，fillCrop
    objectFit: 'contain',
    playUrl: '',
    headerHeight: getApp().globalData.headerHeight,
    statusBarHeight: getApp().globalData.statusBarHeight,
    videoContext: {},
    //输入框显示
    inputShow: false,
    message_list: [{
      name: '公告',
      txt: '购买直播产品请确认您拍下的购买链接与实际商品是否一致，切勿相信专属优惠链接，谨防上当受骗'
    }],
    // input 框的输入内容
    inputModel: '',
    // cover-view 显示的 input 的输入内容,初始值充当placeholder作用
    inputInfo: '',
    //直播间人数
    memberNum: 0,
    //直播间聊天群id
    groupID: '',
    liver_name: '主播名称',
    user_headimg: '',
    //上层是否显示
    upperShow: false,
    liveGoodShow: false,
    addGoodShow: false,
    add_page_index: 1,
    add_search_text: '',
    //推荐商品列表
    rem_goods_list: '',
    //直播间id
    live_id: '',
    anchor_id: '',
    //是否关注0-没有，1-有
    follow_sign: 0,
    //点赞
    like_sign: 0,
    //点赞总数
    like_num: 0,

  },
  onLoad(options) {

    // 扫码进来
    if (options.scene != undefined) {
      let scene = options.scene;
      let scene_arr = scene.split('_');
      if (scene_arr[0] != -1) {
        wx.setStorageSync('higherExtendCode', scene_arr[0]);
      }
      this.data.live_id = scene_arr[1];
      this.data.anchor_id = scene_arr[2];
    }

    if (options.live_id != undefined) {
      this.data.live_id = options.live_id;
    }
    if (options.anchor_id != undefined) {
      this.data.anchor_id = options.anchor_id;
    }

    this.getLiveUrl();

  },
  onReady(res) {

  },
  onShow() {    
    this.getAnchorInfo();
    this.createContext();
    this.getAnchorLiveGoodsList();    
    this.getLiveCountOnlinePeople();
    // 保持屏幕常亮
    wx.setKeepScreenOn({
      keepScreenOn: true,
    })
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
    this.stopPlayer();
    clearInterval(this.data.setInter)
    wx.setKeepScreenOn({
      keepScreenOn: false,
    })
  },
  statechange(e) {
    console.log('live-player code:', e.detail.code)
    let code = e.detail.code.toString()    
    if (e.detail.code == -2301) {
      this.stopPlayer();
      that.getPlatformLiveStatus();
      console.log('网络连接断开，且重新连接亦不能恢复，播放器已停止播放')          
    }
    if (e.detail.code == 2006){
      wx.showModal({
        title: '提示',
        content: '直播已结束',
        showCancel: false,
        success(res){
          if(res.confirm){
            wx.navigateBack({
              delta:1
            })
          }
        }
      })
    }
  },
  error(e) {
    console.error('live-player error:', e.detail.errMsg)

  },
  createContext: function() {
    this.setData({
      videoContext: wx.createLivePlayerContext("player")
    })
  },
  //播放
  bindPlay() {
    const that = this;
    that.data.videoContext.play({
      success: res => {
        console.log('播放成功')
      },
      fail: res => {
        console.log('播放失败，失败原因' + res.errMsg)
      }
    });
    // that.ctx.play({
    //   success: res => {
    //     console.log('播放成功')
    //   },
    //   fail: res => {
    //     console.log('播放失败，失败原因' + res.errMsg)
    //   }
    // })
  },

  stopPlayer() {
    const that = this;
    that.data.videoContext.stop();
  },



  inputShow() {
    const that = this;
    if (that.data.inputShow == false) {
      this.setData({
        inputShow: true,
        inputInfo: '',
      })
    } else {
      this.setData({
        inputShow: false,
        inputInfo: '',
      })
    }
  },


  // 将焦点给到 input（在真机上不能获取input焦点）
  tapInput() {
    this.setData({
      //在真机上将焦点给input
      inputFocus: true,
    });
  },

  // input 失去焦点后将 input 的输入内容给到cover-view
  blurInput(e) {
    this.setData({
      inputInfo: e.detail.value
    });
  },

  // 直播商品展示
  liveGoodShow() {
    const that = this;
    if (that.data.liveGoodShow == false) {
      this.setData({
        liveGoodShow: true,
        upperShow: true,
      })
    } else {
      this.setData({
        liveGoodShow: false,
        upperShow: false,
      })
    }
  },



  inChat() {
    const that = this;

    let options = {
      SDKAppID: that.data.sdkAppid // 接入时需要将 0 替换为您的云通信应用的 SDKAppID ‘1400329035’
    };
    // 创建 SDK 实例，`TIM.create()`方法对于同一个 `SDKAppID` 只会返回同一份实例
    let tims = TIM.create(options); // SDK 实例通常用 tim 表示
    tim = tims

    // 设置 SDK 日志输出级别，详细分级请参见 setLogLevel 接口的说明
    tim.setLogLevel(0); // 普通级别，日志量较多，接入时建议使用
    // tim.setLogLevel(1); // release级别，SDK 输出关键信息，生产环境时建议使用

    // 注册 COS SDK 插件
    tim.registerPlugin({
      'cos-wx-sdk': COS
    });

    // 接下来可以通过 tim 进行事件绑定和构建 IM 应用
    // 监听事件，如：
    // let userSig = genTestUserSig('u2').userSig;
    let promise = tim.login({
      userID: that.data.uname,
      userSig: that.data.userSig
    });
    promise.then(function(imResponse) {
      console.log('登录成功')
      console.log(imResponse.data); // 登录成功
      if (imResponse.data.repeatLogin === true) {
        // 标识账号已登录，本次登录操作为重复登录。v2.5.1 起支持
        console.log(imResponse.data.errorInfo);
        that.joinGroup();
      }
    }).catch(function(imError) {
      console.warn('login error:', imError); // 登录失败的相关信息
    });


    let onSdkReady = function() {
      //加入群
      that.joinGroup();

      // 打开某个会话时，第一次拉取消息列表
      let getMessageList = tim.getMessageList({
        conversationID: 'GROUP' + that.data.groupID,
        count: 15
      });
      getMessageList.then(function(imResponse) {
        const messageList = imResponse.data.messageList; // 消息列表。
        const nextReqMessageID = imResponse.data.nextReqMessageID; // 用于续拉，分页续拉时需传入该字段。
        const isCompleted = imResponse.data.isCompleted; // 表示是否已经拉完所有消息。
        console.log('拉取消息列表')
        console.log(imResponse.data)
      });

    }
    tim.on(TIM.EVENT.SDK_READY, onSdkReady);



    //接受消息的接口
    let onMessageReceived = function(event) {
      // event.data - 存储 Message 对象的数组 - [Message]
      console.log(event.data)
      that.setMessageRead();
      let message_type = '';
      let message_list = that.data.message_list
      for (let item of event.data) {
        if (item.conversationType == "GROUP") {
          if (item.from == '@TIM#SYSTEM') {
            //有成员加群
            if (item.payload.operationType == 1) {
              let user_item = {
                name: '欢迎' + '：',
                txt: item.payload.operatorID + '进入直播间'
              }
              message_list.push(user_item)
              that.setData({
                memberNum: item.payload.memberNum
              })
              //有群成员退群
            } else if (item.payload.operationType == 2) {
              that.setData({
                memberNum: item.payload.memberNum
              })
            }

          } else {
            let user_item = {
              name: item.from + '：',
              txt: item.payload.text
            }
            message_list.push(user_item)
          }
        }

      }
      that.setData({
        message_list: message_list
      })

      // wx.createSelectorQuery().select('#aaa').boundingClientRect(function (rect) {
      //   console.log(rect)
      //   that.setData({
      //     scrollTop: rect.height
      //   })
      // }).exec()

    };
    tim.on(TIM.EVENT.MESSAGE_RECEIVED, onMessageReceived);

  },

  //加入群
  joinGroup() {
    const that = this;
    let joinGroup = tim.joinGroup({
      groupID: that.data.groupID,
      type: TIM.TYPES.GRP_AVCHATROOM
    });
    joinGroup.then(function(imResponse) {
      switch (imResponse.data.status) {
        case TIM.TYPES.JOIN_STATUS_WAIT_APPROVAL:
          break; // 等待管理员同意
        case TIM.TYPES.JOIN_STATUS_SUCCESS: // 加群成功
          console.log('加群成功')
          console.log(imResponse.data.group); // 加入的群组资料
          break;
        default:
          break;
      }
    }).catch(function(imError) {
      console.warn('joinGroup error:', imError); // 申请加群失败的相关信息
    });
  },


  // 将某会话下所有未读消息已读上报
  setMessageRead() {
    const that = this;
    let setMessageRead = tim.setMessageRead({
      conversationID: 'GROUP' + that.data.groupID
    });
    setMessageRead.then(function(imResponse) {
      // 已读上报成功
      console.log('已读上报成功')
      console.log(imResponse)
    }).catch(function(imError) {
      // 已读上报失败
      console.warn('setMessageRead error:', imError);
    });
  },


  //发送消息
  sendMessage() {
    const that = this;
    if (that.data.inputInfo == '') {
      wx.showToast({
        title: '消息内容不能为空',
        icon: 'none',
      })
      return;
    }
    let message = tim.createTextMessage({
      to: that.data.groupID,
      conversationType: TIM.TYPES.CONV_GROUP,
      payload: {
        text: that.data.inputInfo
      }
    });

    that.inputShow();

    // 2. 发送消息
    let sendMessage = tim.sendMessage(message);
    sendMessage.then(function(imResponse) {
      // 发送成功
      console.log('消息发送成功')
      console.log(imResponse);
      that.userTxtInMessage(imResponse.data.message);
      that.setMessageRead();
    }).catch(function(imError) {
      // 发送失败
      console.warn('sendMessage error:', imError);
    });
  },

  //把发送的消息加入列表
  userTxtInMessage: function(message) {
    const that = this;
    let message_list = that.data.message_list;
    let user_item = {
      name: message.from + '：',
      txt: message.payload.text
    }
    message_list.push(user_item);
    that.setData({
      message_list: message_list
    })
  },

  //退群
  quitGroup() {
    const that = this;
    let quitGroup = tim.quitGroup(that.data.groupID);
    quitGroup.then(function(imResponse) {
      console.log('退群成功')
      console.log(imResponse); // 退出成功的群 ID
    }).catch(function(imError) {
      console.warn('quitGroup error:', imError); // 退出群组失败的相关信息
    });
  },

  //获取推拉流地址
  getLiveUrl: function() {
    const that = this;
    wx.showLoading({
      title: '加载....',
    })
    let postData = {
      'live_id': that.data.live_id
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getLiveUrl,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        wx.hideLoading();
        if (res.data.code == 1) {
          that.setData({
            playUrl: res.data.data.play_url
          })
          that.bindPlay();
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

  //获取主播信息
  getAnchorInfo: function() {
    const that = this;
    let postData = {
      'anchor_id': that.data.anchor_id
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getAnchorInfo,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 1) {
          that.setData({
            liver_name: res.data.data.uname,
            user_headimg: res.data.data.user_headimg,
            uid: res.data.data.uid,
            groupID: res.data.data.group_id
          })
          that.getUserSign();
          that.addWatchHistory();
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

  //添加观看历史
  addWatchHistory: function () {
    const that = this;
    let postData = {
      'live_id': that.data.live_id
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_addWatchHistory,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 1) {
         
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

  //获取im即时通信需要的UserSig
  getUserSign: function() {
    const that = this;
    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getUserSign,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 1) {
          that.data.userSig = res.data.data.userSig
          that.data.uname = res.data.data.uname;
          that.data.sdkAppid = res.data.data.sdkAppid
          that.inChat();
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

  //获取直播推荐的商品列表
  getAnchorLiveGoodsList: function() {
    const that = this;
    let postData = {
      'anchor_id': that.data.anchor_id
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getAnchorLiveGoodsList,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 1) {
          let live_goods_list = res.data.data.live_goods_list;
          let rem_goods_list = [];
          for (let item of live_goods_list) {
            if (item.is_recommend == 1) {
              rem_goods_list.push(item);
            }
          }
          that.setData({
            live_goods_list: live_goods_list,
            rem_goods_list: rem_goods_list,
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

  // 点击商品列表
  coverGoods: function(e){
    wx.navigateTo({
      url:'../../../../pages/goods/detail/index?goodsId='+e.currentTarget.dataset.goods_id
    })
  },

  // 点击推荐商品
  recommendGoods:function(e){
    console.log(e);
    console.log(this.data.rem_goods_list);
    
    wx.navigateTo({
      url:'../../../../pages/goods/detail/index?goodsId='+e.currentTarget.dataset.goods_id
    })
  },

  //获取直播间当前状态
  getPlatformLiveStatus: function () {
    const that = this;
    let postData = {
      anchor_id: that.data.anchor_id
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getPlatformLiveStatus,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 1) {
          let live_status = res.data.data.live_status;
          that.liveStatus(live_status);
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

  //直播状态处理
  liveStatus(live_status) {
    let txt = '';
    switch (live_status) {
      case 0:
        txt = '直播间已被禁播';
        break;
      case -1:
        txt = '直播间发现异常';
        break;
      case 4:
        txt = '直播间已下播！';
        break;
    }
    wx.showModal({
      title: '提示',
      content: txt,
      showCancel: false,
      success(res) {
        if (res.confirm) {
          wx.navigateBack({
            delta: 1
          })
        }
      }
    })
  },

  //添加或取消关注
  focus: function() {
    const that = this;
    let postData = {
      'follow_uid': that.data.uid
    }
    let url = ''
    let follow_sign = that.data.follow_sign;

    if (follow_sign == 1) {
      url = api.get_cancleFocus;
    } else {
      url = api.get_addFocus;
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: url,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 1) {
          if (follow_sign == 1) {
            that.setData({
              follow_sign: 0
            })
          } else {
            that.setData({
              follow_sign: 1
            })
          }

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

  //点赞
  likeStart(e) {
    const that = this;
    let like_sign = e.currentTarget.dataset.likesign;
    let like_num = that.data.like_num;
    like_num++
    that.setData({
      like_sign: 1,
      like_num: like_num
    })   
  },

  //点赞
  likeEnd(e) {
    const that = this;
    let like_sign = e.currentTarget.dataset.likesign;
    that.setData({
      like_sign: 0
    })
  },

  //添加点赞的数量给后台
  addLikes: function () {
    const that = this;
    if (that.data.like_num == 0){
      return
    }
    let postData = {
      'live_id': that.data.live_id,
      'likes_num': that.data.like_num,
    }  
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_addLikes,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 1) {          

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

  //举报
  onReportPage(){
    wx.navigateTo({
      url: '../report/detail/index?anchor_id=' + this.data.anchor_id,
    })
  },


  backPage() {
    this.quitGroup();
    this.addLikes()
    wx.navigateBack({
      delta: 1
    })

  },

  getLiveCountOnlinePeople(){
    const that = this;
    that.data.setInter = setInterval(
      function () {
        
        let postData = {
          'live_id': that.data.live_id,
          'online_num':that.data.memberNum
        }
        let datainfo = requestSign.requestSign(postData);
        header.sign = datainfo
        wx.request({
          url: api.get_liveCountOnlinePeople,
          data: postData,
          header: header,
          method: 'POST',
          dataType: 'json',
          responseType: 'text',
          success: (res) => {
            console.log(222);
            
            if (res.data.code >= 0) {
              
            } else {
              wx.showToast({
                title: res.data.message,
                icon: 'none'
              })
            }
          },
          fail: (res) => {},
        })
      }
, 1000);  

  }





})