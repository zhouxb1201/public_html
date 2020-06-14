import { ADD_CARTGOODS, GET_CARTLIST, REMOVE_CARTGOODS, EDIT_CARTGOODSNUM } from "@/api/channel";

const channel = {
  state: {
    cartList: [],
    total_money: 0,
    message: null,
    isAchieveCondie: false  // 是否满足最低采购金额条件
  },
  mutations: {
    setChannelCartList(state, { data }) {
      state.cartList = data.cart_list ? data.cart_list : []
      state.total_money = data.total_money ? data.total_money : 0
      if (data.lowest_purchase_money > 0) {
        state.message = `最低采购金额为${data.lowest_purchase_money}元`
        if (state.total_money >= data.lowest_purchase_money) {
          state.isAchieveCondie = true
        }else{
          state.isAchieveCondie = false
        }
      } else {
        state.isAchieveCondie = true
        state.message = null
      }
    }
  },
  actions: {
    // 获取购物车列表
    getChannelCartList({ commit }, params) {
      return new Promise((resolve, reject) => {
        GET_CARTLIST(params).then(res => {
          commit('setChannelCartList', res)
          resolve(res)
        }).catch(error => {
          reject(error)
        })
      })
    },
    // 添加购物车
    addChannelCart({ commit }, params) {
      return new Promise((resolve, reject) => {
        ADD_CARTGOODS(params).then(({ data }) => {
          resolve(data)
        });
      })
    },
    // 删除购物车
    removeChannelCart({ commit }, params) {
      return new Promise((resolve, reject) => {
        REMOVE_CARTGOODS(params).then(res => {
          resolve(res)
        })
      })
    },
    // 修改购物车数量
    editCartGoodsNum({ commit }, params) {
      return new Promise((resolve, reject) => {
        EDIT_CARTGOODSNUM(params).then(res => {
          resolve(res)
        })
      })
    }
  }
}

export default channel