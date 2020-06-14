<template>
  <van-cell-group v-if="items.length>0">
    <van-cell
      value-class="panel-item"
      clickable
      v-for="(item,index) in items"
      :key="index"
      @click="click(item)"
    >
      <div class="item-left" :class="item.newMsg?'dot':''">
        <img class="img" :src="item.kefu_avatar" :onerror="$ERRORPIC.noAvatar" />
      </div>
      <div class="item-right">
        <div class="title">
          <div class="left-text">{{item.kefu_name}}</div>
          <div class="right-text text-secondary">{{item.chat_time}}</div>
        </div>
        <div class="text">
          <div class="left-text text-secondary">{{item.chat_content}}</div>
          <!-- <div class="badge">{{99}}</div> -->
        </div>
      </div>
    </van-cell>
  </van-cell-group>
</template>

<script>
export default {
  data() {
    return {};
  },
  computed: {
    items() {
      return this.$store.state.message.chatList;
    }
  },
  methods: {
    click({ kefu_code, seller_code }) {
      const $this = this;
      let domain = $this.$store.getters.config.qlkefu_domain_port;
      domain = domain.substring(0, domain.length - 5);
      const url = domain + "/index/index/chatBoxJs/u/" + seller_code;
      $this
        .loadKefu(url)
        .then(() => {
          const {
            uid,
            username,
            member_img,
            reg_time
          } = $this.$store.state.member.info;
          qlkefuChat.init({
            kefuCode: kefu_code,
            uid,
            uName: username,
            avatar: member_img,
            regTime: reg_time || "",
            goods: {
              goods_id: "",
              goods_name: "",
              price: "",
              pic_cover: ""
            }
          });
          $this.$store.commit("removeChatListDot", kefu_code);
          qlkefuChat.close(() => {
            if ($this.$route.name == "message-index") {
              $this.$store.dispatch("getChatList");
              $this.listenerClose();
            }
          });
        })
        .catch(() => {});
    },
    loadKefu(url) {
      return new Promise((resolve, reject) => {
        console.log("客服连接中...");
        let script = document.createElement("script");
        script.src = url;
        script.id = "WS-SHOW-CHAT-SCRIPT";
        document.body.appendChild(script);
        script.onload = () => {
          if (typeof qlkefuChat !== "undefined") {
            console.log("客服连接完成...");
            resolve();
          }
        };
        script.onerror = () => {
          console.log("客服连接出错...");
          reject();
        };
      });
    },
    listenerClose() {
      var wsSrciptNode = document.getElementById("WS-SHOW-CHAT-SCRIPT"),
        wsBtnNode = document.getElementById("WS-SHOW-CHAT-BTN"),
        wsIfarmeNode = document.getElementById("WS-SHOW-CHAT-IFARME");
      if (wsSrciptNode) {
        wsSrciptNode.remove();
      }
      if (wsBtnNode) {
        wsBtnNode.remove();
      }
      if (wsIfarmeNode) {
        wsIfarmeNode.remove();
      }
      this.$store.commit(
        "socketReconnect",
        !this.$store.state.message.reconnect
      );
    }
  }
};
</script>

<style scoped>
.panel-item {
  display: flex;
}

.item-left {
  flex: 0.2;
  margin-right: 10px;
  position: relative;
}

.item-left.dot::after {
  content: "";
  display: block;
  position: absolute;
  right: 0;
  top: 0px;
  width: 8px;
  height: 8px;
  z-index: 10;
  background: red;
  border-radius: 100%;
}

.img {
  width: 48px;
  height: 48px;
  display: block;
  border-radius: 8px;
  background: #f8f8f8;
}

.item-right {
  flex: 1.8;
  flex-direction: column;
  overflow: hidden;
}

.title {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.left-text {
  flex: 1;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.right-text {
  font-size: 12px;
}

.text {
  display: flex;
  align-items: center;
}

.badge {
  border-radius: 8px;
  color: #fff;
  background: #ff4444;
  display: block;
  min-width: 18px;
  height: 18px;
  text-align: center;
  line-height: 18px;
  padding: 0 2px;
  overflow: hidden;
  margin-left: 4px;
  max-width: 24px;
}
</style>