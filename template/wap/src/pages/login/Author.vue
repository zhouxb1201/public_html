<template>
  <Layout ref="load" class="author" loading-text="授权中...">
    <!-- <van-loading/>
    <p>
      授权中。。。
      <br>请稍后。。。
    </p>-->
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import { setSession, getSession, removeSession } from "@/utils/storage";
import { Loading } from "vant";
import http from "@/utils/request";
import store from "@/store";
import { Toast } from "vant";
export default sfc({
  name: "author",
  data() {
    return {};
  },
  beforeRouteEnter(to, from, next) {
    if (to.query.code) {
      // 微信授权获取code
      next($this => {
        $this
          .$get("/login/oauthLogin", { code: to.query.code, type: "WCHAT" })
          .then(({ code, data }) => {
            if (code === 1) {
              store.commit("setUserInfo", {
                user_token: data.user_token,
                have_mobile: data.have_mobile
              });
              Toast.success("授权成功");
              next({ replace: true, path: getSession("toPath") });
            } else if (code === 2) {
              Toast(message);
              next($this => {
                $this.$refs.load.result();
              });
            } else if (code === 3) {
              Toast.fail(message);
              next($this => {
                $this.$refs.load.result();
              });
            } else if (code === 4) {
              alert(data.url);
              // next({ replace: true, path: data.url });
            }
          })
          .catch(() => {
            next($this => {
              $this.onFail();
            });
          });
      });
    } else if (to.query.user_token) {
      // qq授权获取token
      store.commit("setUserInfo", {
        user_token: to.query.user_token,
        have_mobile: true
      });
      Toast.success("授权成功");
      next({ replace: true, path: getSession("toPath") });
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
});
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
