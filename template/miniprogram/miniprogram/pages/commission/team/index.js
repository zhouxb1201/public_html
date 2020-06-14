var requestSign = require('../../../utils/requestData.js');
var api = require('../../../utils/api.js').open_api;
var re = require('../../../utils/request.js');
var util = require('../../../utils/util.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    pageShow:false,
    page_index:1,
    team_list:'',
    //1: 1级, 2: 2级, 3: 3级
    type:1,
    //分销模式1级,2级，3级
    distribution_pattern:'',
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
    const value = wx.getStorageSync('user_token')
    if (value) {
      that.setDistributionData();
      that.distributionCenter();
    } else {
      let onPageData = {
        url: '/pages/logon/index',
        num: 2,
        param: ''
      }
      util.jumpPage(onPageData);
    }
    
    that.teamList().then((res) => {
      if (res.data.code >= 0) {
        that.setData({
          team_list: res.data.data.data,
        })
      } else {
        wx.showToast({
          title: res.data.message,
          icon: 'none'
        })
      }
    });
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
    const that = this;
    that.data.page_index = that.data.page_index + 1;
    that.teamList().then((res) => {
      if (res.data.code >= 0) {
        let team_list = that.data.team_list;
        team_list = team_list.concat(res.data.data.data);
        that.setData({
          team_list: team_list,
        })
      } else {
        wx.showToast({
          title: res.data.message,
          icon: 'none'
        })
      }
    });
  },

  //分销中心，判断是否申请成为分销商
  distributionCenter: function () {
    const that = this;
    let postData = {}
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    wx.showLoading({
      title: '加载中',
    })
    re.request(api.get_distributionCenter, postData, header).then((res) => {
      if (res.data.code >= 0) {
        wx.hideLoading();
        let isdistributor = res.data.data.isdistributor
        let distribution_pattern = res.data.data.distribution_pattern;
        that.setData({
          distribution_pattern: distribution_pattern
        })
        if (isdistributor != 2) {
          wx.showModal({
            title: '提示',
            content: '你还不是分销商，请先申请！',
            success(res) {
              if (res.confirm) {
                let onPageData = {
                  url: '../apply/index',
                  num: 4,
                  param: '?isdistributor=' + isdistributor,
                }
                util.jumpPage(onPageData);
              } else if (res.cancel) {
                wx.navigateBack({
                  delta: 1,
                })
              }
            }
          })
        } else {
          that.setData({
            pageShow: true
          })
        }
      } else {
        wx.showToast({
          title: res.data.message,
          icon: 'none'
        })
      }
    })
  },

  

  //团队列表
  teamList: function () {
    const that = this;

    let postData = {
      'page_index': that.data.page_index,
      'type':that.data.type,
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    return new Promise((resolve, reject) => {
      wx.request({
        url: api.get_teamList,
        data: postData,
        header: header,
        method: 'POST',
        dataType: 'json',
        responseType: 'text',
        success: (res) => {
          resolve(res);
        },
        fail: (res) => { },
      })
    })
  },

  onTabsChange:function(event){
    const that = this;
    console.log(event.detail.index);
    that.data.type = event.detail.index + 1;

    that.teamList().then((res) => {
      if (res.data.code >= 0) {
        that.setData({
          team_list: res.data.data.data,
        })
      } else {
        wx.showToast({
          title: res.data.message,
          icon: 'none'
        })
      }
    });

  },

  //设置分销中心的文案字段
  setDistributionData: function () {
    const that = this;
    let distributionData = getApp().globalData.distributionData;
    if (distributionData == '') {
      util.distributionSet().then((res) => {
        let resultData = res.data.data;
        wx.setNavigationBarTitle({
          title: resultData.my_team,
        })
        that.setData({
          txt_team1: resultData.team1,
          txt_team2: resultData.team2,
          txt_team3: resultData.team3,
        })
      });

    } else {
      wx.setNavigationBarTitle({
        title: distributionData.my_team,
      })
      that.setData({
        txt_team1: distributionData.team1,
        txt_team2: distributionData.team2,
        txt_team3: distributionData.team3,
      })
    }
  },

})