import { GET_ACCOUNTINFO } from "@/api/account";
import { UPLOAD_IMAGES } from '@/api/config'

const account = {
  state: {
    info: null,
    avatar: null,
    jobsOperate: []
  },
  getters: {

  },
  mutations: {
    setAccountInfo(state, info) {
      state.info = info
      state.jobsOperate = info.jobs_info ? info.jobs_info.operation : []
    },
    setAvatar(state, src) {
      state.avatar = src
    }
  },
  actions: {
    // 获取账号基本信息
    getAccountInfo(context) {
      return new Promise((resolve, reject) => {
        const fn = function () {
          GET_ACCOUNTINFO().then(res => {
            resolve(res.data)
            context.commit('setAccountInfo', res.data)
            context.commit('setAvatar', res.data.assistant_headimg)
          }).catch(error => {
            reject(error)
          })
        }
        if (context.state.info) {
          if (context.state.info.store_info.store_id == context.getters.store_id) {
            resolve(context.state.info)
            context.commit('setAccountInfo', context.state.info)
          } else {
            fn()
          }
        } else {
          fn()
        }
      })
    },
    // 上传图片及头像
    uploadImages({ commit }, file) {
      return new Promise((resolve, reject) => {
        // const type = file.get('type')
        UPLOAD_IMAGES(file).then(res => {
          resolve(res)
          // if (type == 'avatar') {
          commit('setAvatar', res.data.src)
          // }
        }).catch(error => {
          reject(error)
        })
      })
    }
  }
}

export default account