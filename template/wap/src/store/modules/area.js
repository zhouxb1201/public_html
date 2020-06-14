import { GET_AREA } from "@/api/address";
import { isEmpty } from "@/utils/util";
import { Toast } from "vant";
const area = {
  state: {
    list: {},
    loading: ''
  },
  mutations: {
    loadArea(state) {
      state.loading = Toast.loading({
        message: "获取省市区",
        duration: 0,
        forbidClick: true,
        loadingType: "circular"
      });
    },
    setArea(state, data) {
      state.list = data
      if (state.loading) {
        state.loading.clear()
      }
    },
  },
  actions: {
    getArea(context, showLoading) {
      return new Promise((resolve, reject) => {
        if (isEmpty(context.state.list)) {
          if (showLoading) {
            context.commit('loadArea')
          }
          GET_AREA().then(res => {
            context.commit('setArea', res.data)
            resolve(res.data)
          }).catch(error => {
            reject(error)
          })
        } else {
          context.commit('setArea', context.state.list)
          resolve(context.state.list)
        }
      })
    }
  }
}

export default area
