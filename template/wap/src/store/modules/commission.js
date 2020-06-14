import { GET_CENTREINFO, GET_UPBONUESLEVEL } from "@/api/commission";

const commission = {
  state: {
    info: null
  },
  mutations: {
    setCommissionInfo(state, info) {
      state.info = info
    }
  },
  actions: {
    /**
     * 获取分销商信息
     * @param {Boolean} update  更新分销商信息，不传则有信息情况读已有信息
     */
    getCommissionInfo(context, update) {
      return new Promise((resolve, reject) => {
        function getInfo() {
          GET_CENTREINFO().then(({ data }) => {
            context.commit('setCommissionInfo', data)
            resolve(data)
          }).catch(error => {
            reject(error)
          })
        }
        if (update) {
          getInfo()
        } else {
          if (context.state.info) {
            context.commit('setCommissionInfo', context.state.info)
            resolve(context.state.info)
          } else {
            getInfo()
          }
        }
      })
    },
    /**
     * 获取分销分红等级信息
     * @param {Object} params 
     * types ==> 1：团队分红 2：区域分红 3：全球分红 4：全网分销
     */
    getUpbonusLevelInfo({commit},params){
      return new Promise((resolve,reject) => {
        GET_UPBONUESLEVEL(params).then(({data}) => {
          resolve(data)
        }).catch(error => {
          reject(error)
        })
      })
    }
  }
}

export default commission