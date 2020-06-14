var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var util = require('../../../../utils/util.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    myBonusData: '',
    typeBonusData: '',
    active: 0,
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {    
    
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
    if(value){
      that.myBonus();
      that.bonusSetTxt();
    }else{
      wx.navigateTo({
        url: '/pages/logon/index',
      })
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

  //分红金额
  myBonus: function () {
    const that = this;
    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_myBonus,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          that.setData({
            myBonusData: res.data.data,
            typeBonusData: res.data.data.global
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


  onBonusChange: function (e) {
    const that = this;
    let myBonusData = that.data.myBonusData;
    let index = e.detail.index;
    that.data.active = index;
    switch (index) {
      //0-全球分红
      case 0:
        that.setData({
          typeBonusData: myBonusData.global,
          txt_withdrawals_detail_bonus: that.data.txt_withdrawals_global_bonus,
          txt_withdrawal_detail_bonus: that.data.txt_withdrawal_global_bonus,
          txt_frozen_detail_bonus: that.data.txt_frozen_global_bonus,
        })
        break;
      //1-区域分红
      case 1:
        that.setData({
          typeBonusData: myBonusData.area,
          txt_withdrawals_detail_bonus: that.data.txt_withdrawals_area_bonus,
          txt_withdrawal_detail_bonus: that.data.txt_withdrawal_area_bonus,
          txt_frozen_detail_bonus: that.data.txt_frozen_area_bonus,
        })
        break;
      //团队分红
      case 2:
        that.setData({
          typeBonusData: myBonusData.team,
          txt_withdrawals_detail_bonus: that.data.txt_withdrawals_team_bonus,
          txt_withdrawal_detail_bonus: that.data.txt_withdrawal_team_bonus,
          txt_frozen_detail_bonus: that.data.txt_frozen_team_bonus,
        })
        break;
    }
  },

  //跳转到明细页面
  onLogPage: function () {

    let onPageData = {
      url: '../log/index',
      num: 4,
      param: ''
    }
    util.jumpPage(onPageData);
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
            txt_withdrawals_global_bonus: resultData.global.withdrawals_global_bonus,
            txt_withdrawal_global_bonus: resultData.global.withdrawal_global_bonus,
            txt_frozen_global_bonus: resultData.global.frozen_global_bonus,
          })
        }
        //团队分红
        if (configAddonsData.teambonus != 0 && resultData.team != null) {
          that.setData({
            txt_team_agreement: resultData.team.team_agreement,
            txt_apply_team: resultData.team.apply_team,
            txt_withdrawals_team_bonus: resultData.team.withdrawals_team_bonus,
            txt_withdrawal_team_bonus: resultData.team.withdrawal_team_bonus,
            txt_frozen_team_bonus: resultData.team.frozen_team_bonus,
          })
        }
        //区域分红
        if (configAddonsData.areabonus != 0 && resultData.area != null) {
          that.setData({
            txt_area_agreement: resultData.area.area_agreement,
            txt_apply_area: resultData.area.apply_area,
            txt_withdrawals_area_bonus: resultData.area.withdrawals_area_bonus,
            txt_withdrawal_area_bonus: resultData.area.withdrawal_area_bonus,
            txt_frozen_area_bonus: resultData.area.frozen_area_bonus,
          })
        }

        that.setData({
          txt_bonus_money: resultData.common.bonus_money,
          txt_bonus_order: resultData.common.bonus_order,
          txt_withdrawals_bonus: resultData.common.withdrawals_bonus,
          txt_withdrawal_bonus: resultData.common.withdrawal_bonus,
          txt_frozen_bonus: resultData.common.frozen_bonus,

          txt_withdrawals_detail_bonus: resultData.global.withdrawals_global_bonus,
          txt_withdrawal_detail_bonus: resultData.global.withdrawal_global_bonus,
          txt_frozen_detail_bonus: resultData.global.frozen_global_bonus,
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
          txt_withdrawals_global_bonus: bonusData.global.withdrawals_global_bonus,
          txt_withdrawal_global_bonus: bonusData.global.withdrawal_global_bonus,
          txt_frozen_global_bonus: bonusData.global.frozen_global_bonus,
        })
      }
      //团队分红
      if (configAddonsData.teambonus != 0 && bonusData.team != null) {
        that.setData({
          txt_team_agreement: bonusData.team.team_agreement,
          txt_apply_team: bonusData.team.apply_team,
          txt_withdrawals_team_bonus: bonusData.team.withdrawals_team_bonus,
          txt_withdrawal_team_bonus: bonusData.team.withdrawal_team_bonus,
          txt_frozen_team_bonus: bonusData.team.frozen_team_bonus,
        })
      }
      //区域分红
      if (configAddonsData.areabonus != 0 && bonusData.area != null) {
        that.setData({
          txt_area_agreement: bonusData.area.area_agreement,
          txt_apply_area: bonusData.area.apply_area,
          txt_withdrawals_area_bonus: bonusData.area.withdrawals_area_bonus,
          txt_withdrawal_area_bonus: bonusData.area.withdrawal_area_bonus,
          txt_frozen_area_bonus: bonusData.area.frozen_area_bonus,
        })
      }

      that.setData({

        txt_bonus_money: bonusData.common.bonus_money,
        txt_bonus_order: bonusData.common.bonus_order,
        txt_withdrawals_bonus: bonusData.common.withdrawals_bonus,
        txt_withdrawal_bonus: bonusData.common.withdrawal_bonus,
        txt_frozen_bonus: bonusData.common.frozen_bonus,

        txt_withdrawals_detail_bonus: bonusData.global.withdrawals_global_bonus,
        txt_withdrawal_detail_bonus: bonusData.global.withdrawal_global_bonus,
        txt_frozen_detail_bonus: bonusData.global.frozen_global_bonus,
      })
    }



  },
})