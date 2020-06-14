var requestSign = require('../../../utils/requestData.js');
var api = require('../../../utils/api.js').open_api;
var re = require('../../../utils/request.js');
var util = require('../../../utils/util.js');
var WxParse = require('../../../common/wxParse/wxParse.js');
var time = require('../../../utils/time.js');
var Base64 = require('../../../utils/base64.js').Base64;
import {
  base64src
} from '../../../utils/base64src.js'
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    pageShow: false,

    //自定义模板数据
    temData: '',
    // 商品明细
    goodDetail: '',
    //当前商品名称
    goodName: '',
    //当前商品id
    goodsId: '',
    //图片地址
    imgList: [],
    //是否采用衔接滑动  
    circular: true,
    //是否显示画板指示点  
    indicatorDots: false,
    //选中点的颜色  
    indicatorcolor: "#000",
    //是否竖直  
    vertical: false,
    //是否自动切换  
    autoplay: true,
    //自动切换的间隔
    interval: 2500,
    //滑动动画时长毫秒  
    duration: 100,
    //所有图片的高度  
    imgheights: [],
    //图片宽度 
    imgwidth: 750,
    //默认  
    imgcurrent: 0,
    //购买数量
    buyNum: 1,
    currentnum: 0,
    //评价当前选择
    evaluatenum: 0,
    //评价数据
    evaluateData: '',
    //网站地址
    dataUrl: getApp().publicUrl,
    //规格选择框显示
    show: true,
    boolen: true,
    //模态框的状态  true-隐藏  false-显示
    hideModal: true,
    animationData: {}, //
    //商品sku
    sku: '',
    skuActive: 0,
    //商品价格
    goodPrice: '',
    //拼团价格
    groupPrice: '',
    groupStatus: '',
    //优惠券框显示
    couponShow: false,
    //sku弹出框
    skuShow: false,
    //优惠券数据
    couponData: '',
    like: 'like-o',
    activeIndex: '',
    //库存数量
    stockNum: '',
    //选择的规格
    specName: '',
    skuId: '',
    //请选择规格按钮
    skuBtnchoose: true,

    userToken: '',
    selectObj: {},
    mapObj: {},

    //预售活动 1：是  0:不是
    is_presell: '',
    // 预售数据
    presell_list: '',
    //预售规则显示
    presellRuleShow: false,
    //预售定金
    presell_first_money: '',
    // 预售总价
    presell_all_money: '',
    // 尾款
    tail_money: '',
    //预售id
    presell_id: '',


    //秒杀数据
    seckillList: '',
    //秒杀是否开启
    seckill_open: false,
    //秒杀id
    seckillId: '',
    //倒计时-天
    oDay: '00',
    //倒计时-时
    oHours: '00',
    //倒计时-分
    oMinutes: '00',
    //倒计时-秒
    oSeconds: '00',
    //价格框显示
    publicPriceShow: true,
    //秒杀框显示
    seckillShow: false,
    //秒杀状态
    seckillStatus: '',
    //秒杀时间
    seckillTime: '',
    //限购数量
    maxBuy: '',
    //砍价显示
    bargainShow: false,

    // 满减数据
    full_cut_list: '',
    //拼团状态
    groupStatus: '',
    //拼团是否开启
    group_open: false,
    //拼图id
    groupId: '',
    //拼团列表
    group_list: '',
    //成团列表 
    group_record_list: '',
    //团购记录id
    record_id: '',
    //拼单弹框
    groupCenterShow: false,
    //
    groupIndex: '',
    //拼团列表弹框
    groupListShow: false,
    sureBtn: '',
    //购买方式（单独购买、拼团购买）
    buyType: '',
    //商品的店铺id
    shopId: '',
    //商品数据
    goodsList: "",
    // 评价类型
    explain_type: '',
    // 评价是否有图片
    is_image: '',

    //分销商
    extend_code: '',
    //点击请选择规格，显示有购物车和立即购买按钮
    sku_btn: true,

    //积分
    give_point: '',

    qrCode: '',
    //退出页面时倒计时关闭
    endTimeShow: false,
    //平台系统是否有海报应用 0-没有 1-有
    config_poster: 0,
    //海报图片
    poster_img: "",
    //海报类型：0-默认海报，1-系统设置的海报
    posterImgType: 1,
    //返佣金
    commission: '',
    //返积分
    dis_point: '',
    //限时折扣
    limit_discount: '',
    //会员折扣
    member_discount: '',

    //微店id
    shopkeeper_id: '',
    //是否有浏览权限
    is_allow_browse: true,
    //是否有购买权限
    is_allow_buy: true,
    //是否显示客服
    kefuShow: false,
    //店铺应用是否开启
    addons_shop: 0,

    code_img:'',
  },

  //弹出层关闭
  onClose: function () {
    this.setData({
      show: false
    })
  },
  //优惠券弹出层开启
  couponShow: function () {
    const that = this;
    if (that.ifLogin() == false) {
      return
    };
    if (that.hasPhoneFun() == false) {
      return
    }
    that.setData({
      couponShow: true
    })
  },

  //优惠券弹出层关闭
  couponOnclose: function () {
    this.setData({
      couponShow: false
    })
  },

  //满减送弹出层开启
  fullCutShowOnShow: function () {
    this.setData({
      fullCutShow: true
    })
  },

  //满减送弹出层关闭
  fullCutShowOnclose: function () {
    this.setData({
      fullCutShow: false
    })
  },

  //sku弹出层开启
  skuShow: function () {
    this.setData({
      skuShow: true,
      sku_btn: false,
    })
  },
  //sku弹出层关闭
  skuOnclose: function () {
    this.setData({
      skuShow: false,
      sku_btn: false,
    })
  },
  //点击选择规格，出现加入购物车和立即购买
  skuBtnShow: function () {
    this.setData({
      skuShow: true,
      sku_btn: true,
    })
  },
  //拼单弹框关闭
  onGroupCenterClose: function () {
    this.setData({
      groupCenterShow: false
    })
  },
  //拼单弹框开启
  onGroupCenterShow: function (e) {
    let groupIndex = e.currentTarget.dataset.groupindex;
    this.setData({
      groupCenterShow: true,
      groupListShow: false,
      groupIndex: groupIndex
    })
  },
  //拼团列表弹框关闭
  onGroupListClose: function () {
    this.setData({
      groupListShow: false,
    })
  },
  //拼团列表弹框开启
  onGroupListShow: function () {
    this.setData({
      groupListShow: true,
    })
  },


  imageLoad: function (e) { //获取图片真实宽度  
    var imgwidth = e.detail.width,
      imgheight = e.detail.height,
      //宽高比  
      ratio = imgwidth / imgheight;
    console.log(imgwidth, imgheight)
    //计算的高度值  
    var viewHeight = 750 / ratio;
    var imgheight = viewHeight;
    var imgheights = this.data.imgheights;
    //把每一张图片的对应的高度记录到数组里  
    imgheights[e.target.dataset.id] = imgheight;
    this.setData({
      imgheights: imgheights
    })
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    var that = this;
    var goodsId = '';
    // 扫码进来
    if (options.scene != undefined) {
      console.log(options.scene)
      let scene = options.scene;
      let scene_arr = scene.split('_');
      if (scene_arr[0] != -1) {
        wx.setStorageSync('higherExtendCode', scene_arr[0]);
      }
      goodsId = scene_arr[1] //获取id值  
      wx.setStorageSync('posterId', scene_arr[2])//获取超级海报id
      wx.setStorageSync('posterType', scene_arr[3])//获取超级海报类型          
      const value = wx.getStorageSync('user_token')
      if (value) {
        console.log('已登录');
        util.checkReferee();
      } else {
        console.log('未登录')
        that.setData({
          loginShow: true,
        })
      }
    }

    // 页面跳转进来
    if (options.goodsId != undefined) {
      goodsId = options.goodsId //获取id值    
    }
    that.data.goodsId = goodsId;

    wx.showLoading({
      title: '加载中',
    })


    if (options.extend_code != undefined) {
      wx.setStorageSync('higherExtendCode', options.extend_code)
    }

    //从微店进来
    if (options.shopkeeper_id) {
      this.data.shopkeeper_id = options.shopkeeper_id;
    }

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
    var that = this;
    that.getGoodDetailData();
    that.setDistributionData();
    that.selectComponent('#getPoster').closePoste();

    that.getKindPoster();
    //that.extend_code();
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
    this.setData({
      endTimeShow: true
    })
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

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function (res) {
    let that = this;
    // 来自页面内转发按钮
    if (res.from === 'button') {
      console.log(res)
    }
    let path_url = "/pages/goods/detail/index?goodsId=" + that.data.goodsId;

    //const extend_code = wx.getStorageSync('extend_code')

    if (that.data.extend_code != '') {
      path_url = path_url + '&extend_code=' + that.data.extend_code;
      console.log(path_url);
    }

    return {
      title: this.data.goodName,
      path: path_url,
      success: (res) => {
        console.log('转发成功！');
      },
      fail: (res) => {
        console.log('转发失败！');
      }
    }
  },

  //商品图片加载出错，替换为默认图片
  imgError: function (e) {
    var errorImgIndex = e.target.dataset.id
    let imgList = this.data.imgList;
    imgList[errorImgIndex] = "/images/no-goods.png"
    this.setData({
      imgList: imgList
    })
  },



  /**
   * 获取自定义数据
   */
  getTemData: function () {
    const that = this;
    let postData = {
      "type": 3,
      'is_mini': 1
    }
    postData['shop_id'] = that.data.shopId;

    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    // 请求默认模板数据
    re.request(api.get_custom, postData, header).then((res) => {
      if (res.data.code >= 0) {
        //判断图片地址是本地图片还是网络图片
        let template_data = res.data.data.template_data
        for (var item in template_data.items) {
          let item_data = template_data.items[item].data;
          for (var index in item_data) {
            if (item_data[index].imgurl != undefined) {
              if (item_data[index].imgurl.substring(0, 1) == 'h') { } else {
                item_data[index].imgurl = that.data.dataUrl + item_data[index].imgurl
              }
            }
          }
        }
        that.setData({
          temData: template_data
        })

      } else {
        wx.showToast({
          title: res.data.message,
          icon: 'none'
        })
      }
    })
  },

  //请求商品明细数据
  getGoodDetailData: function () {
    const that = this;
    let postData = {
      "goods_id": that.data.goodsId
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    wx.request({
      url: api.get_goodsDetail,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        wx.hideLoading();

        if (res.data.code >= 0) {

          let is_allow_browse = res.data.data.is_allow_browse; //是否有权限浏览
          if (is_allow_browse == false) {
            that.setData({
              is_allow_browse: is_allow_browse
            })
            return
          }
          let is_allow_buy = res.data.data.is_allow_buy; //是否有权限购买
          if (is_allow_buy == false) {
            that.setData({
              is_allow_buy: is_allow_buy
            })
          }

          let exceed_max_buy = res.data.data.goods_detail.max_buy; //判断是否超过最大购买数量
          if (exceed_max_buy == -1) {
            that.setData({
              exceed_max_buy: exceed_max_buy
            })
          }


          let limit_discount = res.data.data.limit_discount //限时折扣率
          let member_discount = res.data.data.member_discount // 会员折扣率
          let min_price = res.data.data.goods_detail.min_price //商品最小价格
          let shop_id = res.data.data.goods_detail.shop_id //商品的店铺id
          let good_Price;

          let seckill_list = res.data.data.seckill_list; //秒杀
          let seckill_array = Object.keys(seckill_list); //判断对象是否为空，返回值是数组
          if (seckill_array.length != 0) { //判断秒杀是否有数据
            that.seckillData(seckill_list);
            that.data.seckill_open = true;
          }

          //价格（秒杀、拼团不参与任何折扣）
          if (member_discount != '' && member_discount < 1 && seckill_array.length == 0) {
            good_Price = min_price * member_discount;
            that.setData({
              discount_tip: true
            })
            if (limit_discount != '' && limit_discount < 1) {
              good_Price = good_Price * limit_discount;
            }
          } else if (limit_discount != '' && limit_discount < 1 && seckill_array.length == 0) {
            good_Price = min_price * limit_discount;
            that.setData({
              discount_tip: true
            })
          } else {
            good_Price = min_price;
          };

          //price_type 0-没有折扣， 1-会员折扣 ，2-限时折扣
          if (res.data.data.goods_detail.price_type != 0) {
            that.setData({
              discount_tip: true
            })
          }


          let couponData = ''; //优惠券数据
          if (res.data.data.coupon_type_list != []) {
            couponData = res.data.data.coupon_type_list;
            for (var i = 0; i < couponData.length; i++) {
              couponData[i].discount = parseInt(couponData[i].discount);
              couponData[i].end_time = time.js_date_time(couponData[i].end_time); //时间戳转日期
              couponData[i].start_time = time.js_date_time(couponData[i].start_time);
            }
          };

          let like = 'like-o'; //收藏
          if (res.data.data.goods_detail.is_collection == true) {
            like = 'like'
          }

          let sku = res.data.data.goods_detail.sku;
          for (var i = 0; i < sku.tree.length; i++) { //给sku得tree添加一个未选中的属性
            for (var e = 0; e < sku.tree[i].v.length; e++) {
              sku.tree[i].v[e].isSelect = "false";
            }
          }

          let group_list = res.data.data.group_list; //拼团
          //通过拼团列表是否有group_id这个属性，判断拼团是否开启
          if (group_list.hasOwnProperty('group_id')) {
            that.groupData(group_list, sku);
            that.data.group_open = true;
            //拼团开启时，请选择规格按钮隐藏
            that.setData({
              skuBtnchoose: false,
            })
          }


          let bargain_list = res.data.data.bargain_list //砍价
          let bargain_status = ''; //砍价状态
          if (bargain_list.hasOwnProperty('bargain_id')) { //通过砍价列表是否有bargain_id这个属性，判断砍价是否开启
            that.setData({
              bargainShow: true,
              bargain_list: bargain_list,
              publicPriceShow: false,
            })
            if (bargain_list.status == 1) {
              that.countDownTime(bargain_list.end_bargain_time); //砍价结束倒计时
            } else if (bargain_list.status == 0) {
              that.countDownTime(bargain_list.start_bargain_time); //砍价开始倒计时
            }
          }

          // 满减送数据(判断满减送是否开启)
          if (res.data.data.full_cut_list.length != 0) {
            that.fullCutDataFun(res.data.data.full_cut_list);
          }

          let stock_num = 0; //库存数量
          let maxbuy = 0;
          for (var n = 0; n < sku.list.length; n++) {
            if (res.data.data.is_presell == 1) { //预售已开启，库存数量使用预售设置的数量
              stock_num = stock_num + sku.list[n].presell_num;
            } else {
              stock_num = stock_num + sku.list[n].stock_num;
            }

            if (sku.list[n].hasOwnProperty('max_buy')) {
              maxbuy = sku.list[n].max_buy;
            }

          };          

          that.setData({
            goodDetail: res.data.data.goods_detail,
            couponData: couponData,
            imgList: res.data.data.goods_detail.goods_images,
            goodName: res.data.data.goods_detail.goods_name,
            sku: sku,
            goodPrice: good_Price,
            like: like,
            stockNum: stock_num,
            maxBuy: res.data.data.goods_detail.goods_type == 4 ? 1 : maxbuy,
            shopId: shop_id,
            is_presell: res.data.data.is_presell,
            give_point: res.data.data.give_point,
            commission: res.data.data.commission,
            dis_point: res.data.data.dis_point,
            limit_discount: limit_discount,
            member_discount: member_discount,            
            addons_shop: getApp().globalData.config.addons.shop,
          })


          wx.setNavigationBarTitle({
            title: res.data.data.goods_detail.goods_name,
          })

          // 预售开启
          if (res.data.data.is_presell == 1) {
            that.presellDataFun(res.data.data.presell_list);
          }

          var goodinfo = res.data.data.goods_detail.description;
          WxParse.wxParse('description', 'html', goodinfo, that);
          that.getTemData();

          //平台设置，是否有海报应用 0-没开启 1-开启
          let config_poster = getApp().globalData.config.addons.poster;
          that.setData({
            config_poster: config_poster
          })

          //获取客服信息
          that.qlkefuInfoFun();

          that.setData({
            pageShow: true
          })

        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none',
          })
        }
      },
      fail: (res) => { },
    })

  },

  //拼团数据
  groupData: function (group_list, sku) {
    const that = this;
    let group_record_list = group_list.group_record_list
    let group_status = ''; //拼团状态
    if (group_list.hasOwnProperty('group_id')) { //通过拼团列表是否有group_id这个属性，判断拼团是否开启
      group_status = 'groupStart';
      that.setData({
        groupPrice: sku.list[0].group_price,
        groupId: group_list.group_id,
        group_list: group_list,
        group_record_list: group_record_list,
        groupStatus: group_status,
      })
      if (group_record_list.length > 0) {
        for (let value of group_record_list) {
          that.groupTime(value.finish_time, value.record_id);
        }
      }
    }
  },

  //秒杀数据
  seckillData: function (seckill_list) {
    const that = this;
    let seckill_status = seckill_list.seckill_status //秒杀状态
    if (seckill_status == 'going') {
      that.countDownTime(seckill_list.end_time); //秒杀结束倒计时
    } else if (seckill_status == 'unstart') {
      that.countDownTime(seckill_list.start_time); //秒杀开始倒计时
    }

    if (seckill_list.seckill_day == 'today') {
      seckill_list.seckill_day = '今天'
    }
    if (seckill_list.seckill_day == 'tomorrow') {
      seckill_list.seckill_day = '明天'
    }
    //通过秒杀列表是否有seckill_id这个属性，判断秒杀是否开启
    if (seckill_list.seckill_id != null) {
      that.setData({
        publicPriceShow: false,
        seckillShow: true,
        seckillList: seckill_list,
        seckillStatus: seckill_status,
        seckillId: seckill_list.seckill_id,
        seckillTime: seckill_list.seckill_time,
      })
    } else {
      that.setData({
        seckillShow: false,
      })
    }
  },


  //满减送数据
  fullCutDataFun: function (full_cut_list) {
    const that = this;
    for (let value of full_cut_list) {
      value.start_time = time.js_date_time(value.start_time);
      value.end_time = time.js_date_time(value.end_time);
      let rules_array = [];
      if (value.rules.length != 0) {
        for (let i = 0; i < value.rules.length; i++) {
          if (value.rules[i].discount != '' && value.rules[i].discount != '0.00') {
            rules_array.push('满' + parseFloat(value.rules[i].price) + '减' + parseFloat(value.rules[i].discount) + '元');
          }
          if (value.rules[i].coupon_type_id != '') {
            rules_array.push('满' + parseFloat(value.rules[i].price) + '送优惠券（' + value.rules[i].coupon_type_name + ')');
          }
          if (value.rules[i].free_shipping == 1) {
            rules_array.push('满' + parseFloat(value.rules[i].price) + '包邮');
          }
          if (value.rules[i].gift_card_id != '') {
            rules_array.push('满' + parseFloat(value.rules[i].price) + '送礼品卷（' + value.rules[i].gift_voucher_name + ')');
          }
          if (value.rules[i].gift_id != '' && value.rules[i].gift_name != '') {
            rules_array.push('满' + parseFloat(value.rules[i].price) + '送赠品（' + value.rules[i].gift_name + ')');
          }
        }

        value.rules_array = rules_array;
      }

    };
    that.setData({
      full_cut_list: full_cut_list
    })
  },

  // 预售数据
  presellDataFun: function (presell_list) {
    const that = this;
    //presell_list.state 1 - 正在进行 2 - 未开始 3 - 结束了
    if (presell_list.state == 2) {
      that.countDownTime(presell_list.start_time);
    }
    if (presell_list.state == 1) {
      that.countDownTime(presell_list.end_time);
    }
    // 支付尾款结束时间
    presell_list.pay_end_time = time.js_date_time(presell_list.pay_end_time);
    // 支付尾款开始时间
    presell_list.pay_start_time = time.js_date_time(presell_list.pay_start_time);
    // 预售结束时间
    presell_list.end_time = time.js_date_time(presell_list.end_time);
    // 预售开始时间
    presell_list.start_time = time.js_date_time(presell_list.start_time);
    //送货时间
    presell_list.send_goods_time = time.js_date_time(presell_list.send_goods_time);
    //尾款
    let tail_money = parseFloat(presell_list.allmoney - presell_list.firstmoney).toFixed(2)
    presell_list.tail_money = tail_money;
    //预售定金
    let presell_first_money = presell_list.firstmoney
    // 预售总价
    let presell_all_money = presell_list.allmoney
    that.setData({
      presell_list: presell_list,
      presell_first_money: presell_first_money,
      presell_all_money: presell_all_money,
      tail_money: tail_money,
      presell_id: presell_list.presell_id
    })
  },

  //预售规则显示
  presellRuleShow: function () {
    const that = this;
    that.setData({
      presellRuleShow: true
    })
  },

  //预售规则关闭
  onPresellRuleClose: function () {
    const that = this;
    that.setData({
      presellRuleShow: false
    })
  },


  //商品详情的选项卡
  checkCurrent: function (e) {
    const that = this;
    that.setData({
      currentnum: e.detail.index
    });
    if (e.detail.index == 1 && that.data.goodDetail.goods_type == 4) { //知识付费的目录
      wx.showLoading({
        title: '加载中',
      })
      that.getCourseList();
    }
    if (e.detail.index == 2) {
      that.getGoodsReviewsList();
    }
  },
  //评价选项卡
  checkEvaluate: function (e) {
    const that = this;
    let current = e.currentTarget.dataset.current
    that.setData({
      evaluatenum: current
    });
    if (current == 0) {
      that.data.is_image = '';
      that.data.explain_type = '';
    } else if (current == 1) {
      that.data.is_image = true;
      that.data.explain_type = '';
    } else if (current == 2) {
      that.data.explain_type = 5
      that.data.is_image = '';
    } else if (current == 3) {
      that.data.explain_type = 3;
      that.data.is_image = '';
    } else if (current == 4) {
      that.data.explain_type = 1;
      that.data.is_image = '';
    }
    that.getGoodsReviewsList();

  },
  //请求评价数据  
  getGoodsReviewsList: function () {

    setTimeout(function () {
      wx.hideLoading();
    }, 1000)
    const that = this;
    let postData = {
      "goods_id": that.data.goodsId,
      'page_index': 1,
      'page_size': 20,
      'is_image': that.data.is_image,
      'explain_type': that.data.explain_type,
    }
    let datainfo = requestSign.requestSign(postData);

    header.sign = datainfo
    wx.request({
      url: api.get_goodsReviewsList,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code == 1) {
          let evaluateData = res.data.data;
          for (let value of evaluateData.review_list) {
            value.addtime = time.js_date_time(value.addtime);
          }

          that.setData({
            evaluateData: evaluateData
          })
        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none',
          })
        }


      },
      fail: (res) => { },
    })
  },

  //收藏商品
  collectGoods: function () {
    const that = this;
    if (that.ifLogin() == false) {
      return
    };
    if (that.hasPhoneFun() == false) {
      return
    }
    let postData = {
      "goods_id": that.data.goodsId
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    if (that.data.like == 'like-o') {
      wx.request({
        url: api.get_collectGoods,
        data: postData,
        header: header,
        method: 'POST',
        dataType: 'json',
        responseType: 'text',
        success: (res) => {
          if (res.data.code == 1) {
            wx.showToast({
              title: '收藏成功',
            })
            that.setData({
              like: 'like'
            })
          } else {
            wx.showToast({
              title: res.data.message,
              icon: 'none'
            })
          }


        },
        fail: (res) => { },
      })
    } else if (that.data.like == 'like') {
      wx.request({
        url: api.get_cancelCollectGoods,
        data: postData,
        header: header,
        method: 'POST',
        dataType: 'json',
        responseType: 'text',
        success: (res) => {
          if (res.data.code == 1) {
            wx.showToast({
              title: '取消成功',
            })
            that.setData({
              like: 'like-o'
            })
          } else {
            wx.showToast({
              title: res.data.message,
              icon: "none"
            })
          }

        },
        fail: (res) => { },
      })
    }
  },

  //规格属性的选择
  clickMenu: function (e) {
    const that = this;
    var selectIndex = e.currentTarget.dataset.selectIndex; //组的index
    var attrIndex = e.currentTarget.dataset.attrIndex; //当前的index
    var sku = that.data.sku;
    var spec = sku.tree;

    for (var i = 0; i < spec.length; i++) {
      for (var n = 0; n < spec[i].v.length; n++) {
        if (selectIndex == i) {
          spec[selectIndex].v[n].isSelect = "false";
        }
      }
    }
    spec[selectIndex].v[attrIndex].isSelect = "true";

    that.setData({
      sku: sku
    })

    let attrId = e.currentTarget.dataset.attrId;
    that.data.selectObj[selectIndex] = attrId.toString();


    for (var m = 0; m < sku.list.length; m++) {
      let selectArray = [];
      for (let i in that.data.selectObj) {
        selectArray.push(that.data.selectObj[i]); //属性
      }

      let bool = this.arrayIsEqual(selectArray.sort(), sku.list[m].s.sort());
      let maxBuy = '';
      if (bool == true) {

        if (sku.list[m].hasOwnProperty('max_buy')) {
          maxBuy = sku.list[m].max_buy
        } else if (sku.list[m].hasOwnProperty('group_limit_buy')) {
          maxBuy = sku.list[m].group_limit_buy
        } else {
          maxBuy = sku.list[m].stock_num
        }

        //预售开启
        if (that.data.is_presell == 1) {
          let tail_money = parseFloat(sku.list[m].all_money - sku.list[m].first_money).toFixed(2)
          that.setData({
            stockNum: sku.list[m].presell_num,
            presell_first_money: sku.list[m].first_money,
            presell_all_money: sku.list[m].all_money,
            tail_money: tail_money,
          })
        } else {
          that.setData({
            stockNum: sku.list[m].stock_num,
          })
        }

        let buyNum = '';
        if (that.data.buyNum != 1) {
          buyNum = that.data.buyNum
        } else {
          buyNum = 1
        }

        let goodPrice = sku.list[m].price
        //秒杀，拼团不参与折扣优惠
        if (that.data.seckill_open == true || that.data.group_open == true) {
          goodPrice = sku.list[m].price
        } else {
          if (that.data.member_discount > 0 && that.data.member_discount < 1) {
            goodPrice = parseFloat(goodPrice) * parseFloat(that.data.member_discount);
            if (that.data.limit_discount != '' && that.data.limit_discount < 1) {
              goodPrice = parseFloat(goodPrice) * good_Price(that.data.limit_discount);
            }
          }
        }

        that.setData({
          specName: sku.list[m].sku_name,
          skuId: sku.list[m].id,
          buyNum: buyNum,
          goodPrice: goodPrice,
          maxBuy: maxBuy,
          groupPrice: sku.list[m].group_price
        })
        if (sku.list[m].stock_num == 0) {
          wx.showToast({
            title: '没有库存',
            icon: 'none'
          })
        }
        break;
      }
    }
  },



  //判断2个数组是否相等
  arrayIsEqual: function (arr1, arr2) {
    if (arr1 === arr2) { //如果2个数组对应的指针相同，那么肯定相等，同时也对比一下类型
      return true;
    } else {
      if (arr1.length != arr2.length) {
        return false;
      } else { //长度相同
        for (let i in arr1) { //循环遍历对比每个位置的元素
          if (arr1[i] != arr2[i]) { //只要出现一次不相等，那么2个数组就不相等
            return false;
          }
        } //for循环完成，没有出现不相等的情况，那么2个数组相等
        return true;
      }
    }
  },

  //购买数量
  changeBuynum: function (e) {
    let stockNum = this.data.stockNum;
    if (e.detail == stockNum) {
      wx.showToast({
        title: '库存不足',
        icon: 'none'
      })
      setTimeout(function () {
        wx.hideToast()
      }, 1000)
    }
    this.setData({
      buyNum: e.detail
    })
  },

  //加入购物车
  addCart: function () {
    const that = this;
    if (that.ifLogin() == false) {
      return
    }
    if (that.hasPhoneFun() == false) {
      return
    }
    if (that.data.stockNum == 0) {
      wx.showToast({
        title: '商品库存为零不能购买',
        icon: 'none'
      })
      setTimeout(function () {
        wx.hideToast()
      }, 2000)
      return;
    }
    let skuId = '';
    if (that.data.sku.tree.length == 0) {
      skuId = that.data.sku.list[0].id;
    } else {
      if (that.data.skuId != '') {
        skuId = that.data.skuId;
      } else {
        wx.showToast({
          title: '请选择商品规格！',
          icon: 'loading'
        })
        return;
      }

    }
    let postData = {
      "sku_id": skuId,
      "num": that.data.buyNum
    }

    //微店
    if (that.data.shopkeeper_id) {
      postData.shopkeeper_id = that.data.shopkeeper_id;
      wx.setStorageSync("shopkeeper_id", that.data.shopkeeper_id);
    }

    let datainfo = requestSign.requestSign(postData);

    header.sign = datainfo
    wx.request({
      url: api.get_addCart,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        wx.showToast({
          title: res.data.message
        })
        that.setData({
          skuShow: false,
        })
      },
      fail: (res) => { },
    })
  },

  //底部加入购物车按键
  addcartBtn: function () {
    const that = this;
    if (that.ifLogin() == false) {
      return
    };
    if (that.hasPhoneFun() == false) {
      return
    };
    if (that.data.skuId == '') {
      that.setData({
        skuShow: true,
        sku_btn: false,
        sureBtn: 'addcart'
      })
    } else {
      that.addCart();
    }
  },
  //底部下单按键
  onOrderBtn: function () {
    const that = this;
    if (that.ifLogin() == false) {
      return
    };
    if (that.hasPhoneFun() == false) {
      return
    }
    if (that.data.skuId == '') {
      that.setData({
        skuShow: true,
        sku_btn: false,
        sureBtn: 'order'
      })
    } else {
      that.buyNowOrder();
    }
  },
  //拼图时底部单独购买按钮
  onGroupOnebuybtn: function () {
    const that = this;
    if (that.ifLogin() == false) {
      return
    };
    if (that.hasPhoneFun() == false) {
      return
    }
    if (that.data.skuId == '') {
      that.setData({
        skuShow: true,
        buyType: 'oneBuy',
        sureBtn: 'oneOrderGroup',
        sku_btn: false,
      })
    } else {
      that.onOrderInfoPage();
    }
  },
  //发起拼团按钮
  onGroupbuybtn: function () {
    const that = this;
    if (that.ifLogin() == false) {
      return
    };
    if (that.hasPhoneFun() == false) {
      return
    }
    if (that.data.skuId == '') {
      that.setData({
        skuShow: true,
        buyType: 'groupBuy',
        sureBtn: 'order',
        sku_btn: false,
      })
    } else {
      that.buyNowOrder();

    }
  },

  // 砍价按钮
  bargainbtn: function (e) {
    const that = this;
    if (that.ifLogin() == false) {
      return
    };
    if (that.hasPhoneFun() == false) {
      return
    }
    let bargain_id = e.currentTarget.dataset.bargainid;
    let goods_id = e.currentTarget.dataset.goodsid;
    let bargain_uid = e.currentTarget.dataset.bargainuid;
    let onPageData = {
      url: '/package/pages/bargain/detail/index',
      num: 4,
      param: '?bargain_id=' + bargain_id + '&goods_id=' + goods_id + '&bargain_uid=' + bargain_uid
    }
    util.jumpPage(onPageData);
  },
  //参加拼团
  joinGroup: function (e) {
    const that = this;
    let record_id = e.currentTarget.dataset.recordid;
    that.setData({
      record_id: record_id,
      groupCenterShow: false,
      skuShow: true,
      buyType: 'groupBuy',
      sureBtn: 'order',
      sku_btn: false,
    })
  },
  //立即下单
  buyNowOrder: function () {
    const that = this;
    if (that.hasPhoneFun() == false) {
      return
    }
    let sku_id = '';
    // 商品没有规格
    if (that.data.sku.tree.length == 0) {
      sku_id = that.data.sku.list[0].id;
    } else {
      sku_id = that.data.skuId;
    }
    let skuObj = {
      sku_id: sku_id,
      num: that.data.buyNum,
    };
    if (sku_id == '') {
      wx.showToast({
        title: '请选择规格数量！',
        icon: 'loading'
      })
      return
    }

    if (that.data.seckillId != '') {
      skuObj["seckill_id"] = that.data.seckillId;
    }

    let sku_list = [];
    sku_list.push(skuObj);
    let params = {
      sku_list: sku_list,
      order_tag: 'buy_now',
    }

    // 预售开启
    if (that.data.is_presell == 1) {
      params["presell_id"] = that.data.presell_id;
    } else {
      if (that.data.seckillId != '') {
        params["seckill_id"] = that.data.seckillId;
      }
      if (that.data.groupId != '') {
        params["group_id"] = that.data.groupId;
        params["record_id"] = that.data.record_id;
      }
    }

    //微店
    if (that.data.shopkeeper_id) {
      params["shopkeeper_id"] = that.data.shopkeeper_id;
      wx.setStorageSync("shopkeeper_id", that.data.shopkeeper_id);
    }
    params = Base64.encode(JSON.stringify(params));

    let onPageData = {
      url: '/pages/orderInfo/index',
      num: 4,
      param: '?params=' + params
    }
    util.jumpPage(onPageData);
  },

  //跳转到确认订单页
  onOrderInfoPage: function () {
    const that = this;
    if (that.hasPhoneFun() == false) {
      return
    }
    let sku_id = '';
    // 商品没有规格
    if (that.data.sku.tree.length == 0) {
      sku_id = that.data.sku.list[0].id;
    } else {
      sku_id = that.data.skuId;
    }
    let skuObj = {
      sku_id: sku_id,
      num: that.data.buyNum,
    };
    if (sku_id == '') {
      wx.showToast({
        title: '请选择规格数量！',
        icon: 'loading'
      })
      return
    }

    if (that.data.seckillId != '') {
      skuObj["seckill_id"] = that.data.seckillId;
    }

    let sku_list = [];
    sku_list.push(skuObj);
    let params = {
      sku_list: sku_list,
      order_tag: 'buy_now',
    }
    // 预售开启
    if (that.data.is_presell == 1) {
      params["presell_id"] = that.data.presell_id;
    } else {
      if (that.data.seckillId != '') {
        params["seckill_id"] = that.data.seckillId;
      }


    }

    //微店
    if (that.data.shopkeeper_id) {
      params["shopkeeper_id"] = that.data.shopkeeper_id;
      wx.setStorageSync("shopkeeper_id", that.data.shopkeeper_id);
    }

    params = Base64.encode(JSON.stringify(params));

    let onPageData = {
      url: '/pages/orderInfo/index',
      num: 4,
      param: '?params=' + params
    }
    util.jumpPage(onPageData);
  },



  //跳转到购物车
  onShopCart: function () {
    let onPageData = {
      url: '/pages/shopcart/index',
      num: 4,
      param: '',
    }
    util.jumpPage(onPageData);
  },
  //跳转到店铺
  onShop: function () {
    let onPageData = {
      url: '/pages/shop/home/index',
      num: 4,
      param: '?shopId=' + this.data.shopId
    }
    util.jumpPage(onPageData);

  },
  //是否登录
  ifLogin: function () {
    const that = this;
    var userToken = wx.getStorageSync('user_token');
    var ifLogin = true;
    if (userToken == '') {
      that.setData({
        loginShow: true,
      })
      ifLogin = false;
      return ifLogin
    }
    return ifLogin
  },

  /**
   * 倒计时函数
   */
  countDownTime: function (time) {
    const that = this;

    function resetTime () {
      //定义当前时间
      var startTime = new Date();
      //除以1000将毫秒数转化成秒数方便运算
      startTime = parseInt(startTime.getTime() / 1000)
      //定义结束时间
      var endTime = time;

      //算出中间差并且已秒数返回; ；
      var countDown = endTime - startTime;

      //获取天数 1天 = 24小时  1小时= 60分 1分 = 60秒
      var oDay = parseInt(countDown / (24 * 60 * 60));
      if (oDay < 10) {
        oDay = '0' + oDay
      }

      //获取小时数 
      //特别留意 %24 这是因为需要剔除掉整的天数;
      var oHours = parseInt(countDown / (60 * 60) % 24);
      if (oHours < 10) {
        oHours = '0' + oHours
      }

      //获取分钟数
      //同理剔除掉分钟数
      var oMinutes = parseInt(countDown / 60 % 60);
      if (oMinutes < 10) {
        oMinutes = '0' + oMinutes
      }

      //获取秒数
      //因为就是秒数  所以取得余数即可
      var oSeconds = parseInt(countDown % 60);
      if (oSeconds < 10 && oSeconds >= 0) {
        oSeconds = '0' + oSeconds
      }



      that.setData({
        oDay: oDay,
        oHours: oHours,
        oMinutes: oMinutes,
        oSeconds: oSeconds,
      })

      if (that.data.endTimeShow == true) {
        clearInterval(timer);
      }

      //别忘记当时间为0的，要让其知道结束了;
      if (countDown < 0) {
        clearInterval(timer);
        that.getGoodDetailData();
      }
    }
    var timer = setInterval(resetTime, 1000);

  },

  //判断是否有手机号
  hasPhoneFun: function () {
    const that = this;
    const have_mobile = wx.getStorageSync('have_mobile');
    if (have_mobile != true) {
      that.setData({
        phoneShow: true
      })
      return false
    }
  },
  //绑定手机结果返回
  phonereResult: function (e) {
    const that = this
    let result = e.detail.result;
    if (result == 'success') {
      that.getGoodDetailData();
    }
  },

  //领取优惠券
  getuserCoupon: function (e) {
    const that = this;
    let couponid = e.currentTarget.dataset.couponid
    let postData = {
      "coupon_type_id": couponid,
      "get_type": 5
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_userArchiveCoupon,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        wx.showToast({
          title: res.data.message
        })
        that.couponOnclose();
      },
      fail: (res) => { },
    })
  },



  /**
   * 拼团倒计时函数
   */
  groupTime: function (time, record_id) {
    const that = this;
    let remainingTime = '';

    function resetTime () {
      //定义当前时间
      var startTime = new Date();
      //除以1000将毫秒数转化成秒数方便运算
      startTime = startTime.getTime() / 1000
      //定义结束时间
      var endTime = time;

      //算出中间差并且已秒数返回; ；
      var countDown = endTime - startTime;

      //获取天数 1天 = 24小时  1小时= 60分 1分 = 60秒
      var oDay = parseInt(countDown / (24 * 60 * 60));
      if (oDay < 10) {
        oDay = '0' + oDay
      }

      //获取小时数 
      //特别留意 %24 这是因为需要剔除掉整的天数;
      var oHours = parseInt(countDown / (60 * 60) % 24);
      if (oHours < 10) {
        oHours = '0' + oHours
      }

      //获取分钟数
      //同理剔除掉分钟数
      var oMinutes = parseInt(countDown / 60 % 60);
      if (oMinutes < 10) {
        oMinutes = '0' + oMinutes
      }

      //获取秒数
      //因为就是秒数  所以取得余数即可
      var oSeconds = parseInt(countDown % 60);
      if (oSeconds < 10 && oSeconds >= 0) {
        oSeconds = '0' + oSeconds
      }

      remainingTime = oDay + ':' + oHours + ':' + oMinutes + ':' + oSeconds;
      console.log(remainingTime);
      let group_record_list = that.data.group_record_list;
      for (let item of group_record_list) {
        if (record_id == item.record_id) {
          item.end_time = remainingTime;
        }
      }
      that.setData({
        group_record_list: group_record_list
      })

      if (that.data.endTimeShow == true) {
        clearInterval(timer);
      }

      //别忘记当时间为0的，要让其知道结束了;
      if (countDown < 0) {
        clearInterval(timer);
        that.getGoodDetailData();
      }
    }
    var timer = setInterval(resetTime, 1000);

  },

  //小程序太阳码生成
  getLimitMpCode: function () {
    const that = this;
      const appConfig = getApp().globalData;
    var postData = {
      'website_id': appConfig.website_id,
      'auth_id': appConfig.auth_id,
      'page': 'pages/goods/detail/index',
      "goodsId": that.data.goodsId,
      "code": getApp().globalData.extend_code||''
    };
    console.log(postData)
    // if (wx.getStorageSync('extend_code')) {
    //   postData.code = wx.getStorageSync('extend_code')
    // }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_getUnLimitMpCode, postData, header).then((res) => {
      if (res.data.code == 1) {
        let code_img = res.data.data;
        that.setData({
          code_img: code_img
        })
        
        //that.base64CodeImg(code_img)
      } else {
        that.setData({
          code_img: '/images/no-goods.png'
        })
      }
      that.selectComponent('#getPoster').getAvaterInfo();
    })
  },

  //调用canvas子组件的方法
  getSharePoster: function () {
    const that = this;
    if (that.ifLogin() == false) {
      return
    };    
    console.log(that.data.config_poster)
    //系统平台是否有海报应用1-有，0-没有
    if (that.data.config_poster == 1) {
      //应用已开启，是否有设置海报或海报报错0-没有（把config_poster设置为0，使用自己生成的默认海报并重新请求太阳码），1-有
      if (that.data.posterImgType == 0) {
        that.setData({
          config_poster: 0
        })
        // if (wx.getStorageSync('extend_code') == '') {
        //   that.getLimitMpCode();
        // }
        that.getLimitMpCode();
      } else {
        that.selectComponent('#getPoster').getPosterImg();
      }
    } else {
      that.getLimitMpCode();
    }

  },



  base64CodeImg: function (codeImg) {
    const that = this;
    base64src(codeImg, res => {
      console.log(res);
      that.setData({
        codeimg: res
      })
    });
  },

  //获取超级海报
  getKindPoster: function () {
    const that = this;
    var postData = {
      "poster_type": 2,
      "goods_id": that.data.goodsId,
      "is_mp": 1,
      "mp_page": 'pages/goods/detail/index',
    };

    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_getKindPoster, postData, header).then((res) => {
      if (res.data.code == 0) {
        that.setData({
          posterImgType: 0
        })
      } else {
        that.setData({
          poster_img: res.data.data.poster
        })
      }
    })
  },

  //预览图片
  previewImg: function (e) {
    let img_list = e.currentTarget.dataset.imglist;
    wx.previewImage({
      urls: img_list,
    })
  },

  //设置分销的文案字段
  setDistributionData: function () {
    const that = this;
    let distributionData = getApp().globalData.distributionData;
    if (distributionData == '') {
      util.distributionSet().then((res) => {
        let resultData = res.data.data;
        that.setData({
          txt_commission: resultData.commission,
        })
      });
    } else {
      that.setData({
        txt_commission: distributionData.commission,
      })
    }
  },

  // 监听滚动条坐标
  onPageScroll: function (e) {
    const that = this;
    let scrollTop = e.scrollTop;
    let backTopValue = scrollTop > 500 ? true : false;
    let topNavValue = scrollTop == 0 ? true : false;
    that.setData({
      backTopValue: backTopValue,
      topNavValue: topNavValue
    })
  },

  // extend_code: function() {
  //   const that = this;
  //   let postData = {}
  //   let datainfo = requestSign.requestSign(postData);
  //   header.sign = datainfo
  //   re.request(api.get_qrcode, postData, header).then((res) => {
  //     if (res.data.code == 0) {
  //       wx.setStorageSync("extend_code", res.data.data.extend_code);
  //       that.getLimitMpCode();
  //     }
  //   })

  // },

  //获取客服信息
  qlkefuInfoFun: function () {
    const that = this;
    var postData = {
      "shop_id": that.data.shopId,
      "goods_id": that.data.goodsId,
    };
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_qlkefuInfo, postData, header).then((res) => {
      if (res.data.code == 1) {
        wx.setStorageSync("domain", res.data.data.domain);
        wx.setStorageSync("port", res.data.data.port);
        wx.setStorageSync("seller_code", res.data.data.seller_code);
        let config_qlkefu = getApp().globalData.config.addons.qlkefu;
        let kefuShow = false;
        if (res.data.data.is_use == 1 && config_qlkefu == 1) {
          kefuShow = true
        }
        that.setData({
          kefuShow: kefuShow,
          seller: res.data.data.seller_code
        })
      }
    })
  },



  //跳转到客服页面
  onChat: function () {
    const that = this;
    if (that.ifLogin() == false) {
      return
    };
    let goodsData = that.data.goodDetail
    let goods = {
      goods_id: this.data.goodsId,
      pic_cover: goodsData.goods_images[0],
      goods_name: goodsData.goods_name,
      price: goodsData.min_price,
      shop_name: goodsData.shop_name,
    }
    goods = JSON.stringify(goods);

    wx.navigateTo({
      url: '/packageSecond/pages/chat/index?goods=' + goods + '&seller=' + that.data.seller,
    })
  },

  //会员中心
  getMember: function () {
    const that = this;
    var postData = {};
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_memberIndex, postData, header).then((res) => {
      if (res.data.code >= 0) {
        that.data.extend_code = res.data.data.extend_code;
        that.getLimitMpCode();
      }
    })
  },
  //知识付费目录
  getCourseList: function () {
    const that = this;
    let postData = {
      goods_id: that.data.goodsId
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    wx.request({
      url: api.get_courseDetailList,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        wx.hideLoading();
        if (res.data.code >= 0) {
          that.setData({
            source: res.data.data.konwledge_payment_list,
            is_buy: res.data.data.is_buy
          })
        }
      }
    })
  },
  //前往学习
  onStudy: function () {
    const that = this;
    wx.navigateTo({
      url: '/packageSecond/pages/course/detail/index?goods_id=' + that.data.goodsId,
    })
  },
  //试学
  onTryStudy: function (e) {
    const that = this;
    wx.navigateTo({
      url: '/packageSecond/pages/course/detail/index?goods_id=' + that.data.goodsId + '&cid=' + e.currentTarget.dataset.cid,
    })
  }






})