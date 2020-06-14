<template>
  <div class="login">
    <HeadBanner :src="$BASEIMGPATH + 'login-head-default-02.png'" />

    <AccountLogin ref="accountLogin" />

    <div class="login-assist">
      <router-link to="/forget" class="text-secondary">忘记密码?</router-link>
    </div>
    <div class="foot-btn-group">
      <van-button
        size="normal"
        type="danger"
        round
        block
        @click="onLogin"
        :loading="isLoading"
        loading-text="登录中..."
      >登录</van-button>
    </div>

    <Divider title="其他登录方式" v-if="$store.state.isWeixin">
      <div class="login-type" slot="html">
        <div class="box">
          <img :src="$BASEIMGPATH+'wx-login-icon.png'" @click="otherLogin" />
        </div>
      </div>
    </Divider>
  </div>
</template>

<script>
import HeadBanner from "@/components/HeadBanner";
import Divider from "@/components/Divider";
import AccountLogin from "./component/AccountLogin";
import { validMobile, validPassword } from "@/utils/validator";
export default {
  name: "login",
  data() {
    return {
      isLoading: false
    };
  },
  methods: {
    vaildForm() {
      let form = this.$refs.accountLogin.form;
      if (!validMobile(form.account) || !validPassword(form.password)) {
        return false;
      }
      return form;
    },
    onLogin() {
      const $this = this;
      const form = this.vaildForm();
      if (form) {
        $this.isLoading = true;
        $this.$store
          .dispatch("login", form)
          .then(({ code }) => {
            // $this.isLoading = false;
          })
          .catch(() => {
            $this.isLoading = false;
          });
      }
    },
    otherLogin() {
      this.$store.dispatch("otherLogin", {
        action: "author",
        form: { type: "WCHAT" }
      });
    }
  },
  components: {
    HeadBanner,
    Divider,
    AccountLogin
  }
};
</script>

<style scoped>
.login {
  background: #ffffff;
}

.login-assist {
  display: flex;
  justify-content: space-between;
  padding: 14px 24px;
}

.login-type {
  display: flex;
  justify-content: center;
  margin: 20px 8%;
}

.login-type .box {
  flex: 1;
}

.login-type .box img {
  display: block;
  width: 60px;
  height: 60px;
  margin: 10px auto;
}

.login-type .box img:active {
  opacity: 0.7;
}
</style>
