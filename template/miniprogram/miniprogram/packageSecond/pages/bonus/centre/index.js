var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var util = require('../../../../utils/util.js');
var header = getApp().header;
var WxParse = require('../../../../common/wxParse/wxParse.js');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    pageShow:false,
    bonusData: '',
    //只有一种代理
    is_one_agent: false,
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    wx.showLoading({
      title: '加载中',
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
    const that = this;
    const value = wx.getStorageSync('user_token');
    wx.hideLoading();
    if(value){
      that.bonusSetTxt();
      that.bonusIndex();
    }else{
      that.setData({
        loginShow:true
      })
    }
    
    getApp().globalData.credential_type = 2
  },

  requestLogin: function (e) {
    const that = this;
    let result = e.detail.result;
    if (result == true) {
      that.bonusSetTxt();
      that.bonusIndex();
    }
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

  bonusIndex: function () {
    const that = this;
    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_bonusIndex,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        wx.hideLoading();
        if (res.data.code >= 0) {
          
          let bonusData = res.data.data
          let is_start_array = [];
          if (bonusData.area_is_start == null) {
            is_start_array.push(1);
          }
          if (bonusData.team_is_start == null) {
            is_start_array.push(2);
          }
          if (bonusData.global_is_start == null) {
            is_start_array.push(3);
          }
          if (is_start_array.length >= 2) {
            that.setData({
              is_one_agent: true
            })
          }
          let role_type_array = [];
          if (bonusData.is_area_agent == 2) {
            role_type_array.push(2);
          }
          if (bonusData.is_team_agent == 2) {
            role_type_array.push(1);
          }
          if (bonusData.is_global_agent == 2) {
            role_type_array.push(3);
          }
          that.setData({
            bonusData: bonusData,
            role_type_array: role_type_array,
            pageShow:true,
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
  },

  //分红文案
  bonusSetTxt: function () {
    const that = this;
    let configAddonsData = getApp().globalData.config.addons;
    let bonusData = getApp().globalData.bonusData;
    if (bonusData == '') {
      util.bonusSet().then((res) => {
        let resultData = res.data.data;
        wx.setNavigationBarTitle({
          title: resultData.common.bonus_name,
        })
        //全球分红
        if (configAddonsData.globalbonus != 0 && resultData.global != null) {
          that.setData({
            txt_global_agreement: resultData.global.global_agreement,
            txt_apply_global: resultData.global.apply_global,
          })
        }
        //团队分红
        if (configAddonsData.teambonus != 0 && resultData.team != null) {
          that.setData({
            txt_team_agreement: resultData.team.team_agreement,
            txt_apply_team: resultData.team.apply_team,
          })
        }
        //区域分红
        if (configAddonsData.areabonus != 0 && resultData.area != null) {
          that.setData({
            txt_area_agreement: resultData.area.area_agreement,
            txt_apply_area: resultData.area.apply_area,
          })
        }

        that.setData({
          txt_bonus_money: resultData.common.bonus_money,
          txt_bonus_order: resultData.common.bonus_order,
          txt_withdrawals_bonus: resultData.common.withdrawals_bonus,
          txt_withdrawal_bonus: resultData.common.withdrawal_bonus,
          txt_frozen_bonus: resultData.common.frozen_bonus,
        })
      });
    } else {
      wx.setNavigationBarTitle({
        title: bonusData.common.bonus_name,
      })
      //全球分红
      if (configAddonsData.globalbonus != 0 && bonusData.global != null) {
        that.setData({
          txt_global_agreement: bonusData.global.global_agreement,
          txt_apply_global: bonusData.global.apply_global,
        })
      }
      //团队分红
      if (configAddonsData.teambonus != 0 && bonusData.team != null) {
        that.setData({
          txt_team_agreement: bonusData.team.team_agreement,
          txt_apply_team: bonusData.team.apply_team,
        })
      }
      //区域分红
      if (configAddonsData.areabonus != 0 && bonusData.area != null) {
        that.setData({
          txt_area_agreement: bonusData.area.area_agreement,
          txt_apply_area: bonusData.area.apply_area,
        })
      }
      that.setData({
        txt_bonus_money: bonusData.common.bonus_money,
        txt_bonus_order: bonusData.common.bonus_order,
        txt_withdrawals_bonus: bonusData.common.withdrawals_bonus,
        txt_withdrawal_bonus: bonusData.common.withdrawal_bonus,
        txt_frozen_bonus: bonusData.common.frozen_bonus,
      })
    }


  },



})