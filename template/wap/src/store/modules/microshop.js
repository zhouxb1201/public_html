import { Toast, Dialog } from "vant";
import { GET_CENTREINFO } from "@/api/microshop";
import { setSession } from "@/utils/storage";
const microshop = {
  state: {
    info: null, //店主信息
    set: null
  },
  mutations: {
    setShopkeeperInfo(state, info) {
      const { isdistributor ,isshopkeeper, is_default_shopkeeper , member_name, user_headimg, 
        shopkeeper_level_name, become_shopkeeper_time, shopkeeper_level_time, uid } = info
      state.info = {
        isdistributor, 
        isshopkeeper,
        is_default_shopkeeper, 
        member_name,
        user_headimg,
        shopkeeper_level_name,
        become_shopkeeper_time,
        shopkeeper_level_time,
        uid
      }
      setSession("info",state.info);
    },
    getMicroshopSet(state, info) {
      const { microshop_logo, microshop_name, shopRecruitment_logo, microshop_goods, microshop_introduce} = info
      state.set = {
        microshop_logo,
        microshop_name,
        shopRecruitment_logo,
        microshop_goods,
        microshop_introduce        
      }
      setSession("set",state.set);
    }
  },
  actions: {
    getMicroshopInfo(context) {
      return new Promise((resolve, reject) => {
        GET_CENTREINFO().then(({ data }) => {
          resolve(data)
          context.commit("setShopkeeperInfo", data)
          context.commit("getMicroshopSet", data)
        }).catch(() => {
          reject()
        })
      });
    }
  }
}

export default microshop
