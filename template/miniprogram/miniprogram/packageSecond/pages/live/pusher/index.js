let TIM = require("../common/tim-wx.js");
let COS = require("../common/cos-wx-sdk-v5.js");
// let genTestUserSig = require("../common/GenerateTestUserSig.js");
var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var header = getApp().header;

let tim = '';

// let options = {
//   SDKAppID:''  // 接入时需要将 0 替换为您的云通信应用的 SDKAppID ‘1400329035’
// };
// // 创建 SDK 实例，`TIM.create()`方法对于同一个 `SDKAppID` 只会返回同一份实例
// let tim = TIM.create(options); // SDK 实例通常用 tim 表示

// // 设置 SDK 日志输出级别，详细分级请参见 setLogLevel 接口的说明
// tim.setLogLevel(0); // 普通级别，日志量较多，接入时建议使用
// // tim.setLogLevel(1); // release级别，SDK 输出关键信息，生产环境时建议使用

// // 注册 COS SDK 插件
// tim.registerPlugin({
//   'cos-wx-sdk': COS
// });

Page({

  data: {
    //美颜，取值范围 0-9 ，0 表示关闭
    beauty: 6.3,
    //SD（标清）, HD（高清）, FHD（超清）, RTC（实时通话）
    mode: 'SD',
    //画面方向
    orientation: 'vertical',
    //美白，取值范围 0-9 ，0 表示关闭
    whiteness: 3.0,
    //是否静音
    muted: false,
    //进入后台时推流的等待画面
    waitingImg: 'https://mc.qcloudimg.com/static/img/daeed8616ac5df256c0591c22a65c4d3/pause_publish.jpg',
    //进入后台时是否静音
    backgroundMute: false,
    //开启摄像头
    enableCamera: true,
    pushUrl: '',
    cameraContext: {},
    headerHeight: getApp().globalData.headerHeight,
    statusBarHeight: getApp().globalData.statusBarHeight,
    //输入框显示
    inputShow: false,
    //功能显示
    toolShow: false,
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
    live_id:'',
    anchor_id:'',
    //观看人总数
    watch_num:0,
    //商品点击总次数
    goods_click_num:0,
    //分享人数
    share_num:0,
    //弹幕聊天总数
    chat_num:0,
    //点赞总数
    like_num:0,
    //直播结束时间
    end_time:0,
    //下播标识
    end_live_sign:false,

  },

  onLoad(res) {    
    this.data.live_id = res.live_id;
    this.data.anchor_id = res.anchor_id;    
    this.getLiveUrl();    
  },

  onReady(res) {

  },
  onShow() {        
    this.getAnchorInfo();
    //调用推流摄像头
    this.createContext();       
    this.getAnchorLiveGoodsList();
  },

  /**
  * 生命周期函数--监听页面隐藏
  */
  onHide: function () {
    console.log("onLoad onHide");
    this.saveDisconnectTime();
  },
  
  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function () {
    console.log("onLoad onUnload");
    const that = this;
    //如果主播没有主动下播，页面卸载时要被动下播
    if (that.data.end_live_sign == false){
      that.passiveEndLive();
    }
    
  },

  onShareAppMessage:function(res){
    const that = this;
    let path_url = '';
    console.log('转发');
      path_url = "/packageSecond/pages/live/player/index?live_id=" + that.data.live_id + "&anchor_id=" + that.data.anchor_id;  
    return {      
      path: path_url
    }
  },


  //状态变化事件
  statechange(e) {
    const that = this;
    console.log('live-pusher code:', e.detail.code)    
    if (e.detail.code == -1307){
      that.getPlatformLiveStatus();
    }   
  },
  bindStart() {
    const that = this;
    that.data.cameraContext.start({
      success: res => {
        console.log('start success')
      },
      fail: res => {
        console.log('start fail')
      }
    });    
  },
  createContext: function () {
    var that = this;
    that.setData({
      cameraContext: wx.createLivePusherContext('pusher'),
    })
  },
  
  stopPusher() {
    const that = this;
    that.data.cameraContext.stop({
      success: res => {
        console.log('stop success')
      },
      fail: res => {
        console.log('stop fail')
      }
    });
    console.log('停止直播')
  },
 


  inputShow() {
    const that = this;
    if (that.data.inputShow == false) {
      this.setData({
        inputShow: true,
        inputInfo: ''
      })
    } else {
      this.setData({
        inputShow: false,
        inputInfo: ''
      })
    }
  },

  //功能显示
  toolShow() {
    const that = this;
    if (that.data.toolShow == false) {
      this.setData({
        toolShow: true,
        upperShow: true,
      })
    } else {
      this.setData({
        toolShow: false,
        upperShow: false,
      })
    }
  },

  //翻转
  bindSwitchCamera(){
    this.data.cameraContext.switchCamera();
  },
  //切换手电筒
  bindToggleTorch() {
    this.data.cameraContext.toggleTorch();
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

  addGoodShow() {
    const that = this;    
    if (that.data.addGoodShow == false) {
      this.setData({
        addGoodShow: true,
      })
      that.getAnchorGoodsForAdd();
    } else {
      wx.showModal({
        title: '提示',
        content: '确认退出添加商品',
        success(res) {
          if (res.confirm) {
            that.setData({
              addGoodShow: false,
            })
          } else if (res.cancel) {
            console.log('用户点击取消')
          }
        }
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
            pushUrl: res.data.data.push_url
          })
          that.bindStart();
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
            groupID:res.data.data.group_id
          })
          that.getUserSign();
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
          that.data.sdkAppid = res.data.data.sdkAppid;          
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
  liveStatus(live_status){
    let txt = '';
    switch (live_status){
      case 0:
        txt = '您的直播间已被禁播，请和客服联系！';
        break;
      case -1:
        txt = '您的直播间发现异常，请和客服联系！';
        break;
      case 4:
        txt = '直播间已下播！';
        break;
    }
    wx.showModal({
      title: '提示',
      content: txt,
      showCancel:false,
      success(res){
        if(res.confirm){
          wx.navigateBack({
            delta:1
          })
        }
      }
    })
  },



  inChat() {
    const that = this;

    let options = {
      SDKAppID: that.data.sdkAppid  // 接入时需要将 0 替换为您的云通信应用的 SDKAppID ‘1400329035’
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

    // let userSig = genTestUserSig('u4').userSig;
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
        if (that.data.groupID == '') {
          that.createGroup();
        } else {
          that.joinGroup();
        }
      }     
    }).catch(function(imError) {
      console.warn('login error:', imError); // 登录失败的相关信息
    });

    let onSdkReady = function() {
      console.log(that.data.groupID)
      if (that.data.groupID == ''){
        that.createGroup();
      }else{
        that.joinGroup();      
      }

      

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
      let message_type = '';
      let message_list = that.data.message_list
      for (let item of event.data) {
        if (item.conversationType == "GROUP") {
          if (item.from == '@TIM#SYSTEM') {
            //有成员加群
            if (item.payload.operationType == 1) {
              if (that.data.uname == item.payload.operatorID){
                let user_item = {
                  name: '欢迎' + '：',
                  txt: '主播 ' +item.payload.operatorID
                } 
                message_list.push(user_item)
              } else{
                let user_item = {
                  name: '欢迎' + '：',
                  txt: item.payload.operatorID + '进入直播间'
                }        
                message_list.push(user_item)        
              }     
              
              that.setData({
                memberNum: item.payload.memberNum - 1
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

    };
    tim.on(TIM.EVENT.MESSAGE_RECEIVED, onMessageReceived);

  },

  //创建群
  createGroup() {
    const that = this;
    let createGroup = tim.createGroup({
      type: TIM.TYPES.GRP_AVCHATROOM,
      name: that.data.live_id.toString(),
      memberList: [{
        userID: that.data.uname
      }] // 如果填写了 memberList，则必须填写 userID
    });
    createGroup.then(function(imResponse) { // 创建成功
      console.log('创建群成功')
      console.log(imResponse.data.group); // 创建的群的资料
      that.data.groupID = imResponse.data.group.groupID;
      that.setData({
        groupID:imResponse.data.group.groupID
      })
      that.saveImGroupId();
      that.joinGroup();
    }).catch(function(imError) {
      console.log('创建群失败');
      console.warn('createGroup error:', imError); // 创建群组失败的相关信息
    });
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

  //设置群成员的禁言时间，可以禁言群成员，也可取消禁言
  setGroupMemberMuteTime(e){
    const that = this;
    let uid = e.currentTarget.dataset.uid;
    let setGroupMemberMuteTime = tim.setGroupMemberMuteTime({
      groupID: that.data.groupID,
      userID: uid,
      muteTime: 600 ,// 禁言10分钟；设为0，则表示取消禁言
    });
    setGroupMemberMuteTime.then(function (imResponse) {
      console.log(imResponse.data.group); // 修改后的群资料
      console.log(imResponse.data.member); // 修改后的群成员资料
      console.log(imResponse.data);
    }).catch(function (imError) {
      console.warn('setGroupMemberMuteTime error:', imError); // 禁言失败的相关信息
    });
  },

  //解散群组
  dismissGroup(){
    const that = this;
    let dismissGroup = tim.dismissGroup(that.data.groupID);
    dismissGroup.then(function (imResponse) { // 解散成功
      console.log('解散群成功')
      console.log(imResponse.data.groupID); // 被解散的群组 ID
    }).catch(function (imError) {
      console.warn('dismissGroup error:', imError); // 解散群组失败的相关信息
    });
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
          for (let item of live_goods_list){
            if (item.is_recommend == 1 && rem_goods_list.length < 2){
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


  //获取用于添加的商品
  getAnchorGoodsForAdd: function() {
    const that = this;
    let postData = {
      'page_index': that.data.add_page_index,
      'anchor_id': that.data.anchor_id,
      'search_text': that.data.add_search_text,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_getAnchorGoodsForAdd,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 1) {
          let anchor_goods_list = res.data.data.anchor_goods_list;
          that.setData({
            anchor_goods_list: anchor_goods_list
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


  //添加商品
  actAnchorAddGoods: function(e) {
    const that = this;
    let goods_id = e.currentTarget.dataset.goodsid;
    let isadd = e.currentTarget.dataset.isadd;
    let is_add = '';
    if (isadd == 0) {
      is_add = 1
    } else {
      is_add = 0
    }
    let postData = {
      'goods_id': goods_id,
      'anchor_id': that.data.anchor_id,
      'is_add': is_add,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_actAnchorAddGoods,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 1) {
          let anchor_goods_list = that.data.anchor_goods_list;
          for (let i = 0; i < anchor_goods_list.length;i++){
            if (anchor_goods_list[i].goods_id == goods_id){
              anchor_goods_list[i].is_add = is_add;              
            }
          }
          that.setData({
            anchor_goods_list: anchor_goods_list
          })
          that.getAnchorLiveGoodsList();
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

  //设置主推商品
  recommendLiveGoods: function(e) {
    const that = this;
    let goods_id = e.currentTarget.dataset.goodsid;
    let isrecommend = e.currentTarget.dataset.isrecommend;
    let is_recommend = '';
    if (isrecommend == 0) {
      is_recommend = 1
    } else {
      is_recommend = 0
    }
    let postData = {
      'goods_id': goods_id,
      'anchor_id': that.data.anchor_id,
      'is_recommend': is_recommend,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_recommendLiveGoods,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 1) {          
          that.getAnchorLiveGoodsList()
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


  //保存IM群组的id
  saveImGroupId: function () {
    const that = this;   
    let postData = {
      'group_id': that.data.groupID,
      'anchor_id': that.data.anchor_id,      
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_saveImGroupId,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        // wx.showToast({
        //   title: '群id:' + that.data.groupID+'已保存',
        // })
      },
      fail: (res) => { },
    })
  },

  //主动退出直播
  endLive(){
    const that = this;
    wx.showModal({
      title: '提示',
      content: '请确认下播',
      success(res) {
        if (res.confirm) {
          that.data.end_live_sign = true;
          that.dismissGroup();
          that.actEndLive();
          that.stopPusher();          
        }
      }
    })
  },

  //被动退出直播
  passiveEndLive(){
    const that = this;
    that.dismissGroup();
    that.actEndLive();
    that.stopPusher();
  },


  //下播
  actEndLive: function () {
    const that = this;    
    let end_time = new Date().getTime();
    end_time = parseInt(end_time / 1000)
    let postData = {
      'live_id': that.data.live_id,
      'end_time': end_time,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_actEndLive,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if(res.data.code > 0){
          wx.navigateBack({
            delta: 2
          })
        }else{
          wx.showToast({
            title: res.data.message,
          })
        }
      },
      fail: (res) => { },
    })
  },

  //保存直播间“失去连接”的起始时间
  saveDisconnectTime: function () {
    const that = this;
    let end_time = new Date().getTime();
    end_time = parseInt(end_time / 1000)
    let postData = {
      'live_id': that.data.live_id,
      'disconnect_time': end_time,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_saveDisconnectTime,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code > 0) {
          
        } else {
          wx.showToast({
            title: res.data.message,
          })
        }
      },
      fail: (res) => { },
    })
  },







})