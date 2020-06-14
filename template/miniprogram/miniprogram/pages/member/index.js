var requestSign = require('../../utils/requestData.js');
var api = require('../../utils/api.js').open_api;
var util = require('../../utils/util.js');
var header = getApp().header;
var WxParse = require('../../common/wxParse/wxParse.js');
const app = getApp();
Page({

  /**
   * 页面的初始数据
   */
  data: {
    boxShow: false,
    mobileShow: false,
    is_login: false,
    memberData: '',
    //自定义模板数据
    temData: '',
    dataUrl: getApp().publicUrl,
    //推荐码
    extend_code: '',
    member_img: '',
    user_name: '',
    temItems: {},

    items: {
      "M0_member": {
        "id": "member_fixed",
        "style": {
          "backgroundimage": ""
        },
        "params": {
          "styletype": ''
        }
      },
      "M0_member_bind": {
        "id": "member_bind_fixed",
        "style": {
          "background": "#fff",
          "iconcolor": '#ff454e',
          "titlecolor": '#323233',
          "desccolor": '#909399',
        },
        "params": {
          "title": '绑定手机',
          "desc": '为了账号安全、方便购物和订单同步，请绑定手机号码。'
        }
      },
      "M0_member_assets": {
        "id": "member_assets_fixed",
        "style": {
          "background": "#fff",
          "textcolor": '#323233',
          "iconcolor": '#323233',
          "highlight": '#ff454e',
          "titlecolor": '#323233',
          "titleiconcolor": '#323233',
          "titleremarkcolor": '#909399',
        },
        "params": {
          "title": '我的资产',
          "remark": '更多',
          "iconclass": 'v-icon-assets'
        },
        "data": {
          "C0_balance": {
            "key": 'balance',
            "name": '余额',
            "text": '余额',
            "is_show": '1',
          },
          "C0_points": {
            "key": 'points',
            "name": '积分',
            "text": '积分',
            "is_show": '1',
          },
          "C0_coupontype": {
            "key": 'coupontype',
            "name": '优惠券',
            "text": '优惠券',
            "is_show": '1',
          },
          "C0_giftvoucher": {
            "key": 'giftvoucher',
            "name": '礼品券',
            "text": '礼品券',
            "is_show": '1',
          },
          "C0_store": {
            "key": 'store',
            "name": '消费卡',
            "text": '消费卡',
            "is_show": '1',
          }
        }
      },
      "M0_member_order": {
        "id": "member_order_fixed",
        "style": {
          "background": "#fff",
          "textcolor": '#323233',
          "iconcolor": '#323233',
          "titlecolor": '#323233',
          "titleiconcolor": '#323233',
          "titleremarkcolor": '#909399',
        },
        "params": {
          "title": '我的订单',
          "remark": '全部订单',
          "iconclass": 'v-icon-form'
        },
        "data": {
          "C0123456789101": {
            "key": 'unpaid',
            "name": '待付款',
            "text": '待付款',
            "iconclass": 'v-icon-payment2',
            "is_show": '1',
          },
          "C0123456789102": {
            "key": 'unshipped',
            "name": '待发货',
            "text": '待发货',
            "iconclass": 'v-icon-delivery2',
            "is_show": '1',
          },
          "C0123456789103": {
            "key": 'unreceived',
            "name": '待收货',
            "text": '待收货',
            "iconclass": 'v-icon-logistic3',
            "is_show": '1',
          },
          "C0123456789104": {
            "key": 'unevaluated',
            "name": '待评价',
            "text": '待评价',
            "iconclass": 'v-icon-success1',
            "is_show": '1',
          },
          "C0123456789105": {
            "key": 'aftersale',
            "name": '售后',
            "text": '售后',
            "iconclass": 'v-icon-sale',
            "is_show": '1',
          }
        }
      }
    },
    memberFixed: {},
    membderAssetsFixed: {},
    memberOrder: {},
    memberBind: {}
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {
    wx.showLoading({
      title: '加载中',
    })

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
    const value = wx.getStorageSync('user_token')
    const have_mobile = wx.getStorageSync('have_mobile');
    if (value) {
      that.setData({
        is_login: true
      })
      that.getMember();
      util.extend_code();

      //先判断是否登录再判断是否有手机
      if (have_mobile != true) {
        that.setData({
          mobileShow: true
        })
      } else {
        that.setData({
          mobileShow: false
        })
      }
    } else {
      that.setData({
        is_login: false,
        mobileShow: false
      })
    }

    that.getTemData();

    const extend_code = wx.getStorageSync('extend_code');
    if (extend_code) {
      that.setData({
        extend_code: extend_code
      })
    }

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

  //请求会员中心数据  
  getMember: function() {
    const that = this;
    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_memberIndex,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        
        let code = res.data.code;
        if (res.data.code >= 0) {

          let memberData = res.data.data
          that.setData({
            memberData: memberData,
          })
          let user_name = ''
          if (memberData.user_name != '') {
            user_name = memberData.user_name;
          } else {
            if (memberData.nick_name != '') {
              user_name = memberData.nick_name
              wx.removeStorageSync('nickName');
            } else {
              user_name = memberData.user_tel;
              
            }
          }

          that.setData({
            user_name: user_name
          })

          if (memberData.user_tel != null && memberData.user_tel != undefined && memberData.user_tel != '') {
            that.setData({
              mobileShow: false
            })
          }else{
            that.setData({
              mobileShow: true
            })
          }

          if (memberData.member_img != '') {
            that.setData({
              member_img: memberData.member_img
            })
            wx.removeStorageSync('avatarUrl');
          }
          
        } else {
          wx.showModal({
            title: '提示',
            content: res.data.message,
            showCancel: false,
            success(res) {
              if (code == -1000) {
                wx.redirectTo({
                  url: '../logon/index',
                })
              }
            }
          })
        }
      },
      fail: (res) => {},
    })
  },


  //获取自定义数据  
  getTemData: function() {
    const that = this;
    let postData = {
      'type': 4,
      'is_mini': 1
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_custom,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        wx.hideLoading();
        if (res.data.code >= 0) {
          that.setData({
            boxShow: true
          })
          //判断图片地址是本地图片还是网络图片
          let template_data = res.data.data.template_data
          for (var item in template_data.items) {
            let item_data = template_data.items[item].data;
            for (var index in item_data) {
              if (item_data[index].imgurl != undefined) {
                if (item_data[index].imgurl.substring(0, 1) == 'h') {} else {
                  item_data[index].imgurl = that.data.dataUrl + item_data[index].imgurl
                }
              }
            }
          }

          let copyright = '';
          if (res.data.data.copyright != undefined) {
            copyright = res.data.data.copyright
          }

          that.initCustomData(template_data);

          that.setData({
            copyData: copyright,
            temData: template_data
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




  //绑定手机结果返回
  phonereResult: function(e) {
    const that = this
    let result = e.detail.result;
    if (result == 'success') {
      that.setData({
        mobileShow: false,
      })
      that.getMember();
      that.getTemData();
    }
  },


  //绑定手机
  bindPhone: function() {
    const that = this;
    that.setData({
      phoneShow: true
    })
  },

  //获取template_data中items数据
  initCustomData(template_data) {
    const that = this;
    const templateItems = template_data ? template_data.items : that.data.items;
    let newItems = {};
    const arr = [
      "member_fixed",
      "member_bind_fixed",
      "member_assets_fixed",
      "member_order_fixed"
    ];
    for (let key in templateItems) {
      const item = templateItems[key];
      if (
        item.id == arr[0] &&
        (!item.params || !item.params.styletype) &&
        item.id != arr[1] &&
        item.id != arr[2] &&
        item.id != arr[3]
      ) {
        // 处理旧数据没有相关数据问题，采用默认数据
        for (let i in that.data.items) {
          newItems[i] = that.data.items[i];
        }
      } else {
        newItems[key] = item;
      }
    }
    for (let key in newItems) {


      if (newItems[key].id == "member_fixed") {
        let item = newItems[key];
        let src = "";
        let type = item.params.styletype;

        if (item.style && item.style.backgroundimage) {
          src = item.style.backgroundimage;
        } else {
          if (item.id && type) {
            src = that.data.dataUrl + '/wap/static/images/style/member-head-0' + type + '.png';
          } else {
            src = '/images/member-head-01.png';
          }
        }
        const memberFixed = {
          bgSrc: src,
          styletype: type
        };
        that.setData({
          memberFixed: memberFixed
        })
      }
      if (newItems[key].id == "member_bind_fixed") {
        let item = newItems[key];
        const memberBind = {
          params: item.params,
          styleColor: item.style
        };
        that.setData({
          memberBind: memberBind
        })
      }

      if (newItems[key].id == "member_assets_fixed") {
        let data = newItems[key].data;
        let balanceData, coupontypeData, giftvoucherData, pointsData, storeData,
          coupontype_addons = getApp().globalData.config.addons.coupontype,
          giftvoucher_addons = getApp().globalData.config.addons.giftvoucher,
          store_addons = getApp().globalData.config.addons.store;
        let show_array = []
        for (let j in data) {
          if (data[j].key == "balance") {
            balanceData = data[j];            
          }
          if (data[j].key == "coupontype") {
            coupontypeData = data[j];
          }
          if (data[j].key == "giftvoucher") {
            giftvoucherData = data[j];
          }
          if (data[j].key == "points") {
            pointsData = data[j];
          }
          if (data[j].key == "store") {
            storeData = data[j];
          }
        }

        if (balanceData.is_show == 1) {
          show_array.push('balance')
        }
        if (pointsData.is_show == 1) {
          show_array.push('points')
        }
        if (coupontypeData.is_show == 1 && coupontype_addons == 1) {
          show_array.push('coupontype')
        }
        if (giftvoucherData.is_show == 1 && giftvoucher_addons == 1) {
          show_array.push('giftvoucher')
        }
        if (storeData.is_show == 1 && store_addons == 1) {
          show_array.push('store')
        }

        const membderAssetsFixed = {
          balanceData: balanceData,
          coupontypeData: coupontypeData,
          giftvoucherData: giftvoucherData,
          pointsData: pointsData,
          storeData: storeData,
          params: newItems[key].params,
          styleColor: newItems[key].style,
          show_array: show_array
        };
        that.setData({
          membderAssetsFixed: membderAssetsFixed,

        })

      }

      if (newItems[key].id == "member_order_fixed") {
        let data = newItems[key].data;
        const arr = [];
        for (let i in data) {
          if (data[i].is_show == "1") {
            let obj = {};
            switch (data[i].key) {
              case "unpaid":
                obj = {
                  text: data[i].text,
                  icon: data[i].iconclass,
                  status: 0
                };
                arr.push(obj);
                break;
              case "unshipped":
                obj = {
                  text: data[i].text,
                  icon: data[i].iconclass,
                  status: 1
                };
                arr.push(obj);
                break;
              case "unreceived":
                obj = {
                  text: data[i].text,
                  icon: data[i].iconclass,
                  status: 2
                };
                arr.push(obj);
                break;
              case "unevaluated":
                obj = {
                  text: data[i].text,
                  icon: data[i].iconclass,
                  status: -2
                };
                arr.push(obj);
                break;
              case "aftersale":
                obj = {
                  text: data[i].text,
                  icon: data[i].iconclass,
                  status: -1
                };
                arr.push(obj);
                break;
            }
          }
        }
        const membderOrder = {
          data: arr,
          params: newItems[key].params,
          styleColor: newItems[key].style
        };
        that.setData({
          membderOrder: membderOrder
        })
      }
    }
  }

})