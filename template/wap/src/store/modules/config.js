import { GET_CONFIG } from "@/api/config";
const config = {
  state: {
    wap_status: true,
    close_reason: null,
    base: null,
    addons: null,
    customForm: null,
    cancelList: [],  //取消请求的列表
    errorParam: {
      show: false,
      pageType: 'network',
      message: '哎呀~网络开了小差，重新加载试试',
      btnText: '重新加载',
      showFoot: true,
    }
  },
  mutations: {
    setWapStatus(state, status) {
      state.wap_status = status
    },
    setWapCloseReason(state, reason) {
      state.close_reason = reason
    },
    setConfig(state, data) {
      state.base = data
    },
    setAddons(state, data) {
      state.addons = data
    },
    setCustomForm(state, data) {
      state.customForm = data
    },
    cancelRequestList(state, cancel) {
      state.cancelList.push({ cancel })
    },
    showNetworkError(state, message) {
      state.errorParam.show = true
      if (message) state.errorParam.message = message
    }
  },
  actions: {
    getConfig({ commit }) {
      return new Promise((resolve, reject) => {
        GET_CONFIG().then(({ data }) => {
          commit('setConfig', data.config)
          commit('setAddons', data.addons)
          commit('setCustomForm', data.customform)
          resolve()
        }).catch(error => {
          reject(error)
        })
      })
    }
  }
}

export default config
