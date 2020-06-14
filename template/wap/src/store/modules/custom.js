import { GET_CUSTOM } from "@/api/config";
import { isWeixin } from '@/utils/util'
import config from './config'
const custom = {
  state: {
    template: null,
    copyright: null,
    wechat: null,
    isShowWechat: false
  },
  mutations: {
    setTemplate(state, data) {
      state.template = data
    },
    setCopyright(state, data) {
      state.copyright = data
    },
    setInviteWechat(state, data) {
      let flag = isWeixin() && config.state.base.is_wchat && data.is_show == "1";
      state.wechat = data
      state.isShowWechat = flag
    }
  },
  actions: {
    getCustom(context, params) {
      return new Promise((resolve, reject) => {
        GET_CUSTOM(params).then(({ data }) => {
          if (params.type == 1) {
            context.commit('setTemplate', data.template_data)
          }
          if (params.type == 1 || params.type == 4) {
            context.commit('setCopyright', data.copyright)
          }
          context.commit('setInviteWechat', data.wechat_set ? data.wechat_set : {})
          resolve(data)
        }).catch(error => {
          reject(error)
        })
      })
    }
  }
}

export default custom
