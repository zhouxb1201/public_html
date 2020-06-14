import { GET_ASSEMBLEDETAIL } from "@/api/assemble";

const assemble = {
  state: {
    detail: {}
  },
  mutations: {
    setAssembleDetail(state, data) {
      state.detail = data
    }
  },
  actions: {
    getAssembleDetail({ commit }, params) {
      return new Promise((resolve, reject) => {
        GET_ASSEMBLEDETAIL(params).then(res => {
          // commit('setAssembleDetail', res.data ? res.data : {})
          resolve(res.data ? res.data : {})
        }).catch(error => {
          reject(error)
        })
      })
    }
  }
}

export default assemble