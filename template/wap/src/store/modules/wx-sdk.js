import Vue from 'vue';
import wx from "weixin-js-sdk";
import { GET_WXCONFIG } from '@/api/config'
import { GET_WXCARDPARAMS, GET_WXCARDSTATE } from "@/api/consumercard";
import { Toast, Dialog } from "vant";
import { PAY_WECHAT } from "@/api/pay";
import { PAY_INTEGRALPAY } from "@/api/integral";
import { isIos, base64toFile } from '@/utils/util'

const share = {
  state: {
    jsApiList: [
      'hideMenuItems',
      'translateVoice',
      'updateAppMessageShareData',
      'updateTimelineShareData',
      'onMenuShareAppMessage',
      'onMenuShareTimeline',
      'chooseImage',
      'uploadImage',
      'downloadImage',
      'getLocalImgData',
      'getLocation',
      'scanQRCode',
      'chooseWXPay',
      'addCard',
      'openAddress'
    ]
  },
  actions: {
    wxConfig(context) {
      return new Promise((resolve, reject) => {
        if (context.rootState.isWeixin && context.getters.config.is_wchat) {
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
              // Toast({ message: JSON.stringify(error), duration: 2000 })
              console.log(error)
              reject(error)
            });
          })
        } else {
          reject()
        }
      })
    },
    wxShare(context, params) {
      return new Promise((reslove, reject) => {
        context.dispatch('wxConfig').then(() => {
          // console.log('wxShare==',params)
          wx.updateAppMessageShareData({
            title: params.title||'分享商城',
            desc: params.desc||'分享商城',
            imgUrl: params.imgUrl || (context.rootState.domain + Vue.prototype.$BASEIMGPATH + 'no-square.png'),
            link: params.link||context.rootState.domain,
            success() {
              // 设置成功
              reslove()
            },
            fail() {
              // 设置失败也返回成功
              reslove()
            }
          })
          wx.updateTimelineShareData(params)
        }).catch(() => {
          // 配置失败也返回成功
          reslove()
        })
      })
    },
    wxPay(context, out_trade_no) {
      return new Promise((reslove, reject) => {
        PAY_WECHAT(out_trade_no, context.rootState.isWeixin && context.getters.config.is_wchat ? 1 : 2).then(({ data }) => {
          if (data.mweb_url) {
            // h5微信支付
            reslove({ type: 'h5' })
            window.location.href = data.mweb_url
          } else {
            // 微信内唤起微信支付
            context.dispatch('wxConfig').then(() => {
              wx.chooseWXPay({
                timestamp: data.timeStamp,
                nonceStr: data.nonceStr,
                package: data.package,
                signType: data.signType,
                paySign: data.paySign,
                success(res) {
                  Toast.success('支付成功')
                  reslove({ type: 'wechat', result: 'success', data: res })
                },
                fail(res) {
                  Toast.fail('支付失败')
                  Dialog.alert({ title: 'error', message: JSON.stringify(res) })
                  reject({ type: 'wechat', result: 'fail', data: res })
                },
                cancel(res) {
                  Toast('取消支付')
                  reject()
                }
              })
            }).catch(() => {
              reject()
            })
          }
        }).catch(() => {
          reject()
        })
      })
    },
    wxIntegralPay(context, orderData) {
      return new Promise((reslove, reject) => {
        PAY_INTEGRALPAY(orderData).then(({ data }) => {
          if (data.mweb_url) {
            // h5微信支付
            reslove({ type: 'h5' })
            window.location.href = data.mweb_url
          } else {
            // 微信内唤起微信支付
            context.dispatch('wxConfig').then(() => {
              wx.chooseWXPay({
                timestamp: data.timeStamp,
                nonceStr: data.nonceStr,
                package: data.package,
                signType: data.signType,
                paySign: data.paySign,
                success(res) {
                  Toast.success('支付成功')
                  reslove({ type: 'wechat', result: 'success', data: res, out_trade_no: data.out_trade_no })
                },
                fail(res) {
                  Toast.fail('支付失败')
                  Dialog.alert({ title: 'error', message: JSON.stringify(res) })
                  reject({ type: 'wechat', result: 'fail', data: res, out_trade_no: data.out_trade_no })
                },
                cancel(res) {
                  Toast('取消支付')
                  reject()
                }
              })
            }).catch(() => {
              reject()
            })
          }
        }).catch(() => {
          reject()
        })
      })
    },
    wxGetLocation(context) {
      return new Promise((reslove, reject) => {
        context.dispatch('wxConfig').then(() => {
          wx.getLocation({
            type: 'wgs84',
            success({ latitude, longitude }) {
              reslove({ lat: latitude, lng: longitude })
            },
            fail(res) {
              reject('获取当前位置失败！')
            }
          });
        }).catch(() => {
          reject()
        })
      })
    },
    wxAddCard(context, params) {
      return new Promise((reslove, reject) => {
        context.dispatch('wxConfig').then(() => {
          GET_WXCARDPARAMS(params).then(({ data }) => {
            wx.addCard({
              cardList: data.cardList,
              success(res) {
                GET_WXCARDSTATE(params).then(() => {
                  reslove(res)
                }).catch(() => {
                  reject()
                })
              },
              fail(error) {
                reject()
              }
            })
          }).catch(() => {
            reject()
          })
        }).catch(() => {
          reject()
        })
      })
    },
    wxChooseImage(context, count) {
      return new Promise((reslove, reject) => {
        context.dispatch('wxConfig').then(() => {
          wx.chooseImage({
            count,
            sizeType: ['original', 'compressed'],
            sourceType: ['album', 'camera'],
            success({ localIds }) {
              Promise.all(localIds.map((localId) => context.dispatch('wxGetLocalImgData', localId))).then(contents => {
                reslove(contents)
              }).catch((error) => {
                alert('catch', error)
              })
            },
            fail(error) {
              reject()
            }
          });
        }).catch(() => {
          reject()
        })
      })
    },
    wxGetLocalImgData(context, localId) {
      return new Promise((reslove, reject) => {
        context.dispatch('wxConfig').then(() => {
          wx.getLocalImgData({
            localId,
            success({ localData }) {
              let obj = {}
              var base64Url = ''
              if (isIos()) {
                base64Url = localData.replace('data:image/jgp', 'data:image/jpeg')
              } else {
                base64Url = "data:image/jpeg;base64," + (localData.replace(/\r|\n/g, '').replace('data:image/jgp', 'data:image/jpeg'))
              }
              obj.file = typeof (base64toFile(base64Url, 'jpg'))
              obj.src = base64Url
              reslove(obj)
            },
            fail(error) {
              reject('error', error)
            }
          });
        })
      })
    },
    wxScanQRCode(context, needResult = 1) {
      return new Promise((reslove, reject) => {
        context.dispatch('wxConfig').then(() => {
          wx.scanQRCode({
            needResult, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
            scanType: ["qrCode", "barCode"], // 可以指定扫二维码还是一维码，默认二者都有
            success({ resultStr }) {
              reslove(resultStr)
            },
            fail(error) {
              reject(error)
            }
          });
        }).catch(() => {
          reject()
        })
      })
    }
  }
}

export default share
