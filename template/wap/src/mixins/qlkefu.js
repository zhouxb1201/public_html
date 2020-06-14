
import { GET_CUSTOMERSERVICE } from "@/api/goods";
import { setSession } from "@/utils/storage";

const qlkefu = {
  data() {
    return {

    };
  },

  methods: {
    getKefu(shop_id = 0, goods_id = 0) {
      const $this = this;
      return new Promise((resolve, reject) => {
        if ($this.$store.state.config.addons.qlkefu) {
          GET_CUSTOMERSERVICE(shop_id, goods_id).then(({ data }) => {
            if (data.is_qlkefu == 1 && data.domain) {
              resolve(data)
            } else {
              reject()
            }
          }).catch(() => {
            reject()
          })
        }
      })
    },
    loadKefu(url) {
      const $this = this
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
    openKefu() {
      if (!this.serverFlag && !this.$store.getters.token) {
        this.$Toast("您未登录，请先登录！");
        setSession("toPath", this.$route.fullPath);
        this.$router.push("/login");
      }
    },
    closeKefu() {
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
  },

  beforeDestroy() {
    if (this.$store.state.message.showService) {
      this.closeKefu()
      this.$store.commit("setShowServiceBtn", false);
    } else {
      var wsSrciptNode = document.getElementById("WS-SHOW-CHAT-SCRIPT")
      if (wsSrciptNode) {
        wsSrciptNode.remove()
      }
    }
  }
};

export default qlkefu
