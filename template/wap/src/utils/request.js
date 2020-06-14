import Vue from 'vue'
import axios from 'axios'
import store from '@/store'
import router from '@/router'

import { authSign } from '@/utils/util'
import { setSession } from "@/utils/storage"
import { Toast, Dialog } from 'vant'

// 创建axios实例
const service = axios.create({
  baseURL: process.env.NODE_ENV === 'development' ? '/api' : '/wapapi', // api的base_url
  timeout: 30000, // request timeout
  retry: 0, // 请求出错错误次数限制
  retryDelay: 800  // 请求出错延迟重新请求时间
})

const CancelToken = axios.CancelToken;
var cancel;

var toastLoading = null;

// request 拦截器
service.interceptors.request.use(config => {

  // 需要显示加载状态的请求
  if (config.isShowLoading) {
    toastLoading = Toast.loading({
      forbidClick: true,
      duration: config.timeout,
      message: config.loadingText || ''
    });
  }

  config.headers.post['Content-Type'] = 'application/x-www-form-urlencoded';
  config.headers['X-Requested-With'] = 'XMLHttpRequest';
  config.headers['sign'] = authSign(config.data);
  if (store.getters.token) {
    config.headers['user-token'] = store.getters.token
  }

  // 取消请求
  config.cancelToken = new CancelToken(callback => {
    cancel = callback;
    !config.noCancel && store.commit('cancelRequestList', callback)
  })

  // 操作写入数据时，需绑定账号
  if (config.isWriteIn) {
    if (!store.getters.token) {
      setSession("toPath", router.currentRoute.fullPath)
      cancel({ msg: '您未登录，请先登录！', code: 1 })
    } else if (store.getters.isBingFlag && !store.getters.isBindMobile) {
      setSession("toPath", router.currentRoute.fullPath)
      cancel({ msg: '请先绑定账号！', code: 2 })
    }
  }

  return config
}, error => {
  // 请求错误执行
  console.log(error) // for debug
  Promise.reject(error)
})

// respone 拦截器
service.interceptors.response.use(response => {

  const {
    data, config
  } = response
  const code = parseInt(data.code)

  if (config.isShowLoading) {
    toastLoading.clear();
  }

  if (code >= 0) {
    // 成功返回
    return Promise.resolve(data)
  } else {
    // 错误提示
    if (code === -10000 || code === -10001) {
      // Toast(data.message);
      store.commit('setWapStatus', false);
      store.commit('setWapCloseReason', data.message);
      store.commit('isShowTabbar', false);
      router.push('/unopened');
    } else if (code === -1000) {
      if (store.state.isWeixin && store.getters.config.is_wchat) {
        store.dispatch('fedLogout').then(() => {
          location.reload()
        })
      } else {
        Dialog.alert({
          message: data.message
        }).then(() => {
          setSession("toPath", router.currentRoute.fullPath)
          store.dispatch('fedLogout').then(() => {
            router.push('/login')
          })
        })
      }
    } else {
      Dialog.alert({
        message: data.message
      }).then(() => {
        if (config.errorCallback) {
          router.back()
        }
        console.log('confirm--cancel')
      })
    }
    return Promise.reject(data)
  }
}, error => {
  const {
    config, response, request
  } = error

  let isRetry = true    // 是否重试
  let message = ''

  if (toastLoading) {
    toastLoading.clear();
  }

  // timeout为0时不重复请求
  if (!config || !config.retry || !config.timeout) isRetry = false

  // 请求失败提示
  if (response) {
    if (response.status === 500) {
      message = config.url + '接口出错！'
    } else if (response.status === 502) {
      message = '接口请求失败！'
    } else if (response.status === 404) {
      message = '接口文件不存在！'
      isRetry = false
    }
    Toast.clear()
  } else if (error.message) {
    if (error.message.code) {
      Toast(error.message.msg)
      if (error.message.code === 1) {
        router.push('/login');
      } else if (error.message.code === 2) {
        Vue.prototype.$BindMobile.open()
      }
      return Promise.reject(error.message.msg)
    } else if (error.message.includes('timeout')) {
      console.log('网络请求超时！')
    }
  }
  if (!isRetry) {
    config.showError && store.commit('showNetworkError', message);
    return Promise.reject(error);
  }

  // 请求超时或接口出错时重新请求
  config.__retryCount = config.__retryCount || 0;

  if (config.__retryCount >= config.retry) {
    Dialog.alert({ message })
    return Promise.reject(error);
  }
  config.__retryCount += 1;

  var backoff = new Promise(function (resolve) {
    setTimeout(function () {
      resolve(config);
    }, config.retryDelay || 1);
  });
  return backoff.then(config => {
    config.baseURL = ''
    if (config.data) {
      config.data = JSON.parse(config.data);
    }
    return service(config);
  });

})

const $get = (url, params, config = {}) => {
  return service.get(url, {
    params,
    ...config
  })
}

const $post = (url, data, config = {}) => {
  return service.post(url, data, config)
}

/**
 * 同时发起多个请求时的处理
 * @param arr [fn1(),fn2()]
 */
const $all = (arr) => {
  return service.all(arr).then(
    service.spread((acct, perms) => {
      // 两个请求现在都执行完成
      // console.log(acct,perms)
    })
  );
}

Vue.prototype.$get = $get
Vue.prototype.$post = $post
Vue.prototype.$all = $all

export default service
