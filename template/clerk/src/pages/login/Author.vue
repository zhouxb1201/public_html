<template>
  <Layout ref="load" class="author" loading-text="授权中"></Layout>
</template>

<script>
import { setSession, getSession, removeSession } from "@/utils/storage";
import { Loading } from "vant";
import http from "@/utils/request";
import store from "@/store";
import { Toast } from "vant";
export default {
  name: "author",
  data() {
    return {};
  },
  beforeRouteEnter(to, from, next) {
    if (to.query.code) {
      // 微信授权获取code
      next($this => {
        $this
          .$get(
            "/addons/execute/addons/store/controller/login/action/oauthLogin",
            { code: to.query.code }
          )
          .then(res => {
            if (res.code === 1) {
              $this.$store.commit("setUserInfo", {
                user_token: res.data.user_token
              });
              $this.$Toast.success("授权成功");
              $this.$router.replace($this.$store.getters.getToPath);
            } else if (res.code === 2) {
              $this.$Toast(res.message);
              $this.$router.replace("/bind");
            } else if (res.code === 3) {
              $this.$Toast.fail(res.message);
            } else if (res.code === 4) {
              alert(res.data.url);
            }
          })
          .catch(() => {
            next($this => {
              $this.onFail();
            });
          });
      });
    } else {
      next($this => {
        $this.onFail();
      });
    }
  },
  methods: {
    onFail() {
      this.$refs.load.result();
      this.$Toast.fail("授权失败");
    }
  },
  components: {
    [Loading.name]: Loading
  }
};
</script>

<style scoped>
.author {
  padding: 50px 0;
  display: flex;
  justify-content: center;
}
.author p {
  padding-left: 10px;
}
</style>
