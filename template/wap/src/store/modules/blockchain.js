import { GET_BLOCKCHAINSET, GET_BLOCKCHAINETHINFO, GET_BLOCKCHAINEOSINFO, GET_BLOCKCHAINPAYINFO } from "@/api/blockchain";
import { setSession, getSession, removeSession } from "@/utils/storage";

const blockchain = {
  state: {
    config: null,
    eth: null,
    eos: null
  },
  mutations: {
    setBlockchainSet(state, config) {
      state.config = config
    },
    setEthInfo(state, info) {
      state.eth = info
    },
    setEosInfo(state, info) {
      state.eos = info
    },
    /**
     *  设置钱包导出key值
     *  type ==> 钱包类型 eth/eos 
     *  key  ==> 导出key  keyStore/私钥
     *  value ==> key 值
     */
    setBlockchainExportKey(state, { type, key, value }) {
      if (type && key) {
        state[type][key] = value
        setSession(type + key, value)
      }
    },
    // 删除钱包导出key
    removeBlockchainExportKey(state, { type, key }) {
      if (type && key) {
        state[type][key] = null
        removeSession(type + key)
      }
    }
  },
  actions: {
    getBlockchainSet({ state, commit }, updata) {
      return new Promise((resolve, reject) => {
        if (state.config && !updata) {
          resolve(state.config)
        } else {
          GET_BLOCKCHAINSET().then(({ data }) => {
            commit('setBlockchainSet', data)
            resolve(data)
          }).catch(error => {
            reject(error)
          })
        }
      })
    },
    getEthInfo({ state, commit }, updata) {
      return new Promise((resolve, reject) => {
        if (state.eth && !updata) {
          resolve({ code: 1, data: state.eth })
        } else {
          GET_BLOCKCHAINETHINFO().then(({ code, data }) => {
            if (code == 1) {
              commit('setEthInfo', data)
            }
            resolve({ code, data })
          }).catch((error) => {
            reject(error)
          })
        }
      })
    },
    getEosInfo({ state, commit }, updata) {
      return new Promise((resolve, reject) => {
        if (state.eos && !updata) {
          resolve({ code: 1, data: state.eos })
        } else {
          GET_BLOCKCHAINEOSINFO().then(({ code, data, message }) => {
            if (code == 1) {
              commit('setEosInfo', data)
            }
            resolve({ code, data, message })
          }).catch((error) => {
            reject(error)
          })
        }
      })
    },
    getBlockchainPayInfo({ state, commit }, out_trade_no) {
      return new Promise((resolve, reject) => {
        GET_BLOCKCHAINPAYINFO(out_trade_no).then(({ code, data, message }) => {
          resolve(data)
        }).catch((error) => {
          reject(error)
        })
      })
    }
  }
}

export default blockchain;