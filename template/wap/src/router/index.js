import Vue from 'vue'
import Router from 'vue-router'
import routes from './routes'
import store from '@/store'
import { Toast, Dialog } from 'vant'
import { setSession, getSession } from "@/utils/storage"
import { isIos, filterWxParams, isEmpty, analysLink } from '@/utils/util'
import LoadingBar from '@/components/loading-bar'

Vue.use(Router)

const router = new Router({
  mode: 'history',
  base: '/wap/',
  scrollBehavior (to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    } else {
      if (!from.meta.noKeepAlive) {
        from.meta.savedPosition = window.scrollY
      }
      return { x: 0, y: to.meta.savedPosition || 0 }
    }
  },
  routes
})

router.beforeEach((to, from, next) => {
  let cancelList = store.state.config.cancelList

  LoadingBar.start()

  cancelList.forEach((e, index) => {
    e.cancel('cancel')
    cancelList.splice(index, 1)
  })

  if (to.fullPath.indexOf('from=') != -1 || to.fullPath.indexOf('isappinstalled=') != -1) {
    location.assign('/wap' + filterWxParams(to.fullPath))
  }

  //判断是否第三方链接
  if (to.path.indexOf('http://') !== -1 || to.path.indexOf('https://') !== -1) {
    var link = to.fullPath.replace(to.fullPath.split('http')[0], '')
    window.location.href = link
    console.log(link)
    return false
  }

  // 获取分享进来的推广码
  if (to.query && to.query.extend_code) {
    store.commit('getSupCode', to.query.extend_code)
    // 分享海报参数
    if (to.query.poster_id && to.query.poster_type) {
      store.commit('getSharePosterParams', { poster_id: to.query.poster_id, poster_type: to.query.poster_type })
    }
  }

  if (store.getters.wap_status) {
    if (!store.getters.config) {
      store.dispatch('getConfig').then(() => {
        if (store.getters.config.wap_status == 1) {
          if (to.path == '/unopened') {
            next('/')
          } else {
            operate(to, from, next)
          }
        } else {
          next('/unopened')
        }
      })
    } else {
      if (store.getters.config.wap_status == 1) {
        operate(to, from, next)
      } else {
        next()
      }
    }
  } else {
    next()
  }

})

// 业务逻辑代码
function operate (to, from, next) {
  // 判断是否微信环境和已配置公众号
  if (store.state.isWeixin && !store.getters.config.is_wchat) {
    if (to.path == '/nowechat') {
      next()
    } else {
      next('/nowechat')
    }
  } else {
    // 判断是否登录状态
    if (store.getters.token) {
      if (to.name === 'login') {
        next('/')
      }
      if (store.getters.sup_code) {
        store.dispatch("extendSub");
      }
      getData(to, from, next)
    } else { // 未登录状态
      console.log('未登录状态')
      // 微信授权成功回调携带参数
      if (to.query.code && to.query.state == 'wchat') {
        store.dispatch("otherLogin", { action: 'login', form: { type: 'WCHAT', code: to.query.code } }).then(() => {
          Toast.success('登录成功')
          next({ replace: true, path: getSession("toPath") });
        })
      } else if (to.query.user_token && to.query.state == 'qq') {
        // qq授权获取user_token
        store.commit("setUserInfo", {
          user_token: to.query.user_token,
          have_mobile: true
        });
        Toast.success("登录成功");
        next({ replace: true, path: getSession("toPath") });
      } else {
        // 判断是否微信环境和已配置公众号
        if (store.state.isWeixin && store.getters.config.is_wchat) {
          // 进行微信登录
          setSession("toPath", to.fullPath)
          store.dispatch("otherLogin", { action: 'author', form: { type: 'WCHAT', redirect_url: to.fullPath } }).then(() => {
            Toast.success('登录成功')
            next({ replace: true, path: getSession("toPath") });
          })
        } else {
          // 判断是否需要登录
          if (to.meta.loginRequire) {
            Toast('您未登录，请先登录！')
            if (from.name == 'goods-detail' && store.getters.sup_code) {
              // 商品分享进来携带推广码，登录后需要跳转回商品详情进行分享逻辑
              setSession("toPath", from.fullPath)
            } else {
              setSession("toPath", to.name ? to.fullPath : "/");
            }
            return next('/login')
          } else {
            getData(to, from, next)
          }
        }
      }
    }
  }
}

// 获取相关数据
function getData (to, from, next) {
  if (to.meta.title) {
    document.title = to.meta.title
  }
  /**
   * 平台设置需要存在手机号情况下才能访问的路由
   * isBingFlag 为平台设置的是否开启手机号开关
   * mobileRequire 需要存在手机号才能访问的路由（需判断平台是否关闭绑定手机号状态isBingFlag）
   * isBindMobile 是否绑定过手机号
   */
  if (store.getters.token && to.meta.mobileRequire && (store.getters.isBingFlag && !store.getters.isBindMobile)) {
    LoadingBar.finish()
    Vue.prototype.$BindMobile.open()
    next(false)
  } else {
    store.dispatch('getTabbar').then(() => {
      store.commit('isShowTabbar', to);
      if (store.getters.token) {
        store.dispatch('getMemberInfo', true).then(() => {
          var textRoute = ['commission', 'bonus', 'channel', 'microshop', 'agent']  // 分销、分红、渠道商、微店等应用需要获取后台设置文案
          // 登录情况下商品详情需要显示佣金相关字眼
          if (to.name == 'goods-detail') {
            textRoute.push(to.name);
          }
          if (textRoute.some((item) => to.name.indexOf(item) != -1)) {
            store.dispatch('getCommissionSetText');
            to.name.indexOf('bonus') != -1 && store.dispatch('getBonusSetText');
          }
          next()
        })
      } else {
        next()
      }
      store.dispatch('getMemberSetText'); // 获取会员设置文案
    })
  }
}

router.afterEach((to, from) => {
  LoadingBar.finish()
})

export default router
