import { LOGIN, LOGOUT, GET_MSGCODE, RESET_PASSWORD, OTHER_LOGIN, VALID_MOBILE, BIND_ACCOUNT } from '@/api/user'
import { SET_TOKEN, GET_TOKEN, REMOVE_TOKEN } from '@/utils/auth'
import { getSession } from "@/utils/storage";
import { Toast, Dialog } from "vant";
import router from '@/router'
const user = {
  state: {
    token: GET_TOKEN(),
    name: null,
    avatar: null
  },
  mutations: {
    setUserInfo(state, info) {
      state.token = info.user_token
      SET_TOKEN(info.user_token)
    },
    removeUserInfo(state, info) {
      state.token = info.user_token
      REMOVE_TOKEN()
    }
  },
  actions: {
    // 登录
    login(context, form) {
      return new Promise((resolve, reject) => {
        LOGIN(form).then(res => {
          if (res.code === 0) {
            Toast(res.message)
          } else {
            context.commit('setUserInfo', res.data)
            Toast.success(res.message)
            router.replace(context.getters.getToPath)
          }
          resolve(res)
        }).catch(error => {
          reject(error)
        })
      })
    },
    // 获取短信验证码
    getMsgcode(context, { type, form }) {
      return new Promise((resolve, reject) => {
        function fn() {
          GET_MSGCODE(form).then(res => {
            Toast.success(res.message)
            resolve(res)
          }).then(error => {
            reject(error)
          })
        }
        if (type == 'bind') {
          context.dispatch("validMobile", form.mobile).then(() => {
            fn()
          })
        } else {
          fn()
        }
      })
    },
    // 退出登录
    logout({ commit }) {
      return new Promise((resolve, reject) => {
        Dialog.confirm({
          message: '是否退出登录?'
        }).then(() => {
          LOGOUT().then(res => {
            Toast.success(res.message)
            commit('removeUserInfo', {})
            commit('removeStoreId')
            resolve(res)
          }).catch(error => {
            reject(error)
          })
        })
      })
    },
    // 登录信息过期退出
    fedLogout({ commit }) {
      return new Promise((resolve, reject) => {
        LOGOUT().then(res => {
          commit('removeUserInfo', {})
          commit('removeStoreId')
          resolve(res)
        }).catch(error => {
          reject(error)
        })
      })
    },
    // 重置密码 
    resetPassword({ commit }, form) {
      return new Promise((resolve, reject) => {
        RESET_PASSWORD(form).then(res => {
          Toast.success(res.message)
          commit('setUserInfo', {
            user_token: '',
            user_name: '',
            user_headimg: ''
          })
          REMOVE_TOKEN()
          resolve(res)
        }).then(error => {
          reject(error)
        })
      })
    },
    /**
     * 第三方登录
     * @param {*} action 登录方式  
     * author ==> 授权
     * login ==> 登录
     * relevant ==> 关联
     * @param {*} form 请求参数  
     */
    otherLogin(context, { action, form }) {
      return new Promise((resolve, reject) => {
        OTHER_LOGIN(form).then(res => {
          if (res.code === 1) {
            context.commit('setUserInfo', res.data)
            Toast.success(res.message)
            router.replace(context.getters.getToPath)
            resolve()
          } else if (res.code === 2) {
            Toast(res.message);
            router.push("/bind");
          } else if (res.code === 3) {
            Toast.fail(res.message);
          } else if (res.code === 4) {
            window.location.href = res.data.url
          }
        }).catch(error => {
          reject(error)
        })
      })
    },
    // 验证手机号是否能关联
    validMobile(context, mobile) {
      return new Promise((resolve, reject) => {
        VALID_MOBILE(mobile).then(res => {
          if (res.code == 1) {
            resolve()
          } else {
            Toast(res.message)
            reject()
          }
        }).catch(() => {
          reject()
        })
      })
    },
    // 绑定账号
    bindAccount(context, form) {
      return new Promise((resolve, reject) => {
        BIND_ACCOUNT(form).then(res => {
          context.commit('setUserInfo', res.data)
          Toast.success(res.message)
          resolve()
          router.replace(context.getters.getToPath)
        }).catch(() => {
          reject()
        })
      })
    }
  }
}

export default user
