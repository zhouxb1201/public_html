import { GET_ACCOUNTINFO, SET_ACCOUNTINFO } from "@/api/member";
import { UPLOAD_IMAGES } from "@/api/config"

const account = {
  state: {
    info: {}
  },
  mutations: {
    getAccountInfo(state, info) {
      state.info = info
    },
    setAvatar(state, src) {
      state.info.avatar = src
    }
  },
  actions: {
    // 获取账号基本信息
    getAccountInfo({ commit }, params) {
      return new Promise((resolve, reject) => {
        GET_ACCOUNTINFO().then(res => {
          resolve(res)
          commit('getAccountInfo', res.data)
        }).catch(error => {
          reject(error)
        })
      })
    },
    // 上传图片及头像
    uploadImages({ commit }, file) {
      return new Promise((resolve, reject) => {
        UPLOAD_IMAGES(file).then(res => {
          resolve(res)
        }).catch(error => {
          reject(error)
        })
      })
    }
  }
}

export default account