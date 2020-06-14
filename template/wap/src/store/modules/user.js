import {
  LOGIN,
  REGISTER,
  GET_MSGCODE,
  GET_EMAILCODE,
  IS_HASMOBILE,
  RESET_PASSWORD,
  OTHER_LOGIN,
  LOGOUT,
  BIND_ACCOUNT
} from '@/api/user'
import {
  SET_TOKEN,
  GET_TOKEN,
  REMOVE_TOKEN,
  SET_BINDMOBILE,
  GET_BINDMOBILE,
  REMOVE_BINDMOBILE
} from '@/utils/auth'
import { getSession } from "@/utils/storage";
import {
  Toast,
  Dialog
} from "vant";
import router from '@/router'
const user = {
  state: {
    token: GET_TOKEN(),
    name: null,
    avatar: null,
    openid: null,
    isBindMobile: GET_BINDMOBILE() ? Number(GET_BINDMOBILE()) : 0
  },
  mutations: {
    setUserInfo(state, info) {
      state.token = info.user_token
      state.name = info.user_name
      state.avatar = info.user_headimg
      state.openid = info.openid
      state.isBindMobile = info.have_mobile ? 1 : 0
      SET_TOKEN(info.user_token)
      SET_BINDMOBILE(state.isBindMobile)
    },
    setBindMobile(state, have_mobile) {
      state.isBindMobile = have_mobile ? 1 : 0
      SET_BINDMOBILE(state.isBindMobile)
    },
    removeUserInfo(state, info) {
      state.token = info.user_token
      state.name = info.user_name
      state.avatar = info.user_headimg
      state.openid = ''
      state.isBindMobile = 0
      REMOVE_TOKEN()
      REMOVE_BINDMOBILE()
    }
  },
  actions: {
    // 登录
    login({ commit }, form) {
      return new Promise((resolve, reject) => {
        form.mall_port = 3
        LOGIN(form).then(res => {
          if (res.code === 0) {
            Toast.allowMultiple();
            Toast(res.message)
          } else {
            commit('setUserInfo', res.data)
            Toast.success(res.message)
          }
          resolve(res)
        }).catch(error => {
          reject(error)
        })
      })
    },
    // 注册 
    register(context, form) {
      const mobile = form.mobile
      return new Promise((resolve, reject) => {
        IS_HASMOBILE(mobile, 3).then((e) => {     // 判断是否已注册手机号
          if (e.code === 0) {
            form.mall_port = 3
            REGISTER(form).then(res => {
              context.commit('setUserInfo', res.data)
              // 注册时有推广码注册成功则删除该推广码
              if (form.extend_code) {
                context.commit('removeSupCode')
              }
              Toast.success(res.message)
              resolve(res)
            }).catch(error => {
              reject(error)
            })
          } else {
            Toast('该手机号码已被注册！')
            reject()
          }
        }).catch(() => {
          reject()
        })
      })
    },
    // 获取短信验证码
    getMsgcode(context, form) {
      const mobile = form.mobile  // 获取输入的手机号
      return new Promise((resolve, reject) => {
        if (context.getters.config.mobile_verification) {
          if (form.type === 'register') {     //登录时发送验证码
            IS_HASMOBILE(mobile, 3).then((e) => {     // 判断是否已注册手机号
              if (e.code === 0) {                     // 1 为存在手机号  0为 不存在手机号
                GET_MSGCODE(form).then(res => {
                  Toast.success(res.message)
                  resolve(res)
                }).catch(error => {
                  reject(error)
                })
              } else {
                Toast('该手机号码已被注册！')
              }
            })
          } else if (form.type === 'login' || form.type === 'forget_password') {
            IS_HASMOBILE(mobile, 3).then((e) => {     // 判断是否已注册手机号
              if (e.code === 1) {
                GET_MSGCODE(form).then(res => {
                  Toast.success(res.message)
                  resolve(res)
                }).catch(error => {
                  reject(error)
                })
              } else {
                Toast(e.message)
              }
            })
          } else if (form.type === 'bind_email' || form.type === 'change_pay_password' || form.type === 'change_password' || form.type === 'update_mobile') {
            // 修改资料
            if (form.type == 'update_mobile') {
              form.type = 'bind_mobile'
            }
            GET_MSGCODE(form).then(res => {
              Toast.success(res.message)
              resolve(res)
            }).catch(error => {
              reject(error)
            })
          } else if (form.type === 'bind_mobile') {
            // 绑定手机号
            const port = context.rootState.isWeixin && context.getters.config.is_wchat ? 1 : 3; //判断端口
            IS_HASMOBILE(mobile, port).then((e) => {
              GET_MSGCODE(form).then(res => {
                Toast.success(res.message)
                resolve({ isHasMobile: e.code, msg: e.message })
              }).catch(error => {
                reject(error)
              })
            })
          }
        } else {
          Toast('商城未开启短信模版')
        }
      })
    },
    // 邮箱验证码
    getEmailcode({ commit }, form) {
      return new Promise((resolve, reject) => {
        if (form.type === 'bind_email') {
          GET_EMAILCODE(form).then(res => {
            Toast.success(res.message)
            resolve(res)
          }).catch(error => {
            reject(error)
          })
        }
      })
    },
    // 重置密码 
    resetPassword({ commit }, form) {
      return new Promise((resolve, reject) => {
        form.mall_port = 3
        RESET_PASSWORD(form).then(res => {
          Toast.success(res.message)
          commit('setUserInfo', {
            user_token: '',
            user_name: '',
            user_headimg: ''
          })
          REMOVE_TOKEN()
          resolve(res)
        }).catch(error => {
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
        const params = form
        const method = action == 'author' ? 'post' : 'get'
        if (context.getters.sup_code) {
          params.extend_code = context.getters.sup_code
        }
        OTHER_LOGIN(params, method).then(res => {
          if (res.code === 1) {
            context.commit('setUserInfo', res.data)
            // 微信授权时有推广码注册成功则删除该推广码
            if (params.extend_code) {
              context.commit('removeSupCode')
            }
            resolve()
          } else if (res.code === 2) {
            Toast(res.message);
            // router.push("/bind/account");
          } else if (res.code === 3) {
            Toast.fail(res.message);
          } else if (res.code === 4) {
            // router.push(res.data.url);
            location.replace(res.data.url);
          }
        }).catch(error => {
          reject(error)
        })
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
          resolve(res)
        }).catch(error => {
          reject(error)
        })
      })
    },
    // 关联账户
    bindAccount(context, form) {
      return new Promise((resolve, reject) => {
        BIND_ACCOUNT(form).then(res => {
          if (res.code === 1) {
            context.commit('setUserInfo', res.data)
            Toast.success("关联成功")
            resolve(res)
          } else {
            Toast(res.message)
            reject()
          }
        }).catch(error => {
          reject(error)
        })
      })
    }
  }
}

export default user
