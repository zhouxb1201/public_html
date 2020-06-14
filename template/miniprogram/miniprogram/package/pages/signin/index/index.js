var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var re = require('../../../../utils/request.js');
var util = require('../../../../utils/util.js');
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    isOpen: 0,
    publicUrl: getApp().publicUrl,
    info: {},

    lastDay: null,
    firstDay: null,
    year: null,
    cur_year: '',
    cur_month: '',
    getDate: null,
    month: null,
    week: [],
    day: [],
    days: [], //本月总共天数  
    upperDays: [], //上月剩余天数
    nextDays: [] //下月剩余天数

  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {
    this.setData({
      isOpen: getApp().globalData.config.addons.signin
    })
    this.loadData(); 
    if (this.data.isOpen == 1){
      const value = wx.getStorageSync('user_token')
      if (value) {
        console.log('已登录');
      } else {
        console.log('未登录')
        wx.redirectTo({
          url: '/pages/logon/index',
        })
      }
    }  
    
    //日历  
    this.dataTime();
    this.setNowDate();
    this.setData({
      getDate: this.data.getDate,
      judge: 1,
      month: this.data.month,
    });
    this.getSigninList();
  },
  loadData: function() {
    const that = this;
    let postData = {};
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_signinInfo, postData, header).then((res) => {
      if (res.data.code > 0) {
        that.setData({
          info: res.data.data
        })        
      }
    })
  },
  /**
   * 签到
   */
  onSignin: function() {
    const that = this;    
    if(that.checkPhone() == false){
      return;
    }
    let postData = {};
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.set_signin, postData, header).then((res) => {
      if (res.data.code > 0) {
        let info = that.data.info;
        info.is_signin = 1;
        that.setData({
          info: info
        })
        this.getSigninList();
      }
    })
  },
  /**
   * 显示每个月已签到的日子
   */
  getSigninList: function() {
    const that = this;
    let time = `${this.data.cur_year}-${this.data.cur_month}-01`;
    let postData = {
      time: time
    };
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo;
    re.request(api.get_signinList, postData, header).then((res) => {
      if (res.data.code > 0) {
        let signinList = [];
        for (let i = 0; i < res.data.data.length; i++) {
          signinList.push(that.formatDate(new Date(res.data.data[i].sign_in_time * 1000)));
        }

        let weekday = [];
        let time = [];
        for (let i = 1; i <= that.data.days.length; i++) {
          weekday = "days[" + (i - 1) + "].sign";
          time.push(that.data.cur_year + "-" + that.data.cur_month + "-" + that.data.days[i - 1].item);
          that.setData({
            [weekday]: 0
          })
        }
        for (let j = 0; j < signinList.length; j++) {
          that.data.days[time.indexOf(signinList[j])].sign = 1;
        }
        that.setData({
          days: that.data.days
        })
      }
    })
  },
  formatDate: function(now) {
    var year = now.getFullYear();
    var month = now.getMonth() + 1;
    var date = now.getDate();
    return year + "-" + month + "-" + date;
  },
  onLog: function() {
    wx.navigateTo({
      url: '../log/log'
    })
  },
  /**
   * 日历
   * 
   */
  setNowDate: function() {
    const date = new Date();
    const cur_year = date.getFullYear();
    const cur_month = date.getMonth() + 1;
    const todayIndex = date.getDate();

    const weeks_ch = ['日', '一', '二', '三', '四', '五', '六'];
    this.calculateDays(cur_year, cur_month);
    this.calculateLastMouthDays(cur_year, cur_month);
    this.calculateNextMouthDays(cur_year, cur_month);
    this.setData({
      cur_year: cur_year,
      cur_month: cur_month,
      week: weeks_ch,
      todayIndex,
    })
  },

  getThisMonthDays(year, month) {
    return new Date(year, month, 0).getDate();
  },
  getDaysMoust(year, month) {
    return new Date(year, month - 1, 0).getDate();
  },
  /**
   * 获取当月总共多少天
   */
  calculateDays(year, month) {
    let days = [];
    let weekday;
    const thisMonthDays = this.getThisMonthDays(year, month);

    for (let i = 1; i <= thisMonthDays; i++) {
      weekday = "days[" + (i - 1) + "].item"
      this.setData({
        [weekday]: i
      })
    }
  },
  /**
   * 获取上个月需要显示的天数
   */
  calculateLastMouthDays(year, month) {
    const lastMouthWeek = this.getDaysMoust(year, month);
    let week = [];
    for (let i = 0; i < lastMouthWeek; i++) {
      week.push(i + 1);
    }
    let day = this.data.firstDay;
    let lastDay = this.data.lastDay;
    var b = [];
    if (day !== 0) {
      b = week.slice(-day);
    } else if (day == 0 && (lastDay == 6 || lastDay == 0)){
      b = week.slice(-7);
    }

    this.setData({
      upperDays: b
    })
  },
  /**
   * 获取下个月需要显示的天数
   */
  calculateNextMouthDays(year, month) {
    const lastMouthWeek = this.getDaysMoust(year, month);
    let week = [];
    for (let i = 0; i < lastMouthWeek; i++) {
      week.push(i + 1);
    }
    let lastDay = this.data.lastDay;
    let firstDay = this.data.firstDay;
    let num;
    if (lastDay == 0) {
      if (firstDay == 1){
        num = 13;
      }else{
        num = 6;
      }
    } else if (lastDay == 1) {
      if (firstDay == 6) {
        num = 5;
      } else {
        num = 12;
      }
    } else if (lastDay == 2) {
      num = 11;
    } else if (lastDay == 3) {
      num = 10;
    } else if (lastDay == 4) {
      num = 9;
    } else if (lastDay == 5) {
      num = 8;
    } else if (lastDay == 6) {
      num = 7;
    }


    var b = week.slice(0, num);
    this.setData({
      nextDays: b
    })

  },
  handleCalendar(e) {
    const handle = e.currentTarget.dataset.handle;
    const cur_year = this.data.cur_year;
    const cur_month = this.data.cur_month;


    this.setData({
      days: []
    })

    let newMonth;
    let newYear;
    if (handle === 'prev') {
      newMonth = cur_month - 1;
      newYear = cur_year;
      if (newMonth < 1) {
        newYear = cur_year - 1;
        newMonth = 12;
      }

    } else {
      newMonth = cur_month + 1;
      newYear = cur_year;
      if (newMonth > 12) {
        newYear = cur_year + 1;
        newMonth = 1;
      }
    }

    this.calculateDays(newYear, newMonth);

    let firstDay = new Date(newYear, newMonth - 1, 1);
    this.data.firstDay = firstDay.getDay();
    let lastDay = new Date(newYear, newMonth, 0);
    this.data.lastDay = lastDay.getDay();
    this.setData({
      cur_year: newYear,
      cur_month: newMonth
    })
    this.calculateLastMouthDays(newYear, newMonth);
    this.calculateNextMouthDays(newYear, newMonth);
    this.getSigninList();

    if (this.data.month == newMonth && this.data.year == newYear) {
      this.setData({
        judge: 1
      })
    } else {
      this.setData({
        judge: 0
      })
    }
  },
  dataTime: function() {
    var date = new Date();
    var year = date.getFullYear();
    var month = date.getMonth();
    var months = date.getMonth() + 1;

    //获取现今年份
    this.data.year = year;

    //获取现今月份
    this.data.month = months;

    //获取今日日期
    this.data.getDate = date.getDate();

    //最后一天是星期几
    var d = new Date(year, months, 0);
    this.data.lastDay = d.getDay();

    //第一天星期几
    let firstDay = new Date(year, month, 1);
    this.data.firstDay = firstDay.getDay();
  },

  //是否有电话
  checkPhone: function () {
    let that = this;
    let have_mobile = wx.getStorageSync('have_mobile');
    if (have_mobile != true) {
      that.setData({
        phoneShow: true
      })
    }
    return have_mobile
  },

  //绑定手机结果返回
  phonereResult: function (e) {
    const that = this
    let result = e.detail.result;
    if (result == 'success') {
      that.loadData();
    }
  },

  

})