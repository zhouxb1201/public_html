import Vue from 'vue'
import axios from 'axios'
import store from '@/store'
import router from '@/router'

import { authSign } from '@/utils/util'
import { setSession } from "@/utils/storage"
import { Toast, Dialog } from 'vant'


// 创建axios实例
const service = axios.create({
  baseURL: process.env.NODE_ENV === 'development' ? '/api' : '/wapstore', // api的base_url
  timeout: 300000 // request timeout
})

const CancelToken = axios.CancelToken;
var cancel;

var toastLoading = null;

// request 拦截器
service.interceptors.request.use(config => {
  // 需要显示加载状态的请求
  if (config.isShowLoading) {
    // Toast.allowMultiple()
    toastLoading = Toast.loading({
      forbidClick: true,
      duration: config.timeout
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
  })

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
      router.push('/unopened');
    } else if (code === -1000) {
      Dialog.alert({
        message: data.message
      }).then(() => {
        store.dispatch('fedLogout').then(() => {
          router.push('/login')
        })
      })
    } else if (code === -1009) {
      Toast(data.message)
      store.commit('removeStoreId')
      router.push('/store')
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
    config
  } = error
  if (config.isShowLoading) {
    toastLoading.clear();
  }
  // 请求失败提示
  let message = '网络请求错误！'
  if (error.response) {
    if (error.response.status === 500) {
      message = '接口请求出错！'
    } else if (error.response.status === 404) {
      message = '接口文件不存在！'
    }
  } else if (error.request) {
    console.log(error.request);
  } else {
    console.log('Error', error.message);
  }
  Dialog.alert({
    message
  })
  return Promise.reject(error)
})

const $get = (url, params, config = {}) => {
  return service.get(url, {
    params: params,
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
  return axios.all(arr).then(
    axios.spread((acct, perms) => {
      // 两个请求现在都执行完成
      // console.log(acct,perms)
    })
  );
}

Vue.prototype.$get = $get
Vue.prototype.$post = $post
Vue.prototype.$all = $all

export default service
