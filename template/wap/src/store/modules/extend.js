import { GET_EXTENDCODE, EXTEND_SUB } from "@/api/commission";
import { setSession, getSession, removeSession } from "@/utils/storage"

const extend = {
  state: {
    self_code: null,    // 自身推广码code
    sup_code: null,     // 上级推广码(分享进来的code)
    posterParams: null
  },
  mutations: {
    setExtendCode(state, code) {
      state.self_code = code
    },
    getSupCode(state, sup_code) {
      setSession('getSupCode', sup_code)
      state.sup_code = sup_code
    },
    removeSupCode(state) {
      // removeSession('getSupCode')
      state.sup_code = null
    },
    // 获取分享海报参数
    getSharePosterParams(state, params) {
      state.posterParams = params
    }
  },
  actions: {
    // 获取自身推广码
    getExtendCode(context) {
      return new Promise((resolve, reject) => {
        if (context.getters.token) {
          if (context.getters.extend_code) {
            resolve(context.getters.extend_code)
          } else {
            // 为分销商情况下才获取推广码
            context.dispatch('getMemberInfo', true).then(({ isdistributor }) => {
              if (isdistributor == 2) {
                GET_EXTENDCODE().then(({ data }) => {
                  resolve(data ? data.extend_code : null)
                  context.commit('setExtendCode', data ? data.extend_code : null)
                }).catch(error => {
                  resolve(null)
                })
              } else {
                resolve(null)
              }
            }).catch(() => {
              resolve(null)
            })
          }
        } else {
          resolve(null)
        }
      })
    },
    // 成为下线
    extendSub(context) {
      return new Promise((resolve, reject) => {
        if (context.getters.sup_code) {
          const params = {}
          params.extend_code = context.getters.sup_code
          if (context.state.posterParams) {
            params.poster_id = context.state.posterParams.poster_id
            params.poster_type = context.state.posterParams.poster_type
          }
          EXTEND_SUB(params).then(({ message }) => {
            context.commit('removeSupCode')
            resolve()
            console.log(message)
          }).catch(error => {
            reject(error)
          })
        }
      })
    }
  }
}

export default extend