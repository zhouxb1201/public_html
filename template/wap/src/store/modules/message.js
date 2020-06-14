import { GET_CHATLIST } from "@/api/message";
import Notify from '@/components/notify';
import router from '@/router'

const message = {
  state: {
    showService: false,// 商品详情客服按钮显示标识
    reconnect: false,// 是否重新链接
    chatList: [],   // 会话列表
    serviceList: {}   // 当前活跃的客服池
  },
  mutations: {
    // socket是否渲染成功 
    socketConnect(state, data) {
      // console.log("链接成功", data);
    },
    socketReconnect(state, flag) {
      state.reconnect = flag
    },
    // 常见问题 
    socketComQuestion(state, data) {
      console.log('常见问题', data)
    },
    // 问候语 
    socketHello(state, data) {
      console.log('问候语', data)
    },
    // 被关闭 
    socketIsClose(state, data) {
      console.log('被关闭', data)
    },
    // 处理转接 
    socketRelink(state, data) {
      console.log('处理转接', data)
    },
    // 被主动接待 
    socketLinkByKF(state, data) {
      console.log('被主动接待', data)
    },
    setChatList(state, list) {
      state.chatList = list.map((e) => {
        let item = state.serviceList['KF_' + e.kefu_code]
        if (item) {
          e.chat_content = item.content
          e.chat_time = item.time
          e.newMsg = true
        }
        return e
      })
    },
    // 更新会话列表
    updataChatList(state, item) {
      state.serviceList[item.id] = item
      state.chatList = state.chatList.map((e) => {
        if (item.id === 'KF_' + e.kefu_code) {
          e.chat_content = item.content
          e.chat_time = item.time
          e.newMsg = true
        }
        return e
      });
    },
    // 移除红点
    removeChatListDot(state, kefu_code) {
      delete state.serviceList['KF_' + kefu_code]
      state.chatList = state.chatList.map((e) => {
        if (e.kefu_code === kefu_code) {
          e.newMsg = false
        }
        return e
      });
    },

    setShowServiceBtn(state, flag) {
      state.showService = flag
    }
  },
  actions: {
    getChatList({ commit }, params) {
      return new Promise((resolve, reject) => {
        GET_CHATLIST().then(({ data }) => {
          commit('setChatList', data)
          resolve(data)
        })
      })
    },
    // 聊天消息 
    socketChatMessage({ commit }, { data }) {
      commit('updataChatList', data)
      Notify({
        message: '您有一条新的客服消息！',
        background: '#1989facc',
        duration: 5000,
        onClick() {
          router.push('/message')
        }
      });
    },
  }
}

export default message
