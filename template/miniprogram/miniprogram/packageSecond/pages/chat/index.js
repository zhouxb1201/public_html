const io = require('../../../common/socket-io/weapp.socket.io.js')
// var requestSign = require('../../../utils/requestData.js');
var api = require('../../../utils/api.js').open_api;
var util = require('../../../utils/util.js');
var md5 = require('../../../utils/md5.js');
var header = getApp().header;

// 访客基础信息
let customer = {
  uid: getApp().globalData.uid,
  name: getApp().globalData.username,
  avatar: getApp().globalData.member_img,
  seller: wx.getStorageSync('seller_code'),
  regTime: getApp().globalData.regTime,
  goods: '',
  kefuCode: '',
  tk: '',
  t: ''
};
// 重连客服计时句柄
let reConnectInterval = 0;
// 重新接入计时句柄
let reInInterval = 0;

//创建audio控件
const myaudio = wx.createInnerAudioContext();

//index.js
//获取应用实例
const app = getApp()

/**
 * 生成一条聊天室的消息的唯一 ID
 */
function msgUuid() {
  if (!msgUuid.next) {
    msgUuid.next = 0
  }
  return 'msg-' + ++msgUuid.next
}

/**
 * 生成聊天室的系统消息
 */
function createSystemMessage(content) {
  return {
    id: msgUuid(),
    type: 'system',
    content
  }
}

/**
 * 生成聊天室的聊天消息
 */
function createUserMessage(content, user, isMe, sign, avatar, msg_id) {
  const color = '#6666';
  let logo = '';
  if (avatar == undefined) {
    logo = 'https://wx.qlogo.cn/mmopen/vi_32/DMz9ic0fTeUPnGS0Kevar9Ann8iccIqlHq0JTjOcb35aa7Rq0MadQqKH4NI1qOU8F6ZsBJeWeicdcjW5gmF5DeURg/132';
  } else {
    logo = avatar;
  }
  return {
    id: msgUuid(),
    type: 'speak',
    content,
    user,
    isMe,
    color,
    logo,
    sign,
    msg_id,
    read_status: 0,
  }
}

var COLORS = [
  '#e21400',
  '#91580f',
  '#f8a700',
  '#f78b00',
  '#58dc00',
  '#287b00',
  '#a8f07a',
  '#4ae8c4',
  '#3b88eb',
  '#3824aa',
  '#a700ff',
  '#d300e7',
]

// Gets the color of a username through our hash function
function getUsernameColor(username) {
  // Compute hash code
  var hash = 7
  for (var i = 0; i < username.length; i++) {
    hash = username.charCodeAt(i) + (hash << 5) - hash
  }
  // Calculate color
  var index = Math.abs(hash % COLORS.length)
  return COLORS[index]
}

Page({
  data: {
    inputContent: '',
    messages: [],
    lastMessageId: 'none',
    // 服务的客服标识
    kefuCode: 0,
    // 服务的客服名称
    kefuName: '',
    //客服域名
    domain: wx.getStorageSync('domain'),
    //客服端口
    port: wx.getStorageSync('port'),
    //商户标识
    seller_code: wx.getStorageSync('seller_code'),
    messagesLogs: '',
    page: 1,
    goods: '',
    facesIconArray: ["[微笑]", "[嘻嘻]", "[哈哈]", "[可爱]", "[可怜]", "[挖鼻]", "[吃惊]", "[害羞]", "[挤眼]", "[闭嘴]", "[鄙视]",
      "[爱你]", "[泪]", "[偷笑]", "[亲亲]", "[生病]", "[太开心]", "[白眼]", "[右哼哼]", "[左哼哼]", "[嘘]", "[衰]",
      "[委屈]", "[吐]", "[哈欠]", "[抱抱]", "[怒]", "[疑问]", "[馋嘴]", "[拜拜]", "[思考]", "[汗]", "[困]", "[睡]",
      "[钱]", "[失望]", "[酷]", "[色]", "[哼]", "[鼓掌]", "[晕]", "[悲伤]", "[抓狂]", "[黑线]", "[阴险]", "[怒骂]",
      "[互粉]", "[心]", "[伤心]", "[猪头]", "[熊猫]", "[兔子]", "[ok]", "[耶]", "[good]", "[NO]", "[赞]", "[来]",
      "[弱]", "[草泥马]", "[神马]", "[囧]", "[浮云]", "[给力]", "[围观]", "[威武]", "[奥特曼]", "[礼物]", "[钟]",
      "[话筒]", "[蜡烛]", "[蛋糕]"
    ],
    faceShow: false,
    say_bt_text: '按住 说话',
    voiceShow: false,
    //播放的音频id
    audio_id: '',
    optionShow: false,
    microphoneShow: false,
    cancelShow: false,
    //滚动条的位置
    scrollTop: 0,
    //判断机型是否全屏
    isFullSucreen: getApp().globalData.isFullSucreen,
    noReadIds: [],
    //是否展示商品
    isGoodShow: 1,
  },

  onLoad: function(options) {
    const that = this;
    if (options.goods != undefined) {
      that.setData({
        goods: JSON.parse(options.goods)
      })
      customer.goods = options.goods
    }
    if (options.kefuCode != undefined) {
      customer.kefuCode = options.kefuCode;
    }
    this.verifyInfoFun();



  },

  /**
   * 页面渲染完成后，启动聊天室
   * */
  onReady() {

    if (this.data.goods.shop_name) {
      wx.setNavigationBarTitle({
        title: this.data.goods.shop_name
      })
    } else {
      wx.setNavigationBarTitle({
        title: '客服为你服务'
      })
    }
    if (!this.pageReady) {
      this.pageReady = true
      this.enter()
    }
  },

  /**
   * 后续后台切换回前台的时候，也要重新启动聊天室
   */
  onShow() {
    if (this.pageReady && !this.socket) {
      this.enter()
    }

  },

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function() {
    this.data.page += 1
    this.getChatLog();
    wx.stopPullDownRefresh();
  },

  onUnload() {
    this.quit()
  },

  quit() {
    if (this.socket) {
      this.socket.close()
      this.socket = null
    }

    if (this.osocket) {
      this.osocket.close()
      this.osocket = null
    }
  },

  /**
   * 启动聊天室
   */
  enter() {
    console.log(customer)
    this.pushMessage(createSystemMessage('正在登录...'))
    // 如果登录过，会记录当前用户在 this.me 上
    if (!this.me) {
      // wx.getUserInfo({
      //   success: res => {
      //     this.me = res.userInfo
      //     this.createConnect()
      //   },
      // })      
      this.createConnect()
    } else {
      this.createConnect()
    }
  },

  socketLogin: function() {
    const params = {
      data: {
        customer_id: customer.uid,
        customer_name: customer.name,
        customer_avatar: customer.avatar,
      }
    }
    const that = this;
    this.socket.emit("userIn", JSON.stringify(params), function(res) {
      const {
        code,
        data,
        msg
      } = JSON.parse(res)
      switch (code) {
        case 0:
          console.log('客服接入成功', msg)
          break;
        case 400:
          console.log('接入失败，请重新接入', msg)
          break;
      }
    });

  },

  /**
   * 通用更新当前消息集合的方法
   */
  updateMessages(updater) {
    var messages = this.data.messages
    updater(messages)

    this.setData({
      messages
    })

    // 需要先更新 messagess 数据后再设置滚动位置，否则不能生效
    var lastMessageId = messages.length ?
      messages[messages.length - 1].id :
      'none'
    this.setData({
      lastMessageId
    })
  },

  /**
   * 追加一条消息
   */
  pushMessage(message) {
    this.updateMessages(messages => messages.push(message))
  },

  /**
   * 替换上一条消息
   */
  amendMessage(message) {
    this.updateMessages(messages => messages.splice(-1, 1, message))
  },

  /**
   * 删除上一条消息
   */
  popMessage() {
    this.updateMessages(messages => messages.pop())
  },

  changeInputContent: function(e) {
    this.setData({
      inputContent: e.detail.value,
    })
  },

  inputMessage: function(e) {
    let message = e.detail.value;
    this.sendMessage(message)
    this.setData({
      faceShow: false,
      optionShow: false,
      voiceShow: false,
    })
  },

  sendMessage: function(message, sign) {
    let msg = message
    let msg_id;
    const that = this;
    if (!msg) {
      return
    }
    // this.socket.emit('chatMessage', msg)
    this.socket.emit('chatMessage', JSON.stringify({
      from_name: customer.name,
      from_avatar: customer.avatar,
      from_id: customer.uid,
      to_id: that.data.kefuCode,
      to_name: that.data.kefuName,
      content: msg,
      seller_code: customer.seller
    }), function(data) {
      data = JSON.parse(data)
      msg_id = data.data;

      let avatar = customer.avatar;
      if (sign == 'img') {
        let img_data = {
          content: msg
        };
        img_data = that.replaceContent(img_data);
        let img_src = img_data.content;
        msg = img_src;
      }

      if (sign == 'faces') {
        let faces_data = {
          content: msg
        }
        faces_data = that.replaceContent(faces_data);
        msg = faces_data.content;
      }

      if (sign == 'goods') {
        let goods_data = {
          content: msg
        }
        goods_data = that.replaceContent(goods_data);
        msg = goods_data.goods;
      }

      if (sign == 'audio') {
        let audio_data = {
          content: msg
        }
        audio_data = that.replaceContent(audio_data);
        audio_data.content = audio_data.content
        msg = audio_data;
      }

      that.pushMessage(createUserMessage(msg, customer.name, 'me', sign, avatar, msg_id))
    })


    this.setData({
      inputContent: null
    })
  },

  createConnect: function(e) {
    const that = this;
    this.amendMessage(createSystemMessage('正在加入客服...'))
    const socket = (this.socket = io(
      that.data.domain, {
        path: '/port'
      }
    ))


    /**
     * Aboud connection
     */
    socket.on('connect', () => {
      const that = this;
      this.popMessage()
      // this.pushMessage(createSystemMessage('链接成功'))
      console.log('链接成功')
      //this.socketLogin();
      this.tryReIn();
      if (customer.kefuCode != '') {
        that.tryToKfuConnect()
      } else {
        that.tryToConnect();
      }

      // 收聊天消息     
      socket.on('chatMessage', function(data) {
        let name = data.data.from_name;
        let avatar = data.data.from_avatar;
        data = that.replaceContent(data.data);
        let msg = '';
        if (data.sign == 'audio') {
          msg = data
        } else {
          msg = data.content
        }
        let sign;
        if (data.sign) {
          sign = data.sign;
        }
        let msg_id = data.log_id;
        that.alreadyReadMessage(msg_id);
        that.pushMessage(createUserMessage(msg, name, 'other', sign, avatar, msg_id))
      })

      // 常见问题
      socket.on("comQuestion", function(data) {
        console.log(data);
      });

      // 处理转接
      socket.on("relink", function(data) {
        console.log(data);
      });

      socket.on('readMessage', function(data) {
        that.hasReadMessage(data);
      });

    })

    socket.on('connect_error', d => {
      this.amendMessage(createSystemMessage(`connect_error: ${d}`))
    })

    socket.on('connect_timeout', d => {
      this.pushMessage(createSystemMessage(`connect_timeout: ${d}`))
    })

    socket.on('disconnect', reason => {
      this.pushMessage(createSystemMessage(`disconnect: ${reason}`))
    })

    socket.on('reconnect', attemptNumber => {
      this.pushMessage(
        createSystemMessage(`reconnect success: ${attemptNumber}`),
      )
    })

    socket.on('reconnect_failed', () => {
      this.pushMessage(createSystemMessage('reconnect_failed'))
    })

    socket.on('reconnect_attempt', () => {
      this.amendMessage(createSystemMessage('正在尝试重连'))
    })

    socket.on('error', err => {
      this.pushMessage(createSystemMessage(`error: ${err}`))
    })



  },

  // 尝试接入
  tryReIn: function() {
    const that = this;
    console.log(customer)
    this.socket.emit("customerIn", JSON.stringify({
      data: {
        customer_id: customer.uid,
        customer_name: customer.name,
        customer_avatar: customer.avatar,
        seller_code: that.data.seller_code,
        tk: customer.tk,
        t: customer.t
      }

    }), function(data) {
      data = JSON.parse(data)
      that.amendMessage(createSystemMessage(data.msg))
    })
  },

  // 尝试连接客服
  tryToConnect: function() {
    let code = wx.getStorageSync('seller_code');
    let customers = customer;
    const that = this;
    this.socket.emit("userInit", JSON.stringify(customers), function(data) {
      data = JSON.parse(data)
      that.amendMessage(createSystemMessage(data.msg));
      let str = data.msg;
      if (data.code == 400) {
        that.amendMessage(createSystemMessage(str))
        clearInterval(reConnectInterval);
        reConnectInterval = setInterval(function() {
          that.tryToConnect();
        }, 2000);
      } else if (data.code == 0) {
        that.data.kefuCode = data.data.kefu_code;
        that.data.kefuName = data.data.kefu_name;
        if (that.data.goods.shop_name) {
          wx.setNavigationBarTitle({
            title: that.data.goods.shop_name
          })
        } else {
          wx.setNavigationBarTitle({
            title: data.data.kefu_name + '客服为你服务'
          })
        }

        that.getChatLog();
        clearInterval(reConnectInterval);
      } else if (data.code == 203) {
        that.amendMessage(createSystemMessage(str));
        that.data.kefuCode = data.data.kefu_code;
        that.data.kefuName = data.data.kefu_name;
        clearInterval(reConnectInterval);
      }
    })
  },

  //连接指定客服
  tryToKfuConnect: function() {
    const that = this;
    customer.seller = wx.getStorageSync('seller_code')
    let customers = customer;
    this.socket.emit("userConnect", JSON.stringify(customer), function(data) {
      data = JSON.parse(data)
      that.amendMessage(createSystemMessage(data.msg));
      if (0 == data.code) {
        clearInterval(reConnectInterval);
        that.data.kefuCode = data.data.kefu_code;
        that.data.kefuName = data.data.kefu_name;
        if (that.data.goods.shop_name) {
          wx.setNavigationBarTitle({
            title: that.data.goods.shop_name
          })
        } else {
          wx.setNavigationBarTitle({
            title: data.data.kefu_name + '客服为你服务'
          })
        }
        that.getChatLog();
      } else if (500 == data.code) {
        console.log(data.msg);
        clearInterval(reConnectInterval);
      }
    });
  },

  //转义聊天内容
  replaceContent: function(data) {
    const that = this;
    var content = data.content;
    let faces_array = [];
    content = content.replace(/\s+/g, '');
    content = (content || '').replace(/&(?!#?[a-zA-Z0-9]+;)/g, '&amp;')
      .replace(/img\[([^\s]+?)\]/g, function(img) { // 转义图片
        let img_src = that.data.domain + '' + img.replace(/(^img\[)|(\]$)/g, '') + '';
        data.content = img_src
        data.sign = 'img'
        return data;
      })
      .replace(/face\[([^\s\[\]]+?)\]/g, function(face) { // 转义表情
        var alt = face.replace(/^face/g, '');
        let alt_src = that.data.domain + '' + that.faces([alt]) + '';
        faces_array.push(alt_src)
        data.content = faces_array;
        data.sign = 'faces'
        return data;
      })
      .replace(/file\([\s\S]+?\)\[[\s\S]*?\]/g, function(str) { // 转义文件
        var href = (str.match(/file\(([\s\S]+?)\)\[/) || [])[1];
        var text = (str.match(/\)\[([\s\S]*?)\]/) || [])[1];
        if (!href) return str;
        data.content = text
        data.sign = 'file'
        return data;
      })
      .replace(/goodsid\[([^\s]+?)\]/g, function(goodsid) { //转义商品id
        let goods_id = goodsid.replace(/(^goodsid\[)|(\]$)/g, '');

        data.goods = {
          goods_id: goods_id
        }
        data.sign = 'goods';
        return data;
      })
      .replace(/goodspic\[([^\s]+?)\]/g, function(goodspic) { //转义商品图片
        let goods_pic = goodspic.replace(/(^goodspic\[)|(\]$)/g, '')
        data.goods.goods_pic = goods_pic;
        data.sign = 'goods';
        return data;
      })
      .replace(/goodsurl\[([^\s]+?)\]/g, function(goodsurl) { //转义商品url
        let goods_url = goodsurl.replace(/(^goodsurl\[)|(\]$)/g, '');
        data.goods.goods_url = goods_url;
        data.sign = 'goods';
        return data;
      })
      .replace(/goodsname\[([^\s]+?)\]/g, function(goodsname) { //转义商品名称
        let goods_name = goodsname.replace(/(^goodsname\[)|(\]$)/g, '');
        data.goods.goods_name = goods_name;
        data.sign = 'goods';
        return data;
      })
      .replace(/goodsprice\[([^\s]+?)\]/g, function(goodsprice) { //转义商品价格
        let goods_price = goodsprice.replace(/(^goodsprice\[)|(\]$)/g, '');
        data.goods.goods_price = goods_price;
        data.sign = 'goods';
        return data;
      })
      .replace(/audio\[([^\s]+?)\]/g, function(audio) { //转义音频
        let audio_src = audio.replace(/(^audio\[)|(\]$)/g, '');
        data.content = that.data.domain + audio_src;
        data.bl = false;
        data.time = '30';
        data.sign = 'audio';
        return data;
      })
    return data;
  },

  // 表情对应数组
  getFacesIcon: function() {

    return this.data.facesIconArray
  },
  // 表情替换
  faces: function(altString) {
    var alt = this.getFacesIcon(),
      arr = {};
    for (let i = 0; i < alt.length; i++) {
      if (alt[i] == altString) {
        arr = '/static/common/images/face/' + i + '.gif';
      }
    }
    return arr;
  },


  //客服连接验证
  verifyInfoFun: function() {
    const that = this;
    let seller;
    if (that.data.seller_code == '') {
      seller = wx.getStorageSync('seller_code');
    } else {
      seller = that.data.seller_code
    }
    let postData = {
      seller_code: seller,
      domain: getApp().globalData.domain_wap
    }

    let datainfo = that.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: that.data.domain + '/customerapi/index/verifyInfo',
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        wx.hideLoading();
        if (res.data.code == 0) {
          customer.t = res.data.data.time;
          customer.tk = res.data.data.token;
          customer.seller = res.data.data.seller
        } else {
          that.pushMessage(createSystemMessage(res.data.msg))
          // that.amendMessage(createSystemMessage(res.data.msg))
        }
      },
      fail: (res) => {},
    })
  },

  //-----------封装签名请求--------
  requestSign: function(data) {

    var signString = '';

    for (var i in data) {
      signString = signString + i;
    }

    let key = '02cff04ecdcf64bb';

    let sdata = md5.md5(key + signString)

    return sdata
  },

  //选择照片
  chooseImageFun: function(e) {
    const that = this;
    let mode = e.currentTarget.dataset.mode;
    let sourceType;
    if (mode == 'image') {
      sourceType = ['album']
    } else {
      sourceType = ['camera']
    }
    wx.chooseImage({
      count: 1,
      sizeType: ['original', 'compressed'],
      sourceType: sourceType,
      success(res) {
        // tempFilePath可以作为img标签的src属性显示图片
        const tempFilePaths = res.tempFilePaths

        for (let path of res.tempFilePaths) {
          wx.uploadFile({
            url: that.data.domain + '/index/upload/uploadImg',
            filePath: path,
            name: 'file',
            header: {
              'Content-Type': 'multipart/form-data',
              'X-Requested-With': 'XMLHttpRequest',
              'user-token': wx.getStorageSync('user_token'),
            },
            formData: {
              "type": 'evaluate'
            },
            success: (res) => {
              let srcData = JSON.parse(res.data)
              let src = srcData.data.src
              that.sendMessage('img[' + src + ']', 'img');
              that.setData({
                optionShow: false
              })
            }
          })
        }

      }
    })
  },

  //功能展现
  optionFun: function() {
    if (this.data.optionShow == false) {
      this.setData({
        optionShow: true,
        faceShow: false,
        voiceShow: false,
      })
    } else {
      this.setData({
        optionShow: false
      })
    }
  },

  //聊天记录
  getChatLog: function() {
    const that = this;
    let postData = {
      uid: customer.uid,
      page: that.data.page,
      t: customer.t,
      tk: customer.tk,
      seller: customer.seller,
      kefu_code: customer.kefuCode,
    }

    let datainfo = that.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: that.data.domain + '/customerapi/index/getChatLog',
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 0) {
          let messagesLogs = res.data.data;
          for (let item of messagesLogs) {
            item = that.replaceContent(item)
          }
          if (that.data.page > 1) {
            let oldmessage = that.data.messagesLogs;
            let newmessage = oldmessage.push(messagesLogs)
            that.setData({
              messagesLogs: newmessage
            })
          } else {
            that.setData({
              messagesLogs: messagesLogs,
              scrollTop: 999,
            })
            let msg_id = []
            for (let i = 0; i < messagesLogs.length; i++) {
              if (messagesLogs[i].type == 'user') {
                if (messagesLogs[i].read_status == 0) {
                  msg_id.push(messagesLogs[i].log_id);
                }
              }
            }
            that.alreadyReadMessage(msg_id);
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

  //发送商品信息
  sandGoodsMessage: function() {
    const that = this;
    let goodsid = that.data.goods.goods_id;
    let goodspic = that.data.goods.pic_cover;
    let goodsname = that.data.goods.goods_name;
    let price = that.data.goods.price;
    let goodsurl = that.data.domain + '/goods/goodsinfo?goodsid=' + goodsid;
    that.setData({
      isGoodShow: 0,
    })
    that.sendMessage('goodsid[' + goodsid + ']goodsurl[' + goodsurl + ']goodspic[' + goodspic + ']goodsname[' + goodsname + ']goodsprice[' + price + ']', 'goods');
  },

  //emoji表情信息
  faceMessage: function(e) {
    const that = this;
    let face = e.currentTarget.dataset.facesicon;
    face = 'face' + face + '';
    let input = that.data.inputContent;
    if (input == null) {
      input = face
    } else {
      input += face
    }

    that.setData({
      inputContent: input
    })

  },
  //发送表情
  sandFacesMessage: function() {
    this.sendMessage(this.data.inputContent, 'faces');
    this.setData({
      faceShow: false
    })
  },
  //表情框的显示和隐藏
  facesListFun: function() {
    if (this.data.faceShow == false) {
      this.setData({
        faceShow: true,
        optionShow: false,
        voiceShow: false,
      })
    } else {
      this.setData({
        faceShow: false
      })
    }
  },



  sayPassword: function(e) {
    var timestart = e.timeStamp
    let startNum = e.touches[0].clientY - 200;
    this.setData({
      startNum: startNum
    })
    var recorderManager = wx.getRecorderManager();
    const options = {
      duration: 60000,
      sampleRate: 16000,
      numberOfChannels: 1,
      encodeBitRate: 96000,
      format: 'mp3',
      frameSize: 50
    }
    recorderManager.start(options)
    this.setData({
      timestart: timestart,
      say_bt_text: '松开 结束',
      microphoneShow: true,
      cancelShow: false,
    })
  },

  //取消录音
  sayStop: function(e) {
    const that = this;
    let touches = e.touches;
    let endNum = touches[0].clientY;
    if (endNum < that.data.startNum) {
      this.setData({
        say_bt_text: '按住 说话',
        cancelShow: true,
      })
      console.log('取消录音')
    } else {
      this.setData({
        say_bt_text: '松开 结束',
        cancelShow: false,
      })
    }

  },


  toServer: function(e) {
    const that = this;
    var recorderManager = wx.getRecorderManager(); //获取全局唯一的录音管理器
    var timestart = this.data.timestart;
    var timeout = e.timeStamp;
    var timeIng = 0; //录音的时长
    timeIng = timeout - timestart;

    recorderManager.stop();
    recorderManager.onError((res) => {
      that.amendMessage(createSystemMessage('录音失败'));
      console.log('小伙砸你录音失败了！')
    })

    this.setData({
      say_bt_text: '按住 说话',
      microphoneShow: false,
    })

    recorderManager.onStop((res) => {
      var tempFilePath = res.tempFilePath; // 文件临时路径
      var temp = tempFilePath.replace('.mp3', '')
      if (that.data.cancelShow == true) {
        that.setData({
          cancelShow: false,
        })
      } else {
        wx.uploadFile({
          url: that.data.domain + '/index/upload/uploadVoice',
          filePath: tempFilePath,
          name: 'file',
          header: {
            'Content-Type': 'multipart/form-data',
            'X-Requested-With': 'XMLHttpRequest',
            'user-token': wx.getStorageSync('user_token'),
          },
          formData: {
            "type": 'evaluate'
          },
          success: (res) => {
            let srcData = JSON.parse(res.data)
            let src = srcData.data.src
            that.sendMessage('audio[' + src + ']', 'audio');
          }
        })
        console.log('劳资获取到文件了，开始上传')
      }

    })



  },

  //语音切换
  changeVoice: function() {
    if (this.data.voiceShow == false) {
      var recorderManager = wx.getRecorderManager();
      this.setData({
        voiceShow: true,
        faceShow: false,
        optionShow: false,
      })
    } else {
      this.setData({
        voiceShow: false
      })
    }

  },

  //音频播放 
  audioPlay: function(e) {
    var that = this,
      id = e.currentTarget.dataset.id,
      audioSign = e.currentTarget.dataset.audiosign,
      vidSrc = e.currentTarget.dataset.vidsrc;
    myaudio.src = vidSrc;
    myaudio.autoplay = true;
    that.data.audio_id = id;
    myaudio.stop();
    if (audioSign == 'audiologs') {
      let logs = that.data.messagesLogs;
      //切换显示状态
      for (var i = 0; i < logs.length; i++) {
        logs[i].bl = false;
        if (logs[i].log_id == id) {
          logs[i].bl = true;
        }
      }

      myaudio.play();

      //开始监听
      myaudio.onPlay(() => {
        that.setData({
          messagesLogs: logs
        })
      })

      //结束监听
      myaudio.onEnded(() => {
        for (var i = 0; i < logs.length; i++) {
          if (logs[i].log_id == id) {
            logs[i].bl = false;
          }
        }
        that.setData({
          messagesLogs: logs,
        })
      })
    } else {
      let list = that.data.messages;
      //切换显示状态
      for (var i = 0; i < list.length; i++) {
        if (list[i].sign == 'audio') {
          list[i].content.bl = false;
          if (list[i].id == id) {
            list[i].bl = true;
          }
        }

      }

      myaudio.play();

      //开始监听
      myaudio.onPlay(() => {
        that.setData({
          messages: list
        })
      })
      //结束监听
      myaudio.onEnded(() => {
        for (var i = 0; i < list.length; i++) {
          if (list[i].sign == 'audio') {
            if (list[i].id == id) {
              list[i].content.bl = false;
            }
          }
        }
        that.setData({
          messages: list
        })
      })


    }




  },

  // 音频停止
  audioStop: function(e) {
    var that = this;
    let audioSign = e.currentTarget.dataset.audiosign;
    if (audioSign == 'audiologs') {
      let logs = that.data.messagesLogs;
      //切换显示状态
      for (var i = 0; i < logs.length; i++) {
        logs[i].bl = false;
      }

      myaudio.stop();
      //停止监听
      myaudio.onStop(() => {
        for (var i = 0; i < logs.length; i++) {
          logs[i].bl = false;
        }
        that.setData({
          messagesLogs: logs,
        })
      })
      //结束监听
      myaudio.onEnded(() => {
        for (var i = 0; i < logs.length; i++) {
          logs[i].bl = false;
        }
        that.setData({
          messagesLogs: logs,
        })
      })
    } else {
      let list = that.data.messages;
      //切换显示状态
      for (var i = 0; i < list.length; i++) {
        if (list[i].sign == 'audio') {
          list[i].content.bl = false;
        }
      }

      myaudio.stop();
      //停止监听
      myaudio.onStop(() => {
        for (var i = 0; i < list.length; i++) {
          if (list[i].sign == 'audio') {
            list[i].content.bl = false;
          }
        }
        that.setData({
          messages: list,
        })
      })

      //结束监听
      myaudio.onEnded(() => {
        for (var i = 0; i < list.length; i++) {
          if (list[i].sign == 'audio') {
            list[i].content.bl = false;
          }
        }
        that.setData({
          messages: list,
        })
      })

    }

  },

  //跳转到商品详情
  onGoodsPage: function(e) {
    let goodsId = e.currentTarget.dataset.goodsid;
    wx.navigateTo({
      url: '/pages/goods/detail/index?goodsId=' + goodsId,
    })
  },

  //处理已读未读
  hasReadMessage: function(data) {
    const that = this;
    let result = data.mid.split(',');
    let messages = that.data.messages;
    let messagesLogs = that.data.messagesLogs;
    for (let i = 0; i < result.length; i++) {
      for (let v = 0; v < messages.length; v++) {
        if (messages[v].type == 'speak') {
          if (result[i] == messages[v].msg_id) {
            messages[v].read_status = 1;
          }
        }
      }

      for (let m = 0; m < messagesLogs.length; m++) {
        if (result[i] == messagesLogs[m].log_id) {
          messagesLogs[m].read_status = 1;
        }
      }
    }

    that.setData({
      messages: messages,
      messagesLogs: messagesLogs
    })
  },

  //把已读的数据发回给客服
  alreadyReadMessage: function(msg_id) {
    const that = this;
    let noReadIds;
    if (typeof msg_id == 'string') {
      noReadIds = [];
      noReadIds.push(msg_id);
    } else {
      noReadIds = msg_id;
    }
    this.socket.emit('readMessage', JSON.stringify({
      uid: customer.kefuCode,
      mid: noReadIds.join(',')
    }), function(data) {
      var data = JSON.parse(data);
      let messages = that.data.messages;
      for (let v = 0; v < messages.length; v++) {
        if (messages[v].type == 'speak') {
          if (msg_id == messages[v].msg_id) {
            messages[v].read_status = 1;
          }
        }
      }
      that.setData({
        messages: messages
      })
    })
  },



})