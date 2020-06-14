import { GET_SHOPAPPLYSTATE } from "@/api/shop";

const shop = {
  state: {
    applyState: null,
    shopManageUrl: null
  },
  mutations: {
    setShopApplyState(state, status) {
      state.applyState = status
    },
    setShopApplyManageUrl(state, url) {
      state.shopManageUrl = url
    }
  },
  actions: {
    /**
     * 获取店铺申请状态
     */
    getShopApplyState(context) {
      return new Promise((resolve, reject) => {
        if (context.rootState.config.addons.shop == 1) {
          GET_SHOPAPPLYSTATE().then(({ data }) => {
            context.commit('setShopApplyState', data.status)
            if (data.status == 'is_system') {
              context.commit('setShopApplyManageUrl', data.url)
            }
            resolve(data)
          }).catch(() => {
            reject()
          })
        } else {
          reject('addons')
        }
      })
    }
  }
}

export default shop
