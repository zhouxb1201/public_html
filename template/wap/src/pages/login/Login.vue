<template>
  <div class="login bg-fff">
    <HeadBanner
      :src="$store.getters.config.wap_login_adv ? $store.getters.config.wap_login_adv : $BASEIMGPATH + 'login-head-default-01.png'"
      :link="$store.getters.config.wap_login_jump"
    />
    <HeadBtn />
    <HeadBtn type="home" />
    <van-tabs v-model="tab_active">
      <van-tab v-for="(item,index) in tabs" :key="index" :title="item.title" v-if="item.isShow">
        <component :is="item.type" :ref="item.type" />
      </van-tab>
    </van-tabs>
    <div class="login-assist">
      <router-link to="/register" class="text-maintone">快速注册 ></router-link>
      <router-link to="/forget" class="text-secondary">忘记密码?</router-link>
    </div>

    <div class="foot-btn-group">
      <van-button
        size="normal"
        type="danger"
        round
        block
        @click="login"
        :loading="isLoading"
        loading-text="登录中..."
      >登录</van-button>
    </div>

    <Divider title="其他登录方式" v-if="$store.getters.config.qq_login">
      <div class="login-type" slot="html">
        <div
          class="box"
          v-if="$store.getters.config.wechat_login && $store.state.isWeixin && $store.getters.config.is_wchat"
        >
          <img :src="$BASEIMGPATH+'wx-login-icon.png'" @click="onOtherLogin('WCHAT')" />
        </div>
        <div class="box" v-if="$store.getters.config.qq_login">
          <img :src="$BASEIMGPATH+'qq-login-icon.png'" @click="onOtherLogin('QQLOGIN')" />
        </div>
      </div>
    </Divider>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import HeadBtn from "@/components/HeadBtn";
import HeadBanner from "@/components/HeadBanner";
import Divider from "@/components/Divider";
import MsgLogin from "./component/MsgLogin";
import AccountLogin from "./component/AccountLogin";
import { setSession, getSession, removeSession } from "@/utils/storage";
import {
  validMobile,
  validUsername,
  validMsgcode,
  validImgcode,
  validPassword
} from "@/utils/validator";
export default sfc({
  name: "login",
  data() {
    return {
      tab_active: 0,
      type: "login",
      tabs: [
        {
          title: "账号密码登录",
          type: "AccountLogin",
          isShow: true
        },
        {
          title: "短信验证码登录",
          type: "MsgLogin",
          isShow: this.$store.getters.config.mobile_verification
        }
      ],
      isLoading: false
    };
  },
  methods: {
    // 验证输入
    vaildForm() {
      let form = "";
      if (this.tab_active === 0) {
        form = this.$refs.AccountLogin[0].form;
        if (
          !validUsername(form.account) ||
          !validPassword(form.password) ||
          (this.$refs.AccountLogin[0].show_captcha_code
            ? !validImgcode(form.captcha_code)
            : false) //判断并验证图片验证码
        ) {
          return false;
        }
      } else {
        form = this.$refs.MsgLogin[0].form;
        if (
          !validMobile(form.account) ||
          !validMsgcode(form.verification_code) ||
          (this.$refs.MsgLogin[0].show_captcha_code
            ? !validImgcode(form.captcha_code)
            : false) //判断并验证图片验证码
        ) {
          return false;
        }
      }

      return form;
    },
    login() {
      const $this = this;
      let form = this.vaildForm();

      if (form) {
        $this.isLoading = true;
        $this.$store
          .dispatch("login", form)
          .then(res => {
            // 登录成功后执行
            if (res.code === 0) {
              $this.$refs.AccountLogin[0].show_captcha_code = true;
              $this.$refs.AccountLogin[0].getImgCode();
              if ($this.$refs.MsgLogin) {
                $this.$refs.MsgLogin[0].show_captcha_code = true;
                $this.$refs.MsgLogin[0].getImgCode();
              }
              $this.isLoading = false;
            } else {
              $this.$router.replace(
                getSession("toPath") ? getSession("toPath") : "/mall/index"
              );
            }
          })
          .catch(() => {
            $this.isLoading = false;
            if ($this.tab_active == 0) {
              if ($this.$refs.AccountLogin[0].show_captcha_code) {
                $this.$refs.AccountLogin[0].getImgCode();
              }
            } else {
              if (
                $this.$refs.MsgLogin &&
                $this.$refs.MsgLogin[0].show_captcha_code
              ) {
                $this.$refs.MsgLogin[0].getImgCode();
              }
            }
          });
      }
    },
    // 第三方登录
    onOtherLogin(type) {
      const $this = this;
      $this.$store.dispatch("otherLogin", {
        action: "author",
        form: {
          type,
          redirect_url: getSession("toPath")
        }
      });
    }
  },
  components: {
    HeadBanner,
    HeadBtn,
    Divider,
    MsgLogin,
    AccountLogin
  }
});
</script>

<style scoped>
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
