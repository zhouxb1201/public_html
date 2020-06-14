import wx from "weixin-js-sdk";
import { GET_WXCONFIG } from '@/api/config'
import { Toast, Dialog } from "vant";
const share = {
  state: {
    shareUrl: null,
    jsApiList: [
      'hideMenuItems',
      'translateVoice',
      'updateAppMessageShareData',
      'updateTimelineShareData',
      'onMenuShareAppMessage',
      'onMenuShareTimeline',
      'chooseImage',
      'getLocalImgData',
      'getLocation',
      'scanQRCode',
      'chooseWXPay',
      'openAddress'
    ]
  },
  mutations: {},
  actions: {
    wxConfig(context) {
      return new Promise((resolve, reject) => {
        GET_WXCONFIG(encodeURIComponent(window.location.href.split('#')[0])).then(({ data }) => {
          wx.config({
            debug: false,
            appId: data.appId,
            timestamp: data.timestamp,
            nonceStr: data.nonceStr,
            signature: data.signature,
            jsApiList: context.state.jsApiList
          })
          wx.ready(() => {
            resolve()
          });
          wx.error((error) => {
            // Toast({ message: JSON.stringify(error), duration: 10000 })
            // console.log(error)
            reject(error)
          });
        })
      })
    },
    wxShare(context, params) {
      if (context.rootState.isWeixin) {
        context.dispatch('wxConfig').then(() => {
          wx.hideMenuItems({
            menuList: ['menuItem:copyUrl']
          });
          wx.updateAppMessageShareData(params)
          wx.updateTimelineShareData(params)
        })
      }
    },
    scanQRCode(context, callback) {
      if (context.rootState.isWeixin) {
        return new Promise((reslove, reject) => {
          context.dispatch('wxConfig').then(() => {
            wx.scanQRCode({
              needResult: 1,
              scanType: ["qrCode", "barCode"],
              success: function (res) {
                reslove(res.resultStr)
              }, 
              fail: function (res) {
                
              }
            });
          }).catch(() => {
            reject()
          })
        })
      }
    }
  }
}

export default share
