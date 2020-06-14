import { SELECT_STORE, GET_STORESURVEY } from "@/api/config";
import { Toast, Dialog } from "vant";
import { SET_STOREID, GET_STOREID, REMOVE_STOREID } from '@/utils/auth'
import { setSession, getSession, removeSession } from "@/utils/storage"
const config = {
  state: {
    store_id: GET_STOREID(),
    storeSurvey: {},
    toPath: getSession('clerkToPath')
  },
  mutations: {
    setStoreId(state, store_id) {
      state.store_id = store_id
      SET_STOREID(store_id)
    },
    removeStoreId(state) {
      state.store_id = ''
      REMOVE_STOREID()
    },
    setToPath(state, path) {
      setSession('clerkToPath', path)
      state.toPath = path
    },
    removeToPath(state) {
      removeSession('clerkToPath')
      state.toPath = ''
    },
    setStoreSurvey(state, data) {
      state.storeSurvey = data
    }
  },
  actions: {
    // 选择门店
    selectStore(context, store_id) {
      return new Promise((resolve, reject) => {
        SELECT_STORE(store_id).then(res => {
          if (res.code == 1) {
            context.commit('setStoreId', store_id);
            resolve();
          } else {
            Toast(res.message);
          }
        }).catch(() => {
          reject()
        })
      })
    },
    // 获取经营概况
    getStoreSurvey(context) {
      return new Promise((resolve, reject) => {
        GET_STORESURVEY().then(({ data }) => {
          context.commit('setStoreSurvey', data)
          resolve()
        }).catch(() => {
          reject()
        })
      })
    }
  }
}

export default config
