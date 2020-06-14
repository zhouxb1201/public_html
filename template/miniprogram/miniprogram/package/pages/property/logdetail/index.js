var requestSign = require('../../../../utils/requestData.js');
var api = require('../../../../utils/api.js').open_api;
var header = getApp().header;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    //流水id
    waterid:'',
    tip_text:'',
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    let id = options.id;
    this.data.waterid = id;
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
    that.balanceDetail();
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

  //余额明细
  balanceDetail: function () {
    const that = this;
    let postData = {
      'id': that.data.waterid
    }
    let datainfo = requestSign.requestSign(postData);
    header.sign = datainfo
    wx.request({
      url: api.get_balanceDetail,
      data: postData,
      header: header,
      method: 'POST',
      dataType: 'json',
      responseType: 'text',
      success: (res) => {
        if (res.data.code >= 0) {
          let detailData = res.data.data;
          that.setData({
            type_name:detailData.type_name,
            change_money: detailData.change_money
          })
          that.dataToArr(detailData);
          let tip_text =
            "提示：提现过程中，提现金额将暂时进入冻结余额，提现成功后该笔提现的冻结余额将会扣除，如果提现失败则冻结余额解冻，该笔提现不成立。";
          if (detailData.from_type == 8){
            that.setData({
              tip_text: tip_text
            })
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

  statuName(state) {
    let name = "处理中";
    if (state == -1 || state == 4) {
      name = "失败";
    } else if (state == 3) {
      name = "成功";
    }
    return name;
  },
  statuColor(state) {
    let name = "#ff9900";
    if (state == -1 || state == 4) {
      name = "#ff454e";
    } else if (state == 3) {
      name = "#4b0";
    }
    return name;
  },

  dataToArr(data){
    let arr = [
      {
        title: "交易单号",
        value: data.records_no
      },
      {
        title: "时间",
        value: data.create_time
      },
    ];
    if (data.from_type == 8) {
      arr.push(
        {
          title:"状态",
          value: this.statuName(data.status),
          color: this.statuColor(data.status),
        },
        {
          title:"提现金额",
          value: "¥" + data.number,
        },
        {
          title: "手续费",
          value: "¥" +  data.charge,
        },
        {
          title: "余额",
          value: "¥" +  data.balance,
        }
      )
    }else{
      arr.push({
        title: "余额",
        value: "¥" + data.balance
      });
    }

    if (data.msg) {
      arr.push({
        title: "理由",
        value: data.msg
      });
    }
    this.setData({
      arr:arr
    })
    console.log(arr);
  }
})