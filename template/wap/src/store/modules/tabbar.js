import { analysPath } from '@/utils/util'
const app = {
  state: {
    data: null,
    isShowTabbar: false,
    activeTabbar: 0
  },
  mutations: {
    setTabbar(state, data) {
      state.data = data
    },
    isShowTabbar(state, route) {
      let tabbar = state.data
      let flag = false
      let index = 0
      for (let i in tabbar) {
        var path = analysPath(tabbar[i].path) == '/mall/index' || analysPath(tabbar[i].path) == '/mall' ? '/' : analysPath(tabbar[i].path)
        var routePath = route.path == '/mall/index' || route.path == '/mall' ? '/' : route.path
        tabbar[i].index = index
        index++
        if (path == routePath) {
          state.activeTabbar = tabbar[i].index
          flag = true;
          break
        } else {
          flag = false
        }
      }
      state.isShowTabbar = route ? flag : false
    }
  },
  actions: {
    getTabbar(context) {
      return new Promise((resolve, reject) => {
        if (!context.state.data) {
          context.dispatch('getCustom', { type: 1 }).then(data => {
            context.commit('setTabbar', data.tab_bar.data)
            resolve()
          }).catch(error => {
            reject(error)
          })
        } else {
          resolve()
        }
      })
    }
  }
}

export default app
